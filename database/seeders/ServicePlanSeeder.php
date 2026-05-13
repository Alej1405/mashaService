<?php

namespace Database\Seeders;

use App\Models\ServicePlan;
use Illuminate\Database\Seeder;

class ServicePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key'        => 'basic',
                'nombre'     => 'Plan Basic',
                'sort_order' => 1,
                'descripcion' => 'Acceso al módulo de Mailing para gestionar campañas de correo masivo, contactos y plantillas. Ideal para empresas que buscan una solución de comunicación por correo electrónico.',
                'caracteristicas' => [
                    'Dashboard de Mailing con estadísticas en tiempo real',
                    'Envío de campañas de correo masivo vía Mailgun',
                    'Gestión de contactos y grupos de destinatarios',
                    'Plantillas de correo personalizadas con editor visual',
                    'Estadísticas de entrega, apertura y clics (30 días)',
                    'Historial de eventos por dominio',
                    'Monitoreo de cuota de envíos del período',
                    'Soporte por correo electrónico',
                ],
            ],
            [
                'key'        => 'pro',
                'nombre'     => 'Plan Pro',
                'sort_order' => 2,
                'descripcion' => 'ERP completo para gestionar todas las operaciones de su empresa: ventas, compras, inventario, contabilidad, manufactura y tesorería. Incluye todo lo del plan Basic más el módulo de Mailing.',
                'caracteristicas' => [
                    'Todo lo incluido en el Plan Basic',
                    'Módulo de Ventas: facturas, clientes y seguimiento',
                    'Módulo de Compras: órdenes, proveedores y recepciones',
                    'Control de Inventario: stock, movimientos y alertas',
                    'Contabilidad: plan de cuentas, asientos automáticos y mapeos',
                    'Manufactura: órdenes de producción y consumo de materiales',
                    'Tesorería: caja, bancos, tarjetas de crédito y movimientos',
                    'Módulo de Logística',
                    'Informes financieros con exportación para Supercias',
                    'Numeración automática de documentos (VEN, COM)',
                    'Soporte prioritario',
                ],
            ],
            [
                'key'        => 'enterprise',
                'nombre'     => 'Plan Enterprise',
                'sort_order' => 3,
                'descripcion' => 'La solución más completa del ERP, con acceso a todas las funcionalidades del plan Pro más módulos exclusivos para empresas con operaciones avanzadas.',
                'caracteristicas' => [
                    'Todo lo incluido en el Plan Pro',
                    'Módulo de Diseño de Producto',
                    'Gestión de Comercio Exterior',
                    'Funcionalidades avanzadas y personalizadas',
                    'Soporte dedicado con tiempo de respuesta prioritario',
                    'Acceso anticipado a nuevas funcionalidades',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            ServicePlan::updateOrCreate(
                ['key' => $plan['key']],
                $plan
            );
        }
    }
}
