<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpresaMailingResource\Pages;
use App\Models\Empresa;
use App\Services\MailingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmpresaMailingResource extends Resource
{
    protected static ?string $model = Empresa::class;

    protected static ?string $slug             = 'mailing';
    protected static ?string $navigationIcon   = 'heroicon-o-envelope-open';
    protected static ?string $navigationLabel  = 'Config. Mailing';
    protected static ?string $navigationGroup  = 'Configuración';
    protected static ?int    $navigationSort   = 10;
    protected static ?string $modelLabel       = 'Empresa';
    protected static ?string $pluralModelLabel = 'Configuración de Mailing';

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Empresa')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Credenciales del servicio de correo')
                    ->description('Estas credenciales se usan para enviar correos desde esta empresa. Solo el super administrador puede modificarlas.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\TextInput::make('mailgun_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                            ->helperText('Encuéntrala en el panel de tu proveedor de correo → Account → API Keys.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('mailgun_domain')
                            ->label('Dominio verificado')
                            ->placeholder('mg.tudominio.com')
                            ->helperText('El dominio verificado en tu cuenta del servicio de correo.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('mailgun_from_email')
                            ->label('Email de origen')
                            ->email()
                            ->placeholder('no-reply@tudominio.com')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('mailgun_from_name')
                            ->label('Nombre de origen')
                            ->placeholder('Mi Empresa')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('plan')
                    ->label('Plan')
                    ->colors([
                        'gray'    => 'basic',
                        'info'    => 'pro',
                        'warning' => 'enterprise',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pro'        => 'Pro',
                        'enterprise' => 'Enterprise',
                        default      => 'Basic',
                    }),

                Tables\Columns\IconColumn::make('mailing_configurado')
                    ->label('Mailing')
                    ->boolean()
                    ->getStateUsing(fn (Empresa $record): bool =>
                        ! empty($record->mailgun_api_key) && ! empty($record->mailgun_domain)
                    )
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('mailgun_domain')
                    ->label('Dominio')
                    ->placeholder('No configurado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('mailgun_from_email')
                    ->label('Email de origen')
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Configurar'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpresaMailing::route('/'),
            'edit'  => Pages\EditEmpresaMailing::route('/{record}/edit'),
        ];
    }
}
