@php
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
    'rose'    => ['bg' => '#fff1f2', 'color' => '#e11d48'],
    'indigo'  => ['bg' => '#eef2ff', 'color' => '#4f46e5'],
];
$c = $palette[$color ?? 'slate'] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];

$badges = [
    'plan'      => ['bg' => '#fef3c7', 'color' => '#92400e', 'dot' => '#d97706', 'label' => 'Del plan'],
    'adicional' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'dot' => '#3b82f6', 'label' => 'Adicional'],
    'inactivo'  => ['bg' => '#f1f5f9', 'color' => '#94a3b8', 'dot' => '#cbd5e1', 'label' => 'Inactivo'],
];
$b = $badges[$badgeType ?? 'inactivo'];
@endphp

<div style="display:flex;align-items:center;gap:12px;padding:6px 0;min-height:52px;">
    {{-- Ícono del módulo --}}
    <div style="flex-shrink:0;width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:{{ $c['bg'] }};">
        <x-dynamic-component :component="$icon" style="width:20px;height:20px;color:{{ $c['color'] }};" />
    </div>

    {{-- Nombre y descripción --}}
    <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:600;color:#0f172a;line-height:1.3;">{{ $label }}</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px;line-height:1.4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $descripcion }}</div>
    </div>

    {{-- Badge de origen --}}
    <div style="flex-shrink:0;">
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:6px;font-size:10px;font-weight:700;letter-spacing:0.03em;text-transform:uppercase;background:{{ $b['bg'] }};color:{{ $b['color'] }};">
            <span style="width:5px;height:5px;border-radius:50%;background:{{ $b['dot'] }};flex-shrink:0;"></span>
            {{ $b['label'] }}
        </span>
    </div>
</div>
