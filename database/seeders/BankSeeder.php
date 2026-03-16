<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $instituciones = [

            // ── BANCOS PRIVADOS (SBS) ─────────────────────────────────────────
            ['nombre' => 'Banco Pichincha',                          'tipo' => 'banco_privado'],
            ['nombre' => 'Banco de Guayaquil',                       'tipo' => 'banco_privado'],
            ['nombre' => 'Produbanco',                               'tipo' => 'banco_privado'],
            ['nombre' => 'Banco del Pacífico',                       'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Internacional',                      'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Bolivariano',                        'tipo' => 'banco_privado'],
            ['nombre' => 'Banco General Rumiñahui',                  'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Solidario',                          'tipo' => 'banco_privado'],
            ['nombre' => 'Banco del Austro',                         'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Coopnacional',                       'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Amazonas',                           'tipo' => 'banco_privado'],
            ['nombre' => 'Banco D-Miro',                             'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Comercial de Manabí',                'tipo' => 'banco_privado'],
            ['nombre' => 'Banco ProCredit',                          'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Capital',                            'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Loja',                               'tipo' => 'banco_privado'],
            ['nombre' => 'Banco de Machala',                         'tipo' => 'banco_privado'],
            ['nombre' => 'Banco VisionFund Ecuador',                 'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Finca',                              'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Delbank',                            'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Acceso',                             'tipo' => 'banco_privado'],
            ['nombre' => 'Banco Territorial',                        'tipo' => 'banco_privado'],

            // ── BANCOS PÚBLICOS (SBS) ──────────────────────────────────────────
            ['nombre' => 'BanEcuador B.P.',                          'tipo' => 'banco_publico'],
            ['nombre' => 'Banco del Estado (BEDE)',                  'tipo' => 'banco_publico'],
            ['nombre' => 'Corporación Financiera Nacional (CFN)',    'tipo' => 'banco_publico'],
            ['nombre' => 'Banco Central del Ecuador',                'tipo' => 'banco_publico'],
            ['nombre' => 'Instituto Ecuatoriano de Crédito Educativo (IECE)', 'tipo' => 'banco_publico'],

            // ── COOPERATIVAS DE AHORRO Y CRÉDITO — SEGMENTO 1 (SEPS) ─────────
            ['nombre' => 'JEP (Juventud Ecuatoriana Progresista)',   'tipo' => 'cooperativa'],
            ['nombre' => 'Jardín Azuayo',                            'tipo' => 'cooperativa'],
            ['nombre' => 'Policía Nacional',                         'tipo' => 'cooperativa'],
            ['nombre' => '29 de Octubre',                            'tipo' => 'cooperativa'],
            ['nombre' => 'Cooprogreso',                              'tipo' => 'cooperativa'],
            ['nombre' => 'Mushuc Runa',                              'tipo' => 'cooperativa'],
            ['nombre' => 'Mego',                                     'tipo' => 'cooperativa'],
            ['nombre' => 'San Francisco',                            'tipo' => 'cooperativa'],
            ['nombre' => 'OSCUS',                                    'tipo' => 'cooperativa'],
            ['nombre' => 'Cacpeco',                                  'tipo' => 'cooperativa'],
            ['nombre' => 'Pablo Muñoz Vega',                         'tipo' => 'cooperativa'],
            ['nombre' => 'Atuntaqui',                                'tipo' => 'cooperativa'],
            ['nombre' => 'Riobamba',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'El Sagrario',                              'tipo' => 'cooperativa'],
            ['nombre' => 'Andalucía',                                'tipo' => 'cooperativa'],
            ['nombre' => 'Alianza del Valle',                        'tipo' => 'cooperativa'],
            ['nombre' => 'Cotocollao',                               'tipo' => 'cooperativa'],
            ['nombre' => 'Fernando Daquilema',                       'tipo' => 'cooperativa'],
            ['nombre' => 'Cámara de Comercio de Quito',              'tipo' => 'cooperativa'],
            ['nombre' => 'Luz del Valle',                            'tipo' => 'cooperativa'],
            ['nombre' => 'Chibuleo',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Kullki Wasi',                              'tipo' => 'cooperativa'],
            ['nombre' => 'Pilahuin Tío',                             'tipo' => 'cooperativa'],
            ['nombre' => 'Ejército',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Santa Rosa',                               'tipo' => 'cooperativa'],
            ['nombre' => 'Tulcán',                                   'tipo' => 'cooperativa'],
            ['nombre' => '23 de Julio',                              'tipo' => 'cooperativa'],
            ['nombre' => 'Educadores de Tungurahua',                 'tipo' => 'cooperativa'],
            ['nombre' => 'Sumak Kawsay',                             'tipo' => 'cooperativa'],
            ['nombre' => 'Virgen del Cisne',                         'tipo' => 'cooperativa'],
            ['nombre' => 'Coopmego',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Calceta',                                  'tipo' => 'cooperativa'],
            ['nombre' => 'Tena',                                     'tipo' => 'cooperativa'],

            // ── COOPERATIVAS — SEGMENTO 2 (SEPS) ─────────────────────────────
            ['nombre' => '15 de Abril',                              'tipo' => 'cooperativa'],
            ['nombre' => '9 de Octubre',                             'tipo' => 'cooperativa'],
            ['nombre' => 'Acción Rural',                             'tipo' => 'cooperativa'],
            ['nombre' => 'Baños',                                    'tipo' => 'cooperativa'],
            ['nombre' => 'Camino de la Prosperidad',                 'tipo' => 'cooperativa'],
            ['nombre' => 'Chone Ltda.',                              'tipo' => 'cooperativa'],
            ['nombre' => 'Cía Ltda. Nueva Huancavilca',             'tipo' => 'cooperativa'],
            ['nombre' => 'Ciudad de Zamora',                         'tipo' => 'cooperativa'],
            ['nombre' => 'Coca Ltda.',                               'tipo' => 'cooperativa'],
            ['nombre' => 'Comercio',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Cop. de Ahorro y Crédito de la Pequeña Empresa de Pastaza', 'tipo' => 'cooperativa'],
            ['nombre' => 'COOPAC Austro',                            'tipo' => 'cooperativa'],
            ['nombre' => 'Credi Ya',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'El Cambio',                                'tipo' => 'cooperativa'],
            ['nombre' => 'Fasayñan',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Guaranda',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Huaquillas',                               'tipo' => 'cooperativa'],
            ['nombre' => 'Intercultural Financiera',                 'tipo' => 'cooperativa'],
            ['nombre' => 'Juventud Unida',                           'tipo' => 'cooperativa'],
            ['nombre' => 'La Merced',                                'tipo' => 'cooperativa'],
            ['nombre' => 'Loja Internacional',                       'tipo' => 'cooperativa'],
            ['nombre' => 'Manantial de Oro',                         'tipo' => 'cooperativa'],
            ['nombre' => 'Metropolitana',                            'tipo' => 'cooperativa'],
            ['nombre' => 'Minga',                                    'tipo' => 'cooperativa'],
            ['nombre' => 'Mushuk Yuyay',                             'tipo' => 'cooperativa'],
            ['nombre' => 'Nueva Jerusalén',                          'tipo' => 'cooperativa'],
            ['nombre' => 'Padre Julián Lorente',                     'tipo' => 'cooperativa'],
            ['nombre' => 'Pan American',                             'tipo' => 'cooperativa'],
            ['nombre' => 'Previsión, Ahorro y Desarrollo (PRADEC)', 'tipo' => 'cooperativa'],
            ['nombre' => 'Puéllaro',                                 'tipo' => 'cooperativa'],
            ['nombre' => 'Pusará',                                   'tipo' => 'cooperativa'],
            ['nombre' => 'Quilanga',                                  'tipo' => 'cooperativa'],
            ['nombre' => 'San Gabriel',                              'tipo' => 'cooperativa'],
            ['nombre' => 'San José (Chimborazo)',                    'tipo' => 'cooperativa'],
            ['nombre' => 'San José de Guaslán',                     'tipo' => 'cooperativa'],
            ['nombre' => 'San Miguel de Los Bancos',                 'tipo' => 'cooperativa'],
            ['nombre' => 'San Pedro de Taboada',                     'tipo' => 'cooperativa'],
            ['nombre' => 'Santa Ana',                                'tipo' => 'cooperativa'],
            ['nombre' => 'Santa Bárbara',                            'tipo' => 'cooperativa'],
            ['nombre' => 'SERVIDORES PÚBLICOS del Ministerio de Educación y Cultura', 'tipo' => 'cooperativa'],
            ['nombre' => 'Sociedad Protectora del Trabajador Magisterial', 'tipo' => 'cooperativa'],
            ['nombre' => 'Sol de los Andes',                         'tipo' => 'cooperativa'],
            ['nombre' => 'Textil 14 de Marzo',                       'tipo' => 'cooperativa'],
            ['nombre' => 'Unión El Ejido',                           'tipo' => 'cooperativa'],
            ['nombre' => 'Vencedores',                               'tipo' => 'cooperativa'],
            ['nombre' => 'Visión de los Andes',                      'tipo' => 'cooperativa'],

            // ── MUTUALISTAS (SBS) ─────────────────────────────────────────────
            ['nombre' => 'Mutualista Pichincha',                     'tipo' => 'mutualista'],
            ['nombre' => 'Mutualista Azuay',                         'tipo' => 'mutualista'],
            ['nombre' => 'Mutualista Imbabura',                      'tipo' => 'mutualista'],
            ['nombre' => 'Mutualista Ambato',                        'tipo' => 'mutualista'],

            // ── SOCIEDADES FINANCIERAS (SBS) ──────────────────────────────────
            ['nombre' => 'Diners Club del Ecuador',                  'tipo' => 'financiera'],
            ['nombre' => 'Servicios y Cobranzas Interdin',           'tipo' => 'financiera'],
            ['nombre' => 'Vazcorp Sociedad Financiera',              'tipo' => 'financiera'],

        ];

        foreach ($instituciones as $inst) {
            Bank::updateOrCreate(
                ['nombre' => $inst['nombre']],
                ['tipo'   => $inst['tipo'], 'activo' => true]
            );
        }
    }
}
