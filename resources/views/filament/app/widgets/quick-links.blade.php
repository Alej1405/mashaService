<x-filament-widgets::widget>
    <div class="d-card">
        {{-- Header --}}
        <div class="flex items-center gap-2 mb-4">
            <div class="p-1.5 rounded-lg" style="background:#f0fdf4;">
                <x-heroicon-o-globe-alt class="w-4 h-4" style="color:#16a34a;" />
            </div>
            <span class="text-xs font-bold uppercase tracking-widest" style="color:#64748b;">Accesos Rápidos</span>
        </div>

        @if($websiteUrl)
            {{-- Tarjeta del sitio web --}}
            <a href="{{ $websiteUrl }}" target="_blank" rel="noopener noreferrer"
               class="group block rounded-xl overflow-hidden transition-all duration-200"
               style="border:1px solid #e2e8f0;max-width:280px;"
               onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.10)';this.style.borderColor='#c7d2fe';"
               onmouseout="this.style.boxShadow='none';this.style.borderColor='#e2e8f0';">

                {{-- Miniatura --}}
                <div class="relative overflow-hidden" style="height:150px;background:#f1f5f9;">
                    <img
                        src="{{ $thumbnailUrl }}"
                        alt="Vista previa de {{ $empresa->name }}"
                        class="w-full h-full object-cover object-top transition-transform duration-300 group-hover:scale-105"
                        onerror="this.parentElement.innerHTML='<div style=\'display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:6px;\'><svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke=\'currentColor\' style=\'width:28px;height:28px;color:#94a3b8;\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418\'/></svg><span style=\'font-size:11px;color:#94a3b8;\'>Vista previa no disponible</span></div>';"
                    >
                    {{-- Overlay con icono de enlace --}}
                    <div class="absolute top-2 right-2 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
                         style="background:rgba(255,255,255,0.9);backdrop-filter:blur(4px);">
                        <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" style="color:#4f46e5;" />
                    </div>
                </div>

                {{-- Info del sitio --}}
                <div class="px-3 py-2.5" style="background:#ffffff;">
                    <p class="text-xs font-bold truncate" style="color:#1e293b;">{{ $empresa->name }}</p>
                    <p class="text-[10px] truncate mt-0.5" style="color:#94a3b8;">{{ $websiteUrl }}</p>
                </div>
            </a>

        @else
            {{-- Estado vacío --}}
            <div class="flex flex-col items-center justify-center py-6 text-center rounded-xl"
                 style="background:#f8fafc;border:1px dashed #cbd5e1;">
                <div class="p-3 rounded-full mb-3" style="background:#e0e7ff;">
                    <x-heroicon-o-globe-alt class="w-5 h-5" style="color:#4f46e5;" />
                </div>
                <p class="text-sm font-semibold mb-1" style="color:#1e293b;">Sin sitio web registrado</p>
                <p class="text-xs max-w-xs" style="color:#64748b;">
                    El administrador puede agregar la URL del sitio web desde
                    <strong>Admin → Empresas → Editar</strong>.
                </p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
