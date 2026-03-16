# Manual de Usuario - Flujo de Trabajo Mashaec ERP

Este manual detalla el proceso correcto para operar los módulos de Inventario, Compras y Contabilidad en el sistema **Mashaec ERP**.

---

## 🚀 1. Configuración de Inquilino (Tenant)
Al ingresar al panel `/app`, el sistema te redirigirá automáticamente a tu empresa según tu usuario. Si eres un administrador, puedes gestionar múltiples empresas.

## 📦 2. Catálogo de Inventario
**Ruta:** `Inventario > Ítems`

Antes de realizar compras, debes registrar tus productos.
1.  **Código:** Único para cada producto (ej: `MP-001`).
2.  **Tipo:** Define si es Insumo, Materia Prima o Producto Terminado.
3.  **Unidad de Medida:** Ej: Metro, Resma, Kilo.
4.  **Cuentas Contables:** Asegúrate de que el ítem tenga configuradas sus cuentas de inventario y costo para que el asiento automático funcione.

## 🤝 3. Proveedores
**Ruta:** `Compras > Proveedores`

Registra a tus proveedores con su RUC/Cédula y datos de contacto. El sistema requiere:
- **Contacto Principal:** Persona de referencia.
- **Correo Principal:** Para envío de órdenes.
- **Teléfono Principal:** Para coordinación.

## 🛒 4. Ciclo de Compras
**Ruta:** `Compras > Compras`

### Paso A: Crear Borrador
1.  Crea una nueva compra y selecciona el proveedor.
2.  Agrega los ítems desde el catálogo de inventario.
3.  Ingresa cantidades y precios unitarios. El sistema calculará el IVA (15% por defecto) y los totales.
4.  Guarda en estado **Borrador**. En este punto, no hay impacto en stock ni contabilidad.

### Paso B: Confirmar Compra (Impacto Real)
1.  Revisa los totales.
2.  Cambia el estado a **Confirmado**.
3.  **¿Qué sucede al confirmar?**
    -   📈 **Stock:** El inventario aumenta automáticamente según las cantidades compradas.
    -   📒 **Contabilidad:** Se genera un Asiento Contable (Journal Entry) automático.
    -   🔗 **Vínculo:** El asiento queda amarrado a la compra para auditoría.

## 🧾 5. Auditoría Contable
**Ruta:** `Contabilidad > Asientos`

Puedes consultar los asientos generados. Un asiento saludable de compra debe estar **CUADRADO** (Total Debe = Total Haber).
- **Debe:** Cuentas de Inventario e IVA.
- **Haber:** Cuentas por Pagar o Caja/Bancos (según el tipo de pago).

## 💰 6. Ciclo de Ventas
**Ruta:** `Ventas > Ventas`

El sistema gestiona el proceso de venta de forma integral mediante un asistente (Wizard) de 3 pasos.

1.  **Paso 1 (Cliente):** Selección del cliente y condiciones de cobro.
2.  **Paso 2 (Ítems):** Selección de productos/servicios. El sistema calcula el IVA y totales automáticamente.
3.  **Paso 3 (Resumen):** Vista final antes de guardar en estado **Borrador**.

### ✨ ¿Qué hace el ERP automáticamente al Confirmar una Venta?
Para liberar al usuario de tareas repetitivas, al cambiar el estado a **Confirmado**, Mashaec ERP ejecuta:
- 🔢 **Codificación**: Asigna el número secuencial definitivo (ej: `VEN-2026-00001`).
- 📉 **Inventario**: Rebaja automáticamente el stock de los productos vendidos.
- 🗂 **Movimientos**: Registra un movimiento de inventario de tipo 'salida' referenciado a la venta.
- 📒 **Contabilidad**: Genera el Asiento Contable cuadrado en el Libro Diario, imputando el ingreso, el IVA por pagar, el costo de ventas y descargando la cuenta de inventario activo.
- 🌐 **Sincronización**: Envía una notificación vía webhook (n8n) para integración con otros sistemas.

## 📊 7. Informes Financieros
**Ruta:** `Informes Financieros`

El sistema cuenta con 6 reportes clave para la toma de decisiones:
1.  **Libro Diario:** Listado cronológico de todos los asientos confirmados.
2.  **Libro Mayor:** Movimientos detallados filtrados por cuenta contable.
3.  **Balance de Comprobación:** Resumen de saldos (Debe vs Haber) por cada cuenta activa.
4.  **Balance General:** Estado de Activos, Pasivos y Patrimonio a una fecha de corte.
5.  **Estado de Resultados:** Resumen de Ingresos, Costos y Gastos para determinar la utilidad o pérdida.
6.  **Flujo de Caja:** Movimientos detallados de las cuentas de efectivo y bancos (Caja General, Caja Chica, Bancos). Permite ver el saldo inicial, las entradas y salidas del período, y el saldo final consolidado.

---

## ✨ 8. Interfaz y Experiencia de Usuario (UX)
Mashaec ERP utiliza una estética moderna basada en **Glassmorphism** (inspirada en macOS) para reducir la fatiga visual y mejorar la claridad.

### ➕ Creación Rápida "On-the-fly"
Para evitar interrupciones en tu flujo de trabajo:
- En los selectores de **Cliente**, **Proveedor** e **Ítems**, verás un icono de "+" o la opción de crear uno nuevo.
- Al hacer clic, se abrirá un modal para registrar el dato faltante sin abandonar tu formulario actual.

### 🏭 Producción Inteligente
Al registrar materiales en una Orden de Producción:
- **Indicadores**: Verás el stock actual y su unidad de medida al instante.
- **Alertas**: Si el stock es menor al mínimo configurado, el indicador cambiará a **rojo (⚠️)**.
- **Validación**: El sistema no te permitirá guardar el consumo si supera las existencias físicas disponibles.

---

## 💡 Consejos de Uso
- **Slugs:** El sistema usa el nombre de la empresa para generar la URL. Si cambias el nombre, la URL del panel cambiará.
- **Asientos Cuadrados:** Si un asiento sale descuadrado, revisa el mapeo de cuentas en el perfil de la empresa o en el ítem de inventario.

---
© 2026 Mashaec ERP - Solución Integral Multi-tenant.
