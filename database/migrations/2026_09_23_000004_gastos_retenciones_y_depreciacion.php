<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que faltaba para que el formulario 101 salga del ERP y no de supuestos.
 *
 *   - gastos que no pasan por inventario (alimentación, transporte, servicios
 *     básicos, arriendo, honorarios…), con su IVA, su retención y la marca de
 *     deducible o no deducible, que el 101 exige separada
 *   - retenciones que nos practican los clientes, que son crédito en el 101
 *   - activos fijos y su depreciación en línea recta
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Tipos de gasto: cada uno amarrado a su casillero del 101 -------
        Schema::create('tipos_gasto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('codigo', 80)->nullable();
            $table->foreignId('account_plan_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->string('casillero_101', 10)->nullable();
            $table->string('concepto_retencion', 140)->nullable();
            $table->boolean('deducible')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['empresa_id', 'activo']);
        });

        // ---- Gastos ---------------------------------------------------------
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('retencion_id')->nullable()->constrained('retenciones')->nullOnDelete();
            $table->string('numero_documento', 30)->nullable();
            $table->string('autorizacion', 60)->nullable();
            $table->string('tipo_documento', 30)->default('factura');
            $table->date('fecha');
            $table->string('descripcion', 200)->nullable();
            $table->string('forma_pago', 30)->default('efectivo');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('retenido', 15, 2)->default(0);
            $table->decimal('no_deducible', 15, 2)->default(0);
            $table->string('estado', 20)->default('borrador');
            $table->timestamps();
            $table->index(['empresa_id', 'fecha']);
        });

        Schema::create('gasto_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_id')->constrained('gastos')->cascadeOnDelete();
            $table->foreignId('tipo_gasto_id')->nullable()->constrained('tipos_gasto')->nullOnDelete();
            $table->foreignId('account_plan_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->string('descripcion', 200)->nullable();
            $table->decimal('base', 15, 2)->default(0);
            $table->decimal('porcentaje_iva', 5, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->boolean('deducible')->default(true);
            $table->string('motivo_no_deducible', 160)->nullable();
            $table->timestamps();
        });

        // ---- Retenciones que nos practican ----------------------------------
        Schema::create('retenciones_recibidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('numero', 30)->nullable();
            $table->date('fecha');
            $table->string('tipo', 10);          // renta | iva
            $table->string('concepto', 140)->nullable();
            $table->string('codigo_sri', 10)->nullable();
            $table->decimal('base', 15, 2)->default(0);
            $table->decimal('porcentaje', 6, 2)->default(0);
            $table->decimal('valor', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['empresa_id', 'fecha']);
        });

        // ---- Activos fijos y depreciación -----------------------------------
        Schema::create('activos_fijos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 30)->nullable();
            $table->string('nombre', 160);
            $table->string('categoria', 40)->default('maquinaria');
            $table->string('ubicacion', 120)->nullable();
            $table->date('fecha_compra');
            $table->decimal('costo', 15, 2);
            $table->decimal('valor_residual', 15, 2)->default(0);
            $table->unsignedSmallInteger('vida_util_meses');
            $table->decimal('depreciacion_acumulada', 15, 2)->default(0);
            $table->foreignId('cuenta_activo_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->foreignId('cuenta_depreciacion_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->foreignId('cuenta_gasto_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['empresa_id', 'activo']);
        });

        Schema::create('depreciaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_fijo_id')->constrained('activos_fijos')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->decimal('valor', 15, 2);
            $table->timestamps();
            $table->unique(['activo_fijo_id', 'anio', 'mes']);
        });

        // ---- Tipos de gasto de arranque -------------------------------------
        // Los conceptos de retención salen de la tabla de porcentajes vigente;
        // el casillero del 101 se completa cuando llegue la ficha del SRI.
        $tipos = [
            ['Alimentación', 'Servicios con predominio de mano de obra'],
            ['Transporte y movilización', 'Servicios con predominio de mano de obra'],
            ['Combustible', 'Bienes muebles de naturaleza corporal'],
            ['Servicios básicos', null],
            ['Arriendo de inmuebles', 'Arrendamiento de inmuebles'],
            ['Honorarios profesionales', 'Honorarios profesionales · persona natural'],
            ['Servicios profesionales de sociedades', 'Servicios profesionales prestados por sociedades'],
            ['Publicidad y comunicación', 'Publicidad y comunicación'],
            ['Suministros de oficina', 'Bienes muebles de naturaleza corporal'],
            ['Mantenimiento y reparaciones', 'Servicios con predominio de mano de obra'],
            ['Seguros', null],
            ['Capacitación', 'Servicios con predominio de mano de obra'],
            ['Gastos bancarios y financieros', null],
            ['Impuestos, tasas y contribuciones', null],
        ];

        foreach ($tipos as $orden => [$nombre, $concepto]) {
            DB::table('tipos_gasto')->insert([
                'empresa_id' => null,
                'nombre' => $nombre,
                'codigo' => \Illuminate\Support\Str::slug($nombre),
                'concepto_retencion' => $concepto,
                'deducible' => true,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciaciones');
        Schema::dropIfExists('activos_fijos');
        Schema::dropIfExists('retenciones_recibidas');
        Schema::dropIfExists('gasto_lineas');
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('tipos_gasto');
    }
};
