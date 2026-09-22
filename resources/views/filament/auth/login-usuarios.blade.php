{{--
  Ingreso de usuarios — ERP Masha.net
  Dos caras sobre el mismo lienzo: el panel sólido corre de un lado al otro y
  debajo cambia la tarjeta. En móvil no corre nada: una tarjeta y un enlace.

  IMPORTANTE: el <style> va DENTRO del div raíz para que Livewire encuentre el
  wire:id en el div y no en el <style> (rompería wire:submit).
--}}
<div class="lu" x-data="{ modo: 'acceso' }" :class="modo === 'solicitud' && 'lu-solicitud'">

<style>
/* ── Reset del shell de Filament ─────────────────────────────────── */
.fi-body { background:#475569 !important; padding:0 !important; }
.fi-simple-layout, .fi-simple-main-ctn { display:contents !important; }
.fi-simple-main { all:unset !important; display:block !important; }

.lu {
    --lu-panel:#64748b;
    --lu-panel-btn:#94a3b8;
    --lu-accent:#4f46e5;
    --lu-accent-fuerte:#4338ca;
    --lu-ink:#0f172a;
    --lu-label:#475569;
    --lu-placeholder:#c5cbd2;
    --lu-mut:#94a3b8;
    --lu-ease:cubic-bezier(.65,0,.35,1);

    position:fixed; inset:0; z-index:1; overflow:hidden;
    font-family:'Sansation', ui-sans-serif, system-ui, sans-serif;
    background:#475569;
}

/* Foto de fondo con el velo del diseño */
.lu-fondo { position:absolute; inset:0; overflow:hidden; }
.lu-fondo img { width:100%; height:100%; object-fit:cover; opacity:.2; }
.lu-fondo::after { content:''; position:absolute; inset:0; background:rgba(71,85,105,.71); }

/* ── El panel que corre ──────────────────────────────────────────── */
.lu-panel {
    position:absolute; top:0; bottom:0; left:0; width:50%;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:58px; padding:0 clamp(32px, 6vw, 140px); text-align:center;
    background:var(--lu-panel);
    border-top-right-radius:59px; border-bottom-right-radius:0;
    box-shadow:5px 5px 7px rgba(0,0,0,.15);
    transition:transform 700ms var(--lu-ease), border-radius 700ms var(--lu-ease);
    z-index:3;
}
.lu-solicitud .lu-panel {
    transform:translateX(100%);
    border-top-right-radius:0; border-top-left-radius:59px;
}

.lu-panel h1 { margin:0; color:#fff; font-size:clamp(34px, 4.4vw, 64px); font-weight:700; line-height:1.05; }
.lu-panel p  { margin:27px 0 0; color:#fff; font-size:clamp(15px, 1.4vw, 20px); font-weight:700; }
.lu-panel-btn {
    border:0; cursor:pointer; background:var(--lu-panel-btn); color:#fff;
    font-family:inherit; font-weight:700; font-size:clamp(18px, 2.2vw, 32px);
    padding:12px 50px; border-radius:25px;
    filter:drop-shadow(5px 4px 5px rgba(0,0,0,.15));
    transition:transform .15s ease, filter .15s ease;
}
.lu-panel-btn:hover  { filter:drop-shadow(5px 6px 9px rgba(0,0,0,.22)); }
.lu-panel-btn:active { transform:scale(.98); }

/* ── Las dos tarjetas ────────────────────────────────────────────── */
.lu-cara {
    position:absolute; top:0; bottom:0; width:50%;
    display:flex; align-items:center; justify-content:center; padding:24px;
    transition:opacity 420ms var(--lu-ease), visibility 420ms;
    z-index:2;
}
.lu-cara-acceso    { right:0; }
.lu-cara-solicitud { left:0; opacity:0; visibility:hidden; }
.lu-solicitud .lu-cara-acceso    { opacity:0; visibility:hidden; }
.lu-solicitud .lu-cara-solicitud { opacity:1; visibility:visible; }

.lu-card {
    width:100%; max-width:460px; max-height:calc(100vh - 48px); overflow-y:auto;
    padding:34px 58px;
    background:rgba(255,255,255,.69);
    backdrop-filter:blur(14px) saturate(140%);
    -webkit-backdrop-filter:blur(14px) saturate(140%);
    border-radius:20px;
    box-shadow:1px 1px 12px 1px rgba(0,0,0,.25);
}
.lu-logo {
    width:52px; height:52px; border-radius:13px;
    background:rgba(79,70,229,.08);
    display:flex; align-items:center; justify-content:center;
    color:var(--lu-accent-fuerte); font-weight:700; font-size:18px;
}
.lu-logo img { max-width:34px; max-height:34px; object-fit:contain; }
.lu-kicker { margin:24px 0 0; color:var(--lu-accent-fuerte); font-size:15px; font-weight:700; letter-spacing:.6px; }
.lu-card h2 { margin:2px 0 0; color:#000; font-size:22px; font-weight:700; letter-spacing:.88px; }
.lu-card .lu-sub { margin:2px 0 0; color:var(--lu-mut); font-size:12.5px; letter-spacing:.25px; }
.lu-form { margin-top:25px; }

/* Campos: el formulario lo pinta Filament, aquí solo se le da la piel */
.lu-card .fi-fo-field-wrp + .fi-fo-field-wrp { margin-top:17px; }
.lu-card .fi-fo-field-wrp-label, .lu-card label {
    font-size:12.5px; font-weight:700; color:var(--lu-label); letter-spacing:.25px; margin-bottom:5px;
}
.lu-card .fi-input-wrp { box-shadow:none !important; border-radius:12px !important; background:#fff; }
.lu-card .fi-input,
.lu-card input[type="text"], .lu-card input[type="email"],
.lu-card input[type="password"], .lu-card input[type="tel"], .lu-card textarea {
    height:39px; border:0; border-radius:12px; background:#fff; color:var(--lu-ink);
    font-size:12.5px; font-weight:700; padding:12px 14px;
    box-shadow:1px 1px 1.5px rgba(0,0,0,.25);
}
.lu-card textarea { height:auto; min-height:58px; }
.lu-card ::placeholder { color:var(--lu-placeholder); font-weight:700; letter-spacing:.5px; }
.lu-card .fi-input:focus, .lu-card input:focus, .lu-card textarea:focus {
    outline:none; box-shadow:0 0 0 3px rgba(79,70,229,.18);
}
.lu-card .fi-fo-field-wrp-hint, .lu-card .fi-checkbox-input { accent-color:var(--lu-accent); }

.lu-btn {
    width:100%; margin-top:25px; border:0; cursor:pointer;
    background:var(--lu-accent); color:#fff; font-family:inherit;
    font-size:12.5px; font-weight:700; letter-spacing:.5px;
    padding:11px 20px; border-radius:12px;
    transition:background .15s ease, transform .15s ease;
}
.lu-btn:hover  { background:var(--lu-accent-fuerte); }
.lu-btn:active { transform:scale(.99); }
.lu-btn[disabled] { opacity:.65; cursor:progress; }

.lu-pie { display:block; width:100%; margin-top:10px; text-align:center;
          color:#000; font-size:12.5px; letter-spacing:.5px; text-decoration:none; }
.lu-pie:hover { color:var(--lu-accent-fuerte); }
.lu-enlace-modo { display:none; }

/* ── Modal de confirmación ───────────────────────────────────────── */
.lu-modal-fondo {
    position:fixed; inset:0; z-index:10; display:flex; align-items:center; justify-content:center;
    padding:24px; background:rgba(15,23,42,.55); backdrop-filter:blur(3px);
}
.lu-modal {
    width:100%; max-width:420px; padding:32px 30px; text-align:center;
    background:#fff; border-radius:20px; box-shadow:0 24px 60px -20px rgba(0,0,0,.45);
    animation:lu-entra 320ms var(--lu-ease) both;
}
@keyframes lu-entra { from{opacity:0; transform:translateY(12px) scale(.98)} to{opacity:1; transform:none} }
.lu-modal-icono {
    width:56px; height:56px; margin:0 auto 16px; border-radius:50%;
    background:rgba(16,185,129,.12); color:#047857;
    display:flex; align-items:center; justify-content:center;
}
.lu-modal h3 { margin:0; font-size:20px; font-weight:700; color:var(--lu-ink); }
.lu-modal p  { margin:8px 0 0; font-size:14px; line-height:1.5; color:#475569; }

/* ── Móvil: nada corre. Una tarjeta y un enlace. ─────────────────── */
@media (max-width:900px) {
    .lu-panel { position:static; width:auto; gap:14px; padding:34px 24px 26px;
                border-radius:0 0 28px 28px; box-shadow:0 6px 14px rgba(0,0,0,.18); text-align:center; }
    .lu-solicitud .lu-panel { transform:none; border-radius:0 0 28px 28px; }
    .lu-panel p { margin:8px 0 0; font-size:14px; }
    .lu-panel-btn { display:none; }

    .lu { display:flex; flex-direction:column; overflow-y:auto; }
    .lu-cara { position:static; width:auto; padding:20px 16px 32px; }
    .lu-cara-solicitud { display:none; }
    .lu-solicitud .lu-cara-acceso { display:none; }
    .lu-solicitud .lu-cara-solicitud { display:flex; opacity:1; visibility:visible; }
    .lu-card { max-width:460px; padding:26px 22px; max-height:none; }
    .lu-enlace-modo { display:block; width:100%; margin-top:14px; padding:12px;
                      background:none; border:0; cursor:pointer; font-family:inherit;
                      font-size:13px; font-weight:700; color:var(--lu-accent-fuerte); }
}

@media (prefers-reduced-motion:reduce) {
    .lu-panel, .lu-cara, .lu-modal { transition:none !important; animation:none !important; }
}
</style>

@php
    $marca = \Filament\Facades\Filament::getTenant()?->name ?? 'ERP Masha.net';
@endphp

<div class="lu-fondo">
    <img src="{{ asset('img/login-fondo.jpg') }}" alt="">
</div>

{{-- El panel que corre --}}
<aside class="lu-panel">
    <div>
        <h1>ERP Masha.net</h1>
        <p>Bienvenido. Tu empresa con el mejor registro.</p>
    </div>
    <button type="button" class="lu-panel-btn"
            x-on:click="modo = (modo === 'acceso' ? 'solicitud' : 'acceso')"
            x-text="modo === 'acceso' ? 'Crear una cuenta' : 'Iniciar sesión'"></button>
</aside>

{{-- Cara 1: acceso --}}
<div class="lu-cara lu-cara-acceso">
    <main class="lu-card">
        <div class="lu-logo">M</div>
        <p class="lu-kicker">Portal de Usuarios</p>
        <h2>Bienvenido</h2>
        <p class="lu-sub">Ingresa con tu correo y contraseña</p>

        <x-filament-panels::form id="form" wire:submit="authenticate" class="lu-form">
            {{ $this->form }}

            <button type="submit" class="lu-btn" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="authenticate">Ingresar</span>
                <span wire:loading wire:target="authenticate">Entrando…</span>
            </button>
        </x-filament-panels::form>

        @if (filament()->hasPasswordReset())
            <a class="lu-pie" href="{{ filament()->getRequestPasswordResetUrl() }}">¿Olvidaste tu contraseña…?</a>
        @endif

        <button type="button" class="lu-enlace-modo" x-on:click="modo = 'solicitud'">
            ¿No tienes cuenta? Solicita acceso
        </button>
    </main>
</div>

{{-- Cara 2: solicitud de acceso. No crea usuario: avisa a MashaCorp. --}}
<div class="lu-cara lu-cara-solicitud">
    <main class="lu-card">
        <div class="lu-logo">M</div>
        <p class="lu-kicker">Solicitar acceso</p>
        <h2>Únete al ERP</h2>
        <p class="lu-sub">El acceso lo configuramos nosotros. Déjanos tus datos.</p>

        <x-filament-panels::form id="solicitudForm" wire:submit="solicitarAcceso" class="lu-form">
            {{ $this->solicitudForm }}

            <button type="submit" class="lu-btn" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="solicitarAcceso">Enviar solicitud</span>
                <span wire:loading wire:target="solicitarAcceso">Enviando…</span>
            </button>
        </x-filament-panels::form>

        <button type="button" class="lu-enlace-modo" x-on:click="modo = 'acceso'">
            Ya tengo cuenta, quiero ingresar
        </button>
    </main>
</div>

{{-- Confirmación --}}
@if ($solicitudEnviada)
    <div class="lu-modal-fondo" wire:key="confirmacion-solicitud">
        <div class="lu-modal">
            <div class="lu-modal-icono">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>
            <h3>Acceso solicitado</h3>
            <p>
                Recibimos tu solicitud. Alguien se comunicará contigo para terminar
                la configuración de tu empresa en el ERP.
            </p>
            <button type="button" class="lu-btn" wire:click="cerrarConfirmacion"
                    x-on:click="modo = 'acceso'">Entendido</button>
        </div>
    </div>
@endif

</div>
