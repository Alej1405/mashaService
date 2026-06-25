<?php

namespace App\Filament\Admin\Resources\EmpresaServiciosResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsuariosAccesoRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Usuarios con acceso';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->label('Nombre'),
            Forms\Components\TextInput::make('email')->email()->required()->label('Correo'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable()->color('gray'),
                Tables\Columns\BadgeColumn::make('roles.name')->label('Rol')->color('primary'),
                Tables\Columns\TextColumn::make('created_at')->label('Registrado')->date('d/m/Y')->sortable()->color('gray'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
