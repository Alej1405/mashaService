<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\Scopes\EmpresaScope;
use App\Modules\N8n\RecursoRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CRUD genérico de CMS y Tienda para n8n, dirigido por RecursoRegistry.
 * TODO se ancla a la empresa de la sesión (n8n_empresa) con withoutGlobalScope
 * (la API n8n no tiene tenant Filament ni auth). Un recurso nuevo = una entrada
 * en el registro, no código aquí. El módulo/recurso se leen del route (los
 * defaults no llegan como args por nombre en Laravel).
 */
class N8nRecursoController extends Controller
{
    // ---- Listar / ver -------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        [$col, $dir] = $cfg['orden'] ?? ['id', 'desc'];
        $items = $this->scope($cfg, $this->empresaId($request))
            ->orderBy($col, $dir)->limit(30)->get($cfg['lista']);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function show(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        $item = $this->scope($cfg, $this->empresaId($request))->find($this->id($request));
        if (! $item) {
            return $this->noEncontrado($cfg);
        }

        return response()->json(['ok' => true, 'item' => $this->payload($item, $cfg)]);
    }

    // ---- Crear / editar / borrar -------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        $empresaId = $this->empresaId($request);
        $data = $request->validate($cfg['rules']);

        if (($cfg['singleton'] ?? false) && $this->scope($cfg, $empresaId)->exists()) {
            $item = $this->scope($cfg, $empresaId)->first();

            return response()->json([
                'ok' => false, 'error' => 'ya_existe',
                'mensaje' => $cfg['label'].' ya existe para esta empresa (id '.$item->id.'). Edítalo en vez de crear.',
                'item' => ['id' => $item->id],
            ], 422);
        }

        $data = $this->normalizar($data, $cfg, $empresaId, true);

        /** @var Model $item */
        $item = new $cfg['model'];
        $item->forceFill(['empresa_id' => $empresaId]);

        if (isset($cfg['slug_from'])) {
            $item->slug = $this->slugUnico($cfg, $empresaId, $data[$cfg['slug_from']] ?? 'item');
        }
        $item->fill($data);
        $this->defaultsVisibles($item, $cfg);
        $item->save();

        return response()->json([
            'ok' => true, 'mensaje' => $cfg['label'].' creado.',
            'item' => $this->payload($item, $cfg),
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        $empresaId = $this->empresaId($request);
        $item = $this->scope($cfg, $empresaId)->find($this->id($request));
        if (! $item) {
            return $this->noEncontrado($cfg);
        }

        $data = $request->validate($this->reglasUpdate($cfg['rules']));
        $data = $this->normalizar($data, $cfg, $empresaId, false, $item);
        $item->fill($data);
        $item->save();

        return response()->json([
            'ok' => true, 'mensaje' => $cfg['label'].' actualizado.',
            'item' => $this->payload($item, $cfg),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        $item = $this->scope($cfg, $this->empresaId($request))->find($this->id($request));
        if (! $item) {
            return $this->noEncontrado($cfg);
        }

        foreach (array_keys($cfg['imagenes'] ?? []) as $campo) {
            $this->borrarImagen($item->{$campo} ?? null);
        }
        if (! empty($cfg['galeria'])) {
            foreach (($item->{array_key_first($cfg['galeria'])} ?: []) as $g) {
                $this->borrarImagen($g);
            }
        }
        $item->delete();

        return response()->json(['ok' => true, 'mensaje' => $cfg['label'].' eliminado.']);
    }

    // ---- Imágenes -----------------------------------------------------------

    public function imagen(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        $imagenes = $cfg['imagenes'] ?? [];
        if (empty($imagenes)) {
            return response()->json(['ok' => false, 'error' => 'sin_imagenes', 'mensaje' => 'Este recurso no tiene imágenes.'], 422);
        }

        $data = $request->validate([
            'campo' => ['nullable', 'string'],
            'imagen' => ['required', 'file', 'image', 'max:5120'],
        ]);
        $campo = $data['campo'] ?? array_key_first($imagenes);
        if (! isset($imagenes[$campo])) {
            throw ValidationException::withMessages(['campo' => 'Campo inválido. Opciones: '.implode(', ', array_keys($imagenes))]);
        }

        $item = $this->scope($cfg, $this->empresaId($request))->find($this->id($request));
        if (! $item) {
            return $this->noEncontrado($cfg);
        }

        $this->borrarImagen($item->{$campo} ?? null);
        $item->{$campo} = $request->file('imagen')->store($imagenes[$campo], 'public');
        $item->save();

        return response()->json(['ok' => true, 'mensaje' => 'Imagen actualizada.', 'campo' => $campo, 'url' => $this->url($item->{$campo})]);
    }

    public function galeriaAdd(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        if (empty($cfg['galeria'])) {
            return response()->json(['ok' => false, 'error' => 'sin_galeria'], 422);
        }
        $request->validate(['imagen' => ['required', 'file', 'image', 'max:5120']]);

        $campo = array_key_first($cfg['galeria']);
        $max = $cfg['galeria']['max'];
        $item = $this->scope($cfg, $this->empresaId($request))->find($this->id($request));
        if (! $item) {
            return $this->noEncontrado($cfg);
        }

        $galeria = $item->{$campo} ?: [];
        if (count($galeria) >= $max) {
            return response()->json(['ok' => false, 'error' => 'galeria_llena', 'mensaje' => "Máximo {$max} imágenes."], 422);
        }
        $galeria[] = $request->file('imagen')->store($cfg['galeria'][$campo], 'public');
        $item->{$campo} = $galeria;
        $item->save();

        return response()->json(['ok' => true, 'mensaje' => 'Imagen agregada.', 'total' => count($galeria)]);
    }

    public function galeriaRemove(Request $request): JsonResponse
    {
        $cfg = $this->cfg($request);
        if (empty($cfg['galeria'])) {
            return response()->json(['ok' => false, 'error' => 'sin_galeria'], 422);
        }
        $campo = array_key_first($cfg['galeria']);
        $item = $this->scope($cfg, $this->empresaId($request))->find($this->id($request));
        if (! $item) {
            return $this->noEncontrado($cfg);
        }

        $indice = (int) $request->route('indice');
        $galeria = $item->{$campo} ?: [];
        if (! isset($galeria[$indice])) {
            return response()->json(['ok' => false, 'error' => 'indice_invalido'], 404);
        }
        $this->borrarImagen($galeria[$indice]);
        array_splice($galeria, $indice, 1);
        $item->{$campo} = $galeria;
        $item->save();

        return response()->json(['ok' => true, 'mensaje' => 'Imagen quitada.', 'total' => count($galeria)]);
    }

    // ---- Helpers ------------------------------------------------------------

    private function cfg(Request $request): array
    {
        $cfg = RecursoRegistry::config((string) $request->route('modulo'), (string) $request->route('recurso'));
        abort_unless($cfg, 404, 'Recurso no encontrado.');

        return $cfg;
    }

    private function id(Request $request): int
    {
        return (int) $request->route('id');
    }

    private function empresaId(Request $request): int
    {
        return (int) $request->attributes->get('n8n_empresa')->id;
    }

    private function scope(array $cfg, int $empresaId): Builder
    {
        return $cfg['model']::query()->withoutGlobalScope(EmpresaScope::class)->where('empresa_id', $empresaId);
    }

    private function noEncontrado(array $cfg): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => 'no_encontrado', 'mensaje' => $cfg['label'].' no encontrado en esta empresa.'], 404);
    }

    private function normalizar(array $data, array $cfg, int $empresaId, bool $creando, ?Model $actual = null): array
    {
        foreach ($cfg['upper'] ?? [] as $campo) {
            if (isset($data[$campo])) {
                $data[$campo] = mb_strtoupper(trim($data[$campo]));
            }
        }
        foreach ($cfg['unico'] ?? [] as $campo) {
            if (! isset($data[$campo])) {
                continue;
            }
            $q = $this->scope($cfg, $empresaId)->where($campo, $data[$campo]);
            if ($actual) {
                $q->where('id', '!=', $actual->id);
            }
            if ($q->exists()) {
                throw ValidationException::withMessages([$campo => 'Ya existe un registro con ese '.$campo.' en esta empresa.']);
            }
        }

        return $data;
    }

    private function defaultsVisibles(Model $item, array $cfg): void
    {
        foreach (['activo', 'publicado'] as $campo) {
            if (array_key_exists($campo, $cfg['rules']) && $item->{$campo} === null) {
                $item->{$campo} = true;
            }
        }
    }

    private function reglasUpdate(array $rules): array
    {
        $out = [];
        foreach ($rules as $campo => $regla) {
            $regla = str_replace(['required|', 'required'], '', $regla);
            $out[$campo] = 'sometimes|'.ltrim($regla, '|');
        }

        return $out;
    }

    private function slugUnico(array $cfg, int $empresaId, string $texto): string
    {
        $base = Str::slug($texto) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($this->scope($cfg, $empresaId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function borrarImagen(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function url(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }

    private function payload(Model $item, array $cfg): array
    {
        $out = $item->only(array_merge(['id', 'slug'], array_keys($cfg['rules'])));
        foreach (array_keys($cfg['imagenes'] ?? []) as $campo) {
            $out[$campo.'_url'] = $this->url($item->{$campo} ?? null);
        }
        if (! empty($cfg['galeria'])) {
            $campo = array_key_first($cfg['galeria']);
            $out['galeria_urls'] = collect($item->{$campo} ?: [])->map(fn ($p) => $this->url($p))->all();
        }

        return $out;
    }
}
