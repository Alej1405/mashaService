<?php

namespace App\Filament\App\Pages\Cms;

use App\Models\CmsTerminos;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CmsTerminosPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Términos y Condiciones';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?int    $navigationSort  = 11;
    protected static ?string $title           = 'Términos y Condiciones';

    protected static string $view = 'filament.app.pages.cms.settings-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public function mount(): void
    {
        $record = CmsTerminos::firstOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            ['titulo' => 'Términos y Condiciones', 'activo' => true]
        );

        $this->form->fill($record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Encabezado')
                    ->schema([
                        TextInput::make('titulo')
                            ->label('Título del documento')
                            ->required()
                            ->maxLength(200)
                            ->default('Términos y Condiciones')
                            ->columnSpanFull(),

                        DatePicker::make('ultima_actualizacion')
                            ->label('Fecha de última actualización')
                            ->placeholder('Selecciona una fecha')
                            ->helperText('Se muestra al final del documento como referencia.'),

                        Toggle::make('activo')
                            ->label('Página visible (publicada)')
                            ->inline(false),
                    ])
                    ->columns(2),

                Section::make('Contenido')
                    ->schema([
                        RichEditor::make('contenido')
                            ->label('')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline',
                                'bulletList', 'orderedList',
                                'h2', 'h3', 'paragraph',
                                'link', 'blockquote', 'undo', 'redo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        CmsTerminos::updateOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            $data
        );

        Notification::make()->success()->title('Términos y Condiciones guardados')->send();
    }
}
