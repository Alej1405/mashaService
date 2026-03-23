<?php

namespace App\Filament\App\Pages\Cms;

use App\Models\CmsHero;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CmsHeroPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio (Hero)';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $title           = 'Sección Hero — Pantalla de inicio';

    protected static string $view = 'filament.app.pages.cms.settings-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $record = CmsHero::firstOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            ['titulo' => '', 'activo' => true]
        );

        $this->form->fill($record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Texto principal')
                    ->columns(2)
                    ->schema([
                        TextInput::make('titulo')
                            ->label('Título principal')
                            ->placeholder('La solución que tu negocio necesita')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        TextInput::make('subtitulo')
                            ->label('Subtítulo')
                            ->placeholder('Breve descripción de lo que hacen')
                            ->maxLength(255),

                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Párrafo más largo con el pitch de la empresa…')
                            ->rows(3)
                            ->maxLength(600),
                    ]),

                Section::make('Imagen de fondo')
                    ->schema([
                        FileUpload::make('imagen')
                            ->label('Imagen de fondo')
                            ->image()
                            ->disk('public')
                            ->directory('cms/hero')
                            ->imagePreviewHeight('120')
                            ->maxSize(4096)
                            ->helperText('JPG o PNG. Recomendado: 1920×1080 px.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Botón de llamada a la acción (CTA)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('cta_texto')
                            ->label('Texto del botón')
                            ->placeholder('Contáctanos')
                            ->maxLength(60),

                        TextInput::make('cta_url')
                            ->label('Enlace del botón')
                            ->placeholder('https://wa.me/593999999999')
                            ->url()
                            ->maxLength(500),
                    ]),

                Toggle::make('activo')->label('Sección visible')->inline(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        CmsHero::updateOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            $data
        );

        Notification::make()->success()->title('Hero guardado')->send();
    }

}
