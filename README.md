# ERP MashaCorp

ERP multiempresa para pymes ecuatorianas. Cada operación —compra, venta, producción,
gasto— termina en su asiento contable, en el kardex y, cuando corresponde, en las
declaraciones del SRI y los estados de la Superintendencia de Compañías. Un dato vive
en un solo lugar: el balance, el formulario 104, el ATS y los informes de la SCVS
leen el mismo libro diario.

## Stack

| Pieza | Elección |
|---|---|
| Backend | Laravel 12 · PHP 8.5 en producción |
| Paneles | Filament 3 · Livewire 3 |
| Base de datos | PostgreSQL |
| Colas | `database`, atendida por supervisor (dos workers) |
| Documentos | dompdf (PDF) · PhpSpreadsheet (Excel) |
| Servicios externos | Microservicio SRI (Python, aparte) · n8n para Telegram y correo |

## Módulos

El acceso depende del plan de la empresa (`basic`, `pro`, `enterprise`): cada plan
habilita sus paneles.

| Panel | Ruta | Para qué |
|---|---|---|
| Contabilidad | `/contabilidad` | Plan de cuentas, libro diario, bandeja de comprobantes del SRI, formulario 104, ATS, cumplimiento, estados financieros y archivos de la Superintendencia, firma electrónica, cierre |
| Operaciones | `/operaciones` | Inventario y kardex (promedio ponderado), bodegas, unidades de medida, diseño de producto y formulación, órdenes de producción |
| Tienda | `/store` | Catálogo, categorías, cupones y pedidos con su historial |
| CMS | `/cms` | Contenido de los sitios web de cada empresa, servido por API |
| Gestión | `/app`, `/pro`, `/enterprise` | Ventas, compras, clientes, proveedores, caja, bancos, tarjetas, deudas, soporte y correo masivo |
| Administración | `/admin` | Empresas, usuarios, planes y accesos |

La lógica de cada dominio vive en `app/Services` y en acciones de `app/Modules`
(Compras, Ventas, Ecommerce, Soporte, N8n). Las pantallas solo la llaman.

## Estructura

```
app/
  Filament/<Panel>/   pantallas de cada panel (Resources y Pages)
  Services/           reglas de negocio: contabilidad, kardex, SRI, Superintendencia
  Modules/            acciones por dominio, compartidas con la API de n8n
  Jobs/               trabajos en cola (declaraciones, importaciones)
  Observers/          lo que pasa al guardar: asientos, historial, mapeos
database/
  migrations/         el esquema completo, en orden
  data/               catálogos oficiales (SRI, Superintendencia) y formatos
resources/views/      vistas Blade de los paneles y de los reportes en PDF
```

## Puesta en marcha local

```bash
composer install
npm ci && npm run build
cp .env.example .env        # completar la conexión a PostgreSQL
php artisan key:generate
php artisan migrate
php artisan serve
php artisan queue:work      # las declaraciones y las importaciones van por la cola
```

La base local se trabaja con una copia restaurada de producción (`pg_restore`), no con
datos inventados.

## Despliegue

Un push a `main` despliega por GitHub Actions (`.github/workflows/deploy.yml`):
instala dependencias, compila los assets, corre las migraciones pendientes y
reinicia la cola. No hay ramas de trabajo.

## Convenciones

- Código, tablas, pantallas y mensajes en español.
- El dinero se calcula con `numeric` en la base y bcmath en PHP: nunca con `float`.
- Toda operación que mueve dinero o bienes genera su asiento; sin asiento, no se guarda.
- Los catálogos y porcentajes del SRI viven en tablas con vigencia, nunca en el código.
- Los `.md` de trabajo, los volcados de base y las notas locales no entran al repositorio
  (ver `.gitignore`); este README es la excepción.
