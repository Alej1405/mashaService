{{-- Botón para instalar /admin como app en el celular. --}}
<div
    x-data="{
        visible: false,
        ios: /iPhone|iPad|iPod/.test(navigator.userAgent),
        evento: null,
        pasos: false,
        init() {
            const instalada = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
            const celular = window.matchMedia('(max-width: 1024px)').matches;
            let cerrado = false;
            try { cerrado = localStorage.getItem('masha-instalar-cerrado') === '1'; } catch (e) {}
            if (instalada || ! celular || cerrado) return;
            if (this.ios) { this.visible = true; return; }
            window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); this.evento = e; this.visible = true; });
        },
        async instalar() {
            if (this.evento) { this.evento.prompt(); await this.evento.userChoice; this.evento = null; this.visible = false; return; }
            this.pasos = true;
        },
        cerrar() {
            this.visible = false; this.pasos = false;
            try { localStorage.setItem('masha-instalar-cerrado', '1'); } catch (e) {}
        },
    }"
    x-show="visible"
    x-cloak
    class="mi-app"
>
    <div class="mi-tarjeta" role="dialog" aria-label="Instalar el panel como app">
        <img src="/pwa/icono-192.png" alt="" width="40" height="40">
        <div class="mi-texto">
            <p class="mi-titulo">Masha Panel en tu celular</p>
            <template x-if="! pasos">
                <p>Instálalo y ábrelo como una app, a pantalla completa.</p>
            </template>
            <template x-if="pasos">
                <ol>
                    <li>Toca <strong>Compartir</strong>
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M10 3v10M6 7l4-4 4 4M4 11v5h12v-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        abajo en Safari.</li>
                    <li>Elige <strong>Agregar a pantalla de inicio</strong>.</li>
                </ol>
            </template>
        </div>
        <div class="mi-acciones">
            <button type="button" class="mi-instalar" x-show="! pasos" x-on:click="instalar()">Instalar app</button>
            <button type="button" class="mi-cerrar" x-on:click="cerrar()" aria-label="No mostrar más">
                <svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .mi-app { position: fixed; inset: auto 12px calc(12px + env(safe-area-inset-bottom)) 12px; z-index: 60; }
    .mi-tarjeta { display: flex; gap: 12px; align-items: center; padding: 12px; border-radius: 14px; background: #fff; color: #171b1b; box-shadow: 0 8px 28px rgb(0 0 0 / .16); border: 1px solid rgb(0 0 0 / .06); }
    .mi-tarjeta img { border-radius: 10px; flex-shrink: 0; }
    .mi-texto { flex: 1; font-size: 13px; line-height: 1.4; color: #434a4a; }
    .mi-texto ol { padding-left: 18px; list-style: decimal; }
    .mi-texto svg { display: inline; vertical-align: -3px; }
    .mi-titulo { font-weight: 600; color: #171b1b; margin-bottom: 2px; }
    .mi-acciones { display: flex; align-items: center; gap: 4px; }
    .mi-instalar { min-height: 44px; padding: 0 14px; border-radius: 10px; background: #171b1b; color: #fff; font-size: 14px; font-weight: 600; white-space: nowrap; }
    .mi-cerrar { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 10px; color: #7b8484; }
</style>
