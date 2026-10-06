<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige las horas guardadas mientras la app estaba en UTC.
 *
 * Hasta el 2026-10-05 la app corría en UTC y Postgres en America/Guayaquil:
 * Laravel escribía la hora UTC sin offset y quedaba 5 h adelantada (un login de
 * las 12:26 figuraba 17:26). Desde este despliegue la app y la sesión de Postgres
 * están fijas en America/Guayaquil; esta migración resta las 5 h a lo anterior.
 *
 * Corre una sola vez, dentro del despliegue (con el ERP en mantenimiento), así no
 * se mezclan horas viejas con nuevas. Solo toca horas que pone el sistema (now()):
 * - created_at, updated_at y deleted_at de todas las tablas;
 * - las columnas propias de la lista de abajo.
 * No toca lo que escribe el usuario en un formulario (cms_posts.publicado_en) ni
 * las fechas puras guardadas a medianoche (declaraciones.generado_en leída del PDF).
 * Respaldo previo: ~/respaldos/erp_masha_antes_zona_horaria_20261005_1901.sql.gz (VPS).
 */
return new class extends Migration
{
    private const DESFASE = "interval '5 hours'";

    /** Columnas propias que el código llena con now(). */
    private const COLUMNAS_DEL_SISTEMA = [
        'cash_sessions'              => ['apertura_at'],
        'comprobantes_sri'           => ['importado_en'],
        'empresa_mailing_stats'      => ['last_synced_at'],
        'failed_jobs'                => ['failed_at'],
        'journal_entries'            => ['confirmado_at'],
        'logistics_billing_requests' => ['accepted_at', 'verificado_at'],
        'mail_campaigns'             => ['sent_at'],
        'mailing_send_log'           => ['sent_at'],
        'periodos_contables'         => ['cerrado_en'],
        'personal_access_tokens'     => ['last_used_at'],
        'sales'                      => ['confirmado_at'],
        'support_chats'              => ['user_last_read_at', 'admin_last_read_at'],
        'telegram_sessions'          => ['expires_at', 'last_used_at'],
        'users'                      => ['last_login_at'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columnasPorTabla() as $tabla => $columnas) {
            $sets = implode(', ', array_map(
                fn (string $c) => sprintf('"%1$s" = "%1$s" - %2$s', $c, self::DESFASE),
                $columnas,
            ));
            DB::statement(sprintf('UPDATE "%s" SET %s', $tabla, $sets));
        }

        // generado_en: now() al generar la declaración, o la fecha de recaudación del
        // PDF a medianoche. Solo se corrige la primera.
        DB::statement(sprintf(
            "UPDATE declaraciones SET generado_en = generado_en - %s WHERE generado_en::time <> time '00:00'",
            self::DESFASE,
        ));
    }

    public function down(): void
    {
        // Solo avanzamos: el respaldo previo está en el VPS.
    }

    /** @return array<string,array<int,string>> tabla => columnas de fecha y hora que existen */
    private function columnasPorTabla(): array
    {
        $existentes = DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('current_schema()'))
            ->where('data_type', 'like', 'timestamp%')
            ->get(['table_name', 'column_name']);

        $porTabla = [];
        foreach ($existentes as $col) {
            $estandar = in_array($col->column_name, ['created_at', 'updated_at', 'deleted_at'], true);
            $propia = in_array($col->column_name, self::COLUMNAS_DEL_SISTEMA[$col->table_name] ?? [], true);

            if ($estandar || $propia) {
                $porTabla[$col->table_name][] = $col->column_name;
            }
        }

        return $porTabla;
    }
};
