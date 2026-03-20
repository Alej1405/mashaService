<?php

namespace App\Filament\App\Pages\Cms;

use App\Models\CmsContact;
use Filament\Facades\Filament;
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

class CmsContactPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'Contacto';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?int    $navigationSort  = 8;
    protected static ?string $title           = 'Sección Contacto';

    protected static string $view = 'filament.app.pages.cms.settings-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public function mount(): void
    {
        $record = CmsContact::firstOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            ['activo' => true]
        );

        $this->form->fill($record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de contacto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->placeholder('Av. Amazonas N24-33, Quito, Ecuador')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->placeholder('+593 2 123 4567')
                            ->tel()
                            ->maxLength(30),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->placeholder('+593 99 999 9999')
                            ->maxLength(30),

                        TextInput::make('email')
                            ->label('Email de contacto')
                            ->email()
                            ->placeholder('info@miempresa.com')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Mapa')
                    ->schema([
                        Textarea::make('mapa_embed')
                            ->label('Código embed de Google Maps')
                            ->placeholder('<iframe src="https://www.google.com/maps/embed?..." ...></iframe>')
                            ->rows(4)
                            ->helperText('Pega el código iframe de Google Maps → Compartir → Insertar un mapa.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Redes sociales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facebook')
                            ->label('Facebook')
                            ->placeholder('https://facebook.com/miempresa')
                            ->url()->maxLength(500),

                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->placeholder('https://instagram.com/miempresa')
                            ->url()->maxLength(500),

                        TextInput::make('linkedin')
                            ->label('LinkedIn')
                            ->placeholder('https://linkedin.com/company/miempresa')
                            ->url()->maxLength(500),

                        TextInput::make('youtube')
                            ->label('YouTube')
                            ->placeholder('https://youtube.com/@miempresa')
                            ->url()->maxLength(500),

                        TextInput::make('tiktok')
                            ->label('TikTok')
                            ->placeholder('https://tiktok.com/@miempresa')
                            ->url()->maxLength(500),
                    ]),

                Toggle::make('activo')->label('Sección visible')->inline(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        CmsContact::updateOrCreate(
            ['empresa_id' => Filament::getTenant()->id],
            $data
        );

        Notification::make()->success()->title('Contacto guardado')->send();
    }

}
