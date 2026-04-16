@extends('mobile.layout')
@section('title', 'Nuevo Cliente')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Nuevo Cliente</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Registrar acceso al portal</p>
    </div>
</div>

<div id="paso-form" class="space-y-4">

    {{-- Tipo --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Tipo de cliente</p>
        <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2 px-3 py-2.5 rounded-xl cursor-pointer"
                   style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);"
                   id="lbl-persona">
                <input type="radio" name="tipo" value="persona" id="tipo-persona" class="accent-indigo-400" checked
                       onchange="actualizarTipo()">
                <span class="text-sm text-white">Persona</span>
            </label>
            <label class="flex items-center gap-2 px-3 py-2.5 rounded-xl cursor-pointer"
                   style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);"
                   id="lbl-empresa">
                <input type="radio" name="tipo" value="empresa" id="tipo-empresa" class="accent-indigo-400"
                       onchange="actualizarTipo()">
                <span class="text-sm text-white">Empresa</span>
            </label>
        </div>
    </div>

    {{-- Datos --}}
    <div class="card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide" style="color: rgba(232,230,240,0.35);">Datos del cliente</p>

        <div id="wrap-razon" class="hidden">
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Razón social *</label>
            <input type="text" id="c-razon" class="input w-full px-3 py-2.5 text-sm" placeholder="Nombre de la empresa">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" id="lbl-nombre" style="color: rgba(232,230,240,0.5);">Nombre *</label>
                <input type="text" id="c-nombre" class="input w-full px-3 py-2.5 text-sm" placeholder="Nombre">
            </div>
            <div id="wrap-apellido">
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Apellido</label>
                <input type="text" id="c-apellido" class="input w-full px-3 py-2.5 text-sm" placeholder="Apellido">
            </div>
        </div>

        <div>
            <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Correo electrónico *</label>
            <input type="email" id="c-email" class="input w-full px-3 py-2.5 text-sm"
                   placeholder="cliente@ejemplo.com" inputmode="email">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs mb-1.5" id="lbl-cedula" style="color: rgba(232,230,240,0.5);">Cédula / Pasaporte</label>
                <input type="text" id="c-cedula" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="0000000000" inputmode="numeric">
            </div>
            <div>
                <label class="block text-xs mb-1.5" style="color: rgba(232,230,240,0.5);">Teléfono</label>
                <input type="tel" id="c-telefono" class="input w-full px-3 py-2.5 text-sm"
                       placeholder="+593 9X XXX XXXX">
            </div>
        </div>

        <p class="text-xs" style="color: rgba(232,230,240,0.3);">
            La cédula / RUC se usará como contraseña inicial de acceso al portal.
        </p>
    </div>

    <div id="error-msg" class="hidden px-4 py-3 rounded-xl text-sm text-red-300"
         style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25);"></div>

    <button onclick="guardarCliente()" id="btn-guardar"
            class="btn-primary w-full py-3.5 text-sm font-semibold">
        Registrar Cliente
    </button>
</div>

{{-- Éxito --}}
<div id="paso-exito" class="hidden flex flex-col items-center justify-center text-center py-12">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
         style="background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3);">
        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
    </div>
    <h3 class="text-lg font-bold text-white mb-1">¡Cliente registrado!</h3>
    <p id="exito-nombre" class="text-sm text-indigo-300 mb-6"></p>
    <div class="flex flex-col gap-3 w-full">
        <button onclick="resetForm()" class="btn-primary py-3 text-sm">Registrar otro cliente</button>
        <a href="{{ route('mobile.index') }}"
           class="block py-3 text-sm text-center rounded-xl"
           style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.6); border: 1px solid rgba(255,255,255,0.08);">
            Volver al panel
        </a>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';

function actualizarTipo() {
    const esEmpresa = document.getElementById('tipo-empresa').checked;
    document.getElementById('wrap-razon').classList.toggle('hidden', !esEmpresa);
    document.getElementById('wrap-apellido').classList.toggle('hidden', esEmpresa);
    document.getElementById('lbl-nombre').textContent = esEmpresa ? 'Nombre del contacto *' : 'Nombre *';
    document.getElementById('lbl-cedula').textContent = esEmpresa ? 'RUC' : 'Cédula / Pasaporte';
}

function guardarCliente() {
    const tipo   = document.querySelector('input[name="tipo"]:checked').value;
    const nombre = document.getElementById('c-nombre').value.trim();
    const email  = document.getElementById('c-email').value.trim();

    if (!nombre) { mostrarError('El nombre es obligatorio.'); return; }
    if (!email)  { mostrarError('El correo es obligatorio.'); return; }
    if (tipo === 'empresa' && !document.getElementById('c-razon').value.trim()) {
        mostrarError('La razón social es obligatoria para empresas.'); return;
    }

    const body = {
        tipo,
        nombre,
        apellido:     document.getElementById('c-apellido').value.trim() || null,
        razon_social: document.getElementById('c-razon').value.trim() || null,
        email,
        telefono:     document.getElementById('c-telefono').value.trim() || null,
        cedula_ruc:   document.getElementById('c-cedula').value.trim() || null,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true; btn.textContent = 'Registrando...';
    document.getElementById('error-msg').classList.add('hidden');

    fetch('{{ route("mobile.cliente.guardar") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        btn.disabled = false; btn.textContent = 'Registrar Cliente';
        if (!ok) {
            const msg = data.errors ? Object.values(data.errors).flat()[0] : (data.error || 'Error.');
            mostrarError(msg);
            return;
        }
        document.getElementById('exito-nombre').textContent = data.nombre;
        document.getElementById('paso-form').classList.add('hidden');
        document.getElementById('paso-exito').classList.remove('hidden');
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Registrar Cliente';
        mostrarError('Error de conexión.');
    });
}

function mostrarError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetForm() {
    document.getElementById('tipo-persona').checked = true;
    actualizarTipo();
    ['c-razon','c-nombre','c-apellido','c-email','c-telefono','c-cedula'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('error-msg').classList.add('hidden');
    document.getElementById('paso-exito').classList.add('hidden');
    document.getElementById('paso-form').classList.remove('hidden');
}
</script>

@endsection
