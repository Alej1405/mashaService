<?php

namespace App\Services;

use App\Models\ComprobanteSri;
use Illuminate\Support\Facades\DB;

/**
 * Sube al ERP los listados que la empresa baja del portal del SRI.
 *
 * El parseo **no vive aquí**: lo hace el microservicio, porque el mismo lector
 * lo usará la web cuando alguien sin ERP suba su archivo para sacar su
 * formulario. Aquí solo se guarda lo que devuelve y se concilia con lo que el
 * sistema ya tenía registrado.
 *
 * La conciliación importa más de lo que parece: si una compra ya está en el
 * ERP y además se importa del portal, el mes se declararía por el doble.
 */
class ImportadorComprobantesSri
{
    public function __construct(private readonly ServicioSri $servicio)
    {
    }

    /**
     * @return array{ok: bool, tipo?: string, importados?: int, repetidos?: int,
     *               conciliados?: int, periodos?: array, avisos?: array, error?: string}
     */
    public function importar(int $empresaId, string $contenido, ?string $tipo = null): array
    {
        $lectura = $this->servicio->leerComprobantes($contenido, $tipo);

        if (! ($lectura['ok'] ?? false)) {
            return ['ok' => false, 'error' => $lectura['error'] ?? 'No se pudo leer el archivo.'];
        }

        $importados = 0;
        $repetidos = 0;

        DB::transaction(function () use ($empresaId, $lectura, &$importados, &$repetidos) {
            foreach ($lectura['comprobantes'] as $c) {
                $clave = $c['clave_acceso'] ?: null;

                // La clave de acceso es única en todo el país: es lo que impide
                // que el mismo archivo subido dos veces duplique el mes.
                $existe = $clave
                    ? ComprobanteSri::where('empresa_id', $empresaId)->where('clave_acceso', $clave)->exists()
                    : ComprobanteSri::where('empresa_id', $empresaId)
                        ->where('numero', $c['numero'])->where('identificacion', $c['identificacion'])
                        ->whereDate('fecha_emision', $c['fecha_emision'])->exists();

                if ($existe) {
                    $repetidos++;

                    continue;
                }

                ComprobanteSri::create([
                    'empresa_id'       => $empresaId,
                    'clave_acceso'     => $clave,
                    'origen'           => $c['origen'],
                    'tipo_comprobante' => $c['tipo_comprobante'],
                    'identificacion'   => $c['identificacion'],
                    'razon_social'     => $c['razon_social'],
                    'fecha_emision'    => $c['fecha_emision'],
                    'numero'           => $c['numero'],
                    'establecimiento'  => $c['establecimiento'],
                    'punto_emision'    => $c['punto_emision'],
                    'secuencial'       => $c['secuencial'],
                    'base_gravada'     => $c['base_gravada'],
                    'base_cero'        => $c['base_cero'],
                    'iva'              => $c['iva'],
                    'total'            => $c['total'],
                    'desglose'         => $c['desglose'],
                    'importado_en'     => now(),
                ]);

                $importados++;
            }
        });

        return [
            'ok'          => true,
            'tipo'        => $lectura['tipo'],
            'importados'  => $importados,
            'repetidos'   => $repetidos,
            'conciliados' => $this->conciliar($empresaId),
            'periodos'    => $lectura['periodos'] ?? [],
            'avisos'      => $lectura['avisos'] ?? [],
        ];
    }

    /**
     * Marca los comprobantes que ya están registrados en el ERP.
     *
     * Casan por número y contraparte: si la compra 001-002-000000123 del
     * proveedor 1790012345001 ya existe, el comprobante importado no vuelve a
     * sumar. Lo que queda sin conciliar es justo lo que falta en el sistema.
     */
    public function conciliar(int $empresaId): int
    {
        $conciliados = 0;

        $pendientes = ComprobanteSri::where('empresa_id', $empresaId)->sinConciliar()->get();

        foreach ($pendientes as $c) {
            $casa = $c->origen === 'recibido'
                ? $this->buscarCompra($empresaId, $c)
                : $this->buscarVenta($empresaId, $c);

            if ($casa) {
                $c->update(['conciliado_con' => $casa[0], 'conciliado_id' => $casa[1]]);
                $conciliados++;
            }
        }

        return $conciliados;
    }

    /** @return array{0: string, 1: int}|null */
    private function buscarCompra(int $empresaId, ComprobanteSri $c): ?array
    {
        $numero = $c->numero;

        $compra = DB::table('purchases as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.empresa_id', $empresaId)
            ->where(fn ($q) => $q->where('p.numero_factura', $numero)->orWhere('p.number', $numero))
            ->value('p.id');

        if ($compra) {
            return ['purchase', (int) $compra];
        }

        $gasto = DB::table('gastos')->where('empresa_id', $empresaId)
            ->where('numero_documento', $numero)->value('id');

        return $gasto ? ['gasto', (int) $gasto] : null;
    }

    /** @return array{0: string, 1: int}|null */
    private function buscarVenta(int $empresaId, ComprobanteSri $c): ?array
    {
        $venta = DB::table('sales')->where('empresa_id', $empresaId)
            ->where(fn ($q) => $q->where('referencia', $c->numero)->orWhere('clave_acceso', $c->clave_acceso))
            ->value('id');

        return $venta ? ['sale', (int) $venta] : null;
    }

    /**
     * Los totales del período sumando las dos fuentes sin contar doble: lo
     * registrado en el ERP, más lo importado que no casó con nada.
     *
     * @return array<string, float>
     */
    public function totalesDelPeriodo(int $empresaId, int $anio, int $mes): array
    {
        $filas = ComprobanteSri::where('empresa_id', $empresaId)
            ->delPeriodo($anio, $mes)->sinConciliar()->get();

        $sumar = fn ($origen, $campo) => (float) $filas->where('origen', $origen)->sum($campo);

        return [
            'compras_gravadas' => $sumar('recibido', 'base_gravada'),
            'compras_iva'      => $sumar('recibido', 'iva'),
            'compras_cero'     => $sumar('recibido', 'base_cero'),
            'compras_numero'   => $filas->where('origen', 'recibido')->count(),
            'ventas_gravadas'  => $sumar('emitido', 'base_gravada'),
            'ventas_iva'       => $sumar('emitido', 'iva'),
            'ventas_cero'      => $sumar('emitido', 'base_cero'),
            'ventas_numero'    => $filas->where('origen', 'emitido')->count(),
            'estimados'        => $filas->where('desglose', 'estimado')->count(),
        ];
    }
}
