<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

/**
 * Catálogo estándar de unidades de medida, disponible para TODAS las empresas.
 * Idempotente (firstOrCreate por empresa + nombre). Cada unidad lleva su familia
 * (tipo) y su factor a la unidad base de esa familia, para conversión automática.
 *
 * Bases (factor 1): volumen=mililitro, longitud=metro, masa=gramo, conteo=unidad.
 */
class MeasurementUnitCatalogSeeder extends Seeder
{
    /** @var array<int,array{0:string,1:string,2:string,3:float}> nombre, abreviatura, tipo, factor */
    private array $catalogo = [
        // Conteo
        ['Unidad', 'u', 'conteo', 1],
        ['Par', 'par', 'conteo', 2],
        ['Docena', 'doc', 'conteo', 12],
        ['Ciento', 'cto', 'conteo', 100],
        // Volumen (base: mililitro)
        ['Mililitro', 'ml', 'volumen', 1],
        ['Centímetro cúbico', 'cm³', 'volumen', 1],
        ['Litro', 'l', 'volumen', 1000],
        ['Galón', 'gal', 'volumen', 3785.41],
        // Longitud (base: metro)
        ['Milímetro', 'mm', 'longitud', 0.001],
        ['Centímetro', 'cm', 'longitud', 0.01],
        ['Metro', 'm', 'longitud', 1],
        ['Kilómetro', 'km', 'longitud', 1000],
        ['Pulgada', 'in', 'longitud', 0.0254],
        // Masa (base: gramo)
        ['Miligramo', 'mg', 'masa', 0.001],
        ['Gramo', 'g', 'masa', 1],
        ['Kilogramo', 'kg', 'masa', 1000],
        ['Libra', 'lb', 'masa', 453.592],
        ['Tonelada', 't', 'masa', 1000000],
    ];

    public function run(): void
    {
        foreach (Empresa::all() as $empresa) {
            foreach ($this->catalogo as [$nombre, $abrev, $tipo, $factor]) {
                MeasurementUnit::withoutGlobalScopes()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'nombre' => $nombre],
                    ['abreviatura' => $abrev, 'tipo' => $tipo, 'factor' => $factor, 'activo' => true],
                );
            }
        }
    }
}
