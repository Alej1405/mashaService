@php
// Inline styles para colores dinámicos — Tailwind purging no escanea variables Blade.
// Mapa estático: modificar aquí si se agregan módulos con colores nuevos.
$palette = [
    'violet'  => ['bg' => '#f5f3ff', 'color' => '#7c3aed'],
    'emerald' => ['bg' => '#ecfdf5', 'color' => '#059669'],
    'amber'   => ['bg' => '#fffbeb', 'color' => '#d97706'],
    'blue'    => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'green'   => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
    'orange'  => ['bg' => '#fff7ed', 'color' => '#ea580c'],
    'pink'    => ['bg' => '#fdf2f8', 'color' => '#db2777'],
    'cyan'    => ['bg' => '#ecfeff', 'color' => '#0891b2'],
    'slate'   => ['bg' => '#f1f5f9', 'color' => '#64748b'],
];
$c = $palette[$color] ?? ['bg' => '#eef2ff', 'color' => '#6366f1'];
@endphp

<div class="flex items-center gap-3">
    <div class="w-9 h-9 flex-shrink-0 rounded-lg flex items-center justify-center"
         style="background-color: {{ $c['bg'] }}">
        <x-dynamic-component
            :component="$icon"
            class="w-5 h-5"
            style="color: {{ $c['color'] }}" />
    </div>
    <div class="min-w-0">
        <p class="text-sm font-semibold text-slate-900 leading-none">{{ $label }}</p>
        <p class="mt-1 text-xs text-slate-500 leading-snug line-clamp-2">{{ $descripcion }}</p>
    </div>
</div>
