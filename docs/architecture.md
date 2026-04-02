# Arquitectura técnica — TrackApp (MVP)

## 1. Principios

- **PHP puro** en el servidor, sin framework (sin Composer obligatorio para el MVP; si más adelante se añaden dependencias, se justificará).
- **Sin Node.js** en el pipeline de la aplicación: no bundler, no SSR en Node, no runtime JS en servidor. Si se usa JavaScript en el cliente, será **vanilla** o CSS estático servido por PHP/HTTP.
- **Un solo punto de entrada HTTP** (front controller), enrutamiento explícito por ruta hacia “acciones” o vistas PHP.
- **Separación** entre capa HTTP, configuración, registro de actividad y (futura) capa de integración con el origen de pedidos.

---

## 2. Decisión explícita: sin frameworks ni Node

| Decisión | Motivación |
|----------|------------|
| **Sin Laravel, Symfony, Slim, etc.** | Reducir superficie de ataque y dependencias; control total del flujo; despliegue en entornos con solo PHP-FPM + servidor web. |
| **Sin Node.js** | Mismo criterio de simplicidad operativa; evitar dos runtimes en servidor y en CI. |
| **Sin ORM en el MVP** | No hay base de datos en la primera iteración documentada; la persistencia es **archivos** o equivalente simple. |

Esta decisión es **vinculante** para el MVP salvo revisión formal del documento.

---

## 3. Arquitectura lógica (capas)

```
[ Navegador ]
     │
     ▼
[ Servidor web (nginx/Apache) ] ──► index.php (front controller)
     │
     ├── Router: coincide PATH con /, /consultas, /configuracion, /historial
     │
     ├── Middleware mínimo (futuro): sesión, CSRF si hay formularios mutantes
     │
     ├── Controladores PHP (funciones o clases simples): manejan GET/POST
     │
     ├── Servicios (stubs en MVP): ConsultaService, ConfigService, HistorialService
     │
     └── Vistas PHP (templates) + assets estáticos (/public o /assets)
```

La **integración real** con el sistema de pedidos se encapsula detrás de una interfaz única (p. ej. `OrderGatewayInterface`) implementada al principio como **“null” o fake** que devuelve datos de prueba, sin tocar este documento de arquitectura salvo el contrato de datos.

---

## 4. Estructura de carpetas propuesta

Ruta relativa al repositorio:

```
trackapp/
├── public/                 # Document root del vhost (solo esto expuesto)
│   ├── index.php           # Front controller
│   ├── css/
│   └── js/                 # Opcional, vanilla
├── src/
│   ├── Http/
│   │   ├── Router.php
│   │   └── Request.php
│   ├── Controllers/
│   ├── Services/
│   ├── Support/            # Utilidades (sanitización, fechas)
│   └── Domain/             # Enums, DTOs, reglas puras (sin I/O)
├── views/                  # Plantillas PHP (layout + páginas)
├── config/
│   └── .gitkeep            # Los archivos sensibles fuera de git o en .env.example
├── storage/                # FUERA del document root en producción ideal
│   ├── app.json            # Configuración persistida (ejemplo de nombre)
│   ├── state-map.json      # Mapeo interno → 2/3/4
│   └── logs/
│       └── consultas.log   # o rotación por fecha
├── docs/                   # Especificación y arquitectura
└── README.md
```

**Notas:**

- En **desarrollo** puede aceptarse `storage/` dentro del repo con `.gitignore` para datos y logs.
- En **producción**, `storage/` debe residir en ruta **no servible** por el servidor web.

---

## 5. Enrutamiento y PHP

- **Front controller** (`public/index.php`): carga autoload mínimo (`require` secuenciales o `spl_autoload_register` simple), instancia router, despacha.
- Rutas reservadas:
  - `GET|POST /` → Home
  - `GET|POST /consultas` → Consultas
  - `GET|POST /configuracion` → Configuración
  - `GET /historial` → Historial
- URLs “limpias” requieren **rewrite** (Apache `mod_rewrite` o nginx `try_files` → `index.php`).

---

## 6. Persistencia de configuración

**Formato:** JSON o PHP serialized; **recomendado JSON** por legibilidad y edición manual controlada.

**Contenido mínimo del MVP:**

- `base_url` y `path_template` para enlaces de tracking.
- Tabla o lista de **mapeo** `estado_interno` → `2` | `3` | `4`.
- Zona horaria para fechas en resumen.
- Opcional: nivel de **verbose** en logs.

**Escritura:**

- Solo desde **`/configuracion`** (POST validado).
- Lock de archivo (`flock`) al escribir para evitar corrupción concurrente.
- Copia de respaldo opcional `storage/backups/app-YYYYMMDD.json` (regla de producto futura).

**Secretos:**

- No en JSON versionado; usar variables de entorno `getenv()` o archivo **`storage/secrets.php`** incluido fuera de git con permisos restrictivos.

---

## 7. Persistencia de logs (consultas)

**Objetivo:** auditoría ligera sin base de datos.

**Formato:** una línea por evento en **NDJSON** o **log estándar** con campos fijos:

`timestamp ISO8601 | identificador | estado_mapeado | ok|error | mensaje_corto | ip_cliente(opcional)`

**Rotación:** manual o cron que archive `consultas-YYYYMM.log` (especificación operativa, no implementada aún).

**Privacidad:** no registrar datos personales innecesarios; truncar identificadores largos en vistas de historial si se requiere.

---

## 8. Seguridad (lineamientos para implementación futura)

- Validar y sanitizar todas las entradas (`filter_input`, listas blancas para rutas).
- **CSRF** en formularios POST de configuración y consulta si hay sesión.
- Encabezados de seguridad recomendados a nivel servidor (`X-Content-Type-Options`, etc.).
- Enlaces externos con `rel="noopener noreferrer"`.

---

## 9. Entornos y despliegue

- **PHP** 8.1+ recomendado (compatible con 8.x LTS del hosting).
- Servidor web con TLS en producción.
- Sin Node en build: los assets son archivos estáticos.

---

*Este documento describe la dirección técnica; no incluye código ejecutable.*
