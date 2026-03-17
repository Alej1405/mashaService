# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Setup completo (install, .env, key, migrate, npm install + build)
composer setup

# Desarrollo (servidor PHP, queue, logs pail y Vite en paralelo)
composer dev

# Tests
composer test
php artisan test --filter=NombreDelTest   # Test individual

# Assets
npm run dev    # Vite dev server
npm run build  # Build producción

# Migraciones y seeders
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed
```

## Arquitectura General

ERP multi-tenant construido con **Laravel 12 + Filament 3**. Cada organización es una "Empresa" (tenant).

### Dos paneles Filament

| Panel | URL | Namespace | Propósito |
|-------|-----|-----------|-----------|
| Admin | `/admin` | `App\Filament\Resources\` | Super-administrador: gestión de empresas y usuarios |
| App   | `/app/{empresa-slug}` | `App\Filament\App\Resources\` | Usuarios operativos dentro de su empresa |

### Multi-tenancy

- La clase tenant es `Empresa` (usa `slug` como route key en URLs).
- **`HasEmpresa` trait** (`app/Traits/HasEmpresa.php`): todos los modelos operativos lo usan. Aplica automáticamente `EmpresaScope` que filtra por `empresa_id` usando `Filament::getTenant()` o `auth()->user()->empresa_id`.
- Regla crítica: **siempre filtrar por `empresa_id = Filament::getTenant()->id`** en queries manuales dentro del panel App.

### Módulos del sistema

- **Contabilidad**: `AccountPlan` (plan de cuentas), `AccountingMap` (mapeos), `JournalEntry` / `JournalEntryLine` (asientos)
- **Inventario**: `InventoryItem`, `InventoryMovement`, `MeasurementUnit`
- **Compras**: `Purchase`, `PurchaseItem`, `Supplier`
- **Ventas**: `Sale`, `SaleItem`, `Customer`
- **Manufactura**: `ProductionOrder`, `ProductionMaterial`
- **Tesorería**: `BankAccount`, `CashRegister`, `CashSession`, `CreditCard`, `CreditCardMovement`, `CashMovement`

### Flujo de contabilidad automática

`AccountingService` (`app/Services/AccountingService.php`) centraliza toda la lógica contable:
- `generarAsientoCompra`, `generarAsientoVenta`, `generarAsientoProduccion`, `generarAsientoAjuste`
- `getMapeo($empresaId, $tipoItem, $tipoMovimiento)` → devuelve el `AccountPlan` correcto usando `AccountingMap` con fallback a mapeos globales
- Los asientos se generan automáticamente vía Observers al confirmar una compra/venta/producción

Observers registrados en `AppServiceProvider`: `EmpresaObserver`, `PurchaseObserver`, `SaleObserver`, `InventoryMovementObserver`, `ProductionOrderObserver`, `CashMovementObserver`.

### Autorización

Spatie Permission (`spatie/laravel-permission`). El rol `super_admin` bypasea todos los gates. Gate `view-reports` requiere `super_admin` o permiso `reportes.ver`.

### Sistema de planes de suscripción

La columna `plan` en `Empresa` controla el acceso a módulos: `basic` | `pro` | `enterprise`.

- **Helper**: `\App\Helpers\PlanHelper::can('pro')` → compara niveles (basic=1, pro=2, enterprise=3).
- **Regla obligatoria**: Todo Resource o Page nuevo del panel App **debe** incluir:
  ```php
  public static function canAccess(): bool
  {
      return \App\Helpers\PlanHelper::can('pro');
  }
  ```
- **Plan basic**: Solo ve `MailgunDashboard` (grupo "Mailing"). Sin acceso a módulos ERP.
- **Plan pro**: Acceso completo al ERP (contabilidad, inventario, compras, ventas, manufactura, tesorería, informes).
- **Plan enterprise**: Funcionalidades futuras (actualmente igual que pro).
- **Configuración Mailgun por empresa**: en `/app/{slug}/profile` (EditEmpresaProfile). Campos: `mailgun_api_key`, `mailgun_domain`, `mailgun_from_email`, `mailgun_from_name`.
- **Regla crítica**: **NO tocar** `AccountingService`, Observers ni flujo contable al modificar planes.

## Idioma

Responde siempre en español sin excepción.

## Convenciones importantes

- **No usar** la relación `journalEntryLines` directamente desde `AccountPlan` (no existe); hacer query directa a `JournalEntryLine`.
- Números de documentos auto-generados: compras `COM-YYYY-#####`, ventas `VEN-YYYY-#####`.
- Todos los recursos del panel App van en `app/Filament/App/Resources/` con namespace `App\Filament\App\Resources`.
- Base de datos por defecto en desarrollo: SQLite (`database/database.sqlite`).
- Tests usan SQLite en memoria (ver `phpunit.xml`).
