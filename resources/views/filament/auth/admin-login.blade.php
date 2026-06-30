{{--
  Login — Masha Corp S.A.S.  (SOLO VISTA — la lógica no se toca)
  Tarjeta centrada con transparencia, light mode. Usa {{ $this->form }} de Filament.
  IMPORTANTE: el <style> DEBE estar dentro del <div class="al-shell"> para que Livewire
  encuentre el wire:id en el div raíz y no en el <style> (rompería wire:submit).
--}}
<div class="al-shell">

<style>
/* ── Reset del shell de Filament ───────────────────────────────── */
.fi-body { background:#f8fafc !important; padding:0 !important; }
.fi-simple-layout, .fi-simple-main-ctn { display:contents !important; }
.fi-simple-main { all:unset !important; display:block !important; }

/* ── Tokens ────────────────────────────────────────────────────── */
:root {
    --al-ink:    #0f172a;
    --al-sub:    #475569;
    --al-mut:    #64748b;
    --al-accent: #4f46e5;
    --al-accent-h:#4338ca;
    --al-border: #e2e8f0;
    --al-ring:   rgba(99,102,241,0.18);
    --al-error:  #dc2626;
    --al-ease:   cubic-bezier(0.16, 1, 0.3, 1);
}

/* ── Shell pantalla completa, una columna centrada ─────────────── */
.al-shell {
    position:fixed; inset:0; z-index:1;
    display:grid; place-items:center; padding:24px;
    overflow:hidden;
    font-family:'Sansation', ui-sans-serif, system-ui, sans-serif;
    background:
        radial-gradient(900px 600px at 12% -10%, #eef2ff 0%, transparent 55%),
        radial-gradient(800px 600px at 110% 110%, #e0f2fe 0%, transparent 50%),
        linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

/* ── Manchas suaves de color (la transparencia de la tarjeta las deja ver) ── */
.al-blob { position:absolute; border-radius:50%; filter:blur(70px); opacity:.55; pointer-events:none; }
.al-blob-1 { width:380px; height:380px; top:-90px; left:-60px;  background:#c7d2fe; }
.al-blob-2 { width:320px; height:320px; bottom:-80px; right:-40px; background:#bae6fd; }
.al-blob-3 { width:260px; height:260px; top:40%; left:60%; background:#ddd6fe; opacity:.4; }

/* ── Tarjeta translúcida ───────────────────────────────────────── */
.al-card {
    position:relative; z-index:2;
    width:100%; max-width:420px;
    padding:40px 36px 32px;
    border-radius:22px;
    background:rgba(255,255,255,0.68);
    backdrop-filter:blur(18px) saturate(150%);
    -webkit-backdrop-filter:blur(18px) saturate(150%);
    border:1px solid rgba(255,255,255,0.75);
    box-shadow:0 20px 50px -16px rgba(15,23,42,0.20), 0 1px 0 rgba(255,255,255,0.6) inset;
    animation:al-in 520ms var(--al-ease) both;
}
@keyframes al-in { from{opacity:0; transform:translateY(14px) scale(.985)} to{opacity:1; transform:none} }

/* ── Encabezado ────────────────────────────────────────────────── */
.al-logo { display:flex; justify-content:center; margin-bottom:22px; }
.al-logo img { height:42px; width:auto; display:block; }
.al-heading { font-size:1.5rem; font-weight:700; color:var(--al-ink); letter-spacing:-0.02em; text-align:center; margin:0; text-wrap:balance; }
.al-subheading { font-size:0.9rem; color:var(--al-mut); text-align:center; margin:6px 0 26px; }

/* ── Campos de Filament integrados a la tarjeta ────────────────── */
.al-card .fi-fo-field-wrp + .fi-fo-field-wrp { margin-top:14px; }
.al-card .fi-fo-field-wrp-label, .al-card label { font-size:0.82rem; font-weight:600; color:#334155; margin-bottom:5px; }
.al-card .fi-input, .al-card input[type="text"], .al-card input[type="email"], .al-card input[type="password"] {
    background:rgba(255,255,255,0.85) !important;
    border:1px solid var(--al-border) !important;
    border-radius:11px !important;
    color:var(--al-ink) !important;
    transition:border-color 150ms var(--al-ease), box-shadow 150ms var(--al-ease), background 150ms var(--al-ease);
}
.al-card .fi-input-wrp { box-shadow:none !important; border-radius:11px !important; }
.al-card .fi-input:focus, .al-card input:focus {
    border-color:var(--al-accent) !important;
    box-shadow:0 0 0 4px var(--al-ring) !important;
    background:#fff !important;
    outline:none !important;
}
.al-card .fi-fo-field-wrp .fi-color-picker { display:none; }

/* ── Botón ─────────────────────────────────────────────────────── */
.al-btn {
    margin-top:22px; width:100%;
    display:flex; align-items:center; justify-content:center; gap:.5rem;
    height:46px; border:0; border-radius:11px; cursor:pointer;
    background:var(--al-accent); color:#fff;
    font-family:inherit; font-size:0.95rem; font-weight:600; letter-spacing:.01em;
    box-shadow:0 8px 18px -8px rgba(79,70,229,0.55);
    transition:background 150ms var(--al-ease), transform 120ms var(--al-ease), box-shadow 150ms var(--al-ease);
}
@media (hover:hover){ .al-btn:hover{ background:var(--al-accent-h); box-shadow:0 10px 22px -8px rgba(79,70,229,0.6); transform:translateY(-1px); } }
.al-btn:active { transform:translateY(0) scale(.99); }
.al-btn:disabled { opacity:.75; cursor:default; }
.al-spinner { width:18px; height:18px; animation:al-spin .7s linear infinite; }
@keyframes al-spin { to{ transform:rotate(360deg) } }

/* ── Pie ───────────────────────────────────────────────────────── */
.al-foot { margin-top:22px; text-align:center; font-size:0.78rem; color:var(--al-mut); }

/* ── Overlay de inicio de sesión (light) ───────────────────────── */
#al-auth-overlay {
    position:fixed; inset:0; z-index:50;
    display:none; flex-direction:column; align-items:center; justify-content:center; gap:18px;
    background:rgba(248,250,252,0.82);
    backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
}
#al-auth-overlay.al-overlay--active { display:flex; }
.al-ov-line { display:none; }
.al-ov-logo img { height:38px; width:auto; }
.al-ov-progress { width:180px; height:4px; border-radius:9999px; background:#e2e8f0; overflow:hidden; }
.al-ov-progress-fill { height:100%; width:40%; border-radius:9999px; background:var(--al-accent); animation:al-prog 1.1s var(--al-ease) infinite; }
@keyframes al-prog { 0%{transform:translateX(-100%)} 100%{transform:translateX(350%)} }
.al-ov-text { font-size:0.9rem; color:var(--al-sub); font-weight:500; }
.al-dot { animation:al-blink 1.4s infinite both; }
.al-dot:nth-child(2){ animation-delay:.2s } .al-dot:nth-child(3){ animation-delay:.4s }
@keyframes al-blink { 0%,100%{opacity:.2} 50%{opacity:1} }

@media (max-width:480px){ .al-card{ padding:32px 22px 26px; border-radius:18px; } }
@media (prefers-reduced-motion: reduce){
    .al-card, .al-btn, .al-spinner, .al-ov-progress-fill, .al-dot { animation:none !important; transition:none !important; }
}
</style>

    {{-- ══ OVERLAY DE TRANSICIÓN (wiring intacto) ══ --}}
    <div id="al-auth-overlay"
            role="status" aria-live="polite" aria-label="Iniciando sesión"
            wire:loading.class="al-overlay--active"
            wire:target="authenticate">
        <div class="al-ov-line"></div>

        <div class="al-ov-logo">
            <img src="{{ asset('logo.png') }}" alt="Masha Corp"
                    style="height:38px; width:auto;" onerror="this.style.display='none'">
        </div>

        <div class="al-ov-progress">
            <div class="al-ov-progress-fill"></div>
        </div>

        <span class="al-ov-text">
            Iniciando sesión
            <span class="al-dot">.</span><span class="al-dot">.</span><span class="al-dot">.</span>
        </span>
    </div>

    {{-- ── Fondo decorativo ── --}}
    <span class="al-blob al-blob-1"></span>
    <span class="al-blob al-blob-2"></span>
    <span class="al-blob al-blob-3"></span>

    {{-- ── Tarjeta de acceso ── --}}
    <main class="al-card">

        <div class="al-logo">
            <img src="{{ asset('logo.png') }}" alt="Masha Corp" onerror="this.style.display='none'">
        </div>

        <h1 class="al-heading">Bienvenido de vuelta</h1>
        <p class="al-subheading">Ingresa con tus credenciales de acceso.</p>

        {{-- Formulario — wiring de Filament intacto, solo CSS propio --}}
        <x-filament-panels::form id="form" wire:submit="authenticate" method="get" autocomplete="off">

            {{ $this->form }}

            <button type="submit"
                    class="al-btn"
                    wire:loading.attr="disabled"
                    wire:target="authenticate">

                <span wire:loading.remove wire:target="authenticate">
                    Iniciar sesión
                </span>

                <span wire:loading.flex wire:target="authenticate"
                        style="align-items:center; gap:.5rem;">
                    <svg class="al-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity=".22"/>
                        <path d="M12 2a10 10 0 019.66 7.4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                    Verificando...
                </span>
            </button>

        </x-filament-panels::form>

        <p class="al-foot">Acceso seguro a tu cuenta.</p>
    </main>
</div>
