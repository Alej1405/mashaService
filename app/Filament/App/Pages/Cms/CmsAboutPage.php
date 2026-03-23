<?php

namespace App\Filament\App\Pages\Cms;

use App\Models\CmsAbout;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
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
                Section::make('Contenido principal')
                    ->schema([
                        TextInput::make('titulo')
                            ->label('Título de la sección')
                            ->placeholder('Tu socio estratégico en comercio exterior')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        Textarea::make('descripcion')
                            ->label('Descripción general')
                            ->placeholder('Texto introductorio de la sección...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('¿Por qué nosotros?')
                    ->description('Lista de ventajas competitivas (se muestran con ✔)')
                    ->schema([
                        Repeater::make('por_que_nosotros')
                            ->label('')
                            ->schema([
                                TextInput::make('texto')
                                    ->label('Ventaja')
                                    ->placeholder('Tarifas competitivas')
                                    ->required(),
                            ])
                            ->addActionLabel('Agregar ventaja')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Section::make('Nuestros números')
                    ->description('Estadísticas y métricas destacadas')
                    ->schema([
                        Repeater::make('numeros')
                            ->label('')
                            ->schema([
                                TextInput::make('valor')
                                    ->label('Valor')
                                    ->placeholder('12+')
                                    ->required(),
                                TextInput::make('etiqueta')
                                    ->label('Etiqueta')
                                    ->placeholder('años de trayectoria')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Agregar número')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Section::make('Características destacadas')
                    ->description('Tarjetas con título y descripción corta')
                    ->schema([
                        Repeater::make('caracteristicas')
                            ->label('')
                            ->schema([
                                TextInput::make('titulo')
                                    ->label('Título')
                                    ->placeholder('Experiencia Comprobada')
                                    ->required(),
                                Textarea::make('descripcion')
                                    ->label('Descripción')
                                    ->placeholder('Más de 15 años liderando el comercio exterior en Ecuador.')
                                    ->rows(2)
                                    ->required(),
                            ])
                            ->addActionLabel('Agregar característica')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
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
