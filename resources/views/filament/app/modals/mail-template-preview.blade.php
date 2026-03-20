<div class="space-y-4">

    {{-- Barra de info de la plantilla --}}
    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-lg px-4 py-3">

        <div class="flex items-center gap-1.5">
            <x-heroicon-o-envelope class="w-3.5 h-3.5"/>
            <span class="font-medium text-gray-700 dark:text-gray-300">Asunto:</span>
            <span>{{ $template->subject }}</span>
        </div>

        <span class="text-gray-300 dark:text-gray-600">·</span>

        <div class="flex items-center gap-1.5">
            <x-heroicon-o-cursor-arrow-rays class="w-3.5 h-3.5"/>
            <span>{{ $template->font_family }}, {{ $template->base_font_size }}px</span>
        </div>

        <span class="text-gray-300 dark:text-gray-600">·</span>

        {{-- Paleta de colores --}}
        <div class="flex items-center gap-1.5">
            <x-heroicon-o-swatch class="w-3.5 h-3.5"/>
            <div class="flex gap-1">
                <span title="Fondo exterior"
                      style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $template->background_color }};border:1px solid rgba(0,0,0,.15);"></span>
                <span title="Contenido"
                      style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $template->content_background_color }};border:1px solid rgba(0,0,0,.15);"></span>
                <span title="Encabezado"
                      style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $template->header_background_color }};border:1px solid rgba(0,0,0,.15);"></span>
                <span title="Texto"
                      style="display:inline-block;width:14px;height:14px;border-radius:50%;background:{{ $template->text_color }};border:1px solid rgba(0,0,0,.15);"></span>
            </div>
        </div>

    </div>

    {{-- Nota de variables --}}
    <div class="flex items-start gap-2 text-xs text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-lg px-3 py-2">
        <x-heroicon-o-information-circle class="w-4 h-4 shrink-0 mt-0.5"/>
        <span>Las variables como <code class="bg-indigo-100 dark:bg-indigo-900/40 px-1 rounded">@{{nombre}}</code>, <code class="bg-indigo-100 dark:bg-indigo-900/40 px-1 rounded">@{{empresa}}</code> se reemplazarán con los datos reales al enviar el correo.</span>
    </div>

    {{-- Preview en iframe --}}
    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white shadow-sm">
        <iframe
            srcdoc="{{ $html }}"
            style="width:100%;height:580px;border:none;display:block;"
            sandbox="allow-same-origin"
            title="Vista previa de plantilla">
        </iframe>
    </div>

</div>
