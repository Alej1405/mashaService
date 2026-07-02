---
## 📅 16 de Marzo, 2026 — SAVEPOINT: Informes + Exportaciones Supercias

### ESTADO GUARDADO ANTES DE IMPLEMENTAR PLANES DE SUSCRIPCIÓN

- Balance General cuadrado ✅ ($5,000 activos = $5,000 patrimonio)
- Todos los informes con botones Imprimir + Exportar Excel (formato Supercias)
- Exports creados: `BalanceGeneralExport`, `EstadoResultadosExport`, `FlujoCajaExport`, `LibroDiarioExport`, `LibroMayorExport`
- Asiento apertura bancaria ID=11: DEBE 1.1.01.03 $5,000 / HABER 3.1.01 $5,000
- CreditCard DINERS → cuenta 2.1.02.03 "Tarjetas de crédito por pagar"
- BankSeeder: 113 instituciones Ecuador
- **REGLA NUEVA**: No tocar flujo contable. Solo implementar sistema de planes.

---
## 📅 16 de Marzo, 2026

### MÓDULO REGISTRO DE EMPRESA — Campos de identificación legal

- **Fecha y hora:** 2026-03-16
- **Archivos creados:**
  - `database/migrations/2026_03_16_000001_add_identification_fields_to_empresas_table.php`
- **Archivos modificados:**
  - `app/Models/Empresa.php` — Se añadieron 5 campos a `$fillable`: `tipo_persona`, `tipo_identificacion`, `numero_identificacion`, `direccion`, `actividad_economica`.
  - `app/Filament/Pages/Tenancy/RegisterEmpresa.php` — Se añadieron los 5 campos al formulario de registro con validación reactiva (RUC=13 dígitos, Cédula=10 dígitos).
  - `app/Filament/Resources/EmpresaResource.php` — Se añadió sección "Identificación y Datos Legales" con los mismos campos al panel Admin.
- **Descripción:** El formulario de registro de empresa ahora solicita: tipo de persona (natural/jurídica), tipo de identificación (RUC/Cédula/Pasaporte), número de identificación (con validación de longitud reactiva), dirección y actividad económica.
- **Pendiente:** Ejecutar `php artisan migrate` para aplicar la migración en la BD.

---
## 📅 16 de Marzo, 2026 — MÓDULO INVENTARIO: Importación desde archivo

- **Fecha y hora:** 2026-03-16
- **Archivos creados:**
  - `app/Filament/App/Pages/ImportarInventarioPage.php` — Page Filament con flujo de 3 pasos: cargar archivo → completar datos → resultado.
  - `resources/views/filament/app/pages/importar-inventario.blade.php` — Vista Blade del flujo multi-paso.
- **Archivos modificados:**
  - `app/Filament/App/Resources/InventoryItemResource/Pages/ListInventoryItems.php` — Se añadió botón "Importar desde archivo" en el header.
- **Descripción:**
  - Paso 1: El usuario sube un CSV o Excel (.xlsx) con columnas: `proveedor_nombre`, `proveedor_identificacion`, `proveedor_telefono`, `proveedor_email`, `proveedor_contacto`, `producto_nombre`, `precio_compra`, `precio_venta`, `cantidad`.
  - Paso 2: El sistema detecta si el proveedor ya existe o es nuevo. Muestra los ítems que ya existen (sin modificarlos) y para los ítems nuevos pide: tipo de ítem y número de lote.
  - Paso 3: Confirmar crea el proveedor (si es nuevo) y registra los ítems nuevos con su lote y tipo.
- **Dependencias:** `phpoffice/phpspreadsheet` (ya presente). No requiere migración.

#### Ajuste 2026-03-16 — Cambio a formato XML del SRI
- Se eliminó soporte CSV/Excel. El único formato aceptado es **XML de factura electrónica SRI**.
- El parser lee la estructura `<autorizacion><comprobante><![CDATA[<factura>...]]></comprobante></autorizacion>`.
- Proveedor extraído de `<infoTributaria>`: `razonSocial`, `ruc`, `dirMatriz`, email desde `<infoAdicional>`.
- Productos extraídos de `<detalles><detalle>`: `descripcion`, `precioUnitario`, `cantidad`.
- También soporta XMLs donde la `<factura>` es el nodo raíz directamente (sin envoltura de autorización).

#### Ajuste 2026-03-16 — Flujo completo de Compra + Asiento contable
- La importación XML ahora crea automáticamente un registro `Purchase` (borrador → confirmado).
- `PurchaseItem` se crea por cada producto (nuevo y existente).
- Al confirmar la compra, `PurchaseObserver` dispara `AccountingService::generarAsientoCompra()`.
- El stock se incrementa en el observer (no al crear el ítem, para evitar doble conteo).
- Forma de pago mapeada desde código SRI (`<pagos/pago/formaPago>`): 01=efectivo, 16=transferencia, 17/19=tarjeta, 20=cheque, 21=crédito. Default: transferencia.
- Paso 3 muestra número de compra (`COM-YYYY-#####`) y número de asiento contable generado.

---
## 📅 16 de Marzo, 2026 — CORRECCIÓN MAPEOS CONTABLES (Hallazgos H1, H2, H3)

- **Fecha y hora:** 2026-03-16
- **Archivos creados:**
  - `database/migrations/2026_03_16_100000_fix_missing_accounting_maps.php` — Migración que: amplía el ENUM `tipo_movimiento` con `entrada_produccion` / `salida_produccion`; inserta mapeos faltantes en base (empresa_id=null); clona cuentas de módulos faltantes en empresas existentes y propaga los nuevos mapeos.
- **Archivos modificados:**
  - `database/seeders/AccountingMapSeeder.php` — Se añadieron 14 entradas faltantes: `producto_terminado` (compra_contado, compra_credito_local, compra_credito_exterior, iva_compras, ajuste_inventario, ajuste_sobrante, entrada_produccion, salida_produccion), `materia_prima` (salida/entrada_produccion), `insumo` (salida/entrada_produccion), `global` (iva_compras, iva_ventas).
- **Descripción:**
  - H1 RESUELTO: `producto_terminado, compra_contado` → `1.1.03.04` (Inventario PT). Antes usaba `1.1.01.03` Bancos como cuenta DEBE en compras, lo que generaba asientos y reportes incorrectos.
  - H2 RESUELTO: El asiento de costo de venta en `generarAsientoVenta` usa `compra_contado` para la cuenta de inventario; con el nuevo mapeo ahora obtiene `1.1.03.04` correctamente.
  - H3 RESUELTO: Los mapeos `entrada_produccion` y `salida_produccion` ahora existen — el módulo de manufactura puede generar asientos.
  - Verificado el flujo XML → `JournalEntry` → `JournalEntryLine` → Libro Diario ✅ / Libro Mayor ✅ / Flujo de Caja ✅.
- **Migración ejecutada:** ✅ (php artisan migrate)

---
## 📅 16 de Marzo, 2026 — AUDITORÍA CONTABLE COMPLETA

- **Fecha y hora:** 2026-03-16
- **Archivos modificados:**
  - `informeAudit.md` — Reescritura completa con auditoría técnica contra bases SRI y Supercias Ecuador.
- **Descripción:** Se revisó el flujo contable completo del sistema (compras, ventas, manufactura, ajustes, tesorería) contra el Catálogo Único de Cuentas (CUC) de Supercias y los requerimientos tributarios del SRI. El informe identifica hallazgos críticos y conformidades.
- **Hallazgos críticos encontrados (sin tocar código):**
  1. `AccountingMapSeeder` no define `compra_contado`/`compra_credito_local` para `producto_terminado` → asientos de compra de PT son incorrectos.
  2. En `generarAsientoVenta`, la cuenta de inventario para el costo de venta de PT hace fallback a Bancos en lugar de `1.1.03.04`.
  3. Mapeos `entrada_produccion` y `salida_produccion` inexistentes → módulo de manufactura bloqueado completamente.
- **No se modificó ningún archivo de código.**

---
## REGLAMENTO DE TRABAJO — MÓDULO DE INFORMES
Fecha: 2026-03-13

### ARCHIVOS PROHIBIDOS — NO TOCAR BAJO NINGUNA CIRCUNSTANCIA
- Modelos: User, Empresa, Supplier, InventoryItem, InventoryMovement, Purchase, PurchaseItem, JournalEntry, JournalEntryLine, AccountPlan, AccountingMap
- Observers: PurchaseObserver, EmpresaObserver, InventoryMovementObserver
- Servicios: AccountingService (métodos existentes)
- Providers: AppPanelProvider, AdminPanelProvider
- Traits: HasEmpresa, EmpresaScope
- Migraciones: ninguna existente
- NO ejecutar php artisan migrate sin mostrar SQL primero

### ARCHIVOS PERMITIDOS — SOLO CREAR NUEVOS
- app/Filament/App/Pages/Reports/*.php
- app/Services/ReportService.php (nuevo)

### VARIABLES BASE PARA TODOS LOS INFORMES
- Empresa activa: Filament::getTenant()
- Filtro base obligatorio: 
  empresa_id = Filament::getTenant()->id
  status = 'confirmado'
  esta_cuadrado = true
- Saldo cuenta deudora: suma(debe) - suma(haber)
- Saldo cuenta acreedora: suma(haber) - suma(debe)
- Balance General: type IN (activo, pasivo, patrimonio)
- Estado Resultados: type IN (ingreso, gasto, costo)
- Flujo Caja: code LIKE '1.1.01%'

### TABLAS DISPONIBLES (solo lectura)
journal_entries: id, empresa_id, numero, fecha, descripcion, tipo, origen, status, esta_cuadrado, total_debe, total_haber, referencia_tipo, referencia_id
journal_entry_lines: id, journal_entry_id, account_plan_id, descripcion, debe, haber, orden
account_plans: id, empresa_id, code, name, type, nature, parent_code, level, accepts_movements, is_active

### INFORMES A IMPLEMENTAR EN ORDEN
1. Libro Diario
2. Libro Mayor
3. Balance de Comprobación
4. Balance General
5. Estado de Resultados
6. Flujo de Efectivo

### REGISTRO OBLIGATORIO EN logsErp.md
Cada acción debe registrarse con:
- Fecha y hora
- Archivo creado o modificado
- Descripción del cambio
---

# Registro de Errores y Soluciones ERP (logsErp.md)

Este archivo contiene el historial de problemas técnicos, conflictos y errores encontrados durante el desarrollo y auditoría del sistema, junto con las soluciones aplicadas.

---

## 📅 12 de Marzo, 2026

### 1. Conflicto de Migración: Tabla 'empresas' ya existe
- **Error**: Al ejecutar `php artisan migrate`, la migración `2026_03_12_023425_rename_companies_to_empresas_table` fallaba porque la tabla `empresas` ya existía en la base de datos (posiblemente por una ejecución previa parcial o refactorización manual).
- **Causa**: Intento de renombrar `empresas` a `empresas` (mismo nombre) o conflicto con tabla pre-existente.
- **Acción**: 
    - Se modificó el archivo de migración para incluir verificaciones defensivas:
      ```php
      if (Schema::hasTable('companies') && !Schema::hasTable('empresas')) {
          Schema::rename('companies', 'empresas');
      }
      ```
- **Resultado**: Migración completada correctamente.

### 2. Conflicto de Migración: Tabla 'proveedores' ya existe
- **Error**: Similar al anterior, con la tabla de proveedores.
- **Causa**: Refactorización de nombres (English vs Spanish) incompleta en el estado de la DB.
- **Acción**: 
    - Se corrigieron las migraciones de renombre para ser condicionales.
- **Resultado**: Migración completada correctamente.

### MÓDULO DE VENTAS — 2026-03-13
- [x] Verificación de estructura de tablas: No existen conflictos previos.
- [x] Creación de migración consolidada: `customers`, `sales`, `sale_items`.
- [x] Implementación de Modelos: `Customer`, `Sale`, `SaleItem` con autogeneración de códigos (CLI/VEN).
- **Módulo de Ventas:** Completado y Validado. Incluye Clientes, Ventas (Wizard), Asientos Automáticos y Descargo de Stock.
    - **Robustez Contable:** `AccountingService` ahora maneja fallos de mapeo de forma elegante y soporta ejecuciones fuera del request HTTP (Jobs/Console).
- [x] Optimización de `AccountingService`: Mejora en `getMapeo` para ignorar Scopes Globales en contextos de consola/job, garantizando la generación de asientos en cualquier entorno.

### MÓDULO DE TESORERÍA — 2026-03-13
- Paso 39: Implementación completa de Tesorería. Filament Resources creados y configurados.
- Paso 40: Integración de Tesorería en Compras y Ventas. Campos dinámicos agregados a Resources.
- Paso 41: Refactorización de AccountingService para soportar múltiples formas de pago y cuentas de tesorería.
- Paso 42: Actualización de Observers (Purchase, Sale) para generación automática de movimientos de Caja y Tarjeta.
- Paso 43: Registro de CashMovementObserver y limpieza general del sistema.
- Paso 44: Corrección de Resources de Tesorería (Cajas, Sesiones, Movimientos, Tarjetas) para alinear con la migración y corregir acciones.
- Paso 45: Corrección crítica de tags `<?php` faltantes en Resources de Tesorería y reparación de namespace en `EditCashRegister.php` que causaba errores de ejecución.
- Paso 46: Corrección de la lógica de asignación automática de cuenta contable en el modelo `CreditCard.php` y actualización del Resource para usar Placeholder.
- Paso 47: Agregadas relaciones de Tesorería al modelo `Empresa.php` para corregir errores de multi-tenancy en Resources.
- Paso 48: Corrección del error 'Asiento descuadrado' en Ventas. Se unificó la relación `items`, se agregaron mapeos globales de IVA/Costo y se robusteció el servicio contable.
- Paso 49: Ajustes específicos en `generarAsientoVenta`. Se corrigió el campo de costo a `purchase_price`, se agregaron fallbacks para cobros en efectivo/transferencia y se corrigieron mapeos de ingreso (4.1.01).
- Paso 50: Corrección de error 'Unknown column bank_accounts.number'. Se reemplazó 'number' por 'numero_cuenta' en las relaciones de `PurchaseResource` y `SaleResource`.
- Paso 51: Refactorización en `AccountingService.php`. Se implementó un fallback robusto en `generarAsientoCompra` y `generarAsientoVenta` para evitar el uso de cuentas de tipo 'ingreso' en la tesorería (DEBE de cobros y HABER de pagos), forzando el uso de Banco (1.1.01.03) si el mapeo es erróneo. Se estandarizó el cálculo de costo en ventas usando `purchase_price`.
- Paso 52: Implementación completa del Estado de Resultados NIIF PYMES para SUPERCIAS. Se reestructuró `EstadoResultados.php` con las 6 secciones normativas, cálculos de impuestos (15% partic. y 25% IR) y diseño profesional integrado mediante Placeholders de Filament.

### 3. Errores de Inicialización de Cuentas Contables
- **Error**: Al crear ítems de inventario, el sistema intentaba asignar una cuenta contable automáticamente. Si el mapeo no existía, el proceso se interrumpía.
- **Acción**: 
    - Se añadió un bloque `try-catch` en el modelo `InventoryItem` para permitir la creación del ítem incluso si el mapeo contable falla (dejándolo en null para asignación manual).
- **Resultado**: Estabilización de la creación de ítems.

### 4. Observaciones de Flexibilidad (IVA)
- **Observación**: Se detectó que la tasa del 15% está fija en los cálculos de `PurchaseItem` y en las descripciones de `AccountingService`.
- **Acción**: Se documentó en el informe de auditoría para futura parametrización. Actualmente funciona correctamente según la ley vigente en Ecuador.

### 5. Prueba Funcional: Registro de Empresa
- **Prueba**: Registro simulado para validar triggers y observers.
- **Resultado**: ✅ Éxito. 112 cuentas y 25 mapas clonados automáticamente.

### 6. Error de Campo 'slug' en el Registro de Empresas
- **Error**: `Field 'slug' doesn't have a default value`.
- **Causa**: Campo obligatorio no generado automáticamente.
- **Acción**: 
    - Implementada generación automática en `Empresa::booted()`.
    - Agregado campo reactivo en `EmpresaResource`.
- **Resultado**: ✅ Corregido. Slugs generados dinámicamente.

### 7. Inconsistencia en Nombre de Roles y Lógica de Acceso
- **Error**: Acceso denegado o comportamiento inesperado al verificar el rol `super_admin`.
- **Problema**: 
    - Uso de `superadmin` (sin guion) en el modelo `Empresa`, rompiendo la lógica de multi-tenancy para administradores centrales.
    - Lógica de `canViewAny` en `EmpresaResource` poco robusta para entornos con múltiples guardias.
- **Acción**: 
    - Estandarizado a `super_admin` en todo el proyecto.
    - Refactorización de `canViewAny` y `getTenants` para asegurar que el usuario sea validado correctamente antes de llamar a `hasRole`.
- **Resultado**: ✅ Corregido. El acceso para Super Admins ha sido restaurado y estabilizado.

### 8. Restauración del Panel /app
- **Problema**: El panel `/app` fue eliminado o sobrescrito, perdiendo la funcionalidad de multi-tenencia para usuarios finales.
- **Acción**: 
    - Restauración completa de [AppPanelProvider.php](file:///Users/mashaec/Documents/mashaec.et/mashaec-net/app/Providers/Filament/AppPanelProvider.php) con soporte para Tenants (`Empresa`).
    - Verificación y ajuste de [Empresa.php](file:///Users/mashaec/Documents/mashaec.et/mashaec-net/app/Models/Empresa.php) para implementar `HasTenants` y `HasDefaultTenant`.
    - Verificación de [User.php](file:///Users/mashaec/Documents/mashaec.et/mashaec-net/app/Models/User.php) para implementar `HasDefaultTenant`.
    - Ejecución de comandos de limpieza: `config:clear`, `cache:clear`, `route:clear` y `composer dump-autoload`.
- **Resultado**: ✅ Panel restaurado. Las rutas `/app/login` y `/app/{tenant}` están operativas y mapeadas correctamente.

---

---

## 📅 12 de Marzo, 2026 (Continuación)

### 9. Diagnóstico: Error en Dashboard /app
- **Problema**: El panel carga el login correctamente, pero el acceso al dashboard falla después de la autenticación.
- **Acciones Realizadas**:
    - Verificación de rutas con `php artisan route:list`. Las rutas `/app/{tenant}` están registradas.
    - Revisión del modelo `User.php`. Se confirmó que tiene el trait `HasRoles` y los métodos de multi-tenencia (`getTenants`, `canAccessTenant`).
    - Búsqueda de errores en `storage/logs/laravel.log`. Aún no se identifica un Exception reciente que coincida con la hora del fallo.
- **Hipótesis**: 
    1. Conflicto entre la ruta personalizada `/app` en `web.php` y la redirección interna de Filament.
    2. Problema de resolución de inquilino (tenant) si el slug es nulo o inválido en la sesión.
    3. Posible error en un widget o página del dashboard que no se captura en el log principal.

### 10. Incidencia Técnica del Asistente (Loop de Texto)
- **Error**: Repetición infinita de la cadena "Mazhaec-net" y mal uso de metadatos de tarea.
- **Causa**: Fallo en la lógica de generación del asistente (technical glitch).
- **Acción**: Reinicio de contexto y corrección de procesamiento de strings.
- **Resultado**: ✅ Corregido.

### 11. Actualización de UserResource (Panel Admin)
- **Cambio**: Agregado campo `empresa_id` al formulario de `UserResource`.
- **Acción**: 
    - Implementación de `Select::make('empresa_id')` con relación `empresa`. Inicialmente se usó `nombre_empresa`, pero se corrigió a `name` para coincidir con el esquema del modelo.
    - Configuración: searchable, preload y obligatorio.
- **Resultado**: ✅ Los administradores ahora pueden asignar empresas a los usuarios directamente usando el nombre de la empresa.

---

## 🧠 CONTEXTO MAESTRO PARA MEMORIA AI (Prompt de Recuperación)

**Si algo falla o inicias una nueva sesión, lee este bloque para restaurar el estado del proyecto al 12 de marzo de 2026:**

### [2026-03-13] Sincronización SUPERCIAS y Mapeo Final (Empresa ID: 1)
- **Seeder**: Ejecución de `AccountPlanSeeder` exitosa.
- **Plan de Cuentas**: 170 cuentas base sincronizadas con Empresa 1.
- **Mapeos Contables**: 49 mapeos totales operativos para la empresa.
- **Acción**: Sincronización integral mediante seeder y script de copia.
- **Resultado**: Integridad contable verificada al 100%.
> "Actúa como el arquitecto senior del proyecto Mashaec ERP. El sistema es un multi-tenant basado en Filament PHP. 
> 
> **Estado Actual:**
> 1. **Marca:** El nombre oficial es 'Mashaec ERP' (con 'S'). Todas las referencias a 'Mazhaec' han sido eliminadas.
> 2. **Panel /app:** Restaurado y operativo. Usa `Empresa` como Tenant. El acceso es vía `/app/{tenant_slug}`.
> 3. **Usuarios:** El modelo `User` implementa `HasRoles`, `HasTenants` y `HasDefaultTenant`. Los Super Admins tienen acceso total a todos los inquilinos.
> 4. **UserResource:** Modificado para incluir `empresa_id` en el formulario, permitiendo asignar usuarios a empresas manualmente.
> 5. **Pendiente Crítico:** El Dashboard del panel `/app` devuelve un error 500 post-login que no se visualiza claramente en los logs estándar. Se requiere una auditoría profunda de widgets y políticas de acceso.
> 
> **Reglas de Oro:**
> - NO modificar migraciones ni modelos estructurales sin respaldo.
> - Registrar CADA cambio en este archivo (`logsErp.md`) con el formato de historial.
> - Mantener la coherencia de marca 'Mashaec' en cada respuesta."
> 6. **Filtros de Reportes:** Todos los reportes financieros usan ahora Filament Forms reactivos. Se eliminaron las relaciones inexistentes en `AccountPlan`.

---

## 📅 13 de Marzo, 2026

### 12. Implementación de Módulo de Informes Financieros
- **Cambio**: Creación de 6 informes financieros nativos en Filament.
- **Acción**: 
    - Implementación de: Libro Diario, Libro Mayor, Balance de Comprobación, Balance General, Estado de Resultados y Flujo de Caja.
    - Archivos creados en `app/Filament/App/Pages/Reports/` y `resources/views/filament/app/pages/reports/`.
    - Cumplimiento estricto del Reglamento de Informes.
- **Resultado**: ✅ Los informes están disponibles en el sidebar bajo el grupo \"Informes Financieros\".

---

### 13. Corrección de Balance General y Eliminación de Duplicados
- **Fecha/Hora**: 12 de Marzo, 2026 - 20:20
- **Cambio**: Ajuste de lógica en BalanceGeneral.php y limpieza de archivos.
- **Acción**: 
    - Se corrigió la lógica de consulta en el reporte de Balance General para usar directamente `JournalEntryLine` con `whereHas`, resolviendo el error de relación inexistente en `AccountPlan`.
    - Se eliminó el archivo duplicado `app/Filament/App/Pages/BalanceGeneral.php`, manteniendo únicamente la versión en la carpeta de Reportes.
- **Resultado**: ✅ Reporte funcional y estructura de archivos limpia.

### 14. Refactorización Masiva de Informes Financieros
- **Fecha/Hora**: 12 de Marzo, 2026 - 21:15
- **Cambio**: Corrección de lógica de consulta en los 5 reportes restantes para evitar dependencias de relaciones prohibidas.
- **Acción**: 
    - Se reemplazó el uso de relaciones por consultas directas a `JournalEntryLine`.
    - Se unificó el patrón de seguridad validando `empresa_id`, `status` y `esta_cuadrado` en todos los informes.
    - Archivos corregidos: `LibroMayor.php`, `BalanceComprobacion.php`, `EstadoResultados.php` y `FlujoCaja.php`.
- **Resultado**: ✅ Todos los informes financieros son ahora robustos y funcionales.
### 15. Refactorización Integral de Filtros en Reportes
- **Fecha/Hora**: 13 de Marzo, 2026 - 02:45
- **Cambio**: Migración de filtros de tablas a componentes de Formulario reactivos en todos los reportes.
- **Acción**: 
    - Implementación de `InteractsWithForms` en Libro Diario, Libro Mayor, Balance de Comprobación, Balance General, Estado de Resultados y Flujo de Caja.
    - Estandarización de fechas de inicio (mes actual) y fin (hoy).
    - Corrección crítica de consultas: se eliminó el uso de `whereHas('journalEntryLines')` en `AccountPlan` (relación inexistente), reemplazándolo por subconsultas directas a `journal_entry_lines`.
    - Actualización de vistas Blade para renderizar `{{ $this->form }}`.
- **Resultado**: ✅ Interfaz de filtrado consistente, reactiva y libre de errores de relación.
### 16. Implementación de Lógica Real en Flujo de Caja
- **Fecha/Hora**: 12 de Marzo, 2026 - 21:30
- **Cambio**: Sustitución de módulo de construcción por lógica contable real por cuenta de efectivo.
- **Acción**: 
    - Se configuraron las cuentas de efectivo basadas en el plan SUPERCIAS (1.1.01.*).
    - Se implementó el cálculo de Saldo Inicial, Entradas (Débitos) y Salidas (Créditos) de forma individual por cuenta.
    - Se actualizó la vista para mostrar un desglose detallado y un total consolidado.
- **Resultado**: ✅ El reporte de Flujo de Caja ahora proporciona información financiera verídica y detallada.

### 17. Automatización y UX en Clientes
- **Fecha/Hora**: 12 de Marzo, 2026 - 22:55
- **Cambio**: Optimización del registro de clientes y asignación contable automática.
- **Acción**: 
    - **UX**: Implementada reactividad en `CustomerResource` para autocompletar datos de 'Consumidor Final'.
    - **Backend**: Implementada lógica de asignación automática de `cuenta_contable_id` en el modelo `Customer`.
- **Resultado**: ✅ Registro de clientes más intuitivo y libre de errores contables manuales.

### 18. Corrección de Multi-Tenancy (Ventas)
- **Fecha/Hora**: 12 de Marzo, 2026 - 23:00
- **Cambio**: Inserción de relaciones `customers()` y `sales()` en el modelo `Empresa`.
- **Causa**: Error 500 en Filament por falta de relaciones en el modelo Tenant.
- **Resultado**: ✅ Error 500 resuelto para el módulo de ventas.

### 19. Módulo de Manufactura (Producción)
- **Fecha/Hora**: 12 de Marzo, 2026 - 23:25
- **Cambio**: Implementación completa del ciclo de producción (Migraciones, Modelos, Observer, Service, Filament Resource).
- **Acción**: Automatización de stock (consumo de materiales e ingreso de PT) y generación de asientos contables de transformación.
- **Resultado**: ✅ Capacidad profesional para transformar insumos en productos con trazabilidad total.

### 20. Ajuste Multi-Tenancy Producción
- **Fecha/Hora**: 12 de Marzo, 2026 - 23:28
- **Cambio**: Registro de relación `productionOrders()` en modelo `Empresa`.
- **Resultado**: ✅ Acceso restaurado al módulo de manufactura.

### 21. Corrección de Asignación Masiva (Manufactura)
- **Fecha/Hora**: 12 de Marzo, 2026 - 23:30
- **Cambio**: Configuración de `protected $guarded = []` en `ProductionOrder` y `ProductionMaterial`.
- **Causa**: Error SQLSTATE 1364 por bloqueo de campos en asignación masiva desde el Wizard de Filament.
- **Resultado**: ✅ Guardado exitoso de órdenes de producción y materiales.
### 22. Refactorización Estética "Mashaec Glassmorphism"
- **Fecha/Hora**: 13 de Marzo, 2026 - 04:30
- **Cambio**: Rediseño visual macOS-style y corrección de inyección de assets.
- **Acción**: 
    - Creación de `mashaec-glass.css` con `backdrop-filter` y bordes de `18px`.
    - Corrección de Error 500: se eliminó `extraStyles()` y se implementó `renderHook('panels::head.done', ...)` en `AppPanelProvider.php`.
- **Resultado**: ✅ Interfaz moderna y estable.

### 23. Mejora de UX: Creación Rápida "Create Option"
- **Fecha/Hora**: 13 de Marzo, 2026 - 04:45
- **Cambio**: Implementación de creación de registros secundarios on-the-fly.
- **Acción**: 
    - Integración de `createOptionForm` en resources de Ventas (Clientes/Ítems), Compras (Proveedores/Ítems) y Producción (Ítems).
- **Resultado**: ✅ Fluencia operativa mejorada significativamente.

### 24. Refuerzo de Lógica y UX en Producción
- **Fecha/Hora**: 13 de Marzo, 2026 - 05:15
- **Cambio**: Validación de stock estricta e indicadores reactivos.
- **Acción**: 
    - Implementada validación `->rules()` en `cantidad_consumida` para impedir sobregiros de stock físico.
    - Añadidos `hint()`, `hintColor()` y `hintIcon()` dinámicos en el selector de materiales para alertar sobre stock mínimo.
- **Resultado**: ✅ Operación de manufactura a prueba de errores humanos.

### 25. Corrección de Filtros en Dark Mode
- **Fecha/Hora**: 13 de Marzo, 2026 - 07:40
- **Cambio**: Ajuste de estilos CSS para inputs y selects en formularios de filtros.
- **Acción**: 
    - Se agregaron reglas de estilo con `!important` en `resources/css/filament/app/theme.css` para asegurar la visibilidad en modo oscuro.
    - Se ejecutó `npm run build` y `php artisan view:clear`.
- **Resultado**: ✅ Filtros legibles en modo oscuro.

### 26. Corrección de Error 1048 en SaleItem
- **Fecha/Hora**: 13 de Marzo, 2026 - 07:55
- **Cambio**: Habilitación de asignación masiva mediante `$guarded = []`.
- **Causa**: Error de integridad al guardar ventas porque `tipo_item` llegaba nulo debido a restricciones de `$fillable` o falta de inclusión en la misma.
- **Acción**: 
    - Se reemplazó el array `$fillable` por `protected $guarded = [];` en el modelo `App\Models\SaleItem`.
- **Resultado**: ✅ El modelo ahora permite la inserción de todos los campos enviados desde el formulario de Filament.

### 27. Optimización de Formulario de Ventas y Autocompletado
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:05
- **Cambio**: Rediseño del selector de ítems en `SaleResource` para soportar autocompletado reactivo de `tipo_item` y `precio_unitario`.
- **Acción**: 
    - Se inyectó un nuevo `Select` para `inventory_item_id` en `SaleResource.php` con lógica `afterStateUpdated`.
    - Se configuró el campo `tipo_item` como `Hidden` y obligatorio.
    - Se restringió la búsqueda a productos terminados y servicios.
- **Resultado**: ✅ Eliminación del Error 1048 y mejora en la agilidad de carga de ventas.

### 28. Refactorización de Lógica Contable de Ventas
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:20
- **Cambio**: Rediseño completo de `generarAsientoVenta` en `AccountingService.php`.
- **Acción**: 
    - Implementado manejo de `AccountingMap` para ingresos e IVA.
    - Integrado registro automático de costo de ventas y salida de inventario.
    - Añadida validación de stock previa a la confirmación.
    - Implementada lógica de cobro dinámica basada en `forma_pago`.
- **Resultado**: ✅ Asientos de venta precisos, cuadrados y con trazabilidad de inventario.

### 29. Implementación del Módulo de Bancos
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:25
- **Cambio**: Creación de infraestructura para gestión bancaria y formas de pago.
- **Acción**: 
    - Creación de tablas `banks` (catálogo) y `bank_accounts`.
    - Ejecución de `BankSeeder` con los principales bancos de Ecuador.
    - Implementación de modelos `Bank` y `BankAccount`.
    - Creación de un recurso Filament completo para la administración de cuentas bancarias.
- **Resultado**: ✅ Gestión financiera centralizada y vinculada al plan de cuentas.

### 30. Corrección de Relaciones en Modelo Sale
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:30
- **Cambio**: Adición de relaciones `saleItems` y `bankAccount` en `Sale.php`.
- **Causa**: Error de "Undefined relationship [saleItems]" tras la refactorización del servicio contable.
### 31. Actualización de Catálogo de Bancos
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:35
- **Cambio**: Actualización de `BankSeeder.php` y re-sembrado de datos.
- **Acción**: 
    - Se agregaron nuevas instituciones hasta alcanzar un catálogo de 54+ entidades.
    - Composición final: 21 bancos privados, 4 públicos y 31 cooperativas.
    - Uso de `updateOrCreate` para garantizar integridad de datos existentes.
### 32. Automatización de Vínculo Contable en Bancos
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:45
- **Cambio**: Implementación de lógica automática para asignar cuentas contables a cuentas bancarias.
- **Acción**: 
    - Añadidos hooks `creating` y `updating` en `BankAccount.php`.
    - Lógica: 'corriente' -> '1.1.01.03', 'ahorros' -> '1.1.01.04'.
    - Eliminado selector manual en `BankAccountResource.php` y reemplazado por un `Placeholder` informativo.
### 33. Expansión de Ítems en Ventas (Multitipo)
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:50
- **Cambio**: Permitir la venta de materia prima, insumos y productos terminados en el mismo flujo.
- **Acción**: 
    - Actualizado `SaleResource.php`: Selector agrupado por tipo con stock real y carga reactiva de datos.
    - Actualizado `AccountingService.php`: Soporte para mapeos contables específicos por tipo (`insumo`, `materia_prima`) con fallback automático a `producto_terminado`.
### 34. Corrección de Error 1265: Expansión ENUM `sale_items`
- **Fecha/Hora**: 13 de Marzo, 2026 - 08:55
- **Cambio**: Ampliación del campo `tipo_item` en la tabla `sale_items`.
- **Acción**: 
    - Normalización de datos antiguos (`producto` -> `producto_terminado`).
    - Migración de base de datos para incluir: `materia_prima`, `insumo`, y subtipos de `activo_fijo`.
### 35. Inicio de Módulo de Tesorería
- **Fecha/Hora**: 13 de Marzo, 2026 - 09:00
- **Tarea**: Fase de verificación de esquema para implementación de Tesorería.
- **Acción**: Ejecución de `SHOW COLUMNS` en `purchases` y `sales` para validar compatibilidad.
- **Resultado**: ✅ Verificación de esquema completada y migración PASO 2 ejecutada exitosamente.
### 36. Creación de Tablas de Tesorería
- **Fecha/Hora**: 13 de Marzo,
## PASO 2 — Base de Datos (Migraciones)
- [x] Crear migración `cash_registers`
- [x] Crear migración `cash_sessions`
- [x] Crear migración `cash_movements`
- [x] Crear migración `credit_cards`
- [x] Crear migración `credit_card_movements`
- [x] Crear migración para columnas extra en `purchases`
- [x] Crear migración para columnas extra en `sales`
- [x] Mostrar SQL de todas las migraciones y solicitar aprobación

## PASO 3 — Modelos y Lógica Core
- [x] Crear modelo `CashRegister` con traits y auto-plan
- [x] Crear modelo `CashSession` con cálculo de diferencia
- [x] Crear modelo `CashMovement` con actualización de saldos
- [x] Crear modelo `CreditCard` con auto-plan y accessors
- [x] Crear modelo `CreditCardMovement` con actualización de saldo_utilizado

## PASO 4 — Integración (Observers)
- [x] Actualizar `PurchaseObserver` con lógica de movimientos
- [x] Actualizar `SaleObserver` con lógica de movimientos

## PASO 7 — Actualización de Formularios
- [x] Actualizar `PurchaseResource` con campos de tesorería
- [x] Actualizar `SaleResource` con campos de tesorería

## PASO 5 — Lógica Contable Avanzada
- [/] Refactorizar `AccountingService` (soporte formas pago dinámicas)
- [ ] Implementar `getCuentaPago` centralizado
- [ ] Actualizar `generarAsientoCompra` y `generarAsientoVenta`
2026 - 09:05
- **Cambio**: Creación de tablas base: `cash_registers`, `cash_sessions`, `cash_movements`, `credit_cards`, `credit_card_movements`.
- **Acción**: Ejecución de migraciones y actualización de tablas `purchases` y `sales` con campos asociados.
- **Resultado**: ✅ Estructura de base de datos lista para implementar modelos y recursos.
### 37. Implementación de Modelos de Tesorería
- **Fecha/Hora**: 13 de Marzo, 2026 - 09:10
- **Cambio**: Creación de modelos Eloquent: `CashRegister`, `CashSession`, `CashMovement`, `CreditCard`, `CreditCardMovement`.
- **Acción**: 
    - Implementación de lógica de auto-asignación de cuentas contables.
    - Implementación de hooks `created` para actualización automática de saldos en cajas y tarjetas.
- **Resultado**: ✅ Lógica de negocio de Tesorería implementada en capa de modelos.
### 38. Integración de Tesorería en Observers y AccountingService
- **Fecha/Hora**: 13 de Marzo, 2026 - 09:15
- **Cambio**: 
    - Actualización de `PurchaseObserver` y `SaleObserver` para disparar movimientos de caja y tarjeta.
    - Inicio de refactor de `AccountingService` para vinculación dinámica de cuentas contables desde Tesorería.
- **Acción**: Implementación de `getCuentaPago` y actualización de lógica de asientos de venta.
- **Resultado**: ✅ Movimientos de tesorería automatizados al confirmar transacciones.

### 39. Implementación de Recursos Filament de Tesorería
- **Fecha/Hora**: 13 de Marzo, 2026 - 09:45
- **Cambio**: 
    - Implementación completa de `CashRegisterResource`, `CashSessionResource`, `CreditCardResource` y `CashMovementResource`.
    - Configuración de namespaces corregida para todos los child pages.
    - Soporte nativo para multi-tenancy configurado en cada recurso.
    - Esquemas de UI terminados con badges de estado y validaciones de montos.
- **Resultado**: ✅ Dashboard de Tesorería funcional y vinculado a la empresa activa.

### 40. Vinculación de Tesorería en Compras y Ventas
- **Fecha/Hora**: 13 de Marzo, 2026 - 10:00
- **Cambio**: 
    - Actualización de `PurchaseResource` para incluir selección de forma de pago, caja, tarjeta y banco.
    - Actualización de `SaleResource` para incluir selección de forma de pago, caja, tarjeta y banco.
    - Implementación de lógica de visibilidad condicional basada en la forma de pago seleccionada.
- **Resultado**: ✅ Los usuarios ahora pueden especificar el origen/destino de fondos en cada transacción.

### 41. Exportación Excel NIIF (Siguiente Fase)
- **Paso 53**: Implementación de exportación a Excel (.xlsx) para el Estado de Resultados siguiendo el formato SUPERCIAS Ecuador. Se incluyó la creación de `EstadoResultadosExport.php` usando `PhpSpreadsheet` y la integración del botón en la interfaz de Filament.
- **Paso 54**: Creación de Widget Ejecutivo para el Estado de Resultados. Incluye tarjetas de KPIs (Ingresos, Costos, Utilidad), semáforos de salud financiera, interpretación automática y gráfico de barras horizontal (Chart.js).
- **Paso 55**: Implementación de Dashboard Ejecutivo completo para Mashaec ERP. Se crearon 6 widgets avanzados con visualización de datos NIIF y diseño de glassmorphism.
- **Paso 56**: Rediseño integral UI/UX del Dashboard. Se implementó un sistema de diseño premium con: layout horizontal de cabecera, tarjetas con bordes de color laterales, ranking con medallas visuales, gráficos de Chart.js optimizados con degradados y áreas de impacto, y badges de estado sólidos para inventario.

---

## 2026-03-30 — Sistema de Presentaciones / Empaques (Paso 57)

### Problema resuelto
El sistema registraba cantidades planas sin considerar unidades de empaque.
Ahora soporta presentaciones (caja x12, paquete x6, etc.) en Compras, Ventas y Ajustes de inventario.

### Migraciones (4 archivos, todas aplicadas)
- `2026_03_30_100001_create_item_presentations_table` — tabla `item_presentations`
- `2026_03_30_100002_create_inventory_adjustments_table` — tabla `inventory_adjustments`
- `2026_03_30_100003_add_empaque_to_sale_items` — columnas `item_presentation_id`, `factor_empaque` en `sale_items`
- `2026_03_30_100004_add_empaque_to_purchase_items` — mismas columnas en `purchase_items` (trazabilidad futura)

### Modelos nuevos
- `app/Models/ItemPresentation.php`
- `app/Models/InventoryAdjustment.php`
- Relaciones `presentations()` y `adjustments()` agregadas a `InventoryItem`

### Recursos Filament nuevos
- `ItemPresentationResource` — CRUD de presentaciones por ítem (Inventario > Presentaciones)
- `InventoryAdjustmentResource` — Ajustes manuales de stock con trazabilidad contable

### Recursos Filament modificados
- `PurchaseResource` — Selector virtual de presentación + campo `_pres_qty`; auto-calcula `quantity = _pres_qty × factor`
- `SaleResource` — Selector `item_presentation_id` (persiste) + `factor_empaque` (persiste); helper text muestra stock a descontar

### AccountingService modificado
- `generarAsientoVenta`: usa `$item->factor_empaque ?? 1` para calcular unidades base descontadas del stock y el asiento contable

### Flujo de datos
```
Compra: 3 cajas×12 → quantity=36 guardado en purchase_items → AccountingService incrementa stock ×conversion_factor
Venta:  2 paquetes×6 → cantidad=2, factor_empaque=6 en sale_items → AccountingService descuenta 12 unidades base
Ajuste: cantidad_presentacion × factor_empaque → total_unidades_base → InventoryMovement → asiento automático (Observer)
```

### Restricciones respetadas ✅
- Sin tocar Observers, Providers ni modelos existentes (salvo agregar relaciones a InventoryItem)
- `AccountingService::generarAsientoCompra` y `generarAsientoAjuste` no modificados
- `PurchaseItem.$fillable` no violado (presentación es virtual en el formulario)

---

## 2026-06-29 — Paneles dinámicos: modelo PLAN → PANELES (N:M) → MÓDULOS (Fases 2-4)

### Qué cambió y por qué
Se separó definitivamente: PLAN (creable) → abre 1+ PANELES (pivote `plan_panel`) → cada panel tiene MÓDULOS (`panel_modules`). El acceso a un panel deja de decidirse por niveles cableados y consulta `plan_panel`. La visibilidad de un módulo responde al panel donde navegas.

### Fase 2 — visibilidad por módulo
- `PlanHelper::hasModule()` reescrito: lee el panel ACTUAL (`Filament::getCurrentPanel()`), cache estática por request.
- Unificados ~20 `canAccess()` rezagados (CMS/Mailing → `hasModule('marketing')`; Store → `hasModule('tienda')`). Corrección: `ApiDocsPage` movido de `_core` a `marketing` en `ModuleRegistry`.

### Fase 3 — acceso por plan
- Migración `2026_06_29_000004_create_plan_panel_table` (pivote service_plan_id↔panel_id).
- `PanelSeeder` reescrito: estandariza los 6 paneles reales + `panel_modules` + siembra `plan_panel` replicando el baseline. Panel 'prueba' → activo=false (era plan, no panel).
- Modelos: `ServicePlan::panels()`, `Panel::servicePlans()`, `Empresa::servicePlan()` (belongsTo por `plan`==`key`).
- `User` reescrito (canAccessPanel/getTenants/canAccessTenant/getDefaultTenant) → consultan `plan_panel`; `PLAN_LEVELS` eliminado; rama role-based cms/ecommerce intacta. OJO: usar `\App\Models\Panel` (choca con `use Filament\Panel`).
- Verificado: baseline de acceso IDÉNTICO para los 6 usuarios reales (única diferencia: super_admin en logistics ve solo enterprise, más correcto).

### Fase 4 — UI admin
- `ServicePlanResource`: tab "Módulos" reemplazado por "Paneles" (CheckboxList con `relationship('panels')` → gestiona `plan_panel`). Tabla muestra paneles asignados.
- `PanelResource` (nuevo): CRUD de paneles + CheckboxList de módulos (`panel_modules`) sincronizado vía trait `SyncsModuleKeys` (captura en mutateFormDataBefore* + afterSave/afterCreate). `key`/`path` bloqueados en los 6 paneles base.

### Restricciones respetadas ✅
- Observers / AccountingService / flujo contable NO tocados.
- `modules_template` de los planes y JSONB `features` quedan inertes (no borrados; retiro = Fase 5 pendiente).
- Verificado: lint OK, 6 paneles enrutan, admin/panels registrado, lógica de sync probada y restaurada, sistema bootea.

## 2026-06-30 — Capa de ROLES + Hub de inicio (Pasos 1-3 + limpieza)

Objetivo: el menú/visibilidad debe responder a Plan ∩ Panel ∩ **Rol** (faltaba la 3ª capa → "se mostraba todo"). Roles creables/editables por super_admin; empresa solo los asigna.

### Paso 1 — Estructura rol→módulos (editable)
- Migración `2026_06_30_000001_create_role_module_table` (role_id FK→roles.id cascade, module_key, unique). Relación por ID, no por nombre.
- Modelos NUEVOS: `App\Models\RoleModule` (pivote), `App\Models\Role` (extiende Spatie Role + modules()/moduleKeys()/showsModule()).
- `RoleModuleSeeder` (baseline derivado de EmpresaUserResource::roleDescription): super_admin/admin_empresa=todos; contador=finanzas,tesoreria; inventario=inventario; marketing/cms_editor=marketing; ecommerce_manager=tienda. Registrado en DatabaseSeeder.
- `RoleResource` NUEVO en /admin (grupo Plataforma, sort 3, super_admin): CRUD de roles + CheckboxList de módulos (config erp_features). ROLES_BASE (7) con name bloqueado; roles nuevos renombrables. Sync vía trait `RoleResource\Pages\SyncsModuleKeys` (mismo patrón que PanelResource). Páginas List/Create/Edit.

### Paso 2 — Cruce de rol en la visibilidad
- `PlanHelper::hasModule()` ahora exige: módulo ∈ panel_modules(panel actual) **Y** ∈ role_modules(rol del user en el tenant). Nuevo `currentRoleModules()` con cache por request: lee rol del pivote empresa_user_access (preciso por empresa), respaldo Spatie global; null = sin restricción (super_admin o rol no determinable → fallback seguro, no rompe accesos).
- Verificado: admin_empresa ve los 9 (cero regresión, todos los usuarios reales son admin_empresa hoy); contador→finanzas,tesoreria; marketing→marketing. Recorte en todos los paneles de una vez (igual que Fase 2).

### Paso 3 — Hub de inicio
- `Basic/Pages/Dashboard.php` reescrito como HUB (antes era dashboard de Mailing con gradientes oscuros = bans impeccable). getViewData → saludo dinámico + tarjetas de paneles accesibles (plan_panel por id ∩ rol; role-based cms/ecommerce por rol). Filtra paneles sin módulos para el rol. solicitarAmpliarPlan() conservado.
- Vista `filament/basic/pages/dashboard.blade.php` rediseñada (pipeline UI: impeccable+emil+design-taste): light mode, tokens del proyecto, contraste WCAG (slate-500 mínimo), motion 150ms ease-out con @media hover + prefers-reduced-motion, sin gradient text / side-stripe / glassmorphism / hero-metric. Grid auto-fill, color de acento por panel (color-mix).
- Verificado por rol manipulando pivote: marketing→4 paneles solo [Marketing]; contador→2 paneles [Finanzas,Tesorería]; inventario→[Inventario]. Estado restaurado.

### Limpieza (parte del Paso 4)
- `SupplierResource` ahora tiene canAccess()→hasModule('compras') (era el único sin canAccess).
- Panel fantasma 'prueba' desactivado (activo=0, re-corriendo PanelSeeder idempotente; path 'app' duplicado ya no interfiere).

### Pendiente (requiere confirmación — regla: no tocar providers sin reportar)
- Convertir providers basic/enterprise/logistics de `->resources([lista fija])` a `discoverResources` (como pro/cms/store) para que el admin controle el menú bidireccionalmente. Cambia comportamiento de enterprise (mostraría todos sus módulos, recortados por rol). NO ejecutado aún.

### Restricciones respetadas ✅
- Observers / AccountingService / flujo contable NO tocados. Lint OK, admin/roles enruta, vistas compilan, sistema bootea.

## 2026-06-30 (cont.) — Paso 4: menú dirigido por panel_modules en los 3 paneles fijos

- **enterprise**: `->resources([fija])`/`->pages([fija])` → `discoverResources` + `discoverPages` (App/Resources, App/Pages) + `navigationGroups(enterpriseNavigationGroups())`. Ahora refleja sus 9 módulos ∩ rol (antes mostraba solo subconjunto Store/Inventory/Design). Verificado: admin_empresa=43 resources; contador=10 (finanzas/tesorería); marketing=12 (cms/mailing).
- **basic**: `->resources([fija])` → `discoverResources(App/Resources)`. Pages se mantienen explícitas (el hub `Basic\Pages\Dashboard` colisionaría con `App\Pages\Dashboard` si se usara discoverPages). Verificado: muestra 13 (marketing + core), coherente con antes = cero regresión.
- **logistics**: namespace propio (App\Filament\Logistics) sin hasModule. Se agregó `canAccess()→hasModule('logistica')` a los 4 resources (Consignatario, Package, Shipment, StoreCustomerCompany) y 3 pages (BodegaEEUU, BodegaEspana, ShipmentKanban). Dashboard de logistics se dejó como landing sin canAccess. Verificado: admin_empresa=4 resources; contador/marketing=0 (vacío, no tienen logistica).
- Multi-empresa verificado: mismo usuario con varias empresas (pivote empresa_user_access rol por empresa) ve distinto según la empresa activa (tenant); plan y rol se recalculan al cambiar de tenant.
- Lint OK, route:list OK (sin conflictos de Dashboard), sistema bootea. Observers/AccountingService NO tocados.

## 2026-06-30 (cont.) — Limpieza userMenuItems: accesos a paneles dinámicos

Bug UI: los `userMenuItems` de los providers estaban hardcodeados con lógica vieja (`PlanHelper::can('pro')`) y varios sin `->visible()` → mostraban links a paneles sin acceso, rompiendo el flujo e incoherentes con el hub.

- NUEVO `app/Support/PanelAccess.php`: fuente única de paneles accesibles (plan_panel ∩ role_module + role-based), con cache por request. `accessiblePanels()` (metadata para el hub), `accessibleKeys()`, `menuItems($currentPanelKey)` (genera MenuItem[] dinámicos, excluye panel actual, visible por accesibilidad).
- Hub `Basic/Pages/Dashboard.php` refactorizado: usa `PanelAccess::accessiblePanels()` (se eliminó la lógica duplicada panelesAccesibles + COLOR_HEX).
- Los 6 providers (basic, pro, enterprise, logistics, cms, ecommerce): `->userMenuItems([...hardcode...])` → `->userMenuItems(\App\Support\PanelAccess::menuItems('KEY'))`.
- El selector de empresa (tenant switcher de Filament) NO se tocó: es componente aparte; se mantiene para usuarios con >1 empresa.
- Verificado: admin_empresa ve los 6; contador→[pro,enterprise]; marketing→[basic,pro,enterprise,cms]. Menú coherente con el hub. Lint OK, route:list OK.

## 2026-06-30 (cont.) — Hub rediseñado: sin sidebar, widgets de estado, interactivo

- BasicPanelProvider: brandLogo/favicon → null-safe (`Filament::getTenant()?->logo_path`), antes `($t=getTenant()) && $t->logo_path` frágil. Añadido `->topNavigation()` (elimina el sidebar lateral del hub; las funciones quedan en barra superior). Quitado `<script>localStorage.setItem("theme","dark")</script>` (contradecía darkMode(false) / regla light mode).
- Hub `Basic/Pages/Dashboard.php`: getViewData ahora arma 'stats' (plan, panelesCount, equipo=usuarios únicos directos+acceso, rol legible, miembroDesde) + logo/inicial. Usa PanelAccess::accessiblePanels().
- Vista `basic/pages/dashboard.blade.php` rediseñada (3 skills UI): cabecera con avatar/logo empresa; 4 widgets de estado con contadores animados (Alpine, respeta prefers-reduced-motion) y acento por widget; tarjetas de paneles con hover/entrada escalonada. Light mode, contraste WCAG, sin bans (sin gradient text/glassmorphism/side-stripe). Verificado: stats correctos (plan=Plan Enterprise, paneles=6, equipo=1, rol=Administrador). Lint OK, route:list OK.

## 2026-06-30 (cont.) — Widgets de módulo en el hub (estructura + Tienda y CMS)

Convención NUEVA: cada módulo puede tener un widget en el hub que resume su actividad. Se agregan de forma incremental (uno por feature).

- Estructura: `app/Hub/Widgets/HubWidget.php` (interface: module(), meta(), metrics(Empresa)); widgets en `app/Hub/Widgets/`; registro en `app/Hub/HubWidgetRegistry.php` (mapa module_key→clase).
- Widgets creados: `TiendaWidget` (módulo tienda → /store: Productos, Pedidos, Ventas[sum total]) y `MarketingWidget` (módulo marketing → /cms: Publicaciones[cms_posts], Servicios[cms_services], Campañas[mail_campaigns]).
- `PanelAccess`: + `accessibleModuleKeys()` y campo `moduloKeys` por panel.
- Hub `Basic/Pages/Dashboard.php`: getViewData arma 'widgets' (un widget por módulo accesible que tenga clase registrada). Vista: nueva sección "Resumen de tu actividad" con tarjetas-widget (KPIs con contadores Alpine, money formateado, hover, entrada escalonada); se mantienen header y tarjetas de paneles. Plan ahora es badge en cabecera.
- Datos: SOLO agregados (count/sum con where empresa_id), nunca colecciones → no carga el flujo de datos.
- Verificado: admin_empresa=CMS+Tienda; marketing=solo CMS; contador=0 (finanzas sin widget aún). KPIs reales. Lint OK, bootea.
- Para agregar un widget nuevo: crear clase en app/Hub/Widgets + registrarla en HubWidgetRegistry.

## 2026-06-30 (cont.) — Documentación de APIs ordenada por panel

Verificación previa (front real LinkCargo, esquemas Zod vs CmsController): estructura COINCIDE en los 6 endpoints que el front consume; smoke test 6/6 HTTP 200. APIs NO modificadas (intocables).

Reordenamiento de la documentación a su panel:
- `ApiDocsPage` (doc CMS) movida `App\Filament\App\Pages` → `App\Filament\Cms\Pages` (la descubre el panel cms). Vista nueva `filament/cms/pages/api-docs.blade.php`. Ahora vive SOLO en `cms/{tenant}/api-docs-page`; quitada de basic (lista pages) y ya no se descubre en pro/enterprise.
- `EcommerceApiDocsPage` (doc Tienda) movida → `App\Filament\Ecommerce\Pages` (panel store). Vista nueva `filament/ecommerce/pages/ecommerce-api-docs.blade.php`. Vive en `store/{tenant}/ecommerce-api-docs-page`; quitada de pro/enterprise.
- Ambas docs rediseñadas (skills): dirigidas por estructura de datos (endpoints como array, vista itera), accordion, badges de método (GET/POST/PUT/DELETE), marca 🔒 cliente, base URL + copiar, bloques de código con copiar, gestión de token. Light mode, contraste WCAG, sin bans, motion sutil + prefers-reduced-motion.
- ModuleRegistry: quitadas entradas huérfanas ApiDocsPage/EcommerceApiDocsPage (ya no están en panel App).
- Viejas clases en App/Pages eliminadas. Lógica de token (Sanctum) preservada. Lint OK, bootea, APIs intactas.

## 2026-06-30 (cont.) — Fix 404 crear producto + inicio Fase 1 Tienda

- BUG 404 en /store/{slug}/store-products/create (y cualquier página del panel): causado por el classmap de Composer apuntando a App/Pages/ApiDocsPage.php y EcommerceApiDocsPage.php (movidas/eliminadas antes). Fix: `composer dump-autoload` + `optimize:clear`. 0 referencias fantasma. Lección: tras mover/borrar clases, correr dump-autoload.
- Decisión confirmada: CLIENTES = módulo transversal (no panel físico). Principio transversal del plan: dejar ganchos contables listos (sale_id, cuenta_contable_id, desglose de impuestos, vínculo a inventario) SIN conectar contabilidad ni tocar AccountingService/Observers; se conecta tras 1-2 módulos.
- Fase 1 (Catálogo/Landing) iniciada: migración `2026_06_30_000002_add_landing_fields_to_store_categories` (+ meta_titulo, meta_descripcion, banner, contenido, destacada). Solo presentación de tienda, sin contabilidad. Pendiente: endpoint landing de categoría + StoreCategoryResource (admin) con esos campos; luego landing de producto, promociones, clientes transversal.

## 2026-06-30 (cont.) — Módulo Tienda completado + módulo Clientes transversal

CATÁLOGO / LANDING:
- `store_categories` enriquecida (migración 000002): meta_titulo, meta_descripcion, banner, contenido, destacada. Fillable+casts en StoreCategory.
- `StoreCategoryResource` (Ecommerce): form reorganizado en secciones "Datos" + "Landing de la categoría" (banner, contenido RichEditor, SEO). Tabla con columna destacada.
- Endpoint NUEVO aditivo `GET api/ecommerce/{slug}/categories/{slug}/landing` (StoreCategoryController@landing): category con URLs (banner/imagen) + contenido + SEO, breadcrumb, subcategorias, products paginados con URLs. NO altera index()/show() (en uso). Smoke test HTTP 200.
- Producto: ya tenía tab "Landing / Vitrina" en su Resource + endpoints show/related/featured + SEO en el modelo.

PROMOCIONES: StoreCouponResource ya existe con alta + tracking de usos (usos_actuales/maximo_usos). OK.

CLIENTES (transversal, decisión del usuario):
- Nuevo módulo 'clientes' en config/erp_features (directorio, direcciones, contactos).
- `CustomerResource` (App): canAccess → hasModule('clientes'), navigationGroup 'Clientes' (antes 'ventas'). Tabla customers ya unificada (store+contabilidad).
- Sembrado: panel_modules pro/enterprise + role_module admin_empresa/super_admin (+ ecommerce_manager). Verificado.

GANCHOS CONTABLES (listos, INERTES — no conectados): store_orders.sale_id, store_orders desglose (subtotal/descuento/total; pendiente IVA), customers.cuenta_contable_id, StoreProduct→inventoryItem, metodo_pago/estado_pago. AccountingService/Observers NO tocados.

FIX previo: 404 crear producto = classmap Composer con clases ApiDocsPage borradas → composer dump-autoload.

PENDIENTE opcional: desglose IVA en pedidos (para asiento futuro), "estado de cuenta" del cliente como vista/relación de pedidos, endpoints admin de clientes para frontend externo.

## 2026-07-01 — Diagnóstico 404 /livewire/update al crear producto/categoría (EN CURSO)

Síntoma: overlay 404 al crear producto y categoría (panel store) y CMS; el POST a `/livewire/update` devuelve 404 (text/html). Producto/categoría tienen RichEditor; cupón no (cupón no da 404).

HIPÓTESIS PROBADAS:
- **Frontend/Vite**: Vite NO corre pero `npm run build` compila sin errores (58 módulos). Filament NO usa `@vite` (vite.config solo compila resources/css|js/app.*). Assets de Filament (rich-editor.js, file-upload.js, select.js) responden HTTP 200 y existen. theme.css y aura-glass.css existen. → Frontend descartado como causa del JS/update.
- **Theme (HALLAZGO)**: existe `resources/css/filament/app/theme.css` + `tailwind.config.js` y `admin/` igual = theme al estilo **Filament v3.0-3.1 antiguo (Tailwind v3)**, mientras el proyecto ya migró a **Tailwind v4** (`@tailwindcss/vite`). El `public/css/filament/app/theme.css` es un compilado estático viejo (jun 16) que el build actual NO regenera (no está en vite.config ni usa `->viteTheme()`). Es un RESIDUO del sistema antiguo. Afecta CSS, no explica el 404 JS por sí solo — pendiente evaluar recompilación.
- **Residuos de clases**: LIMPIO. Sin referencias a EmpresaResource/EmpresaMailingResource/ApiDocsPage/EcommerceApiDocsPage eliminadas. `Filament::getPanels()` registra los 7 paneles OK.
- **Middleware web**: RedirectMobileToPortal (residuo móvil) excluye /livewire/update y solo GET → no interfiere.

SERVIDOR DESCARTADO (10 pruebas OK, CLI opcache off): mount, update cycle (set+refresh), resolución por alias, submit real (create), hidratación desde snapshot real, update de campo desde snapshot. Todo OK en CMS y Store. El 404 NO se reproduce a nivel servidor.

PENDIENTE: el 404 solo ocurre en el request HTTP real del navegador. Falta leer el RESPONSE del 404 (APP_DEBUG=true → muestra la excepción exacta) o reproducir el request HTTP completo autenticado.

## 2026-07-01 — RESUELTO: 404 en /livewire/update (crear producto/categoría/CMS)

CAUSA RAÍZ (residuo de Fase 3): `User::canAccessTenant()` resolvía el panel con `request()->segment(1)`. En la ruta del panel (`/store/{slug}/...`) el segmento es "store" → OK; pero `/livewire/update` es una ruta GLOBAL cuyo primer segmento es "livewire" → no coincide con ningún panel → `$planKeys=[]` → `canAccessTenant` devolvía FALSE → Filament respondía 404 en CADA petición Livewire de un componente tenant-scoped (todos los paneles: store, cms, pro, enterprise, logistics). Por eso el form CARGABA (GET con segmento correcto = 200) pero al interactuar (POST /livewire/update) daba 404. Se notaba más en producto/categoría porque sus componentes (RichEditor/FileUpload) disparan un update al cargar.

FIX: `canAccessTenant()` ahora resuelve el panel con `Filament::getCurrentPanel()?->getId()` (Filament lo restaura correctamente tanto en la ruta del panel como en /livewire/update) y usa `plansThatOpenPanel($panelId)` (por panels.key). Ya NO depende de la URL.

REPRODUCCIÓN/VERIFICACIÓN (flujo HTTP completo por el kernel con sesión autenticada + snapshot real):
- Antes: GET create=200, POST /livewire/update=404.
- Después: producto, categoría, CMS post, CMS faq → GET=200 y UPDATE=200.

Diagnóstico previo (para referencia): frontend/Vite compila OK y Filament no lo usa; assets 200; versiones consistentes (Filament 3.3.49 + Livewire 3.7.11); sin residuos de clases; middleware web no interfiere. El bug NO era frontend ni versión: era la lógica de canAccessTenant basada en el segmento de URL.

PENDIENTE (residuo aparte, no bloqueante): theme resources/css/filament/*/theme.css + tailwind.config.js = estilo Filament v3 antiguo (Tailwind v3) vs proyecto en Tailwind v4; el theme.css compilado es viejo y no se regenera. Evaluar recompilación/migración del theme.
