# Contexto de Actualización Mashaec ERP (contextAc.md)

Este archivo sirve como memoria flash para el asistente AI, asegurando que cada nueva intervención comprenda el estado exacto del proyecto y no repita errores de comunicación o técnicos previos.

---

## 🕒 Última Sincronización: 16 de Marzo, 2026 — 23:00

### 🎯 Estado de la Misión
**Mashaec ERP** es un sistema ERP multi-tenant SaaS que **Mashaec distribuye mediante planes de suscripción**. El enfoque actual es implementar control de acceso por plan (Basic / Pro / Enterprise) usando Spatie Permission + columna `plan` en Empresa, sin romper lo existente.

---

### ✅ Módulos Completados y Estables (Plan Pro)

| Módulo | Estado | Notas |
|--------|--------|-------|
| Contabilidad (Plan de Cuentas, Asientos) | ✅ Estable | CUC Supercias, auto-asientos vía observers |
| Inventario | ✅ Estable | Importación XML SRI, movimientos automáticos |
| Compras | ✅ Estable | COM-YYYY-#####, asiento automático |
| Ventas | ✅ Estable | VEN-YYYY-#####, COGS automático |
| Manufactura | ✅ Estable | Órdenes de producción, transformación de materiales |
| Tesorería | ✅ Estable | Cajas, sesiones, tarjetas de crédito, movimientos |
| Bancos | ✅ Estable | Catálogo 113 instituciones Ecuador |
| Informes Financieros | ✅ Estable | 6 reportes con export Excel formato Supercias |

### ✅ Informes Financieros — Exportación Supercias Implementada

| Reporte | Exportación | Clase Export |
|---------|-------------|--------------|
| Balance General | ✅ Excel | `BalanceGeneralExport.php` |
| Estado de Resultados | ✅ Excel | `EstadoResultadosExport.php` |
| Flujo de Caja | ✅ Excel | `FlujoCajaExport.php` |
| Libro Diario | ✅ Excel | `LibroDiarioExport.php` |
| Libro Mayor | ✅ Excel | `LibroMayorExport.php` |
| Balance de Comprobación | — | Pendiente export |

### ✅ Balance General — Datos verificados (2026-03-16)

```
Activos corrientes:
  1.1.01.01  Caja general             -$19.50
  1.1.01.03  Bancos cuenta corriente  $5,000.00
  1.1.03.02  Inventario mat. primas     $16.96
  1.1.05.01  Crédito tributario IVA      $2.54
Patrimonio:
  3.1.01     Capital suscrito          $5,000.00
CUADRADO ✅ ($5,000 = $5,000)
```

---

### 🏗️ Próxima Tarea: Sistema de Planes de Suscripción

**Objetivo**: Controlar acceso por plan desde el modelo `Empresa` usando Spatie Permission.

| Plan | Acceso |
|------|--------|
| `basic` | Solo dashboard Mailgun |
| `pro` | Todos los módulos ERP actuales |
| `enterprise` | Pro + funciones futuras |

**Archivos de referencia del plan**: Ver `handoff.md` sección "Plan Suscripciones".

---

### ⚠️ Reglas Críticas que NO Cambiar

- `AccountingService` — no modificar métodos existentes
- `AppServiceProvider` — observers registrados, no tocar
- `HasEmpresa` trait + `EmpresaScope` — filtrado multi-tenant, no tocar
- `AccountPlan` — no agregar relación `journalEntryLines` (no existe)
- Migraciones existentes — no modificar, solo agregar nuevas
- Empresa columna principal es `name` (no `nombre`)
- `BankAccount` no tiene `saldo_actual`, solo `saldo_inicial`

---

### 📜 Prompt de Reinicio Rápido

"Lee `logsErp.md` para el historial técnico y `contextAc.md` para el estado operativo actual. El proyecto es **Mashaec ERP** (con 's'). Es un SaaS multi-tenant Laravel 12 + Filament 3. Tenant = Empresa. Hay dos paneles: `/admin` (super_admin) y `/app/{empresa-slug}` (usuarios). El plan actual tiene tres niveles: basic, pro, enterprise. Lee `handoff.md` para las reglas de continuidad."
