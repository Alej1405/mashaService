<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            // Masa / Peso
            ['nombre' => 'Kilogramo',       'abreviatura' => 'kg'],
            ['nombre' => 'Gramo',           'abreviatura' => 'g'],
            ['nombre' => 'Tonelada',        'abreviatura' => 't'],
            ['nombre' => 'Libra',           'abreviatura' => 'lb'],
            ['nombre' => 'Onza',            'abreviatura' => 'oz'],
            ['nombre' => 'Miligramo',       'abreviatura' => 'mg'],
            ['nombre' => 'Quintal',         'abreviatura' => 'qq'],
            // Volumen
            ['nombre' => 'Litro',           'abreviatura' => 'L'],
            ['nombre' => 'Mililitro',       'abreviatura' => 'mL'],
            ['nombre' => 'Galón',           'abreviatura' => 'gal'],
            ['nombre' => 'Centilitro',      'abreviatura' => 'cL'],
            ['nombre' => 'Metro cúbico',    'abreviatura' => 'm³'],
            // Longitud
            ['nombre' => 'Metro',           'abreviatura' => 'm'],
            ['nombre' => 'Centímetro',      'abreviatura' => 'cm'],
            ['nombre' => 'Milímetro',       'abreviatura' => 'mm'],
            ['nombre' => 'Kilómetro',       'abreviatura' => 'km'],
            ['nombre' => 'Pulgada',         'abreviatura' => 'in'],
            ['nombre' => 'Pie',             'abreviatura' => 'ft'],
            ['nombre' => 'Yarda',           'abreviatura' => 'yd'],
            // Área
            ['nombre' => 'Metro cuadrado',  'abreviatura' => 'm²'],
            ['nombre' => 'Centímetro cuadrado', 'abreviatura' => 'cm²'],
            ['nombre' => 'Hectárea',        'abreviatura' => 'ha'],
            // Conteo
            ['nombre' => 'Unidad',          'abreviatura' => 'u'],
            ['nombre' => 'Docena',          'abreviatura' => 'doc'],
            ['nombre' => 'Caja',            'abreviatura' => 'cja'],
            ['nombre' => 'Par',             'abreviatura' => 'par'],
            ['nombre' => 'Paquete',         'abreviatura' => 'paq'],
            ['nombre' => 'Saco',            'abreviatura' => 'saco'],
            ['nombre' => 'Fardo',           'abreviatura' => 'fardo'],
            ['nombre' => 'Rollo',           'abreviatura' => 'rllo'],
            ['nombre' => 'Plancha',         'abreviatura' => 'plnch'],
            ['nombre' => 'Bulto',           'abreviatura' => 'bulto'],
            ['nombre' => 'Paleta',          'abreviatura' => 'palet'],
            ['nombre' => 'Botella',         'abreviatura' => 'bot'],
            ['nombre' => 'Lata',            'abreviatura' => 'lata'],
            ['nombre' => 'Tubo',            'abreviatura' => 'tubo'],
            // Tiempo
            ['nombre' => 'Hora',            'abreviatura' => 'h'],
            ['nombre' => 'Día',             'abreviatura' => 'día'],
            ['nombre' => 'Semana',          'abreviatura' => 'sem'],
            ['nombre' => 'Mes',             'abreviatura' => 'mes'],
            // Servicios
            ['nombre' => 'Servicio',        'abreviatura' => 'serv'],
            ['nombre' => 'Porción',         'abreviatura' => 'porc'],
            ['nombre' => 'Juego',           'abreviatura' => 'jgo'],
        ];

        Empresa::all()->each(function (Empresa $empresa) use ($unidades) {
            foreach ($unidades as $u) {
                MeasurementUnit::firstOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'nombre'     => $u['nombre'],
                    ],
                    [
                        'abreviatura' => $u['abreviatura'],
                        'activo'      => true,
                    ]
                );
            }
        });

        $this->command->info('Unidades de medida estándar registradas para todas las empresas.');
    }
}
