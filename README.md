# TrackApp

Aplicación web en **PHP puro** (sin framework de aplicación ni **Node.js**) para consultar órdenes en Merkaweb por estados **2, 3 y 4**, normalizar resultados, generar enlaces de seguimiento y registrar el historial de consultas.

## Requisitos de PHP

- **PHP 8.1 o superior** (recomendado 8.2+)
- Extensiones: **pdo_mysql**, **openssl**, **json**, **session**, **curl**, **mbstring** (recomendada para textos)

No se utiliza Composer para el núcleo de la aplicación ni runtime Node.

## Instalación rápida

1. Clonar o subir el proyecto al servidor.
2. Apuntar el **document root** del virtual host a la carpeta **`public/`** (no sirvas `app/`, `config/` ni `storage/` directamente).
3. Copiar **`.env.example`** a **`.env`** y completar variables (ver siguientes secciones).
4. Crear la base de datos MySQL/MariaDB y un usuario con permisos sobre esa base.
5. Ejecutar migración: `php database/migrate.php`
6. Ajustar permisos de escritura si el host lo requiere para `storage/logs/` y sesiones PHP.

## Base de datos

Definir en **`.env`** (o variables del panel del hosting):

| Variable | Descripción |
|----------|-------------|
| `DB_ENABLED` | `true` para activar PDO en la aplicación |
| `DB_HOST` | Host MySQL (p. ej. `127.0.0.1` o el socket/hostname del proveedor) |
| `DB_PORT` | Puerto (por defecto `3306`) |
| `DB_DATABASE` | Nombre de la base de datos |
| `DB_USERNAME` | Usuario |
| `DB_PASSWORD` | Contraseña |

La configuración efectiva está en `config/database.php` (lee `getenv`).

## Inicialización del esquema (`schema.sql`)

Desde la raíz del proyecto:

```bash
php database/migrate.php
```

Esto ejecuta las sentencias de **`database/schema.sql`** (tablas `app_settings` y `query_logs`). Requiere acceso de red al servidor MySQL y credenciales válidas.

## APP_KEY (cifrado del token API)

El token de Merkaweb se guarda **cifrado** en base de datos. Hace falta una clave de aplicación:

- En **`.env`**: `APP_KEY=` (cadena larga y aleatoria, p. ej. `openssl rand -hex 32`)
- O definir la variable en el panel del hosting (equivalente a `getenv('APP_KEY')`).

Sin `APP_KEY` no se puede cifrar/descifrar el token al guardar o al llamar al API.

## Uso de las pantallas

### `/configuracion`

- Guarda **URL base del API**, **tienda_id** y **token de acceso** (el token no se reenvía al navegador una vez guardado).
- **Probar conexión** ejecuta una petición real al API (estado 2) usando la configuración persistida.
- Requiere `DB_ENABLED=true`, migración aplicada y `APP_KEY` para operaciones con token.

### `/consultas`

- Selección de estados **2, 3 y 4** (uno, varios o todos).
- **Consultar órdenes** llama al API por cada estado, **normaliza** y muestra tabla (escritorio) o tarjetas (móvil).
- **Historial de consulta**: cada envío registra una fila en `query_logs` (estados pedidos, número de resultados tras deduplicar, éxito global de las llamadas y mensaje de error agregado si hubo fallos parciales).

### `/historial`

- Lista las entradas de `query_logs`: **fecha y hora**, **estados consultados**, **cantidad de resultados**, **éxito o error** y **mensaje de error** cuando aplica.
- Lectura desde base de datos; si esta no está disponible, se muestra un aviso claro.

## Despliegue en hosting compartido típico

- **Document root**: solo `public/`. Colocar el resto del proyecto **por encima** de `public_html` si el proveedor lo permite, o asegurarse de que no haya listado de directorios en `app/`, `config/`, `storage/`.
- **`.env`**: no versionar; permisos restrictivos (`600`). El repositorio incluye **`.env.example`** sin secretos.
- **Rewrite**: usar `.htaccess` en `public/` (incluido) con `mod_rewrite`, o reglas equivalentes en Nginx (`try_files` hacia `index.php`).
- **PHP de consola**: si no hay SSH, subir SQL manualmente o ejecutar el contenido de `database/schema.sql` desde phpMyAdmin y usar el panel para variables de entorno donde exista.
- **HTTPS**: recomendado; activar cookies seguras en producción (`SESSION_SECURE=true` en `.env` si corresponde).
- **Rendimiento**: sin build frontend; Bootstrap e iconos se cargan por CDN (si se desea sin CDN, descargar assets en `public/assets/` y actualizar el layout).

## Estructura relevante

- `public/index.php` — frontal único
- `app/` — controladores, dominio, servicios, vistas
- `config/` — configuración PHP (sin secretos en git)
- `database/schema.sql` — esquema base
- `storage/` — logs y datos locales (ignorar en git salvo `.gitkeep`)

## Licencia y soporte

Proyecto interno TrackApp. Ajustar según las políticas de tu organización.
