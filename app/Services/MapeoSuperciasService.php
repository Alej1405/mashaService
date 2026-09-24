<?php

namespace App\Services;

use App\Models\AccountPlan;
use App\Models\CatalogoSupercias;
use Illuminate\Support\Collection;

/**
 * Cada cuenta del plan sabe a qué línea del catálogo de la Superintendencia
 * suma. **Todas**, sin excepción.
 *
 * Un balance al que le faltan cuentas no se puede presentar, y pedirle al
 * contador que empareje noventa cuentas a mano es pedirle que no lo haga. Así
 * que el sistema asigna siempre, y él corrige lo que no le cuadre: revisar
 * doce líneas dudosas es trabajo de contador; teclear noventa, no.
 *
 * La asignación baja por tres escalones y el último nunca falla:
 *
 *   1. El nombre de la cuenta contra las líneas hoja del catálogo.
 *   2. El nombre del grupo al que pertenece (su cuenta padre en el plan).
 *   3. La estructura del código: 1.1 es activo corriente, 6 son gastos.
 *
 * Cada asignación guarda con cuánta confianza se hizo, para que la pantalla
 * ponga primero lo que hay que mirar.
 *
 * Marco: skill `supercias-ec`.
 */
class MapeoSuperciasService
{
    /** Por encima de esto la coincidencia de nombre es fiable. */
    public const CONFIANZA_ALTA = 75;

    /** Por debajo de esto conviene que alguien lo mire. */
    public const CONFIANZA_DUDOSA = 60;

    /**
     * Por debajo de esto el nombre no dice nada y se baja al grupo.
     *
     * Entre este número y el anterior la línea se asigna igual, porque apuntar
     * a la línea probable —aunque haya que revisarla— sirve más que caer en el
     * título genérico del grupo.
     */
    private const CONFIANZA_MINIMA = 45;

    /**
     * Techo de lo que se asigna por grupo o por estructura.
     *
     * Aciertan el cajón, no la línea, así que nunca deben pasar por buenas:
     * se quedan siempre por debajo del umbral de revisión.
     */
    private const TECHO_GENERICO = 50;

    /**
     * El último escalón: a dónde cae una cuenta por su código cuando ni su
     * nombre ni el de su grupo se parecen a nada.
     *
     * Son los prefijos del plan de cuentas del ERP, que sigue la raíz 1–6 que
     * espera la Superintendencia.
     */
    private const POR_ESTRUCTURA = [
        '1.1' => '101',   // activo corriente
        '1.2' => '102',   // activo no corriente
        '2.1' => '201',   // pasivo corriente
        '2.2' => '202',   // pasivo no corriente
        '3'   => '30',    // patrimonio neto
        '4'   => '401',   // ingresos de actividades ordinarias
        '5'   => '501',   // costo de ventas y producción
        '6'   => '502',   // gastos
    ];

    /**
     * Palabras de la cuenta → palabras de la línea del catálogo.
     *
     * El plan de cuentas habla como el contador y el catálogo como el
     * organismo: "Bancos" allá es "INSTITUCIONES FINANCIERAS".
     */
    private const SINONIMOS = [
        'banco'        => 'instituciones financieras privadas',
        'bancos'       => 'instituciones financieras privadas',
        'ahorro'       => 'instituciones financieras privadas',
        'ahorros'      => 'instituciones financieras privadas',
        'corriente'    => 'instituciones financieras privadas',
        'cliente'      => 'clientes cuentas por cobrar',
        'clientes'     => 'clientes cuentas por cobrar',
        'incobrable'   => 'provision cuentas incobrables deterioro',
        'incobrables'  => 'provision cuentas incobrables deterioro',
        'proveedor'    => 'proveedores cuentas por pagar',
        'proveedores'  => 'proveedores cuentas por pagar',
        'iess'         => 'obligaciones con el iess',
        'sueldo'       => 'sueldos salarios y demas remuneraciones',
        'sueldos'      => 'sueldos salarios y demas remuneraciones',
        'terminado'    => 'prod term mercaderia almacen',
        'terminados'   => 'prod term mercaderia almacen',
        'depreciacion' => 'depreciacion acumulada propiedades planta y equipo',
        'maquinaria'   => 'maquinaria y equipo',
        'vehiculo'     => 'vehiculos equipo de transporte',
        'vehiculos'    => 'vehiculos equipo de transporte',
        'muebles'      => 'muebles y enseres',
        'capital'      => 'capital suscrito o asignado',
        'utilidad'     => 'resultados acumulados',
        'perdida'      => 'resultados acumulados',
        'iva'          => 'credito tributario impuesto al valor agregado',
        'renta'        => 'credito tributario impuesto a la renta',
        // El catálogo nombra los gastos con su propio vocabulario.
        'publicidad'   => 'promocion publicidad',
        'propaganda'   => 'promocion publicidad',
        'honorario'    => 'honorarios comisiones dietas personas naturales',
        'honorarios'   => 'honorarios comisiones dietas personas naturales',
        'patronal'     => 'aportes seguridad social fondo reserva',
        'arriendo'     => 'arrendamiento',
        'basico'       => 'agua energia luz telecomunicaciones',
        'basicos'      => 'agua energia luz telecomunicaciones',
        'luz'          => 'agua energia luz telecomunicaciones',
        'telefono'     => 'agua energia luz telecomunicaciones',
    ];

    /** La raíz del plan de cuentas y la del catálogo tienen que coincidir. */
    private const RAIZ = [
        '1' => ['1'],
        '2' => ['2'],
        '3' => ['3'],
        '4' => ['4'],
        '5' => ['5', '4'],   // costos: el catálogo los mete bajo resultados
        '6' => ['5'],        // gastos
    ];

    private ?Collection $catalogo = null;

    /**
     * Asigna la línea del catálogo a todas las cuentas que no la tengan.
     *
     * Es lo que corre solo: al crear la empresa, al cargar un plan de cuentas
     * y cuando se añade una cuenta nueva. No pisa lo que alguien ya revisó.
     *
     * @return array{asignadas: int, dudosas: int, sin_mapear: int}
     */
    public function mapearTodo(int $empresaId, bool $incluirRevisadas = false): array
    {
        $pendientes = AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->when(! $incluirRevisadas, fn ($q) => $q->whereNull('codigo_supercias'))
            ->when($incluirRevisadas, fn ($q) => $q->where('codigo_supercias_revisado', false))
            ->get();

        $asignadas = 0;
        $dudosas = 0;

        foreach ($pendientes as $cuenta) {
            $propuesta = $this->proponer($cuenta);

            if (! $propuesta['codigo']) {
                continue;
            }

            $cuenta->forceFill([
                'codigo_supercias'            => $propuesta['codigo'],
                'codigo_supercias_confianza'  => $propuesta['confianza'],
                'codigo_supercias_revisado'   => false,
            ])->save();

            $asignadas++;

            if ($propuesta['confianza'] < self::CONFIANZA_DUDOSA) {
                $dudosas++;
            }
        }

        return [
            'asignadas'  => $asignadas,
            'dudosas'    => $dudosas,
            'sin_mapear' => AccountPlan::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->where('accepts_movements', true)
                ->whereNull('codigo_supercias')->count(),
        ];
    }

    /**
     * La propuesta para una cuenta, bajando por los tres escalones.
     *
     * @return array{codigo: string|null, linea: string|null, confianza: int, via: string}
     */
    public function proponer(AccountPlan $cuenta): array
    {
        $catalogo = $this->catalogo();
        $raices = self::RAIZ[substr((string) $cuenta->code, 0, 1)] ?? [];
        $candidatas = $catalogo->filter(
            fn ($l) => $raices === [] || in_array(substr($l['codigo'], 0, 1), $raices, true),
        );

        // 1 · el nombre de la cuenta
        $mejor = $this->mejorCoincidencia($cuenta->name, $candidatas);

        if ($mejor && $mejor['confianza'] >= self::CONFIANZA_MINIMA) {
            return ['codigo' => $mejor['codigo'], 'linea' => $mejor['nombre'],
                    'confianza' => $mejor['confianza'], 'via' => 'nombre'];
        }

        // 2 · el nombre del grupo al que pertenece
        $padre = $this->nombreDelGrupo($cuenta);

        if ($padre) {
            $porGrupo = $this->mejorCoincidencia($padre, $candidatas->where('nivel', '<=', 7));

            if ($porGrupo && $porGrupo['confianza'] >= self::CONFIANZA_DUDOSA) {
                // Acierta el cajón, no la línea: nunca pasa por buena.
                return ['codigo' => $porGrupo['codigo'], 'linea' => $porGrupo['nombre'],
                        'confianza' => min((int) round($porGrupo['confianza'] * 0.8), self::TECHO_GENERICO),
                        'via' => 'grupo'];
            }
        }

        // 3 · la estructura del código, que nunca falla
        $estructural = $this->porEstructura($cuenta);

        if ($estructural) {
            return $estructural + ['via' => 'estructura'];
        }

        // Queda la mejor coincidencia aunque sea floja: mejor eso que nada,
        // porque una cuenta sin línea desaparece del balance sin avisar.
        return $mejor
            ? ['codigo' => $mejor['codigo'], 'linea' => $mejor['nombre'],
               'confianza' => max($mejor['confianza'], 20), 'via' => 'aproximada']
            : ['codigo' => null, 'linea' => null, 'confianza' => 0, 'via' => 'ninguna'];
    }

    /**
     * Las cuentas ordenadas por lo que hay que mirar primero.
     *
     * @return Collection<int, array{cuenta: AccountPlan, codigo: string|null, linea: string|null,
     *                               confianza: int, via: string, alternativas: array}>
     */
    public function paraRevisar(int $empresaId, bool $soloDudosas = true): Collection
    {
        $catalogo = $this->catalogo();

        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->when($soloDudosas, fn ($q) => $q->where('codigo_supercias_revisado', false)
                ->where(fn ($s) => $s->whereNull('codigo_supercias')
                    ->orWhere('codigo_supercias_confianza', '<', self::CONFIANZA_ALTA)))
            ->orderBy('codigo_supercias_confianza')
            ->orderBy('code')
            ->get()
            ->map(function (AccountPlan $cuenta) use ($catalogo) {
                $raices = self::RAIZ[substr((string) $cuenta->code, 0, 1)] ?? [];
                $linea = $catalogo->firstWhere('codigo', $cuenta->codigo_supercias);

                return [
                    'cuenta'       => $cuenta,
                    'codigo'       => $cuenta->codigo_supercias,
                    'linea'        => $linea['nombre'] ?? null,
                    'confianza'    => (int) ($cuenta->codigo_supercias_confianza ?? 0),
                    'via'          => $cuenta->codigo_supercias ? 'guardada' : 'ninguna',
                    'alternativas' => $catalogo
                        ->filter(fn ($l) => $raices === [] || in_array(substr($l['codigo'], 0, 1), $raices, true))
                        ->map(fn ($l) => $l + [
                            'confianza' => $this->parecido($this->conSinonimos($cuenta->name), $l['normal']),
                        ])
                        ->sortByDesc('confianza')->take(8)
                        ->map(fn ($l) => ['codigo' => $l['codigo'], 'nombre' => $l['nombre'],
                                          'confianza' => $l['confianza']])
                        ->values()->all(),
                ];
            });
    }

    /**
     * Guarda lo que el contador decidió. Lo que él toca queda marcado como
     * revisado y el automático no lo vuelve a tocar.
     *
     * @param  array<int, string>  $mapa  cuenta_id => código del catálogo
     */
    public function aplicar(int $empresaId, array $mapa, bool $revisado = true): int
    {
        $validos = CatalogoSupercias::whereIn('codigo', array_filter($mapa))->pluck('codigo')->all();
        $aplicados = 0;

        foreach ($mapa as $cuentaId => $codigo) {
            if (! $codigo || ! in_array($codigo, $validos, true)) {
                continue;
            }

            $aplicados += AccountPlan::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)->whereKey($cuentaId)
                ->update([
                    'codigo_supercias'           => $codigo,
                    'codigo_supercias_confianza' => $revisado ? 100 : null,
                    'codigo_supercias_revisado'  => $revisado,
                ]);
        }

        return $aplicados;
    }

    /** Cuántas cuentas conviene mirar, para el aviso del panel. */
    public function porRevisar(int $empresaId): int
    {
        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('accepts_movements', true)
            ->where('codigo_supercias_revisado', false)
            ->where(fn ($q) => $q->whereNull('codigo_supercias')
                ->orWhere('codigo_supercias_confianza', '<', self::CONFIANZA_ALTA))
            ->count();
    }

    // ─── lo de dentro ────────────────────────────────────────────────────────

    /** @param Collection<int, array> $candidatas */
    private function mejorCoincidencia(?string $texto, Collection $candidatas): ?array
    {
        if (! $texto || $candidatas->isEmpty()) {
            return null;
        }

        // Cuando hay un sinónimo aplicable manda el nombre reforzado, no el
        // mejor de los dos: el sinónimo está ahí porque alguien sabe que el
        // catálogo llama de otra forma a esa cuenta, y quedarse con el máximo
        // deja que una línea equivocada gane con el nombre pelado mientras la
        // correcta pierde con el reforzado.
        $limpio = $this->normalizar($texto);
        $reforzado = $this->conSinonimos($texto);
        $buscado = $reforzado !== $limpio ? $reforzado : $limpio;

        return $candidatas
            ->map(fn ($l) => $l + ['confianza' => $this->parecido($buscado, $l['normal'])])
            ->sortByDesc('confianza')
            ->first();
    }

    /**
     * Cuánto se parecen dos nombres, de 0 a 100.
     *
     * `similar_text` por sí solo compara letras y se deja engañar: "productos
     * terminados" y "productos en proceso" comparten casi todas y daba un 81 %,
     * suficiente para asignar mal y ni siquiera pedir revisión. Por eso pesa
     * más cuántas **palabras** coinciden, que es lo que de verdad distingue una
     * línea del catálogo de su vecina.
     */
    private function parecido(string $a, string $b): int
    {
        similar_text($a, $b, $porLetras);

        $palabrasA = $this->raices($a);
        $palabrasB = $this->raices($b);

        if ($palabrasA === [] || $palabrasB === []) {
            return (int) round($porLetras);
        }

        // Cuántas palabras de la línea del catálogo aparecen en la cuenta, y al
        // revés: así una palabra que sobra o falta pesa de verdad.
        $comunes = 0;

        foreach ($palabrasA as $palabra) {
            foreach ($palabrasB as $otra) {
                // Por prefijo, no por igualdad: el catálogo abrevia con puntos
                // —"PROD. TERM. Y MERCAD."— y esas formas cortadas tienen que
                // reconocer a la palabra entera.
                if ($palabra === $otra
                    || (mb_strlen($palabra) >= 4 && str_starts_with($otra, $palabra))
                    || (mb_strlen($otra) >= 4 && str_starts_with($palabra, $otra))) {
                    $comunes++;

                    break;
                }
            }
        }

        $porPalabras = $comunes / max(count($palabrasA), count($palabrasB)) * 100;

        $mezcla = $porLetras * 0.35 + $porPalabras * 0.65;

        // Que el nombre completo de la cuenta esté dentro de la línea sigue
        // siendo la señal más fuerte que hay.
        if (str_contains($b, $a) || str_contains($a, $b)) {
            $mezcla = max($mezcla, 88);
        }

        return (int) round($mezcla);
    }

    /**
     * Las palabras de un texto sin su plural.
     *
     * El plan de cuentas dice "materias primas" y el catálogo "materia prima":
     * son la misma cuenta y sin esto no se reconocen. No es un lematizador, y
     * no hace falta que lo sea: basta con que las dos caras lleguen a la misma
     * forma.
     *
     * @return array<int, string>
     */
    private function raices(string $texto): array
    {
        $palabras = array_filter(explode(' ', $texto), fn ($p) => mb_strlen($p) > 2);

        return array_values(array_unique(array_map(function (string $palabra) {
            if (mb_strlen($palabra) > 4 && str_ends_with($palabra, 'es')) {
                return mb_substr($palabra, 0, -2);
            }

            if (mb_strlen($palabra) > 3 && str_ends_with($palabra, 's')) {
                return mb_substr($palabra, 0, -1);
            }

            return $palabra;
        }, $palabras)));
    }

    /** El nombre de la cuenta padre: "1.1.03.02" pertenece a "1.1.03 Inventarios". */
    private function nombreDelGrupo(AccountPlan $cuenta): ?string
    {
        $partes = explode('.', (string) $cuenta->code);

        if (count($partes) < 2) {
            return null;
        }

        array_pop($partes);

        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $cuenta->empresa_id)
            ->where('code', implode('.', $partes))
            ->value('name');
    }

    /**
     * El escalón que nunca falla: por el código, a la línea del grupo.
     *
     * @return array{codigo: string, linea: string|null, confianza: int}|null
     */
    private function porEstructura(AccountPlan $cuenta): ?array
    {
        $code = (string) $cuenta->code;

        foreach (self::POR_ESTRUCTURA as $prefijo => $codigoCatalogo) {
            if (! str_starts_with($code, $prefijo)) {
                continue;
            }

            $linea = $this->catalogo()->firstWhere('codigo', $codigoCatalogo);

            if ($linea) {
                // Confianza baja a propósito: acierta el grupo, no la línea.
                return ['codigo' => $linea['codigo'], 'linea' => $linea['nombre'], 'confianza' => 35];
            }
        }

        return null;
    }

    /** @return Collection<int, array{codigo: string, nombre: string, normal: string, nivel: int}> */
    private function catalogo(): Collection
    {
        return $this->catalogo ??= CatalogoSupercias::query()
            ->whereIn('estado', ['situacion_financiera', 'resultado_integral'])
            ->whereNotNull('nombre')
            ->get(['codigo', 'nombre', 'nivel'])
            ->map(fn ($l) => [
                'codigo' => $l->codigo,
                'nombre' => $l->nombre,
                'nivel'  => (int) $l->nivel,
                'normal' => $this->normalizar((string) $l->nombre),
            ]);
    }

    private function conSinonimos(?string $nombre): string
    {
        $normal = $this->normalizar($nombre);

        foreach (self::SINONIMOS as $palabra => $equivalente) {
            if (preg_match('/\b' . preg_quote($palabra, '/') . '\b/', $normal)) {
                $normal .= ' ' . $equivalente;
            }
        }

        return $normal;
    }

    /** Sin acentos, sin paréntesis, sin el guion de las cuentas correctoras. */
    private function normalizar(?string $texto): string
    {
        $limpio = mb_strtolower(strtr((string) $texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
        ]));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', $limpio)));
    }
}
