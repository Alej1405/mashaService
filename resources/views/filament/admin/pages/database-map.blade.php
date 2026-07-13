<x-filament-panels::page>
    @php($schema = $this->getSchemaData())

    <div
        x-data="databaseMap(@js($schema))"
        x-init="init()"
        class="dbmap"
        wire:ignore
    >
        <style>
            .dbmap {
                --dbm-surface: #ffffff;
                --dbm-panel: #f8fafc;
                --dbm-border: #e2e8f0;
                --dbm-border-strong: #cbd5e1;
                --dbm-ink: #0f172a;
                --dbm-ink-2: #475569;
                --dbm-ink-3: #64748b;
                --dbm-accent: #f59e0b;
                --dbm-accent-ink: #b45309;
                --dbm-accent-soft: #fffbeb;
                --dbm-accent-border: #fcd34d;
                font-family: 'Sansation', ui-sans-serif, system-ui, sans-serif;
                color: var(--dbm-ink);
            }

            /* ── Toolbar ── */
            .dbmap__bar {
                display: flex; align-items: center; gap: .75rem;
                flex-wrap: wrap; margin-bottom: .75rem;
            }
            .dbmap__crumbs { display: flex; align-items: center; gap: .35rem; font-size: .875rem; }
            .dbmap__crumb {
                background: none; border: 0; cursor: pointer; padding: .25rem .1rem;
                color: var(--dbm-ink-3); font: inherit; transition: color .15s ease-out;
            }
            .dbmap__crumb:hover { color: var(--dbm-accent-ink); }
            .dbmap__crumb--current { color: var(--dbm-ink); font-weight: 600; cursor: default; }
            .dbmap__crumb-sep { color: var(--dbm-border-strong); }

            .dbmap__spacer { flex: 1 1 auto; }

            .dbmap__search {
                background: var(--dbm-surface);
                border: 1px solid var(--dbm-border-strong);
                border-radius: .625rem;
                color: var(--dbm-ink); padding: .5rem .75rem; min-width: 15rem;
                font: inherit; font-size: .875rem;
                transition: border-color .15s ease-out, box-shadow .15s ease-out;
            }
            .dbmap__search::placeholder { color: var(--dbm-ink-3); }
            .dbmap__search:focus-visible {
                outline: none; border-color: var(--dbm-accent);
                box-shadow: 0 0 0 3px rgba(245, 158, 11, .18);
            }

            .dbmap__legend { display: flex; gap: 1rem; font-size: .8rem; color: var(--dbm-ink-2); }
            .dbmap__dot {
                display: inline-block; width: .7rem; height: .7rem; border-radius: 4px;
                margin-right: .4rem; vertical-align: -1px; border: 1px solid;
            }

            /* ── Canvas ── */
            .dbmap__canvas {
                height: 480px; width: 100%;
                background:
                    radial-gradient(circle at 1px 1px, #e9edf3 1px, transparent 0) 0 0 / 22px 22px,
                    var(--dbm-panel);
                border: 1px solid var(--dbm-border);
                border-radius: 1rem;
            }

            /* ── Detalle ── */
            .dbmap__detail {
                margin-top: 1rem;
                background: var(--dbm-surface);
                border: 1px solid var(--dbm-border);
                border-radius: 1rem;
                box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
                padding: 1.5rem;
            }
            .dbmap__detail-head { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
            .dbmap__detail-head h3 { font-size: 1.15rem; font-weight: 700; letter-spacing: -.01em; margin: 0; }
            .dbmap__tag {
                font-size: .68rem; font-weight: 600; letter-spacing: .02em;
                padding: .15rem .5rem; border-radius: .375rem;
                background: var(--dbm-accent-soft);
                color: var(--dbm-accent-ink);
                border: 1px solid var(--dbm-accent-border);
            }
            .dbmap__tag--muted {
                background: var(--dbm-panel); color: var(--dbm-ink-3);
                border-color: var(--dbm-border);
            }

            .dbmap__section-label {
                font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em;
                color: var(--dbm-ink-3); margin: 1.4rem 0 .5rem;
            }
            .dbmap__section-label:first-of-type { margin-top: 1rem; }

            .dbmap table { width: 100%; border-collapse: collapse; font-size: .82rem; }
            .dbmap th {
                text-align: left; color: var(--dbm-ink-3); font-weight: 600;
                padding: .4rem .55rem; border-bottom: 1px solid var(--dbm-border);
                white-space: nowrap;
            }
            .dbmap td {
                padding: .4rem .55rem; border-bottom: 1px solid #f1f5f9;
                color: var(--dbm-ink); vertical-align: top;
            }
            .dbmap tr:last-child td { border-bottom: 0; }
            .dbmap code {
                font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: .76rem;
                color: var(--dbm-ink-2);
            }
            .dbmap__col-key { color: var(--dbm-accent-ink); font-weight: 700; }
            .dbmap__muted { color: var(--dbm-ink-3); }

            /* Relaciones */
            .dbmap__rels { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
            .dbmap__rel-link {
                background: none; border: 0; padding: 0; cursor: pointer; font: inherit;
                color: var(--dbm-accent-ink); font-weight: 600;
                border-bottom: 1px solid transparent; transition: border-color .15s ease-out;
            }
            .dbmap__rel-link:hover { border-bottom-color: var(--dbm-accent-border); }

            .dbmap__empty {
                display: flex; flex-direction: column; align-items: center; gap: .35rem;
                color: var(--dbm-ink-3); font-size: .9rem; padding: 2.5rem 1rem; text-align: center;
            }
            .dbmap__empty strong { color: var(--dbm-ink-2); font-weight: 600; }

            /* Aparición del detalle */
            .dbmap__fade { animation: dbmap-fade .18s ease-out; }
            @keyframes dbmap-fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
            @media (prefers-reduced-motion: reduce) {
                .dbmap__fade { animation: none; }
                .dbmap__crumb, .dbmap__search, .dbmap__rel-link { transition: none; }
            }
        </style>

        {{-- ── Toolbar ── --}}
        <div class="dbmap__bar">
            <nav class="dbmap__crumbs" aria-label="Ruta">
                <button type="button" class="dbmap__crumb" :class="{ 'dbmap__crumb--current': view === 'modules' }"
                        @click="backToModules()">Módulos</button>
                <template x-if="view === 'tables'">
                    <span style="display:contents">
                        <span class="dbmap__crumb-sep">›</span>
                        <span class="dbmap__crumb dbmap__crumb--current" x-text="activeModule"></span>
                    </span>
                </template>
            </nav>

            <div class="dbmap__spacer"></div>

            <div class="dbmap__legend" x-show="view === 'tables'" x-cloak>
                <span><span class="dbmap__dot" style="background: var(--dbm-accent-soft); border-color: var(--dbm-accent-border);"></span>Multi-empresa (empresa_id)</span>
                <span><span class="dbmap__dot" style="background: #fff; border-color: var(--dbm-border-strong);"></span>Global</span>
            </div>

            <input
                type="text" list="dbmap-tables" class="dbmap__search"
                placeholder="Ir a una tabla…" aria-label="Buscar tabla"
                x-model="query" @change="goToTable(query)"
            >
            <datalist id="dbmap-tables">
                <template x-for="t in tables" :key="t.name">
                    <option :value="t.name" x-text="t.name"></option>
                </template>
            </datalist>
        </div>

        {{-- ── Grafo ── --}}
        <div class="dbmap__canvas" x-ref="canvas"></div>

        {{-- ── Detalle ── --}}
        <div class="dbmap__detail">
            {{-- Sin selección: hint según el nivel --}}
            <template x-if="!selected">
                <div class="dbmap__empty">
                    <template x-if="view === 'modules'">
                        <div class="dbmap__fade">
                            <div style="font-size:1.6rem; line-height:1;">🗂️</div>
                            <strong>Explora la base de datos por módulo</strong>
                            <span x-text="`${modules.length} módulos · ${tables.length} tablas · ${foreignKeys.length} relaciones`"></span>
                            <span>Haz clic en un módulo del grafo para ver sus tablas, o busca una tabla arriba.</span>
                        </div>
                    </template>
                    <template x-if="view === 'tables'">
                        <div class="dbmap__fade">
                            <strong x-text="`Módulo ${activeModule}`"></strong>
                            <span>Haz clic en una tabla del grafo para ver sus columnas, relaciones e índices.</span>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Con tabla seleccionada --}}
            <template x-if="selected">
                <div class="dbmap__fade" :key="selected.name">
                    <div class="dbmap__detail-head">
                        <h3 x-text="selected.name"></h3>
                        <span class="dbmap__tag" x-show="selected.is_tenant">multi-empresa · empresa_id</span>
                        <span class="dbmap__tag dbmap__tag--muted" x-show="!selected.is_tenant">global</span>
                        <span class="dbmap__tag dbmap__tag--muted" x-text="selected.module"></span>
                    </div>

                    <div class="dbmap__section-label" x-text="`Columnas · ${selected.columns.length}`"></div>
                    <table>
                        <thead><tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr></thead>
                        <tbody>
                            <template x-for="c in selected.columns" :key="c.name">
                                <tr>
                                    <td :class="{ 'dbmap__col-key': c.name === 'id' || c.name === 'empresa_id' }" x-text="c.name"></td>
                                    <td><code x-text="c.type + (c.length ? '(' + c.length + ')' : '')"></code></td>
                                    <td class="dbmap__muted" x-text="c.nullable ? 'sí' : 'no'"></td>
                                    <td class="dbmap__muted"><code x-text="c.default ?? '—'"></code></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    {{-- Relaciones (FK) --}}
                    <div class="dbmap__section-label"
                         x-text="`Relaciones · ${relationsOut.length + relationsIn.length}`"></div>
                    <template x-if="relationsOut.length === 0 && relationsIn.length === 0">
                        <p class="dbmap__muted" style="font-size:.82rem;">Sin llaves foráneas.</p>
                    </template>
                    <div class="dbmap__rels" x-show="relationsOut.length || relationsIn.length">
                        <div x-show="relationsOut.length">
                            <div class="dbmap__muted" style="font-size:.75rem; margin-bottom:.4rem;">Apunta a →</div>
                            <table>
                                <tbody>
                                    <template x-for="r in relationsOut" :key="r.constraint">
                                        <tr>
                                            <td><code x-text="r.from_column"></code></td>
                                            <td>
                                                <button type="button" class="dbmap__rel-link"
                                                        @click="goToTable(r.to_table)" x-text="r.to_table"></button>
                                                <span class="dbmap__muted">.<span x-text="r.to_column"></span></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="relationsIn.length">
                            <div class="dbmap__muted" style="font-size:.75rem; margin-bottom:.4rem;">← Referenciada por</div>
                            <table>
                                <tbody>
                                    <template x-for="r in relationsIn" :key="r.constraint">
                                        <tr>
                                            <td>
                                                <button type="button" class="dbmap__rel-link"
                                                        @click="goToTable(r.from_table)" x-text="r.from_table"></button>
                                                <span class="dbmap__muted">.<span x-text="r.from_column"></span></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="dbmap__section-label">Constraints</div>
                    <table>
                        <thead><tr><th>Tipo</th><th>Definición</th></tr></thead>
                        <tbody>
                            <template x-for="k in selected.constraints" :key="k.name">
                                <tr>
                                    <td class="dbmap__muted" style="white-space:nowrap;" x-text="k.type"></td>
                                    <td><code x-text="k.definition"></code></td>
                                </tr>
                            </template>
                            <template x-if="selected.constraints.length === 0">
                                <tr><td colspan="2" class="dbmap__muted">Sin constraints declaradas.</td></tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="dbmap__section-label" x-text="`Índices · ${selected.indexes.length}`"></div>
                    <table>
                        <thead><tr><th>Nombre</th><th>Definición</th></tr></thead>
                        <tbody>
                            <template x-for="i in selected.indexes" :key="i.name">
                                <tr>
                                    <td style="white-space:nowrap;" x-text="i.name"></td>
                                    <td><code x-text="i.definition"></code></td>
                                </tr>
                            </template>
                            <template x-if="selected.indexes.length === 0">
                                <tr><td colspan="2" class="dbmap__muted">Sin índices.</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>

    @push('scripts')
    <script>
        function databaseMap(schema) {
            // Paleta light — coherente con el panel Admin (amber + slate).
            const C = {
                nodeBg: '#ffffff', nodeBorder: '#cbd5e1', ink: '#0f172a',
                tenantBg: '#fffbeb', tenantBorder: '#fcd34d',
                accent: '#f59e0b', edge: '#cbd5e1', edgeHi: '#f59e0b',
            };

            // La instancia de vis.Network NO puede vivir en el objeto reactivo de Alpine:
            // usa campos privados de clase (#) y el Proxy reactivo rompe su acceso
            // ("Private element is not present on this object"). Vive en el closure.
            let net = null;

            return {
                tables: schema.tables,
                foreignKeys: schema.foreignKeys,
                modules: schema.modules,
                moduleLinks: schema.moduleLinks,

                view: 'modules',      // 'modules' | 'tables'
                activeModule: null,
                selected: null,
                relationsOut: [],
                relationsIn: [],
                query: '',
                reduce: false,

                init() {
                    this.reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    // Cargamos vis-network dinámicamente: fiable en carga completa y en
                    // navegación SPA de Livewire (donde un <script src> estático puede no
                    // ejecutarse en orden). onload/onerror distinguen red vs. librería.
                    this.loadVis()
                        .then(() => this.buildNetwork())
                        .catch((msg) => this.showCanvasError(msg));
                },

                loadVis() {
                    const src = "{{ asset('js/vendor/vis-network.min.js') }}";
                    return new Promise((resolve, reject) => {
                        if (window.vis?.Network) return resolve();

                        const done = () =>
                            window.vis?.Network ? resolve() : reject('cargó pero no expuso «vis» (posible conflicto de módulos)');

                        let s = document.getElementById('vis-network-lib');
                        if (s) { s.addEventListener('load', done); s.addEventListener('error', () => reject('no se pudo descargar la librería')); return; }

                        s = document.createElement('script');
                        s.id = 'vis-network-lib';
                        s.src = src;
                        s.onload = done;
                        s.onerror = () => reject('no se pudo descargar la librería (' + src + ')');
                        document.head.appendChild(s);
                    });
                },

                showCanvasError(msg) {
                    this.$refs.canvas.innerHTML =
                        '<div style="display:flex;height:100%;align-items:center;justify-content:center;color:#64748b;font-size:.9rem;padding:1rem;text-align:center;">Grafo no disponible: ' +
                        (msg || 'vis-network') + '</div>';
                },

                buildNetwork() {
                    net = new vis.Network(this.$refs.canvas, { nodes: [], edges: [] }, this.baseOptions());
                    net.on('click', (p) => {
                        if (!p.nodes.length) return;
                        this.view === 'modules' ? this.openModule(p.nodes[0]) : this.selectTable(p.nodes[0]);
                    });
                    this.showModules();
                },

                baseOptions() {
                    return {
                        nodes: {
                            shape: 'box',
                            borderWidth: 1.5,
                            shapeProperties: { borderRadius: 8 },
                            margin: { top: 9, bottom: 9, left: 14, right: 14 },
                            font: { color: C.ink, face: 'Sansation', size: 14 },
                            color: {
                                background: C.nodeBg, border: C.nodeBorder,
                                highlight: { background: C.tenantBg, border: C.accent },
                                hover: { background: C.tenantBg, border: C.accent },
                            },
                        },
                        edges: {
                            arrows: { to: { enabled: true, scaleFactor: .55 } },
                            color: { color: C.edge, highlight: C.edgeHi, hover: C.edgeHi },
                            smooth: { type: 'cubicBezier', roundness: .5 },
                            selectionWidth: 1.5,
                        },
                        interaction: { hover: true, tooltipDelay: 120, zoomView: true, navigationButtons: false },
                        physics: {
                            enabled: true,
                            stabilization: { enabled: true, iterations: this.reduce ? 1 : 220 },
                            barnesHut: { springLength: 150, avoidOverlap: 0.85, gravitationalConstant: -8000 },
                        },
                    };
                },

                // ── Nivel 0: módulos ──
                showModules() {
                    const nodes = this.modules.map(m => ({
                        id: 'mod:' + m.name,
                        label: `${m.name}\n${m.table_count} tablas`,
                        font: { color: C.ink, face: 'Sansation', size: 15, multi: false },
                        title: `${m.name} · ${m.table_count} tablas (${m.tenant_count} multi-empresa)`,
                    }));
                    const edges = this.moduleLinks.map(l => ({
                        from: 'mod:' + l.from_module,
                        to: 'mod:' + l.to_module,
                        width: Math.min(1 + l.count / 3, 5),
                        title: `${l.count} relación(es)`,
                    }));
                    net.setData({ nodes, edges });
                    net.setOptions(this.baseOptions());
                },

                openModule(nodeId) {
                    const name = nodeId.replace(/^mod:/, '');
                    this.activeModule = name;
                    this.view = 'tables';
                    this.selected = null;
                    this.renderModuleTables(name);
                },

                // ── Nivel 1: tablas del módulo ──
                renderModuleTables(moduleName) {
                    const inModule = this.tables.filter(t => t.module === moduleName);
                    const names = new Set(inModule.map(t => t.name));
                    const nodes = inModule.map(t => ({
                        id: t.name,
                        label: t.name,
                        color: t.is_tenant
                            ? { background: C.tenantBg, border: C.tenantBorder,
                                highlight: { background: '#fef3c7', border: C.accent }, hover: { background: '#fef3c7', border: C.accent } }
                            : undefined,
                        title: `${t.columns.length} columnas`,
                    }));
                    // Solo aristas internas del módulo (las cruzadas viven en el detalle).
                    const edges = this.foreignKeys
                        .filter(fk => names.has(fk.from_table) && names.has(fk.to_table) && fk.from_table !== fk.to_table)
                        .map(fk => ({ from: fk.from_table, to: fk.to_table, title: `${fk.from_column} → ${fk.to_column}` }));
                    net.setData({ nodes, edges });
                    net.setOptions(this.baseOptions());
                },

                selectTable(name) {
                    const t = this.tables.find(x => x.name === name);
                    if (!t) return;
                    this.selected = t;
                    this.relationsOut = this.foreignKeys.filter(fk => fk.from_table === name);
                    this.relationsIn = this.foreignKeys.filter(fk => fk.to_table === name && fk.from_table !== name);
                    if (net) {
                        net.selectNodes([name]);
                        net.focus(name, { scale: 1.1, animation: !this.reduce });
                    }
                },

                // Búsqueda / navegación por relaciones: salta al módulo y selecciona la tabla.
                goToTable(name) {
                    const t = this.tables.find(x => x.name === name);
                    if (!t) return;
                    if (this.activeModule !== t.module) {
                        this.activeModule = t.module;
                        this.view = 'tables';
                        this.renderModuleTables(t.module);
                        // Esperar al estabilizado antes de enfocar el nodo.
                        net.once('stabilizationIterationsDone', () => this.selectTable(name));
                        net.stabilize();
                    } else {
                        this.selectTable(name);
                    }
                    this.query = '';
                },

                backToModules() {
                    if (this.view === 'modules') return;
                    this.view = 'modules';
                    this.activeModule = null;
                    this.selected = null;
                    this.showModules();
                },
            };
        }
    </script>
    @endpush
</x-filament-panels::page>
