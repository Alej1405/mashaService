{{--
  Admin Login — Masha Corp S.A.S.
  Split-screen corporativo. Usa {{ $this->form }} de Filament para wiring confiable.
  IMPORTANTE: el <style> DEBE estar dentro del <div class="al-shell"> para que Livewire
  encuentre el wire:id en el div raíz y no en el <style> (lo que rompe wire:submit).
--}}

{{-- ══════════════════════════════════════════════════════════════
     SPLIT LAYOUT — raíz única del componente Livewire
══════════════════════════════════════════════════════════════ --}}
<div class="al-shell">

<style>
/* ── Reset del shell de Filament ───────────────────────────────── */
.fi-body {
    background: #0f172a !important;
    padding: 0 !important;
}
.fi-simple-layout,
.fi-simple-main-ctn {
    display: contents !important;
}
.fi-simple-main {
    all: unset !important;
    display: block !important;
}

/* ── Tokens ────────────────────────────────────────────────────── */
:root {
    --al-brand:   #0f172a;
    --al-amber:   #f59e0b;
    --al-amber-h: #d97706;
    --al-ink:     #0f172a;
    --al-sub:     #64748b;
    --al-label:   #374151;
    --al-border:  #d1d5db;
    --al-ring:    rgba(245, 158, 11, 0.15);
    --al-error:   #dc2626;
    --ease-expo:  cubic-bezier(0.19, 1, 0.22, 1);
}

/* ── Shell pantalla completa ───────────────────────────────────── */
.al-shell {
    position: fixed;
    inset: 0;
    z-index: 1;
    display: flex;
    font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif;
}

/* ═══════════════════════════════════════════
   PANEL IZQUIERDO
═══════════════════════════════════════════ */
.al-left {
    display: none;
    width: 42%;
    min-height: 100dvh;
    background: var(--al-brand);
    position: relative;
    overflow: hidden;
    flex-direction: column;
}
@media (min-width: 1024px) { .al-left { display: flex; } }

.al-left-accent {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom,
        transparent 0%, #f59e0b 20%, #f59e0b 80%, transparent 100%);
    z-index: 1;
}
.al-left-glow {
    position: absolute;
    bottom: -80px; left: -60px;
    width: 420px; height: 420px;
    background: radial-gradient(circle, rgba(245,158,11,.07) 0%, transparent 70%);
    pointer-events: none;
}
.al-left-dots {
    position: absolute;
    inset: 0;
    opacity: 0.045;
    background-image: radial-gradient(circle, #ffffff 1px, transparent 1px);
    background-size: 28px 28px;
}

.al-brand-center {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 3rem 4rem 2rem;
    z-index: 1;
}
.al-logo-wrap {
    margin-bottom: 2.5rem;
    display: flex; align-items: center; justify-content: center;
    width: 72px; height: 72px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 16px;
}
.al-company-name {
    color: #f8fafc;
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.1;
    text-align: center;
}
.al-company-suffix {
    color: #f59e0b;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    margin-top: 0.3rem;
    text-align: center;
}
.al-rule {
    width: 24px; height: 1px;
    background: rgba(245,158,11,.4);
    margin: 2rem auto;
}
.al-tagline {
    color: #64748b;
    font-size: 0.875rem;
    text-align: center;
    line-height: 1.7;
    max-width: 240px;
}
.al-metrics {
    display: flex;
    align-items: center;
    margin-top: 3rem;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 10px;
    overflow: hidden;
}
.al-metric { padding: .875rem 1.5rem; text-align: center; flex: 1; }
.al-metric + .al-metric { border-left: 1px solid rgba(255,255,255,.06); }
.al-metric-val { color: #e2e8f0; font-size: .8125rem; font-weight: 700; line-height: 1; }
.al-metric-lbl { color: #334155; font-size: .625rem; text-transform: uppercase; letter-spacing: .1em; margin-top: .3rem; }

.al-footer-left {
    position: relative; z-index: 1;
    padding: 1.25rem 4rem;
    border-top: 1px solid rgba(255,255,255,.05);
    display: flex; align-items: center; justify-content: space-between;
}
.al-footer-left span { color: #334155; font-size: .6875rem; letter-spacing: .03em; }
.al-version-badge {
    background: rgba(245,158,11,.12);
    color: #f59e0b;
    font-size: .625rem; font-weight: 700;
    letter-spacing: .1em; text-transform: uppercase;
    padding: .2rem .6rem;
    border-radius: 4px;
    border: 1px solid rgba(245,158,11,.2);
}

/* ═══════════════════════════════════════════
   PANEL DERECHO
═══════════════════════════════════════════ */
.al-right {
    flex: 1;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 2.5rem 1.5rem;
    background: #ffffff;
    min-height: 100dvh;
    overflow-y: auto;
    position: relative;
}
.al-right::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, #e5e7eb 15%, #e5e7eb 85%, transparent);
}
.al-form-wrap { width: 100%; max-width: 376px; }

.al-mobile-logo {
    display: flex; justify-content: center;
    margin-bottom: 2rem;
}
@media (min-width: 1024px) { .al-mobile-logo { display: none; } }

/* ── Encabezado ────────────────────────────────────────────────── */
.al-heading {
    color: var(--al-ink);
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.025em;
    margin: 0 0 0.5rem;
    text-wrap: balance;
}
.al-subheading {
    color: var(--al-sub);
    font-size: 0.9375rem;
    margin: 0;
    line-height: 1.5;
}

/* ═══════════════════════════════════════════
   OVERRIDE del formulario de Filament
═══════════════════════════════════════════ */

/* ── 1. ESPACIADO — la fuente real del doble-gap ─────────────────
   fi-fo-component-ctn tiene gap-6 (24px) de Tailwind.
   Lo bajamos a 1rem. SIN margin en hijos para no duplicar.    */
.al-form-wrap .fi-fo-component-ctn {
    gap: 1rem !important;
}
.al-form-wrap .fi-fo-field-wrp,
.al-form-wrap .fi-fo-component-ctn > * {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}
/* El fi-form en sí también tiene gap-y-6 */
.al-form-wrap .fi-form {
    gap: 0 !important;
}

/* ── 2. LABELS ───────────────────────────────────────────────────*/
.al-form-wrap .fi-fo-field-wrp-label span,
.al-form-wrap label.fi-fo-field-wrp-label span {
    color: #374151 !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif !important;
    line-height: 1.4 !important;
}

/* ── 3. INPUT — texto y fuente; borde lo maneja el ring de Filament */
.al-form-wrap .fi-input {
    color: #0f172a !important;
    font-size: 0.9375rem !important;
    font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif !important;
    padding-top: 0.6875rem !important;
    padding-bottom: 0.6875rem !important;
}
.al-form-wrap .fi-input::placeholder {
    color: #6b7280 !important; /* gray-500: 4.6:1 contra blanco — pasa WCAG AA */
}

/* ── 4. INPUT WRAPPER — radio de borde y tamaño del ring ─────────
   Filament usa box-shadow ring. Primary=amber (ya configurado en
   AdminPanelProvider con Color::Amber), así que el ring on-focus
   ya será ámbar. Solo sobreescribimos radio y el ring inactivo.  */
.al-form-wrap .fi-input-wrp {
    border-radius: 7px !important;
    box-shadow: 0 0 0 1px #d1d5db !important;
    transition: box-shadow 130ms ease !important;
}
.al-form-wrap .fi-input-wrp:focus-within {
    box-shadow: 0 0 0 2px #f59e0b, 0 0 0 4px rgba(245, 158, 11, 0.15) !important;
}

/* Botón revelar contraseña */
.al-form-wrap .fi-input-wrp button[type="button"] {
    color: #9ca3af !important;
    transition: color 130ms ease !important;
}
.al-form-wrap .fi-input-wrp button[type="button"]:hover {
    color: #f59e0b !important;
}

/* ── 5. CHECKBOX "Recordarme" ────────────────────────────────────*/
.al-form-wrap .fi-fo-checkbox .fi-fo-field-wrp-label {
    min-height: 44px !important; /* touch target WCAG 2.5.5 */
    display: flex !important;
    align-items: center !important;
}
.al-form-wrap input[type="checkbox"] {
    accent-color: #f59e0b !important;
    width: 15px !important;
    height: 15px !important;
    border-radius: 3px !important;
    cursor: pointer !important;
}
.al-form-wrap .fi-fo-field-wrp-label span:has(+ *),
.al-form-wrap .fi-checkbox-label-ctn span,
.al-form-wrap .fi-fo-checkbox .fi-fo-field-wrp-label span {
    color: #64748b !important;
    font-size: 0.875rem !important;
    font-weight: 400 !important;
}

/* ── 6. ERRORES ──────────────────────────────────────────────────*/
.al-form-wrap p[data-validation-error],
.al-form-wrap .fi-fo-field-wrp-error-message {
    color: #dc2626 !important;
    font-size: 0.8125rem !important;
    font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif !important;
}
.al-form-wrap .fi-input-wrp:has(+ p[data-validation-error]),
.al-form-wrap .fi-fo-field-wrp-error .fi-input-wrp {
    box-shadow: 0 0 0 2px #dc2626 !important;
}

/* ── Botón principal ───────────────────────────────────────────── */
.al-btn {
    width: 100%;
    padding: 0.8125rem 1.5rem;
    background: var(--al-amber);
    color: #1a1200;
    font-size: 0.9375rem;
    font-weight: 700;
    font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif;
    letter-spacing: 0.02em;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition:
        transform 150ms var(--ease-expo),
        box-shadow 150ms var(--ease-expo),
        background-color 130ms ease;
    will-change: transform;
    margin-top: 1.25rem;
}
.al-btn:hover:not(:disabled) {
    background: var(--al-amber-h);
    box-shadow: 0 6px 24px rgba(245,158,11,.28);
}
.al-btn:active:not(:disabled) {
    transform: scale(0.97);
    box-shadow: 0 2px 8px rgba(245,158,11,.16);
}
.al-btn:disabled { opacity: .68; cursor: not-allowed; }

@keyframes al-spin { to { transform: rotate(360deg); } }
.al-spinner {
    width: 15px; height: 15px;
    animation: al-spin .65s linear infinite;
    flex-shrink: 0;
}

/* ── Pie de formulario ─────────────────────────────────────────── */
.al-form-foot {
    margin-top: 2rem;
    padding-top: 1.375rem;
    border-top: 1px solid #f3f4f6;
    text-align: center;
}
.al-form-foot p {
    color: #9ca3af;
    font-size: .75rem;
    margin: 0;
    line-height: 1.5;
}

/* ── Animaciones de entrada ────────────────────────────────────── */
@keyframes al-enter {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.al-e {
    opacity: 0;
    animation: al-enter 420ms var(--ease-expo) forwards;
}
.al-e-1 { animation-delay:  50ms; }
.al-e-2 { animation-delay: 110ms; }
.al-e-3 { animation-delay: 160ms; }
.al-e-4 { animation-delay: 210ms; }

@media (prefers-reduced-motion: reduce) {
    .al-e { animation: none; opacity: 1; }
}

    /* ════════════════════════════════════════
    OVERLAY DE TRANSICIÓN — AUTH → DASHBOARD
    ════════════════════════════════════════
    Se activa cuando Livewire detecta redirect.
    clip-path wipe desde abajo: hardware-accel,
    sin repaint, sin layout thrash.              */

#al-auth-overlay {
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    pointer-events: none;
    clip-path: inset(100% 0 0 0);
    /* Exit: esperar 600ms antes de bajar.
        El redirect de Livewire dispara en < 50ms después de quitar
        la clase → la página navega antes de que el delay expire.
       En auth fallida el delay da margen para mostrar el estado. */
    transition: clip-path 280ms 600ms cubic-bezier(0.55, 0, 1, 0.45);
}
#al-auth-overlay.al-overlay--active {
    pointer-events: all;
    /* Entry: la animación override la transition */
    animation: al-wipe-up 500ms cubic-bezier(0.19, 1, 0.22, 1) forwards;
    transition: none;
}
@keyframes al-wipe-up {
    from { clip-path: inset(100% 0 0 0); }
    to   { clip-path: inset(0% 0 0 0); }
}

/* Logo en el overlay */
.al-ov-logo {
    width: 72px; height: 72px;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    animation: none;
}
#al-auth-overlay.al-overlay--active .al-ov-logo {
    animation: al-logo-appear 300ms 200ms var(--ease-expo) forwards;
}
@keyframes al-logo-appear {
    from { opacity: 0; transform: scale(.88); }
    to   { opacity: 1; transform: scale(1); }
}

/* Barra de progreso ámbar */
.al-ov-progress {
    width: 160px; height: 2px;
    background: rgba(245,158,11,.15);
    border-radius: 2px;
    overflow: hidden;
    opacity: 0;
}
#al-auth-overlay.al-overlay--active .al-ov-progress {
    animation: al-bar-appear 200ms 320ms ease forwards;
}
@keyframes al-bar-appear {
    to { opacity: 1; }
}
.al-ov-progress-fill {
    height: 100%;
    background: #f59e0b;
    width: 0%;
    border-radius: 2px;
    animation: none;
}
#al-auth-overlay.al-overlay--active .al-ov-progress-fill {
    animation: al-bar-fill 1.8s 380ms cubic-bezier(0.19, 1, 0.22, 1) forwards;
}
@keyframes al-bar-fill {
    0%   { width: 0%;   opacity: 1; }
    72%  { width: 88%;  opacity: 1; }
    100% { width: 94%;  opacity: .6; }
}

/* Texto de estado */
.al-ov-text {
    color: #475569;
    font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif;
    font-size: .75rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    opacity: 0;
}
#al-auth-overlay.al-overlay--active .al-ov-text {
    animation: al-logo-appear 280ms 420ms var(--ease-expo) forwards;
}
/* Puntos animados */
.al-ov-text .al-dot {
    display: inline-block;
    animation: al-dot-pulse 1.2s .5s ease-in-out infinite;
}
.al-ov-text .al-dot:nth-child(2) { animation-delay: .65s; }
.al-ov-text .al-dot:nth-child(3) { animation-delay: .80s; }
@keyframes al-dot-pulse {
    0%, 80%, 100% { opacity: .2; }
    40%           { opacity: 1; }
}

/* Línea ámbar decorativa inferior */
.al-ov-line {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(to right, transparent, #f59e0b 30%, #f59e0b 70%, transparent);
    opacity: 0;
}
#al-auth-overlay.al-overlay--active .al-ov-line {
    animation: al-logo-appear 400ms 300ms ease forwards;
}

@media (prefers-reduced-motion: reduce) {
    #al-auth-overlay.al-overlay--active {
        animation: none;
        clip-path: inset(0% 0 0 0);
    }
    .al-ov-logo, .al-ov-progress, .al-ov-text, .al-ov-line { opacity: 1; animation: none; }
    .al-ov-progress-fill { width: 60%; animation: none; }
    .al-dot { animation: none; opacity: 1; }
}
</style>

    {{-- ══ OVERLAY DE TRANSICIÓN ══════════════════════════════════
            Se activa en el click (inmediato, sin esperar Livewire).
            Solo se oculta si authenticate() falla — nunca si hay redirect. --}}
    <div id="al-auth-overlay"
            role="status" aria-live="polite" aria-label="Iniciando sesión"
            wire:loading.class="al-overlay--active"
            wire:target="authenticate">
        <div class="al-ov-line"></div>

        <div class="al-ov-logo">
            <img src="{{ asset('logo.png') }}" alt="Masha Corp"
                    style="height: 34px; width: auto; filter: brightness(0) invert(1);"
                    onerror="this.style.display='none'">
        </div>

        <div class="al-ov-progress">
            <div class="al-ov-progress-fill"></div>
        </div>

        <span class="al-ov-text">
            Iniciando sesión
            <span class="al-dot">.</span><span class="al-dot">.</span><span class="al-dot">.</span>
        </span>
    </div>

    {{-- ── PANEL IZQUIERDO ── --}}
    <aside class="al-left">
        <div class="al-left-accent"></div>
        <div class="al-left-glow"></div>
        <div class="al-left-dots"></div>

        <div class="al-brand-center">
            <div class="al-logo-wrap">
                <img src="{{ asset('logo.png') }}"
                        alt="Masha Corp"
                        style="height: 36px; width: auto; display: block; filter: brightness(0) invert(1);"
                        onerror="this.style.display='none'">
            </div>

            <div class="al-company-name">Masha Corp</div>
            <div class="al-company-suffix">S.A.S.</div>

            <div class="al-rule"></div>

            <p class="al-tagline">
                Sistema integrado de gestión empresarial y administración centralizada de operaciones.
            </p>

            <div class="al-metrics">
                <div class="al-metric">
                    <div class="al-metric-val">Multi</div>
                    <div class="al-metric-lbl">Empresa</div>
                </div>
                <div class="al-metric">
                    <div class="al-metric-val">ERP</div>
                    <div class="al-metric-lbl">Integrado</div>
                </div>
                <div class="al-metric">
                    <div class="al-metric-val">Pro</div>
                    <div class="al-metric-lbl">Cloud</div>
                </div>
            </div>
        </div>

        <div class="al-footer-left">
            <span>&copy; {{ date('Y') }} Masha Corp S.A.S.</span>
            <span class="al-version-badge">v2.0</span>
        </div>
    </aside>

    {{-- ── PANEL DERECHO ── --}}
    <main class="al-right">

        <div class="al-mobile-logo">
            <img src="{{ asset('logo.png') }}" alt="Masha Corp"
                    style="height: 40px; width: auto; display: block;"
                    onerror="this.style.display='none'">
        </div>

        <div class="al-form-wrap">

            {{-- Encabezado --}}
            <div class="al-e al-e-1" style="margin-bottom: 2rem;">
                <h1 class="al-heading">Bienvenido de vuelta</h1>
                <p class="al-subheading">Ingresa con tus credenciales de acceso.</p>
            </div>

            {{-- Formulario — wiring de Filament, CSS propio --}}
            <div class="al-e al-e-2">
                <x-filament-panels::form id="form" wire:submit="authenticate" method="get" autocomplete="off">

                    {{-- Campos renderizados por Filament (email, password, remember) --}}
                    {{ $this->form }}

                    {{-- Botón custom dentro del form para que wire:submit lo tome --}}
                    <button type="submit"
                            class="al-btn"
                            wire:loading.attr="disabled"
                            wire:target="authenticate">

                        <span wire:loading.remove wire:target="authenticate">
                            Iniciar sesión
                        </span>

                        <span wire:loading.flex wire:target="authenticate"
                                style="align-items: center; gap: .5rem;">
                            <svg class="al-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="2.5" opacity=".22"/>
                                <path d="M12 2a10 10 0 019.66 7.4"
                                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                            Verificando...
                        </span>
                    </button>

                </x-filament-panels::form>
            </div>


            {{-- Pie --}}
            <div class="al-e al-e-4 al-form-foot">
                <p>Acceso exclusivo para administradores autorizados.</p>
            </div>

        </div>
    </main>



</div>
