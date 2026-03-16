<div
    id="mashaec-loader"
    style="
        position:fixed; inset:0; z-index:99999;
        display:flex; flex-direction:column;
        align-items:center; justify-content:center;
        background:rgba(3,7,18,0.7);
        backdrop-filter:blur(20px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    "
>
    <div style="
        font-family:'Inter',sans-serif;
        font-size:2rem; font-weight:800;
        letter-spacing:-0.04em;
        background:linear-gradient(135deg,#6366f1,#8b5cf6);
        -webkit-background-clip:text;
        -webkit-text-fill-color:transparent;
        margin-bottom:8px;
        animation:logoAura 2s ease-in-out infinite;
    ">Mashaec ERP</div>

    <div style="
        font-family:'Inter',sans-serif;
        font-size:0.7rem; color:rgba(148,163,184,0.6);
        letter-spacing:0.2em; text-transform:uppercase; font-weight:600;
        margin-bottom:24px;
    ">Procesando Datos</div>

    <div style="
        width:100px; height:2px;
        background:rgba(255,255,255,0.05);
        border-radius:2px; overflow:hidden;
    ">
        <div style="
            height:100%;
            background:linear-gradient(90deg,#6366f1,#8b5cf6);
            border-radius:2px;
            animation:loaderAura 1s ease-in-out infinite;
        "></div>
    </div>
</div>

<style>
@keyframes logoAura {
    0%,100% { opacity:1; filter: drop-shadow(0 0 5px rgba(99,102,241,0.2)); }
    50% { opacity:0.8; filter: drop-shadow(0 0 15px rgba(99,102,241,0.4)); }
}
@keyframes loaderAura {
    0%   { width:0%;  margin-left:0%; }
    50%  { width:60%; margin-left:20%; }
    100% { width:0%;  margin-left:100%; }
}
</style>

<script>
document.addEventListener('livewire:init', () => {
    let loaderTimeout;
    const loader = document.getElementById('mashaec-loader');

    Livewire.hook('request', ({ fail, respond }) => {
        // Solo mostramos el loader si la petición tarda más de 150ms
        // para evitar parpadeos en acciones ultra-rápidas
        loaderTimeout = setTimeout(() => {
            loader.style.opacity = '1';
            loader.style.visibility = 'visible';
            loader.style.pointerEvents = 'all';
        }, 150);

        respond(() => {
            clearTimeout(loaderTimeout);
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            loader.style.pointerEvents = 'none';
        });

        fail(() => {
            clearTimeout(loaderTimeout);
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            loader.style.pointerEvents = 'none';
        });
    });
});
</script>
