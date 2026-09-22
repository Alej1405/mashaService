<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lo que pasa cuando alguien apunta la cámara al QR de una gaveta.
 *
 * La URL es corta y pública en forma, pero la ficha exige sesión: el token
 * identifica el producto, no autoriza a verlo. Quien escanee sin haber entrado
 * al ERP va al login y vuelve aquí.
 */
class QrController extends Controller
{
    public function ficha(Request $request, string $token)
    {
        $item = InventoryItem::withoutGlobalScopes()
            ->with(['stocks.ubicacion', 'stocks.almacen'])
            ->where('qr_token', $token)
            ->firstOrFail();

        abort_unless($this->puedeVer($item), 403, 'Este producto es de otra empresa.');

        $movimientos = $item->movimientos()
            ->withoutGlobalScopes()
            ->latest('date')->latest('id')
            ->limit(5)
            ->get();

        return view('inventario.qr-ficha', compact('item', 'movimientos'));
    }

    /**
     * Hoja de etiquetas para imprimir y pegar en las gavetas.
     * Se imprime por almacén: nadie etiqueta la bodega entera de una vez.
     */
    public function etiquetas(Request $request)
    {
        $items = $this->itemsParaEtiquetas($request);

        return view('inventario.etiquetas', compact('items'));
    }

    /** @return \Illuminate\Support\Collection<int, InventoryItem> */
    private function itemsParaEtiquetas(Request $request)
    {
        $usuario = Auth::user();
        abort_unless($usuario, 403);

        // El super admin no tiene empresa propia: sin este respaldo la hoja salía
        // con cero productos. Se toma la del parámetro, la suya, o la primera a
        // la que tenga acceso.
        $empresaId = $request->integer('empresa')
            ?: $usuario->empresa_id
            ?: \Filament\Facades\Filament::getTenant()?->id
            ?: $usuario->empresasAcceso()->value('empresas.id');

        abort_unless($empresaId, 404, 'No hay empresa de la que imprimir etiquetas.');

        return InventoryItem::withoutGlobalScopes()
            ->with(['empresa', 'stocks.ubicacion'])
            ->where('empresa_id', $empresaId)
            ->when($request->filled('tipo'), fn ($q) => $q->where('type', $request->string('tipo')))
            // Una etiqueta suelta: la del ítem que se está mirando en su ficha.
            ->when($request->filled('item'), fn ($q) => $q->whereKey($request->integer('item')))
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Las mismas etiquetas, en PDF de 60 x 20 mm.
     *
     * Una etiqueta por página con el tamaño exacto del rollo: así la impresora
     * térmica no reescala ni recorta, que es lo que pasa al imprimir desde el
     * navegador con márgenes de A4.
     */
    public function etiquetasPdf(Request $request)
    {
        $items = $this->itemsParaEtiquetas($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventario.etiquetas-pdf', compact('items'));

        // Dompdf trabaja en puntos: 1 mm = 2,8346 pt.
        $pdf->setPaper([0, 0, 60 * 2.8346, 20 * 2.8346]);

        $nombre = 'etiquetas-' . now()->format('Y-m-d') . '.pdf';

        return $request->boolean('ver')
            ? $pdf->stream($nombre)
            : $pdf->download($nombre);
    }

    private function puedeVer(InventoryItem $item): bool
    {
        $usuario = Auth::user();

        if (! $usuario) {
            return false;
        }

        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('super_admin')) {
            return true;
        }

        if ((int) $usuario->empresa_id === (int) $item->empresa_id) {
            return true;
        }

        return method_exists($usuario, 'empresasAcceso')
            && $usuario->empresasAcceso()->where('empresas.id', $item->empresa_id)->exists();
    }
}
