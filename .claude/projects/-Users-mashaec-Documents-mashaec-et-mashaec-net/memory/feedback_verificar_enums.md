---
name: Verificar enums antes de usarlos
description: Siempre leer la migración de la tabla antes de asignar valores a campos enum
type: feedback
---

Antes de asignar un valor a un campo enum en cualquier modelo o service, leer la migración correspondiente para ver los valores permitidos.

**Why:** Se usó `'tipo' => 'financiero'` en `journal_entries` pero el enum solo permite: `apertura`, `manual`, `compra`, `venta`, `manufactura`, `ajuste`, `cierre`, `depreciacion`. Causó un error SQL en producción.

**How to apply:** Cuando se escribe código que inserta en una tabla con campos enum, grep la migración (`create_*_table`) para ver los valores exactos antes de escribir el código. Nunca asumir valores.
