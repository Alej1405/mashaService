# 🤖 Instrucciones para Traspaso de IA (Mashaec ERP)

Si eres una nueva IA tomando este proyecto, este archivo contiene todo lo que necesitas para continuar sin errores.

## 📁 Archivos que DEBES leer primero:
1. **`logsErp.md`**: Historial técnico completo, reglas de oro y lista de archivos prohibidos/permitidos.
2. **`contextAc.md`**: Estado actual de la misión y prompt de reinicio rápido.
3. **`CLAUDE.md`**: Instrucciones de arquitectura general del proyecto.

---

## 🚀 Resumen del Estado Actual (16 de Marzo, 2026)

- **Proyecto:** ERP Multi-tenant SaaS basado en Laravel 12 + Filament 3.
- **Marca Oficial:** **Mashaec ERP** (Se escribe con 's', no 'z').
- **Modelo de Negocio:** Mashaec distribuye el ERP mediante **planes de suscripción** (Basic / Pro / Enterprise).
- **Tenant:** Modelo `Empresa` — slug usado como route key en `/app/{empresa-slug}`.

### Módulos Estabilizados (Plan Pro):
- Contabilidad — Plan de cuentas CUC Supercias, asientos automáticos
- Inventario — Importación XML SRI, movimientos
- Compras / Ventas — Ciclo completo con asientos
- Manufactura — Órdenes de producción
- Tesorería — Cajas, bancos, tarjetas de crédito
- Informes Financieros — 6 reportes con export Excel formato Supercias

---

## ⚠️ Reglas Críticas de Desarrollo

### Acceso a datos
- SIEMPRE filtrar por `empresa_id = Filament::getTenant()->id` en el panel App.
- Usar `withoutGlobalScopes()` en queries manuales sobre modelos con `HasEmpresa`.
- Saldo cuenta deudora: `SUM(debe) - SUM(haber)`. Saldo acreedora: `SUM(haber) - SUM(debe)`.

### Filament
- Panel Admin: `app/Filament/Resources/` | namespace `App\Filament\Resources`
- Panel App: `app/Filament/App/Resources/` | namespace `App\Filament\App\Resources`
- Páginas App: `app/Filament/App/Pages/` | namespace `App\Filament\App\Pages`
- **No usar** relación `journalEntryLines` desde `AccountPlan` — no existe.

### Base de datos
- DB por defecto en dev: MariaDB (`erpMasha`).
- No modificar migraciones existentes — solo crear nuevas.
- `bank_accounts` no tiene `saldo_actual`, solo `saldo_inicial`.
- `Empresa` columna es `name` (no `nombre`).

### Roles (Spatie)
- `super_admin` bypasea todos los gates.
- Rol `admin_empresa` para administradores de tenant.
- Gate `view-reports` requiere `super_admin` o permiso `reportes.ver`.

---

## 🗺️ Plan de Suscripciones (Diseño Acordado — PENDIENTE IMPLEMENTAR)

### Columna `plan` en `empresas`
ENUM: `basic | pro | enterprise` — default `pro` para empresas existentes.

### Distribución de acceso por plan

| Funcionalidad | Basic | Pro | Enterprise |
|---------------|-------|-----|------------|
| Dashboard Mailgun | ✅ | ✅ | ✅ |
| Contabilidad | ❌ | ✅ | ✅ |
| Inventario | ❌ | ✅ | ✅ |
| Compras | ❌ | ✅ | ✅ |
| Ventas | ❌ | ✅ | ✅ |
| Manufactura | ❌ | ✅ | ✅ |
| Tesorería | ❌ | ✅ | ✅ |
| Informes Financieros | ❌ | ✅ | ✅ |
| Funciones Enterprise | ❌ | ❌ | ✅ |

### Mecanismo de control
- Columna `plan` en `empresas` → valor: `basic | pro | enterprise`.
- Helper `PlanHelper::can(string $minimumPlan): bool` compara niveles.
- Filament: `->visible(fn() => PlanHelper::can('pro'))` en recursos/páginas del panel App.
- Spatie permissions para control granular dentro de un plan (permisos de usuario, no de plan).

### Archivos a crear
- `database/migrations/XXXX_add_plan_to_empresas_table.php`
- `app/Helpers/PlanHelper.php`
- `app/Filament/App/Pages/MailgunDashboard.php`
- `resources/views/filament/app/pages/mailgun-dashboard.blade.php`
- Actualizar `app/Filament/Pages/Tenancy/RegisterEmpresa.php` (campo plan)
- Actualizar `app/Filament/Resources/EmpresaResource.php` (campo plan en Admin)
- Actualizar cada Resource/Page del panel App para agregar `->visible(fn() => PlanHelper::can('pro'))`

### Archivos a NO tocar
- `AppPanelProvider.php` — solo agregar navigation groups condicionalmente
- `AppServiceProvider.php` — observers, no tocar
- `AccountingService.php` — lógica contable, no tocar
- Modelos existentes — solo agregar `plan` a Empresa y `$fillable`

---

## 📝 Pendientes Actuales

1. **[PRIORIDAD ALTA]** Implementar sistema de planes de suscripción (ver sección anterior).
2. Exportación Excel para Balance de Comprobación (único reporte sin export aún).
3. Dashboard Ejecutivo del panel App — mejorar widgets.
