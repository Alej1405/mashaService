<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\ProductoProveedor;
use App\Models\TipoGasto;
use Illuminate\Support\Str;

/**
 * Propone qué es una línea de factura: un ítem del inventario, un insumo o materia
 * prima nuevos, o un tipo de gasto. Quien registra no tiene por qué saber dónde va
 * "servicios técnicos y logísticos para el control de plagas": lo sabe el ERP.
 *
 * Sin IA, en este orden:
 *   1. Lo que ya se decidió antes para un producto de texto parecido (de cualquier proveedor).
 *   2. ¿Bien o servicio? Por las palabras y las unidades (ml, mm, caja… frente a servicio, honorarios…).
 *   3. Bien: el ítem del inventario más parecido; si ninguno se parece, crearlo (insumo o materia prima).
 *   4. Servicio: el tipo de gasto con más coincidencias del diccionario (config/clasificacion.php),
 *      con las frases de varias palabras pesando más. Profesionales: persona natural o sociedad por el RUC.
 *
 * Devuelve siempre el motivo, para que quien confirma vea por qué.
 */
class ClasificadorProductos
{
    /** Parecido mínimo (0-100) para proponer un ítem que ya existe. */
    private const PARECIDO_ITEM = 62;

    /** Parecido mínimo con un producto ya clasificado para repetir su decisión. */
    private const PARECIDO_HISTORIA = 70;

    /**
     * @return array{valor: string, etiqueta: string, motivo: string, confianza: int, servicio: bool}
     *   valor: 'item:12' | 'gasto:5' | 'nuevo:insumo' | 'nuevo:materia_prima'
     */
    public function proponer(int $empresaId, string $descripcion, ?string $rucProveedor = null): array
    {
        $texto = self::normalizar($descripcion);

        if ($historia = $this->porHistoria($empresaId, $texto)) {
            return $historia;
        }

        $bien = $this->puntos($texto, config('clasificacion.bien'));
        $servicio = $this->puntos($texto, config('clasificacion.servicio')) > $bien;
        $gasto = $this->tipoDeGasto($texto, $rucProveedor);

        // Sin ninguna señal de bien físico (ni unidad ni envase), lo que el diccionario
        // de gastos reconoce es gasto: "EXTRA" en una gasolinera es combustible.
        if ($gasto && ($servicio || $bien === 0)) {
            return $gasto;
        }

        if (! $servicio) {
            if ($item = $this->itemParecido($empresaId, $texto)) {
                return $item;
            }
            // Un bien que el inventario no tiene: se crea con el nombre de la factura.
            $tipo = $this->puntos($texto, config('clasificacion.materia_prima')) > 0 ? 'materia_prima' : 'insumo';

            return ['valor' => "nuevo:{$tipo}", 'servicio' => false, 'confianza' => 60,
                    'etiqueta' => $tipo === 'insumo' ? 'Crear insumo nuevo' : 'Crear materia prima nueva',
                    'motivo' => 'es un bien y no hay un ítem parecido en el inventario'];
        }

        return ['valor' => '', 'servicio' => true, 'confianza' => 0, 'etiqueta' => '',
                'motivo' => 'es un servicio, pero no reconozco de qué tipo'];
    }

    /** Texto comparable: minúsculas, sin tildes, sin signos, espacios simples. */
    public static function normalizar(string $texto): string
    {
        $texto = Str::lower(Str::ascii($texto));
        $texto = preg_replace('/[^a-z0-9 ]+/', ' ', $texto);

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    /** Coincidencias de un diccionario: una frase de n palabras suma n. */
    private function puntos(string $texto, array $palabras): int
    {
        $puntos = 0;
        foreach ($palabras as $p) {
            if (preg_match('/\b' . preg_quote($p, '/') . '\b/', $texto)) {
                $puntos += substr_count($p, ' ') + 1;
            }
        }

        return $puntos;
    }

    /** @return array<string, int> palabras con su peso, para decir el motivo */
    private function coincidencias(string $texto, array $palabras): array
    {
        $hallado = [];
        foreach ($palabras as $p) {
            if (preg_match('/\b' . preg_quote($p, '/') . '\b/', $texto)) {
                $hallado[$p] = substr_count($p, ' ') + 1;
            }
        }
        arsort($hallado);

        return $hallado;
    }

    private function tipoDeGasto(string $texto, ?string $ruc): ?array
    {
        $mejor = null;

        foreach (config('clasificacion.gastos') as $codigo => $palabras) {
            $hallado = $this->coincidencias($texto, $palabras);
            $puntos = array_sum($hallado);

            if ($puntos === 0) {
                continue;
            }

            // Profesionales: la retención cambia si quien factura es persona natural o sociedad.
            if ($ruc && in_array($codigo, ['honorarios-profesionales', 'servicios-profesionales-de-sociedades'], true)) {
                $esSociedad = strlen($ruc) === 13 && in_array($ruc[2] ?? '', ['6', '9'], true);
                $puntos += ($codigo === 'servicios-profesionales-de-sociedades') === $esSociedad ? 2 : -2;
            }

            if (! $mejor || $puntos > $mejor['puntos']) {
                $mejor = ['codigo' => $codigo, 'puntos' => $puntos, 'palabra' => array_key_first($hallado)];
            }
        }

        $tipo = $mejor ? TipoGasto::where('codigo', $mejor['codigo'])->where('activo', true)->first() : null;

        return $tipo ? [
            'valor' => "gasto:{$tipo->id}", 'servicio' => true, 'etiqueta' => $tipo->nombre,
            'confianza' => min(95, 50 + $mejor['puntos'] * 12),
            'motivo' => "dice «{$mejor['palabra']}»",
        ] : null;
    }

    private function itemParecido(int $empresaId, string $texto): ?array
    {
        $mejor = null;

        foreach (InventoryItem::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('activo', true)
                     ->get(['id', 'nombre', 'type']) as $item) {
            $parecido = $this->parecido($texto, self::normalizar($item->nombre));
            if (! $mejor || $parecido > $mejor[1]) {
                $mejor = [$item, $parecido];
            }
        }

        if (! $mejor || $mejor[1] < self::PARECIDO_ITEM) {
            return null;
        }

        return ['valor' => "item:{$mejor[0]->id}", 'servicio' => false, 'confianza' => (int) $mejor[1],
                'etiqueta' => $mejor[0]->nombre, 'motivo' => "se parece a «{$mejor[0]->nombre}» del inventario"];
    }

    /** Lo que ya se decidió para un producto de texto parecido, de cualquier proveedor. */
    private function porHistoria(int $empresaId, string $texto): ?array
    {
        $mejor = null;

        foreach (ProductoProveedor::withoutGlobalScopes()->where('empresa_id', $empresaId)
                     ->where(fn ($q) => $q->whereNotNull('inventory_item_id')->orWhereNotNull('tipo_gasto_id'))
                     ->latest('configurado_en')->limit(500)->get() as $p) {
            $parecido = $this->parecido($texto, self::normalizar((string) $p->descripcion));
            if (! $mejor || $parecido > $mejor[1]) {
                $mejor = [$p, $parecido];
            }
        }

        if (! $mejor || $mejor[1] < self::PARECIDO_HISTORIA) {
            return null;
        }

        $p = $mejor[0];
        $valor = $p->inventory_item_id ? "item:{$p->inventory_item_id}" : "gasto:{$p->tipo_gasto_id}";
        $etiqueta = $p->inventory_item_id
            ? (InventoryItem::withoutGlobalScopes()->whereKey($p->inventory_item_id)->value('nombre') ?? 'ítem')
            : (TipoGasto::whereKey($p->tipo_gasto_id)->value('nombre') ?? 'gasto');

        return ['valor' => $valor, 'servicio' => ! $p->inventory_item_id, 'confianza' => (int) $mejor[1],
                'etiqueta' => $etiqueta, 'motivo' => 'así se clasificó antes «' . Str::limit((string) $p->descripcion, 40) . '»'];
    }

    /**
     * Parecido 0-100 entre dos textos: lo mejor entre las palabras compartidas
     * (las medidas como "28 mm" cuentan) y el parecido letra a letra.
     */
    private function parecido(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0;
        }

        $pa = array_unique(explode(' ', $a));
        $pb = array_unique(explode(' ', $b));
        $comunes = count(array_intersect($pa, $pb));
        $palabras = 100 * $comunes / max(count(array_unique(array_merge($pa, $pb))), 1);

        similar_text($a, $b, $letras);

        return max($palabras, $letras * 0.9);
    }
}
