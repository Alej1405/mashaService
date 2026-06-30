<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->jsonb('modules_template')->nullable()->after('caracteristicas');
        });

        // Poblar templates para los 3 planes fijos
        $templates = [
            'basic' => [
                'finanzas'   => false,
                'tesoreria'  => false,
                'compras'    => false,
                'inventario' => false,
                'ventas'     => false,
                'produccion' => false,
                'marketing'  => true,
                'tienda'     => false,
                'logistica'  => false,
            ],
            'pro' => [
                'finanzas'   => true,
                'tesoreria'  => true,
                'compras'    => true,
                'inventario' => true,
                'ventas'     => true,
                'produccion' => true,
                'marketing'  => true,
                'tienda'     => false,
                'logistica'  => true,
            ],
            'enterprise' => [
                'finanzas'   => true,
                'tesoreria'  => true,
                'compras'    => true,
                'inventario' => true,
                'ventas'     => true,
                'produccion' => true,
                'marketing'  => true,
                'tienda'     => true,
                'logistica'  => true,
            ],
        ];

        foreach ($templates as $key => $template) {
            DB::table('service_plans')
                ->where('key', $key)
                ->update(['modules_template' => json_encode($template)]);
        }
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn('modules_template');
        });
    }
};
