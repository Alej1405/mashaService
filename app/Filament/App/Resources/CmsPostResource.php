<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CmsPostResource\Pages;
use App\Models\CmsPost;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use App\Services\MailingService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsPostResource extends Resource
{
    protected static ?string $model = CmsPost::class;

    protected static ?string $tenantRelationshipName = 'cmsPosts';

    protected static ?string $navigationIcon   = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel  = 'Noticias';
    protected static ?string $navigationGroup  = 'CMS';
    protected static ?int    $navigationSort   = 9;
    protected static ?string $modelLabel       = 'Noticia';
    protected static ?string $pluralModelLabel = 'Noticias';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título de la noticia')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) =>
                        $set('slug', Str::slug($state ?? ''))
                    )
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')
                    ->label('URL amigable (slug)')
                    ->required()
                    ->maxLength(200)
                    ->helperText('Se genera automáticamente del título. Puedes editarlo.')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('contenido')
                    ->label('Contenido de la noticia')
                    ->required()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline',
                        'bulletList', 'orderedList',
                        'h2', 'h3', 'paragraph',
                        'link', 'blockquote', 'undo', 'redo',
                    ])
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('imagen')
                    ->label('Imagen principal')
                    ->image()->disk('public')->directory('cms/posts')
                    ->imagePreviewHeight('120')->maxSize(3072)
                    ->helperText('JPG o PNG. Recomendado: 1200×630 px.')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('publicado_en')
                    ->label('Fecha de publicación')
                    ->placeholder('Dejar vacío para publicar ahora')
                    ->helperText('Si se deja vacío, se usará la fecha actual al activar.'),

                Forms\Components\Toggle::make('activo')->label('Publicada')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->height(40)->width(64),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->description(fn (CmsPost $r) => $r->slug),

                Tables\Columns\TextColumn::make('publicado_en')
                    ->label('Publicada')
                    ->since()
                    ->placeholder('Sin fecha')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\ToggleColumn::make('activo')->label('Publicada'),
            ])
            ->actions([
                Tables\Actions\Action::make('enviar_correo')
                    ->label('Enviar por correo')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->modalHeading(fn (CmsPost $r) => 'Enviar noticia: ' . $r->titulo)
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\Radio::make('destinatarios')
                            ->label('¿A quién enviar?')
                            ->options([
                                'grupo'     => 'Grupo de contactos',
                                'seleccion' => 'Seleccionar contactos individuales',
                            ])
                            ->default('grupo')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('mailing_group_id')
                            ->label('Grupo')
                            ->options(function () {
                                return MailingGroup::where('empresa_id', Filament::getTenant()->id)
                                    ->withCount(['contacts' => fn ($q) => $q->where('active', true)])
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->mapWithKeys(fn ($g) => [
                                        $g->id => $g->name . ' — ' . number_format($g->contacts_count) . ' activos',
                                    ])
                                    ->toArray();
                            })
                            ->visible(fn (Forms\Get $get) => $get('destinatarios') === 'grupo')
                            ->required(fn (Forms\Get $get) => $get('destinatarios') === 'grupo')
                            ->live(),

                        Forms\Components\Select::make('contactos_ids')
                            ->label('Contactos')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) =>
                                MailingContact::where('empresa_id', Filament::getTenant()->id)
                                    ->where('active', true)
                                    ->where(fn ($q) => $q
                                        ->where('nombre', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                    )
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => "{$c->nombre} — {$c->email}"])
                                    ->toArray()
                            )
                            ->visible(fn (Forms\Get $get) => $get('destinatarios') === 'seleccion')
                            ->required(fn (Forms\Get $get) => $get('destinatarios') === 'seleccion')
                            ->helperText('Busca por nombre o correo.'),
                    ])
                    ->action(function (CmsPost $record, array $data): void {
                        $empresa = Filament::getTenant();
                        $service = new MailingService($empresa);

                        if (! $service->isConfigured()) {
                            Notification::make()
                                ->title('Servicio de correo no configurado')
                                ->body('El administrador debe activar el servicio de correo primero.')
                                ->warning()->send();
                            return;
                        }

                        if ($data['destinatarios'] === 'grupo') {
                            $contacts = MailingContact::where('empresa_id', $empresa->id)
                                ->where('mailing_group_id', $data['mailing_group_id'])
                                ->where('active', true)
                                ->select('nombre', 'email')
                                ->get()->toArray();
                        } else {
                            $contacts = MailingContact::whereIn('id', $data['contactos_ids'] ?? [])
                                ->where('empresa_id', $empresa->id)
                                ->select('nombre', 'email')
                                ->get()->toArray();
                        }

                        if (empty($contacts)) {
                            Notification::make()
                                ->title('Sin destinatarios')
                                ->body('No hay contactos activos o no seleccionaste ninguno.')
                                ->warning()->send();
                            return;
                        }

                        $html = self::buildPostHtml($record, $empresa);

                        $result = $service->sendRawMassEmail(
                            $contacts,
                            $record->titulo,
                            $html,
                        );

                        Notification::make()
                            ->title($result['success'] ? 'Noticia enviada' : 'Enviada con errores')
                            ->body($result['message'])
                            ->{$result['success'] ? 'success' : 'warning'}()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsPosts::route('/'),
            'create' => Pages\CreateCmsPost::route('/create'),
            'edit'   => Pages\EditCmsPost::route('/{record}/edit'),
        ];
    }

    private static function buildPostHtml(CmsPost $post, \App\Models\Empresa $empresa): string
    {
        $logoHtml = '';
        if ($empresa->logo_path) {
            $logoUrl  = Storage::disk('public')->url($empresa->logo_path);
            $logoHtml = "<div style='text-align:center;margin-bottom:24px;'>
                           <img src='{$logoUrl}' alt='" . e($empresa->name) . "'
                                style='max-height:52px;max-width:180px;object-fit:contain;'>
                         </div>";
        }

        $imagenHtml = '';
        if ($post->imagen) {
            $imagenUrl  = Storage::disk('public')->url($post->imagen);
            $imagenHtml = "<img src='{$imagenUrl}' alt='" . e($post->titulo) . "'
                                style='width:100%;max-height:280px;object-fit:cover;display:block;border-radius:8px;margin-bottom:24px;'>";
        }

        $extracto = Str::limit(strip_tags($post->contenido ?? ''), 400);
        $fecha    = $post->publicado_en ? $post->publicado_en->format('d/m/Y') : now()->format('d/m/Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f9;padding:40px 16px;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;">
  <tr>
    <td style="background-color:#1e293b;padding:28px 40px;text-align:center;">
      {$logoHtml}
      <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">{$empresa->name}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:32px 40px 0;">
      {$imagenHtml}
      <p style="margin:0 0 8px;color:#94a3b8;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">{$fecha}</p>
      <h1 style="margin:0 0 16px;color:#1e293b;font-size:22px;font-weight:700;line-height:1.3;">{$post->titulo}</h1>
      <p style="margin:0 0 28px;color:#475569;font-size:15px;line-height:1.8;">{$extracto}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:0 40px 40px;">
      <p style="margin:0;color:#94a3b8;font-size:11px;text-align:center;">
        Este correo fue enviado por <strong style="color:#64748b;">{$empresa->name}</strong>.
      </p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
