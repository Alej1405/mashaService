{{-- Tarjeta de acceso del hub. Recibe: $url, $icono, $titulo, $texto y, opcional, $externo. --}}
@php($externo = $externo ?? false)
<a href="{{ $url }}" @if ($externo) target="_blank" rel="noopener noreferrer" @endif
   style="display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;text-decoration:none;box-shadow:0 1px 2px rgba(16,24,40,.04);transition:box-shadow .15s,border-color .15s"
   onmouseover="this.style.boxShadow='0 6px 20px rgba(16,24,40,.08)';this.style.borderColor='#c7d2fe'"
   onmouseout="this.style.boxShadow='0 1px 2px rgba(16,24,40,.04)';this.style.borderColor='#e5e7eb'">
    <span style="flex:0 0 auto;width:44px;height:44px;border-radius:12px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center">
        @svg($icono, '', ['style' => 'width:22px;height:22px'])
    </span>
    <span style="flex:1;min-width:0">
        <strong style="display:block;font-size:15px;color:#111827">{{ $titulo }}</strong>
        <small style="color:#6b7280">{{ $texto }}</small>
    </span>
    <span style="color:#9ca3af">
        @svg($externo ? 'heroicon-o-arrow-top-right-on-square' : 'heroicon-o-chevron-right', '', ['style' => 'width:18px;height:18px'])
    </span>
</a>
