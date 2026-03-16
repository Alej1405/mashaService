# 🤖 Instrucciones para Traspaso de IA (Mashaec ERP)

Si eres una nueva IA tomando este proyecto, este archivo contiene todo lo que necesitas para continuar sin errores.

## 📁 Archivos que DEBES leer primero:
1.  **`logsErp.md`**: Historial técnico completo, reglas de oro y lista de archivos prohibidos/permitidos.
2.  **`contextAc.md`**: Estado actual de la misión y prompt de reinicio rápido.
3.  **`manualUsuario.md`**: Guía operativa del sistema para entender el flujo de negocio.

## 🚀 Resumen del Estado Actual
- **Proyecto:** ERP Multi-tenant basado en Filament PHP 3.
- **Marca Oficial:** **Mashaec ERP** (Se escribe con 's').
- **Módulos Estabilizados:** 
    - Ventas (Flujo completo validado).
    - Contabilidad (Asientos automáticos y robustez en `getMapeo`).
    - Informes Financieros (6 reportes con filtros reactivos).
    - Manufactura (Validación estricta de stock e indicadores reactivos).
- **Último Hito:** Refactorización estética **Glassmorphism** y optimización de UX con creación rápida "on-the-fly" en todos los módulos clave.

## ⚠️ Reglas Críticas de Desarrollo
- **Acceso a Datos:** Siempre filtrar por `empresa_id = Filament::getTenant()->id`.
- **Informes:** Ningún informe debe usar la relación `journalEntryLines` desde `AccountPlan` (no existe). Usa consultas directas a `JournalEntryLine`.
- **Estructura Filament:** 
    - Resources: `app/Filament/App/Resources` (Namespace: `App\Filament\App\Resources`)
    - Pages: `app/Filament/App/Pages` (Namespace: `App\Filament\App\Pages`)

## 📝 Pendientes Inmediatos
1.  **Diagnóstico:** Investigar Error 500 en el Dashboard del panel `/app` tras el login.
2.  **Mantenimiento**: Limpiar registros de prueba (Dry-Run) si se desea un entorno de producción puro.
3.  **Cambio de Tema**: Pendiente transición estética según requerimiento del usuario.

---
*Este documento fue generado por el asistente previo para asegurar la continuidad del proyecto.*
