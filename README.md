# Sistema ERP y Gestión Médica AMPAR

Este proyecto es una plataforma web (ERP) diseñada para la gestión operativa, logística, administrativa y médica de la empresa AMPAR. Permite controlar desde el inventario en múltiples almacenes hasta la programación de procedimientos, facturación, órdenes de compra y trazabilidad de equipos mediante tecnología RFID.

## Arquitectura y Tecnologías

- **Frontend:** HTML5, CSS3, JavaScript puro y jQuery. Uso de Bootstrap para el diseño responsivo y SweetAlert2 para notificaciones.
- **Backend:** PHP.
- **Base de Datos:** Firebird SQL.
- **Documentos PDF:** Generación dinámica de reportes, remisiones y formatos usando librerías de PDF (`gdocs/`).
- **Integraciones:** Notificaciones automáticas por WhatsApp (vía API Baileys/Ampar), e integración con impresoras térmicas y dispositivos RFID.

---

## Estructura de Directorios Principal

- `/gui`: Contiene todas las vistas y pantallas principales (Frontend) a las que accede el usuario (Ej. `eventos.php`, `remisiones.php`, `almacen.php`).
- `/includes`: Fragmentos de código reutilizables (Modales de Bootstrap, headers, footers, formularios de creación).
- `/ajax`: Endpoints (Controladores API internos) que procesan las peticiones en segundo plano y devuelven datos al frontend.
- `/class`: Clases PHP con la lógica de negocio central y consultas a la base de datos Firebird (Ej. `eventos.php`, `articulos.php`, `firebird.php`).
- `/gdocs`: Scripts para la generación y renderizado de documentos en PDF.
- `/bd`: Scripts y respaldos relacionados con la base de datos Firebird.
- `/cron`: Tareas programadas (Background jobs) para envío de recordatorios, reportes y limpieza de sistema.

---

## Módulos Principales del Sistema

### 1. Gestión de Procedimientos (`/gui/eventos.php`, `eventos.mis.php`)

Es el corazón operativo médico. Permite programar cirugías y procedimientos clínicos.

- **Asignación de Recursos:** Permite asignar "Equipo Capital" (monitores, consolas) y "Maletas" (kits prearmados de catéteres/stents) al evento.
- **Trazabilidad:** Controla el estado del evento (En revisión, Autorizado, Finalizado).
- **Consumo:** Al finalizar el evento, los médicos o encargados confirman (mediante checklist y evidencias fotográficas) exactamente qué insumos se utilizaron durante la cirugía para descontarlos del inventario y generar la remisión.

### 2. Inventario y Almacenes (`/gui/inventarioglobal.php`, `almacenes.php`)

Control estricto de la existencia de insumos a nivel nacional.

- Soporta múltiples sucursales y sub-almacenes lógicos o físicos (Ej. "SALTILLO", "Saltillo CEDIS").
- **Entradas y Salidas:** Registro manual o automatizado de movimientos de mercancía.
- **Traspasos (`traspasos.php`):** Logística de movimiento de stock entre diferentes almacenes, con estados de validación como "En tránsito" y "Recibido".

### 3. Remisiones (`/gui/remisiones.php`, `remisiones.mostrador.php`)

Controla la salida oficial de la mercancía hacia el cliente (hospitales/médicos).

- **Remisiones de Evento:** Se generan automáticamente tras finalizar un evento quirúrgico, listando lo consumido y los artículos provistos por externos.
- **Remisiones de Mostrador:** Para ventas directas o entregas rápidas de material que no requieren la logística de un evento.

### 4. Catálogos y Artículos (`/gui/articulos.php`, `catalogos.php`)

Administración de la base de datos maestra de la empresa.

- **Artículos (`articulos.php`):** Gestión del catálogo individual de productos (insumos médicos, catéteres, stents). Permite dar de alta nuevos productos, asignarles claves, familias, y gestionar sus características.
- **Equipo Capital (`equipocapital.php`):** Activos fijos de alto valor tecnológico que se prestan o rentan para los eventos (tienen número de serie, marca, ubicación fija o móvil).
- **Maletas (`maletas.php`):** Agrupaciones lógicas de artículos (Kits quirúrgicos) que se preparan en almacén y se envían cerrados a los hospitales para agilizar los procedimientos.
- Tipos de eventos, médicos referidores, intervencionistas, especialistas, hospitales, clientes y proveedores.

### 5. Compras y Recepciones (`/gui/oc.php`, `recepcionesmercancia.php`)

Módulo de abastecimiento de inventario.

- **Órdenes de Compra (OC):** Creación y autorización de pedidos formales a proveedores externos.
- **Recepción de Mercancía:** Al llegar el producto físico, se ingresa al almacén principal, se capturan folios, lotes, series y fechas de caducidad, actualizando el stock global automáticamente.
- **Sugerencias de Compra:** Análisis automático de stock mínimo contra consumo histórico para sugerir reabastecimiento a compras.

### 6. RFID, Escaneos y Etiquetas (`/gui/etiquetas.php`, `escaneos.php`)

Módulo tecnológico para la automatización e innovación de operaciones en almacén.

- Permite la impresión de códigos en etiquetas en lenguaje ZPL enviadas directamente a impresoras térmicas.
- Integración con escáneres RFID para hacer inventarios masivos o validar el contenido completo de una "Maleta" quirúrgica en segundos sin necesidad de conteo manual pieza por pieza.

### 7. Administración y Seguridad (`/gui/usuarios.php`, `permisos.php`)

- Gestión de cuentas de los empleados y directivos.
- Sistema robusto de roles y permisos granulares que definen a qué módulos y sucursales tiene acceso (lectura/escritura) cada trabajador de AMPAR.

### 8. Proyectos e Incidencias (`/gui/proyectos.php`, `tickets.php`)

- **Proyectos:** Flujos de trabajo especializados que agrupan múltiples eventos o tareas a largo plazo bajo un mismo presupuesto o meta.
- **Tickets (Incidencias):** Sistema de control de discrepancias y faltantes de inventario. Cuando se escanea una maleta o un almacén (usando RFID o manualmente) y el sistema detecta que falta un artículo que debería estar ahí, se genera automáticamente una "Incidencia". Aquí los encargados pueden revisar el artículo faltante, justificarlo, rechazarlo o autorizar el ajuste de inventario.

---

## Flujo de Trabajo Típico

1. **Abastecimiento:** Se genera una OC a un proveedor. Llega la mercancía y el encargado la recibe usando `recepcionesmercancia.php` (ingresando al Almacén Matriz).
2. **Logística:** Mediante el módulo de traspasos, se envía material médico desde el Almacén Matriz a una sucursal foránea (Ej. "Puebla").
3. **Programación:** Un especialista crea un Evento programado para el día siguiente en un hospital, asignando Maletas y Equipos Capitales.
4. **Ejecución:** Se traslada el equipo al hospital. Termina la cirugía y, en la plataforma, el especialista finaliza el evento confirmando qué se consumió realmente y subiendo evidencias.
5. **Cierre:** El sistema descuenta el stock utilizado de manera definitiva, devuelve el resto a inventario, genera una "Nota de Remisión", y libera el evento.
