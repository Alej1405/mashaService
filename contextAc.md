# Contexto de Actualización Mashaec ERP (contextAc.md)

Este archivo sirve como memoria flash para el asistente AI, asegurando que cada nueva intervención comprenda el estado exacto del proyecto y no repita errores de comunicación o técnicos previos.

---

## 🕒 Última Sincronización: 12 de Marzo, 2026 - 19:30

### 🎯 Estado de la Misión
Estamos estabilizando el sistema multi-tenant **Mashaec ERP**. El enfoque actual es la gestión de usuarios y la resolución de errores en el acceso al dashboard del panel `/app`.

### 🛠️ Lo que hemos hecho hoy (NO REPETIR)
1.  **Limpieza de Marca:** Estandarizado a **Mashaec ERP**.
2.  **Restauración de Panel:** Panel `/app` operativo.
3.  **Gestión de Usuarios:** Corregida relación en `UserResource` a `name`.
4.  **Prueba Funcional Exitosa:** Validado flujo de Compra -> Stock -> Contabilidad.
5.  **Manual de Usuario:** Creado `manualUsuario.md`.
6.  **Módulo de Informes:** Implementados y refactorizados los 6 informes financieros. El reporte de **Flujo de Caja** ya cuenta con lógica real detallada por cuenta de efectivo (12 de Marzo - 21:30).

### ⚠️ Incidentes Superados
- **Relaciones Inexistentes:** Corregido fallo en `AccountPlan` donde se intentaba acceder a `journalEntryLines` directamente.

### 📝 Pendientes Próximos
- [x] **Verificación Previa Ventas:** Estructura de directorios y configuración de PanelProvider auditada (12 de Marzo - 21:55).
- [ ] **Diagnóstico Error 500:** Investigar por qué el Dashboard de `/app/{tenant}` falla tras el login (auditar Widgets y Pages personalizadas).
- [ ] **Limpieza de Datos de Prueba:** Opcionalmente eliminar los datos de prueba COM-2026-00002.
- [x] Implementación Módulo de Ventas (Estructura base, Modelos, Observer, Lógica Contable, Resources)
- [ ] Ejecutar migraciones módulo de ventas (Pendiente aprobación SQL)
- [ ] Pruebas funcionales de ventas con datos de prueba
- [ ] Dashboards adicionales de ventas
mespace verificados.
- [ ] **Verificación de Exportación:** Validar que los reportes permitan exportación a PDF/Excel con los nuevos filtros.

### 📜 Prompt de Reinicio Rápido
"Lee `logsErp.md` para el historial técnico y `contextAc.md` para el estado operativo actual. Mantén siempre el nombre **Mashaec ERP**. No intentes regenerar archivos que ya están marcados como restaurados hoy."

---
