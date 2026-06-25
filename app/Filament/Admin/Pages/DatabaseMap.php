<?php

namespace App\Filament\Admin\Pages;

use App\Shared\Documentation\SchemaInspector;
use Filament\Pages\Page;

/**
 * Mapa de la BDD — documentación viva en /admin.
 * Solo super_admin: expone la estructura interna completa, nunca debe verse en /app.
 */
class DatabaseMap extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-circle-stack';
    protected static ?string $navigationGroup = 'Documentación';
    protected static ?string $navigationLabel = 'Mapa de la BDD';
    protected static ?string $title           = 'Mapa de la base de datos';

    protected static string $view = 'filament.admin.pages.database-map';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getSchemaData(): array
    {
        return app(SchemaInspector::class)->schema();
    }
}
