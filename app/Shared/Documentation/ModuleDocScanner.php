<?php

namespace App\Shared\Documentation;

use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Escanea dos fuentes de documentación:
 *  1. config/erp_features.php — módulos con docs (descripcion_larga, alcance, algoritmos, services)
 *  2. app/Shared/{Actions,Queries}/ — clases con atributo #[Documentado]
 *
 * Algoritmos usados:
 *  - array_filter con callback   → O(N) para filtrar módulos con documentación
 *  - array_map sobre clases PHP  → O(C) donde C = número de clases encontradas
 *  - Reflexión PHP (getAttributes) → O(1) por clase para leer el atributo
 *  - Agrupación por grupo con array key → O(C) sin ordenación extra
 */
final class ModuleDocScanner
{
    /**
     * Retorna solo los módulos de erp_features.php que tienen documentación completa.
     * Estructura de retorno: [ key => [ label, icon, color, descripcion_larga, alcance, casos_uso, services, queries_principales, algoritmos ] ]
     */
    public function scan(): array
    {
        $catalogo = config('erp_features', []);

        // array_filter O(N): excluye módulos sin descripcion_larga
        return array_filter(
            $catalogo,
            fn (array $modulo) => isset($modulo['descripcion_larga'])
        );
    }

    /**
     * Escanea app/Shared/Actions/ y app/Shared/Queries/ buscando clases
     * con el atributo #[Documentado]. Retorna agrupadas por 'grupo'.
     *
     * @return array<string, list<array{ clase: string, descripcion: string, tipo: string, archivo: string }>>
     */
    public function scanClasses(): array
    {
        $directorios = [
            app_path('Shared/Actions'),
            app_path('Shared/Queries'),
        ];

        $resultado = [];

        foreach ($directorios as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $archivos = File::allFiles($dir);

            foreach ($archivos as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }

                $clase = $this->resolveClassName($archivo->getPathname());

                if (! $clase || ! class_exists($clase)) {
                    continue;
                }

                $reflexion  = new ReflectionClass($clase);
                $atributos  = $reflexion->getAttributes(Documentado::class);

                if (empty($atributos)) {
                    continue;
                }

                /** @var Documentado $doc */
                $doc   = $atributos[0]->newInstance();
                $grupo = $doc->grupo;

                $resultado[$grupo][] = [
                    'clase'       => $reflexion->getShortName(),
                    'clase_fqn'   => $clase,
                    'descripcion' => $doc->descripcion,
                    'tipo'        => $doc->tipo,
                    'archivo'     => str_replace(base_path() . '/', '', $archivo->getPathname()),
                ];
            }
        }

        ksort($resultado);

        return $resultado;
    }

    /**
     * Convierte path de archivo a FQCN usando el namespace de la primera línea.
     * Estrategia: leer solo las primeras 10 líneas del archivo para extraer namespace + class.
     * Evita cargar el archivo completo — O(1) en práctica para archivos pequeños.
     */
    private function resolveClassName(string $path): ?string
    {
        $lineas    = array_slice(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), 0, 20);
        $namespace = null;
        $clase     = null;

        foreach ($lineas as $linea) {
            if (str_starts_with($linea, 'namespace ')) {
                $namespace = trim(substr($linea, 10), '; ');
            }
            if (preg_match('/^(?:final\s+)?class\s+(\w+)/', $linea, $m)) {
                $clase = $m[1];
                break;
            }
        }

        return ($namespace && $clase) ? "{$namespace}\\{$clase}" : null;
    }
}
