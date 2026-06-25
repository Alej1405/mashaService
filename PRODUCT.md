# Product

## Register

product

## Users

Super-administrador del sistema (único usuario: `admin@mashaec.net`). Rol: operar y supervisar la plataforma ERP multi-tenant. Contexto de uso: pantalla de escritorio, entorno de oficina, sesiones de trabajo de 30–90 min, múltiples empresas cliente activas simultáneamente. Tarea primaria en cualquier sesión: verificar estado de las empresas, activar/configurar servicios, revisar facturación, responder incidentes.

## Product Purpose

Panel de control para el operador de Masha Corp S.A.S. que le permite gestionar las empresas cliente que usan el ERP: activar/desactivar módulos de servicio (9 módulos × N sub-servicios), controlar planes de suscripción, monitorear sesiones activas, revisar facturación del servicio SaaS, y atender eventos del sistema. El éxito se mide en: tiempo para activar un nuevo módulo a una empresa < 30 segundos, visibilidad del estado global de todas las empresas en menos de 5 segundos de carga.

## Brand Personality

Preciso. Confiable. Eficiente. Tono: operacional neutro, sin decoración innecesaria. El panel debe sentirse como una herramienta profesional que desaparece para dejar al operador en la tarea, no como un producto de marketing.

## Anti-references

- Filament default: tablas aburridas con filas infinitas sin contexto visual
- SaaS dashboard genérico: hero-metrics con números enormes sobre gradientes
- Notion/ClickUp complejidad innecesaria para lo que es una herramienta de un solo usuario
- Colores saturados por decoración, glassmorphism sin función
- Múltiples dashboards fragmentados por módulo (estado actual a corregir)

## Design Principles

1. **Una sola fuente de verdad**: un dashboard unificado que sintetiza el estado completo de la plataforma, no tres dashboards fragmentados.
2. **Acción directa**: cada elemento de información debe tener una acción clara adjunta o no debería estar ahí.
3. **Densidad funcional**: mostrar más con menos clics. Las tablas de Filament son correctas para CRUD; los dashboards necesitan visualizaciones que sumen contexto.
4. **Navegación limpia**: cero duplicados, cero items redundantes. Si dos cosas hacen lo mismo, una sobra.
5. **Amber como firma**: el color primario amber (#f59e0b) es la única señal de identidad — se usa solo para acciones primarias, estados activos, y alertas de alto valor. Todo lo demás es neutro.

## Accessibility & Inclusion

WCAG AA. Un solo usuario administrador experimentado, sin necesidades especiales conocidas. Contraste mínimo 4.5:1 en texto de datos. Soporte para prefers-reduced-motion en transiciones de dashboard.
