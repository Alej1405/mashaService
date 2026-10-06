<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Logística sale del ERP. Fue un ensayo (AlemCargo, sin operación real) y su
 * código vive ahora en webMasha/logistica. Aquí se borran sus tablas y su rastro
 * en la configuración de paneles, permisos y cola. Sus datos no se conservan.
 *
 * Las migraciones que crearon estas tablas se quedan como historia: en una
 * instalación nueva se crean y esta las borra al final.
 */
return new class extends Migration
{
    private const TABLAS = [
        'logistics_shipment_packages', 'logistics_shipment_history', 'logistics_shipment_charges',
        'logistics_shipment_bills', 'logistics_payment_claims', 'logistics_billing_requests',
        'logistics_package_items', 'logistics_documents', 'logistics_packages',
        'logistics_shipments', 'logistics_consignatarios', 'logistics_bodegas',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("DROP TABLE IF EXISTS \"{$tabla}\" CASCADE");
            } else {
                Schema::disableForeignKeyConstraints();
                Schema::dropIfExists($tabla);
                Schema::enableForeignKeyConstraints();
            }
        }

        $panel = Schema::hasTable('panels') ? DB::table('panels')->where('key', 'logistics')->value('id') : null;
        if ($panel) {
            DB::table('plan_panel')->where('panel_id', $panel)->delete();
            DB::table('panel_modules')->where('panel_id', $panel)->delete();
            DB::table('panels')->where('id', $panel)->delete();
        }
        if (Schema::hasTable('panel_modules')) {
            DB::table('panel_modules')->where('module_key', 'logistica')->delete();
        }
        if (Schema::hasTable('role_module')) {
            DB::table('role_module')->where('module_key', 'logistica')->delete();
        }

        // Correos de logística que fallaron en abril: sus clases ya no existen.
        DB::table('failed_jobs')->where('payload', 'like', '%LogisticsPackageStatusMail%')
            ->orWhere('payload', 'like', '%LogisticsBillingApprovedMail%')->delete();
    }

    public function down(): void
    {
        // Solo avanzamos: el código está en webMasha/logistica.
    }
};
