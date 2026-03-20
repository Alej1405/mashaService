<?php

namespace App\Filament\App\Pages\Cms;

use App\Models\CmsAbout;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CmsAboutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-information-circle';
    protected static ?string $navigationLabel = 'Nosotros';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $title           = 'Sección Nosotros';

    protected static string $view = 'filament.app.pages.cms.settings-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public function mount(): void
    {
        $record = CmsAbout::firstOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            ['titulo' => '', 'activo' => true]
        );

        $this->form->fill($record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Contenido')
                    ->schema([
                        TextInput::make('titulo')
                            ->label('Título de la sección')
                            ->placeholder('¿Quiénes somos?')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        RichEditor::make('cuerpo')
                            ->label('Contenido')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline',
                                'bulletList', 'orderedList',
                                'h2', 'h3', 'paragraph',
                                'link', 'undo', 'redo',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Imagen')
                    ->schema([
                        FileUpload::make('imagen')
                            ->label('Imagen de la sección')
                            ->image()
                            ->disk('public')
                            ->directory('cms/about')
                            ->imagePreviewHeight('120')
                            ->maxSize(3072)
                            ->helperText('JPG o PNG. Recomendado: 800×600 px.')
                            ->columnSpanFull(),
                    ]),

                Toggle::make('activo')->label('Sección visible')->inline(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        CmsAbout::updateOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            $data
        );

        Notification::make()->success()->title('Sección Nosotros guardada')->send();
    }

}
