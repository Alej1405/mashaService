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
                --accent: #6366f1;
                --surface: rgba(255, 255, 255, 0.04);
                --border: rgba(255, 255, 255, 0.08);
                --text-dim: rgba(255, 255, 255, 0.55);
                font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            }
            .dbmap__bar {
                display: flex; gap: .75rem; align-items: center;
                flex-wrap: wrap; margin-bottom: .75rem;
            }
            .dbmap__search {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: .625rem;
                color: inherit; padding: .5rem .75rem; min-width: 14rem;
                font-size: .875rem;
            }
            .dbmap__search:focus-visible {
                outline: 2px solid var(--accent); outline-offset: 2px;
            }
            .dbmap__legend { display: flex; gap: 1rem; font-size: .75rem; color: var(--text-dim); }
            .dbmap__dot { display: inline-block; width: .65rem; height: .65rem; border-radius: 50%; margin-right: .35rem; vertical-align: middle; }
            .dbmap__canvas {
                height: 460px; width: 100%;
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 1rem;
                backdrop-filter: blur(10px);
            }
            .dbmap__detail {
                margin-top: 1rem;
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 1rem;
                backdrop-filter: blur(10px);
                padding: 1.25rem;
            }
            .dbmap__detail h3 { font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; gap: .5rem; }
            .dbmap__tag {
                font-size: .65rem; font-weight: 600; letter-spacing: .03em;
                padding: .12rem .45rem; border-radius: .375rem;
                background: color-mix(in srgb, var(--accent) 22%, transparent);
                color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
            }
            .dbmap__section-label {
                font-size: .7rem; text-transform: uppercase; letter-spacing: .06em;
                color: var(--text-dim); margin: 1.1rem 0 .4rem;
            }
            .dbmap table { width: 100%; border-collapse: collapse; font-size: .8rem; }
            .dbmap th { text-align: left; color: var(--text-dim); font-weight: 500; padding: .35rem .5rem; border-bottom: 1px solid var(--border); }
            .dbmap td { padding: .35rem .5rem; border-bottom: 1px solid var(--border); }
            .dbmap code { font-family: ui-monospace, monospace; font-size: .75rem; }
            .dbmap__col-key { color: var(--accent); font-weight: 600; }
            .dbmap__muted { color: var(--text-dim); }
            .dbmap__empty { color: var(--text-dim); font-size: .85rem; padding: 2rem 0; text-align: center; }
        </style>

        <div class="dbmap__bar">
            <input
                type="text" list="dbmap-tables" class="dbmap__search"
                placeholder="Buscar tabla…" aria-label="Buscar tabla"
                x-model="query" @change="selectByName(query)"
            >
            <datalist id="dbmap-tables">
                <template x-for="t in tables" :key="t.name">
                    <option :value="t.name"></option>
                </template>
            </datalist>

            <div class="dbmap__legend">
                <span><span class="dbmap__dot" style="background: var(--accent)"></span>Con empresa_id (tenant)</span>
                <span><span class="dbmap__dot" style="background: #64748b"></span>Sin tenant</span>
            </div>
        </div>

        <div class="dbmap__canvas" x-ref="canvas"></div>

        <div class="dbmap__detail">
            <template x-if="!selected">
                <div class="dbmap__empty">Haz clic en una tabla del grafo (o búscala arriba) para ver columnas, constraints e índices.</div>
            </template>

            <template x-if="selected">
                <div>
                    <h3>
                        <span x-text="selected.name"></span>
                        <span class="dbmap__tag" x-show="selected.is_tenant">tenant · empresa_id</span>
                    </h3>

                    <div class="dbmap__section-label">Columnas</div>
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

                    <div class="dbmap__section-label">Constraints</div>
                    <table>
                        <thead><tr><th>Tipo</th><th>Definición</th></tr></thead>
                        <tbody>
                            <template x-for="k in selected.constraints" :key="k.name">
                                <tr>
                                    <td class="dbmap__muted" x-text="k.type"></td>
                                    <td><code x-text="k.definition"></code></td>
                                </tr>
                            </template>
                            <template x-if="selected.constraints.length === 0">
                                <tr><td colspan="2" class="dbmap__muted">Sin constraints declaradas.</td></tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="dbmap__section-label">Índices</div>
                    <table>
                        <thead><tr><th>Nombre</th><th>Definición</th></tr></thead>
                        <tbody>
                            <template x-for="i in selected.indexes" :key="i.name">
                                <tr>
                                    <td x-text="i.name"></td>
                                    <td><code x-text="i.definition"></code></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>

    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <script>
        function databaseMap(schema) {
            return {
                tables: schema.tables,
                fks: schema.foreignKeys,
                selected: null,
                query: '',
                network: null,

                init() {
                    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    const nodes = this.tables.map(t => ({
                        id: t.name,
                        label: t.name,
                        color: t.is_tenant
                            ? { background: 'rgba(99,102,241,0.18)', border: '#6366f1' }
                            : { background: 'rgba(100,116,139,0.15)', border: '#64748b' },
                        font: { color: '#e2e8f0', face: 'Inter' },
                        shape: 'box',
                        borderWidth: 1.5,
                    }));

                    const edges = this.fks.map(fk => ({
                        from: fk.from_table,
                        to: fk.to_table,
                        arrows: 'to',
                        color: { color: 'rgba(148,163,184,0.45)', highlight: '#6366f1' },
                        smooth: { type: 'cubicBezier' },
                        title: `${fk.from_table}.${fk.from_column} → ${fk.to_table}.${fk.to_column}`,
                    }));

                    this.network = new vis.Network(
                        this.$refs.canvas,
                        { nodes, edges },
                        {
                            layout: { improvedLayout: true },
                            physics: {
                                enabled: true,
                                stabilization: { enabled: true, iterations: reduce ? 1 : 180 },
                                barnesHut: { springLength: 140, avoidOverlap: 0.6 },
                            },
                            interaction: { hover: true, tooltipDelay: 120 },
                        }
                    );

                    this.network.on('click', (params) => {
                        if (params.nodes.length) this.select(params.nodes[0]);
                    });
                },

                select(name) {
                    this.selected = this.tables.find(t => t.name === name) ?? null;
                    if (this.selected && this.network) {
                        this.network.selectNodes([name]);
                        this.network.focus(name, { scale: 1.1, animation: true });
                    }
                },

                selectByName(name) {
                    const match = this.tables.find(t => t.name === name);
                    if (match) this.select(match.name);
                },
            };
        }
    </script>
</x-filament-panels::page>
