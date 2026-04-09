<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MailCampaignResource\Pages;
use App\Jobs\SendMailCampaignJob;
use App\Models\MailCampaign;
use App\Models\MailingContact;
use App\Models\MailingGroup;
use App\Models\MailTemplate;
use App\Services\MailingService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class MailCampaignResource extends Resource
{
    protected static ?string $model = MailCampaign::class;
    protected static ?string $tenantRelationshipName = 'mailCampaigns';

    protected static ?string $navigationIcon   = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel  = 'Campañas';
    protected static ?string $navigationGroup  = 'Mailing';
    protected static ?int    $navigationSort   = 4;
    protected static ?string $modelLabel       = 'Campaña';
    protected static ?string $pluralModelLabel = 'Campañas de correo';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la campaña')
                    ->placeholder('Boletín marzo 2026, Promoción temporada…')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Forms\Components\Select::make('mail_template_id')
                    ->label('Plantilla de correo')
                    ->relationship('mailTemplate', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Se usará el asunto y el diseño de la plantilla seleccionada.')
                    ->columnSpanFull(),

                Forms\Components\Select::make('mailing_group_id')
                    ->label('Grupo de contactos')
                    ->options(function (): array {
                        return MailingGroup::where('empresa_id', Filament::getTenant()->id)
                            ->withCount(['contacts' => fn ($q) => $q->where('active', true)])
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($g) => [
                                $g->id => $g->name . ' — ' . number_format($g->contacts_count) . ' contactos activos',
                            ])
                            ->toArray();
                    })
                    ->required()
                    ->live()
                    ->helperText('Cada grupo tiene hasta 1.500 contactos. Los grupos se crean automáticamente.')
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('info_destinatarios')
                    ->label('Resumen')
                    ->content(function (Get $get): HtmlString {
                        $groupId = $get('mailing_group_id');
                        $empresa = Filament::getTenant();
                        $service     = new MailingService($empresa);
                        $quota       = $service->getQuotaInfo();
                        $quotaReset  = $quota['reset_label'] ?? $quota['reset_date'];

                        if (! $groupId) {
                            return new HtmlString(
                                "<span style='color:#d97706;'>Selecciona un grupo para continuar.</span>"
                            );
                        }

                        $group = MailingGroup::withCount(['contacts' => fn ($q) => $q->where('active', true)])->find($groupId);
                        $count = $group?->contacts_count ?? 0;
                        $color = $count > 0 ? '#059669' : '#d97706';
                        $icon  = $count > 0 ? '✓' : '⚠';

                        $quotaColor = $quota['remaining'] >= $count ? '#059669' : '#dc2626';
                        $quotaIcon  = $quota['remaining'] >= $count ? '✓' : '⚠';

                        return new HtmlString(
                            "<div style='display:flex;flex-direction:column;gap:6px;'>"
                            . "<span style='color:{$color};font-weight:600;'>{$icon} {$count} contacto(s) activo(s) en {$group?->name}.</span>"
                            . "<span style='color:{$quotaColor};font-size:0.8rem;'>{$quotaIcon} Cuota disponible: <strong>{$quota['remaining']}</strong> de {$quota['limit']} — se renueva el {$quotaReset}.</span>"
                            . "</div>"
                        );
                    })
                    ->live()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campaña')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MailCampaign $r) => $r->mailTemplate?->subject),

                Tables\Columns\TextColumn::make('mailingGroup.name')
                    ->label('Grupo')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (MailCampaign $r) => $r->statusLabel())
                    ->color(fn (MailCampaign $r) => $r->statusColor()),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Destinatarios')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '—'),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Enviados')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '—')
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Fallidos')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '—')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Enviada')
                    ->since()
                    ->placeholder('—')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->since()
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->actions([
                // ── Enviar campaña ────────────────────────────────────────
                Tables\Actions\Action::make('enviar')
                    ->label('Enviar ahora')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (MailCampaign $r) => $r->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading(fn (MailCampaign $r) => 'Enviar campaña: ' . $r->name)
                    ->modalDescription(function (MailCampaign $r): string {
                        $empresa = Filament::getTenant();
                        $service = new MailingService($empresa);
                        $quota   = $service->getQuotaInfo();

                        $query = MailingContact::where('empresa_id', $empresa->id)->where('active', true);
                        if ($r->mailing_group_id) {
                            $query->where('mailing_group_id', $r->mailing_group_id);
                        }
                        $total = $query->count();

                        $groupName = $r->mailingGroup?->name ?? 'todos los contactos';
                        $warning   = $quota['remaining'] < $total
                            ? " ⚠ Solo tienes {$quota['remaining']} envíos disponibles."
                            : " Cuota disponible: {$quota['remaining']} (se renueva el {$quota['reset_date']}).";

                        return "Se enviará la plantilla \"{$r->mailTemplate?->name}\" a {$total} contacto(s) de {$groupName}. Esta acción no se puede deshacer.{$warning}";
                    })
                    ->modalSubmitActionLabel('Sí, enviar campaña')
                    ->action(function (MailCampaign $record): void {
                        $empresa  = Filament::getTenant();
                        $service  = new MailingService($empresa);

                        if (! $service->isConfigured()) {
                            Notification::make()
                                ->title('Servicio de correo no configurado')
                                ->body('El administrador debe activar el servicio de correo primero.')
                                ->warning()->send();
                            return;
                        }

                        $query = MailingContact::where('empresa_id', $empresa->id)->where('active', true);
                        if ($record->mailing_group_id) {
                            $query->where('mailing_group_id', $record->mailing_group_id);
                        }
                        $total = $query->count();

                        if ($total === 0) {
                            Notification::make()
                                ->title('Sin contactos activos')
                                ->body('Importa contactos primero desde el módulo Contactos.')
                                ->warning()->send();
                            return;
                        }

                        $record->update([
                            'status'           => 'sending',
                            'total_recipients' => $total,
                        ]);

                        SendMailCampaignJob::dispatch($record->id, $empresa->id);

                        Notification::make()
                            ->title('Campaña en proceso')
                            ->body("El envío a {$total} contacto(s) está procesándose en segundo plano. El estado se actualizará al terminar.")
                            ->success()
                            ->send();
                    }),

                // ── Ver resultado ─────────────────────────────────────────
                Tables\Actions\Action::make('verResultado')
                    ->label('Ver resultado')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->visible(fn (MailCampaign $r) => in_array($r->status, ['sent', 'failed']))
                    ->modalHeading(fn (MailCampaign $r) => 'Resultado: ' . $r->name)
                    ->modalContent(fn (MailCampaign $r) => new HtmlString(
                        '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;padding:8px 0;">'
                        . self::statCard('Destinatarios', number_format($r->total_recipients), '#6366f1')
                        . self::statCard('Enviados', number_format($r->sent_count), '#059669')
                        . self::statCard('Fallidos', number_format($r->failed_count), $r->failed_count > 0 ? '#dc2626' : '#9ca3af')
                        . '</div>'
                        . ($r->error_log ? '<p style="color:#dc2626;font-size:.8rem;margin-top:12px;"><strong>Detalle:</strong> ' . e($r->error_log) . '</p>' : '')
                        . ($r->sent_at ? '<p style="color:#9ca3af;font-size:.75rem;margin-top:8px;">Enviada el ' . $r->sent_at->format('d/m/Y H:i') . '</p>' : '')
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->label('Editar')->visible(fn (MailCampaign $r) => $r->status === 'draft'),
                    Tables\Actions\DeleteAction::make()->label('Eliminar'),
                ])
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Más opciones'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionadas'),
                ]),
            ]);
    }

    /** Genera una tarjeta de estadística para el modal de resultado. */
    private static function statCard(string $label, string $value, string $color): string
    {
        return "<div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;text-align:center;'>"
            . "<p style='font-size:1.75rem;font-weight:700;color:{$color};margin:0;'>{$value}</p>"
            . "<p style='font-size:.8rem;color:#6b7280;margin:4px 0 0;'>{$label}</p>"
            . "</div>";
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMailCampaigns::route('/'),
            'create' => Pages\CreateMailCampaign::route('/create'),
            'edit'   => Pages\EditMailCampaign::route('/{record}/edit'),
        ];
    }
}
