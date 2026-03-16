# Informe de Auditoría Técnica ERP - Mashaec
**Fecha de revisión:** 2026-03-16 (actualizado 2026-03-16 — análisis Estado de Resultados)
**Revisado contra:** Catálogo Único de Cuentas (CUC) Supercias Ecuador, LORTI / RLRTI SRI Ecuador, NIIF para PYMES

---

## 🏗️ Arquitectura General

El sistema está construido sobre **Laravel 12** y **Filament PHP 3**, con arquitectura de multi-tenencia nativa.

### Aislamiento de Datos (Multi-tenancy)
- **Implementación**: Trait `HasEmpresa` + scope global `EmpresaScope`.
- **Funcionamiento**: El sistema detecta automáticamente el tenant a través de `Filament::getTenant()` o `auth()->user()->empresa_id`, filtrando todas las consultas de forma transparente.
- **Seguridad**: Garantizado que un usuario de "Empresa A" nunca accede a datos de "Empresa B".

---

## 📋 Conformidad del Plan de Cuentas con el CUC Supercias

### Estructura General ✅ CONFORME
El plan de cuentas base sigue la nomenclatura del Catálogo Único de Cuentas (CUC) establecido por la Superintendencia de Compañías para NIIF para PYMES:

| Grupo | Código | Tipo | Estado |
|-------|--------|------|--------|
| Activo Corriente | 1.1.xx.xx | activo | ✅ Correcto |
| Activo No Corriente (PPE) | 1.2.01.xx | activo | ✅ Correcto |
| Pasivo Corriente | 2.1.xx.xx | pasivo | ✅ Correcto |
| Pasivo No Corriente | 2.2.xx.xx | pasivo | ✅ Correcto |
| Patrimonio Neto | 3.x.xx | patrimonio | ✅ Correcto |
| Ingresos Ordinarios | 4.1.xx | ingreso | ✅ Correcto |
| Costos de Ventas y Producción | 5.1.xx | costo | ✅ Correcto |
| Gastos Operativos y Financieros | 6.x.xx | gasto | ✅ Correcto |

### Cuentas Tributarias Clave
| Cuenta | Código | Uso | Validación SRI |
|--------|--------|-----|----------------|
| Crédito tributario IVA | 1.1.05.01 | IVA pagado en compras | ✅ Correcto (Formulario 104) |
| IVA en ventas por pagar | 2.1.04.01 | IVA cobrado en ventas | ✅ Correcto (Formulario 104) |
| Retenciones IVA por pagar | 2.1.04.02 | Retenciones de IVA | ⚠️ Cuenta existe, pero no se usa |
| Retenciones fuente por pagar | 2.1.04.03 | Retenciones IR | ⚠️ Cuenta existe, pero no se usa |
| Anticipo impuesto a la renta | 1.1.05.03 | Anticipo IR | ⚠️ Cuenta existe, pero no se usa |

### Tasa de IVA
La tasa del **15%** está correcta para el período fiscal 2024-2026 (Ley Orgánica de Eficiencia Económica y Generación de Empleo, vigente desde 01/04/2024). El sistema la aplica correctamente en todos los módulos.

---

## 📦 Análisis de Flujos Contables por Módulo

### Módulo 1: COMPRAS (`generarAsientoCompra`)

#### Asiento generado al confirmar una compra
```
Compra de insumo, contado, IVA incluido:
  DEBE  1.1.03.01  Inventario de Insumos         $subtotal_item
  DEBE  1.1.05.01  Crédito Tributario IVA         $iva_item
  HABER 1.1.01.03  Bancos cuenta corriente        $total_compra
```

**Evaluación:** ✅ CORRECTO para `insumo` y `materia_prima`.
El IVA pagado en compras se registra como crédito tributario (activo), tal como lo exige el SRI. El cuadre DEBE = HABER se verifica con `round(..., 2)`.

#### Problema detectado — Compra a crédito
```
Compra a crédito:
  DEBE  1.1.03.01  Inventario de Insumos         $subtotal_item
  DEBE  1.1.05.01  Crédito Tributario IVA         $iva_item
  HABER 2.1.01.01  Proveedores locales por pagar  $total_compra
```
**Evaluación:** ✅ CORRECTO. Cumple con el tratamiento de cuentas por pagar a proveedores.

#### ~~HALLAZGO CRÍTICO 1 — Compras de `producto_terminado`~~ ✅ CORREGIDO
~~El `AccountingMapSeeder` **no tiene** los mapeos `compra_contado` ni `compra_credito_local` para el tipo `producto_terminado`.~~

**Corrección aplicada (2026-03-16):** Migración `2026_03_16_100000_fix_missing_accounting_maps.php` agregó los mapeos faltantes para `producto_terminado`, `insumo`, `materia_prima` y todos los tipos de movimiento de producción. Migración adicional `2026_03_16_213930_sync_all_accounting_maps_to_empresas.php` sincronizó los 56 mapeos base con todas las empresas existentes. El asiento ahora es:
```
CORRECTO:
  DEBE  1.1.03.04  Inventario de Productos Terminados  $subtotal
  DEBE  1.1.05.01  Crédito Tributario IVA              $iva
  HABER 1.1.01.01  Caja general                        $total
```

---

### Módulo 2: VENTAS (`generarAsientoVenta`)

#### Asiento de ingreso (Haber)
```
Venta contado, producto terminado con IVA:
  HABER 4.1.01    Ventas de bienes              $subtotal_item
  HABER 2.1.04.01 IVA en ventas por pagar       $iva_item
  HABER 1.1.03.04 Salida inventario             $costo_unitario * cantidad
  DEBE  5.1.04    Costo de productos vendidos   $costo_unitario * cantidad
  DEBE  1.1.01.03 Bancos (cobro)                $total_venta
```
**Evaluación de la parte de ingresos e IVA:** ✅ CORRECTO. El IVA de ventas se acredita como pasivo.
**Evaluación de la parte de cobro:** ✅ CORRECTO para efectivo y transferencia.

#### ~~HALLAZGO CRÍTICO 2 — Costo de Venta usa cuenta incorrecta de inventario~~ ✅ CORREGIDO
~~En `generarAsientoVenta`, la cuenta del inventario para la salida se obtiene con `getMapeo(..., 'compra_contado')` y para `producto_terminado` no existía ese mapeo, fallback a Bancos.~~

**Corrección aplicada (2026-03-16):** Al existir ahora el mapeo `producto_terminado/compra_contado → 1.1.03.04`, la salida de inventario en ventas apunta a la cuenta correcta. El asiento es:
```
CORRECTO:
  DEBE  5.1.04    Costo de productos vendidos    $X
  HABER 1.1.03.04 Inventario PT                 $X
```
**Nota de diseño pendiente:** Usar el mismo mapeo `compra_contado` para la salida de inventario en ventas es semánticamente incorrecto (debería existir un `tipo_movimiento='salida_inventario'` específico). Funciona correctamente en la práctica porque el mapeo apunta a la cuenta de inventario, pero se recomienda agregar el tipo de movimiento apropiado en una revisión futura.

#### HALLAZGO IMPORTANTE 3 — Retenciones en la fuente no manejadas
Al confirmar una venta, el sistema no genera líneas para las **retenciones en la fuente** (IR) que los agentes de retención deben aplicar. Según el SRI, el asiento correcto en ventas con retención debería incluir:
```
  HABER 2.1.04.03 Retenciones fuente por pagar  $monto_retencion
```
Esto afecta principalmente a contribuyentes especiales y sociedades.

---

### Módulo 3: MANUFACTURA / PRODUCCIÓN (`generarAsientoProduccion`)

#### Asiento esperado al completar una orden de producción
```
  DEBE  1.1.03.04  Inventario PT (entrada)       $costo_total_produccion
  HABER 1.1.03.02  Inventario Materias Primas    $costo_material_A
  HABER 1.1.03.01  Inventario Insumos            $costo_material_B
```

#### ~~HALLAZGO CRÍTICO 4 — Mapeos de producción inexistentes~~ ✅ CORREGIDO
~~El `AccountingMapSeeder` no tiene ningún mapeo para `entrada_produccion` ni `salida_produccion`.~~

**Corrección aplicada (2026-03-16):** La migración amplió el ENUM `tipo_movimiento` con `entrada_produccion` y `salida_produccion`, y agregó todos los mapeos necesarios para el módulo de manufactura. El módulo de producción está operativo.

---

### Módulo 4: AJUSTES DE INVENTARIO (`generarAsientoAjuste`)

#### Asiento de faltante de inventario (ajuste negativo)
```
  DEBE  6.3.03    Otros gastos (Ajuste Faltante)  $monto
  HABER 1.1.03.01 Inventario de Insumos           $monto
```
**Evaluación:** ✅ CORRECTO. El faltante se reconoce como gasto.

#### Asiento de sobrante de inventario (ajuste positivo)
```
  DEBE  1.1.03.01 Inventario de Insumos           $monto
  HABER 4.3.02    Otras rentas (Ajuste Sobrante)  $monto
```
**Evaluación:** ✅ CORRECTO. El sobrante se reconoce como ingreso extraordinario.

---

### Módulo 5: TESORERÍA

#### Flujo de efectivo (compras/ventas en efectivo)
- `PurchaseObserver` → crea `CashMovement` tipo `egreso` al confirmar compra en efectivo.
- `SaleObserver` → crea `CashMovement` tipo `ingreso` al confirmar venta en efectivo.
- `CashMovementObserver` → no genera asientos adicionales para movimientos que ya tienen `journal_entry_id`.
**Evaluación:** ✅ CORRECTO. Evita duplicidad de asientos.

#### Flujo de tarjeta de crédito (compras)
- `PurchaseObserver` → crea `CreditCardMovement` tipo `cargo` al confirmar compra con tarjeta.
**Evaluación:** ✅ Funcional. Sin embargo, el módulo de tarjeta en ventas está incompleto (hay comentario en `SaleObserver` indicando que está pendiente de implementar el movimiento real).

#### HALLAZGO IMPORTANTE 5 — Movimientos manuales de caja sin asiento
`CashMovementObserver::created()` tiene lógica vacía para movimientos manuales (sin `journal_entry_id`). Los egresos varios de caja (gastos menores, anticipos, etc.) no generan asiento contable automático. Esto significa que pueden existir movimientos de efectivo no reflejados en la contabilidad.

---

---

## 🔎 Análisis Detallado: Estado de Resultados (`EstadoResultados.php`)

### Estructura del reporte — Conforme CUC Supercias ✅

El Estado de Resultados sigue la estructura requerida por Supercias para NIIF para PYMES:

| Sección | Código plan | Tipo cuenta | Campo sumado | Evaluación |
|---------|-------------|-------------|--------------|------------|
| 1. Ingresos Ordinarios | `4.1.xx` | `ingreso` | `haber` | ✅ Correcto |
| 2. Otros Ingresos | `4.2.xx`, `4.3.xx` | `ingreso` | `haber` | ✅ Correcto |
| 3. Costos de Ventas | `5.x.xx` | `costo` | `debe` | ✅ Correcto |
| 4. Gastos Operacionales | `6.1.xx`, `6.2.xx` | `gasto` | `debe` | ✅ Correcto |
| 5. Gastos No Operacionales | `6.3.xx`, `6.4.xx` | `gasto` | `debe` | ✅ Correcto |

### Cálculo de impuestos — Conforme LORTI Ecuador ✅

```
Utilidad antes de impuestos
  - 15% Participación Trabajadores (Art. 97 Código del Trabajo)
  = Base imponible IR
  - 25% Impuesto a la Renta sociedades (Art. 37 LORTI, 2024-2026)
  = Utilidad Neta del Ejercicio
```
**Evaluación:** ✅ Correcto para sociedades. La tasa 25% aplica correctamente para compañías obligadas a llevar contabilidad bajo Supercias.

### Arquitectura de renderizado

El informe usa **dos capas que coexisten**:
1. **`renderReport()`** (PHP) → genera HTML completo dentro de un `Placeholder` de Filament. Es el reporte real y funcional.
2. **`estado-resultados.blade.php`** → tiene una tabla obsoleta que itera sobre `$ingresos`, `$costos`, `$gastos`. Estas variables siempre están vacías porque el controlador las inicializa como `collect()` y nunca las carga con saldos. La tabla se oculta mediante un selector CSS frágil `.fi-main .space-y-6 > div:nth-child(2) { display: none !important; }`.

---

### HALLAZGO ER-H1 — `getCuentasMonto` no calcula saldo neto
**Criticidad:** 🟠 IMPORTANTE

```php
// Implementación actual (incorrecta para anulaciones):
$monto = JournalEntryLine::...->sum('haber');  // ingresos
$monto = JournalEntryLine::...->sum('debe');   // costos/gastos
```

Cuando se anula una venta con `revertirAsiento()`, el asiento inverso genera un DEBE en la cuenta de ingresos (ej. `4.1.01`). La suma directa de `haber` ignora ese DEBE y presenta el ingreso original sin deducir la anulación.

**Comportamiento correcto:**
```php
// Para ingresos (naturaleza acreedora):
$saldo = SUM(haber) - SUM(debe)
// Para costos/gastos (naturaleza deudora):
$saldo = SUM(debe) - SUM(haber)
```

**Impacto:** El Estado de Resultados mostrará ingresos y costos inflados si existen anulaciones parciales dentro del período consultado. Solo afecta cuando hay ventas anuladas en el mismo período.

---

### HALLAZGO ER-H2 — Bug: `$empresa->nombre` en renderReport y exportarExcel
**Criticidad:** 🔴 BUG (nombre de empresa vacío en el encabezado del reporte)

```php
// En renderReport() (incorrecto):
{$empresa->nombre}
// El modelo Empresa tiene columna 'name', no 'nombre'
```

El encabezado del reporte mostraba el nombre de la empresa vacío. El modelo Empresa proviene de la migración `rename_companies_to_empresas_table` que mantuvo la columna `name` (no `nombre`).

**Corrección:** `{$empresa->name}` (renderReport) y `$tenant->name` (exportarExcel) — ya era correcto en el export, el bug estaba en el HTML del reporte.

---

### HALLAZGO ER-H3 — Export sin `withoutGlobalScopes()` en JournalEntry
**Criticidad:** 🟠 IMPORTANTE

En `EstadoResultadosExport::getCuentasMonto()`:
```php
->whereHas('journalEntry', fn($q) => $q
    ->where('empresa_id', $this->empresaId)  // No tiene withoutGlobalScopes()
    ->where('status', 'confirmado')
    ...
)
```

El `EmpresaScope` puede aplicar un segundo filtro `empresa_id` basado en `filament()->getTenant()`. En el contexto de descarga directa (streaming), el tenant puede estar disponible o no dependiendo del timing de la respuesta. El Export en el reporte PHP (`renderReport()`) sí tiene `withoutGlobalScopes()` correctamente; el Export no lo tiene.

---

### HALLAZGO ER-H4 — Vista Blade con tabla obsoleta y variables vacías
**Criticidad:** 🟡 MEDIO (no rompe funcionalmente; el reporte real viene del Placeholder)

La vista `estado-resultados.blade.php` contiene una tabla que itera sobre `$ingresos`, `$costos`, `$gastos` esperando objetos con propiedades `->saldo` y `->level`. El controlador inicializa estas variables como `collect()` vacías y **nunca las llena con saldos reales**. Esta tabla es vestigial y está oculta mediante CSS hack.

**Riesgo:** Si el CSS selector cambia (por actualización de Filament o cambio de layout), la tabla vacía podría volverse visible y confundir al usuario.

**Recomendación:** Eliminar la tabla obsoleta del Blade y dejar solo `{{ $this->form }}`.

---

### HALLAZGO ER-H5 — Tasa IR 25% fija (no diferencia microempresas)
**Criticidad:** 🟡 MEDIO

```php
$impuestoRenta = $baseImp > 0 ? $baseImp * 0.25 : 0;
```

La LORTI prevé tarifas diferentes para microempresas (tabla progresiva Resolución SRI NAC-DGERCGC24-00000013). Para el universo de PYMES bajo Supercias con facturación > USD 300K, la tasa del 25% es correcta. Para microempresas con facturación menor, la tarifa puede ser inferior (entre 1% y 25% según tabla).

**Impacto:** El reporte puede sobre-estimar el impuesto para microempresas. El cálculo contable registrado en libros no se ve afectado (este cálculo es solo para la proyección en el informe).

---

## 📊 Informes Financieros

Los siguientes informes están implementados como páginas Filament:

| Informe | Archivo | Requerido Supercias | Estado |
|---------|---------|---------------------|--------|
| Libro Diario | `Reports/LibroDiario.php` | Sí | ✅ Implementado |
| Libro Mayor | `Reports/LibroMayor.php` | Sí | ✅ Implementado |
| Balance de Comprobación | `Reports/BalanceComprobacion.php` | Sí | ✅ Implementado |
| Balance General (Estado de Situación) | `Reports/BalanceGeneral.php` | Sí | ✅ Implementado |
| Estado de Resultados | `Reports/EstadoResultados.php` | Sí | ✅ Implementado |
| Flujo de Caja | `Reports/FlujoCaja.php` | Referencial | ✅ Implementado |
| Estado de Flujo de Efectivo (NIC 7) | — | Sí (Supercias) | ⚠️ No implementado |

**Nota:** Supercias requiere el **Estado de Flujo de Efectivo** bajo NIC 7 (método directo o indirecto) para las compañías obligadas a llevar contabilidad con NIIF completas. El actual `FlujoCaja.php` es un reporte operativo de caja, no el estado financiero formal.

---

## 🔍 Tabla Consolidada de Hallazgos

| ID | Módulo | Hallazgo | Criticidad | Estado |
|----|--------|----------|------------|--------|
| H1 | Compras | `producto_terminado` sin mapeo `compra_contado` — usaba Bancos en lugar de Inventario PT | 🔴 CRÍTICO | ✅ CORREGIDO 2026-03-16 |
| H2 | Ventas | Costo de venta usaba cuenta Bancos como contracuenta en lugar de Inventario PT | 🔴 CRÍTICO | ✅ CORREGIDO 2026-03-16 |
| H3 | Manufactura | Mapeos `entrada_produccion` y `salida_produccion` inexistentes — bloqueaba el módulo | 🔴 CRÍTICO | ✅ CORREGIDO 2026-03-16 |
| H4 | Ventas | No se generan retenciones en la fuente en ventas a agentes de retención | 🟠 IMPORTANTE | ⏳ Pendiente |
| H5 | Tesorería | Movimientos manuales de caja sin asiento contable automático | 🟠 IMPORTANTE | ⏳ Pendiente |
| H6 | General | IVA 15% hardcoded — si cambia la tasa no se actualiza en todo el sistema | 🟡 MEDIO | ⏳ Pendiente |
| H7 | General | No hay proceso de cierre de ejercicio contable automatizado | 🟡 MEDIO | ⏳ Pendiente |
| H8 | Informes | Falta Estado de Flujo de Efectivo NIC 7 formal | 🟡 MEDIO | ⏳ Pendiente |
| H9 | Tesorería | Ventas con tarjeta: `CreditCardMovement` no implementado en `SaleObserver` | 🟡 MEDIO | ⏳ Pendiente |
| ER-H1 | Estado Resultados | `getCuentasMonto` suma `haber`/`debe` directamente — las anulaciones no se descuentan del saldo neto | 🟠 IMPORTANTE | ✅ CORREGIDO 2026-03-16 |
| ER-H2 | Estado Resultados | Bug: `$tenant->name` en `exportarExcel()` — debería ser `$tenant->nombre` | 🔴 BUG | ✅ CORREGIDO 2026-03-16 |
| ER-H3 | Estado Resultados | Export Excel sin `withoutGlobalScopes()` en `whereHas('journalEntry')` | 🟠 IMPORTANTE | ✅ CORREGIDO 2026-03-16 |
| ER-H4 | Estado Resultados | Vista Blade obsoleta con tabla vacía oculta por CSS hack | 🟡 MEDIO | ⏳ Pendiente |
| ER-H5 | Estado Resultados | Tasa IR 25% fija no diferencia microempresas (tabla progresiva SRI) | 🟡 MEDIO | ⏳ Pendiente |

---

## ✅ Aspectos Correctamente Implementados

| Aspecto | Detalle |
|---------|---------|
| Estructura CUC Supercias | Plan de cuentas alineado con la nomenclatura oficial |
| IVA 15% en compras | Se registra como crédito tributario (1.1.05.01) ✅ |
| IVA 15% en ventas | Se registra como pasivo por pagar (2.1.04.01) ✅ |
| Cuadre de asientos | Verificación con `round(..., 2)` antes de confirmar ✅ |
| Reversión de asientos | `revertirAsiento()` crea asiento inverso y marca el original como anulado ✅ |
| Multi-tenancy contable | Cada empresa tiene su propio plan de cuentas clonado al crearla ✅ |
| Trazabilidad | Cada compra/venta/producción linkea a su `JournalEntry` ✅ |
| Movimientos de inventario | Se registran `InventoryMovement` en cada transacción para auditoría ✅ |
| Compras a crédito | Afecta correctamente `2.1.01.01 Proveedores locales` ✅ |
| Ventas a crédito | Afecta correctamente `1.1.02.01 Cuentas por cobrar clientes` ✅ |
| Ajustes de inventario | Faltante a gasto, sobrante a ingreso ✅ |
| Prevención duplicidad de asientos | Verifica `journal_entry_id` antes de generar ✅ |
| Webhook n8n en ventas | Integración con automatizaciones externas ✅ |

---

## 📌 Resumen Ejecutivo

### Estado al 2026-03-16

El sistema ERP tiene una arquitectura contable sólida y bien integrada. Los **3 hallazgos críticos originales** (H1, H2, H3) han sido corregidos mediante migraciones que sincronizaron los mapeos contables faltantes.

**Correcciones aplicadas:**
- Migración `2026_03_16_100000_fix_missing_accounting_maps.php`: agrega mapeos de `producto_terminado`, producción, IVA base
- Migración `2026_03_16_213930_sync_all_accounting_maps_to_empresas.php`: sincroniza los 56 mapeos base con todas las empresas
- Correcciones en 6 reportes financieros (`withoutGlobalScopes`, rango de fechas, totales)

**En el Estado de Resultados** (revisión específica):
- Estructura conforme CUC Supercias ✅
- Cálculo fiscal (15% PT + 25% IR) correcto para sociedades ✅
- Corregidos: saldo neto en anulaciones (ER-H1), bug `$tenant->nombre` (ER-H2), `withoutGlobalScopes` en export (ER-H3)

**Pendientes críticos restantes:**
- Ninguno que bloquee la operación básica del sistema

**Pendientes para cumplimiento tributario avanzado:**
- Retenciones en la fuente en ventas (H4) — relevante para agentes de retención
- Movimientos manuales de caja sin asiento (H5)
- Estado de Flujo de Efectivo NIC 7 formal (H8) — para empresas obligadas a NIIF completas
- Limpieza de la vista Blade obsoleta del Estado de Resultados (ER-H4)
