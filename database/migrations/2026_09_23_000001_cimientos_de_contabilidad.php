<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cimientos del módulo de contabilidad.
 *
 * Sale de la auditoría contra las skills `supercias-ec` e
 * `inventarios-contable-ec`. Cubre lo que no depende de la ficha del SRI:
 *
 *   - el panel, registrado en la base (sin esta fila, canAccess da 403)
 *   - la línea del estado financiero por cuenta (sin ella el balance se arma
 *     a mano cada año)
 *   - tarifas de IVA y porcentajes de retención CON VIGENCIA POR FECHA
 *   - retenciones emitidas, con su detalle
 *   - la ficha societaria: tipo de compañía, socios y administradores
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. El panel, en la base -------------------------------------
        $panelId = DB::table('panels')->where('key', 'contabilidad')->value('id');

        if (! $panelId) {
            $panelId = DB::table('panels')->insertGetId([
                'key' => 'contabilidad', 'name' => 'Contabilidad', 'path' => 'contabilidad',
                'color' => 'indigo', 'icon' => 'heroicon-o-calculator',
                'activo' => true, 'sort' => 8, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach (['finanzas', 'tesoreria'] as $modulo) {
            $existe = DB::table('panel_modules')->where('panel_id', $panelId)->where('module_key', $modulo)->exists();
            if (! $existe) {
                DB::table('panel_modules')->insert([
                    'panel_id' => $panelId, 'module_key' => $modulo,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        foreach (DB::table('service_plans')->whereIn('key', ['pro', 'enterprise'])->pluck('id') as $planId) {
            $existe = DB::table('plan_panel')->where('panel_id', $panelId)->where('service_plan_id', $planId)->exists();
            if (! $existe) {
                DB::table('plan_panel')->insert([
                    'service_plan_id' => $planId, 'panel_id' => $panelId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // ---- 2. Cada cuenta sabe a qué línea del estado suma ---------------
        Schema::table('account_plans', function (Blueprint $table) {
            $table->string('linea_estado', 60)->nullable()->after('modulo');
            $table->string('estado_financiero', 20)->nullable()->after('linea_estado');
        });

        // ---- 3. La compañía ante la Superintendencia ----------------------
        Schema::table('empresas', function (Blueprint $table) {
            // SAS, S.A., Cía. Ltda. — sale del documento del RUC
            $table->string('tipo_compania', 30)->nullable()->after('tipo_identificacion');
            $table->boolean('agente_retencion')->default(false)->after('tipo_compania');
            $table->string('marco_contable', 20)->default('niif_pymes')->after('agente_retencion');
            $table->date('inicio_ejercicio')->nullable()->after('marco_contable');
        });

        // ---- 4. Tarifas de IVA con vigencia -------------------------------
        Schema::create('tarifas_iva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 10);
            $table->decimal('porcentaje', 5, 2);
            $table->string('descripcion', 120);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'vigente_desde']);
        });

        // ---- 5. Porcentajes de retención con vigencia ---------------------
        Schema::create('porcentajes_retencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo', 10);                 // renta | iva
            $table->string('concepto', 140);
            $table->string('codigo_sri', 10)->nullable(); // lo llena la ficha del SRI
            $table->decimal('porcentaje', 6, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('resolucion', 60)->nullable();
            $table->timestamps();
            $table->index(['tipo', 'vigente_desde']);
        });

        // ---- 6. Retenciones emitidas --------------------------------------
        Schema::create('retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('numero', 30)->nullable();
            $table->date('fecha');
            $table->decimal('base_renta', 15, 2)->default(0);
            $table->decimal('retenido_renta', 15, 2)->default(0);
            $table->decimal('base_iva', 15, 2)->default(0);
            $table->decimal('retenido_iva', 15, 2)->default(0);
            $table->decimal('total_retenido', 15, 2)->default(0);
            $table->string('estado', 20)->default('emitida');
            $table->timestamps();
            $table->index(['empresa_id', 'fecha']);
        });

        Schema::create('retencion_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retencion_id')->constrained('retenciones')->cascadeOnDelete();
            $table->foreignId('porcentaje_retencion_id')->nullable()->constrained('porcentajes_retencion')->nullOnDelete();
            $table->string('tipo', 10);
            $table->string('concepto', 140);
            $table->string('codigo_sri', 10)->nullable();
            $table->decimal('base', 15, 2);
            $table->decimal('porcentaje', 6, 2);
            $table->decimal('valor', 15, 2);
            $table->timestamps();
        });

        // ---- 7. Nómina societaria (anexo obligatorio de la SCVS) ----------
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo_identificacion', 20)->default('cedula');
            $table->string('identificacion', 20);
            $table->string('nombre', 160);
            $table->string('nacionalidad', 60)->default('Ecuatoriana');
            $table->decimal('participacion', 6, 2)->default(0);
            $table->decimal('capital', 15, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['empresa_id', 'activo']);
        });

        Schema::create('administradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('identificacion', 20);
            $table->string('nombre', 160);
            $table->string('cargo', 80);
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->timestamps();
            $table->index(['empresa_id']);
        });

        // ---- 8. Cierre de ejercicio ---------------------------------------
        Schema::create('ejercicios_contables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->timestamp('cerrado_en')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asiento_cierre_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->decimal('resultado', 15, 2)->nullable();
            $table->timestamps();
            $table->unique(['empresa_id', 'anio']);
        });

        // ---- 9. Datos de arranque: lo verificado en las skills -------------
        $ivas = [
            ['codigo' => '4', 'porcentaje' => 15, 'descripcion' => 'Tarifa general', 'desde' => '2024-04-01', 'hasta' => null],
            ['codigo' => '2', 'porcentaje' => 12, 'descripcion' => 'Tarifa general anterior', 'desde' => '2008-01-01', 'hasta' => '2024-03-31'],
            ['codigo' => '8', 'porcentaje' => 8,  'descripcion' => 'Tarifa reducida por decreto', 'desde' => '2024-01-01', 'hasta' => null],
            ['codigo' => '5', 'porcentaje' => 5,  'descripcion' => 'Materiales de construcción', 'desde' => '2024-01-01', 'hasta' => null],
            ['codigo' => '0', 'porcentaje' => 0,  'descripcion' => 'Bienes y servicios con tarifa cero', 'desde' => '2008-01-01', 'hasta' => null],
        ];
        foreach ($ivas as $i) {
            DB::table('tarifas_iva')->insert([
                'empresa_id' => null, 'codigo' => $i['codigo'], 'porcentaje' => $i['porcentaje'],
                'descripcion' => $i['descripcion'], 'vigente_desde' => $i['desde'], 'vigente_hasta' => $i['hasta'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Renta: Resolución NAC-DGERCGC26-00000009, vigente desde el 01/03/2026.
        // IVA: Resolución NAC-DGERCGC20-00000061. El código del SRI queda vacío
        // hasta tener la ficha oficial: no se inventa.
        $retenciones = [
            ['renta', 'Bienes muebles de naturaleza corporal', 2.00, '2026-03-01', 'NAC-DGERCGC26-00000009'],
            ['renta', 'Servicios con predominio de mano de obra', 3.00, '2026-03-01', 'NAC-DGERCGC26-00000009'],
            ['renta', 'Publicidad y comunicación', 3.00, '2026-03-01', 'NAC-DGERCGC26-00000009'],
            ['renta', 'Arrendamiento de inmuebles', 10.00, '2026-03-01', 'NAC-DGERCGC26-00000009'],
            ['renta', 'Honorarios profesionales · persona natural', 10.00, '2026-03-01', 'NAC-DGERCGC26-00000009'],
            ['renta', 'Servicios profesionales prestados por sociedades', 5.00, '2026-03-01', 'NAC-DGERCGC26-00000009'],
            ['iva', 'Bienes', 30.00, '2020-01-01', 'NAC-DGERCGC20-00000061'],
            ['iva', 'Servicios, comisiones y consultoría', 70.00, '2020-01-01', 'NAC-DGERCGC20-00000061'],
            ['iva', 'Honorarios, arriendo a persona natural y liquidaciones', 100.00, '2020-01-01', 'NAC-DGERCGC20-00000061'],
            ['iva', 'Entre agentes de retención · bienes', 10.00, '2020-01-01', 'NAC-DGERCGC20-00000061'],
            ['iva', 'Entre agentes de retención · servicios', 20.00, '2020-01-01', 'NAC-DGERCGC20-00000061'],
        ];
        foreach ($retenciones as [$tipo, $concepto, $pct, $desde, $res]) {
            DB::table('porcentajes_retencion')->insert([
                'empresa_id' => null, 'tipo' => $tipo, 'concepto' => $concepto, 'codigo_sri' => null,
                'porcentaje' => $pct, 'vigente_desde' => $desde, 'vigente_hasta' => null, 'resolucion' => $res,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('retencion_lineas');
        Schema::dropIfExists('retenciones');
        Schema::dropIfExists('porcentajes_retencion');
        Schema::dropIfExists('tarifas_iva');
        Schema::dropIfExists('socios');
        Schema::dropIfExists('administradores');
        Schema::dropIfExists('ejercicios_contables');

        Schema::table('account_plans', fn (Blueprint $t) => $t->dropColumn(['linea_estado', 'estado_financiero']));
        Schema::table('empresas', fn (Blueprint $t) => $t->dropColumn(['tipo_compania', 'agente_retencion', 'marco_contable', 'inicio_ejercicio']));

        $panelId = DB::table('panels')->where('key', 'contabilidad')->value('id');
        if ($panelId) {
            DB::table('plan_panel')->where('panel_id', $panelId)->delete();
            DB::table('panel_modules')->where('panel_id', $panelId)->delete();
            DB::table('panels')->where('id', $panelId)->delete();
        }
    }
};
