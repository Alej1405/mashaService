{{-- Modo «Cuadre de gestión anterior» encendido: visible en todo Contabilidad mientras dure. --}}
<div role="status" style="display:flex; flex-wrap:wrap; align-items:center; gap:8px 14px; margin-bottom:12px;
     padding:10px 16px; border-radius:12px; border:1px dashed #d97706; background:#fffbeb; color:#92400e; font-size:13px">
    <strong>Modo cuadre de gestión anterior encendido</strong>
    <span>
        Los meses anteriores a {{ $empresa->inicio_gestion_erp?->translatedFormat('F \d\e Y') ?? 'el inicio de gestión' }}
        admiten cambios aunque estén cerrados ·
        lo encendió {{ \App\Models\User::find($empresa->modo_cuadre_por)?->name ?? '—' }}
        el {{ $empresa->modo_cuadre_en->format('d/m/Y H:i') }}
    </span>
</div>
