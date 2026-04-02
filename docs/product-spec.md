# Especificación de producto — TrackApp (MVP)

## 1. Objetivo de la webapp

Permitir a operadores internos (o usuarios autorizados) **consultar el estado de pedidos** a partir de un identificador o referencia, **visualizar un resumen legible** por pedido y **acceder al enlace de seguimiento** cuando aplique, sin exponer lógica de negocio compleja en el cliente. La aplicación prioriza **claridad**, **trazabilidad de consultas** y **configuración centralizada** de parámetros de conexión y plantillas de URL.

---

## 2. Flujo principal de consulta

1. El usuario entra en **`/consultas`** (o en **`/`** y navega a consultas).
2. Introduce **identificador del pedido** (y, si el modelo lo requiere, datos auxiliares definidos en configuración; en el MVP puede bastar un solo campo).
3. Envía el formulario (**consulta síncrona** en el MVP: la petición se procesa en la misma solicitud HTTP).
4. El sistema **resuelve el estado del pedido** (valores numéricos/códigos internos) y lo **mapea** a la taxonomía pública de estados **2, 3 y 4** (ver §4).
5. Se muestra:
   - el **estado actual mapeado** (etiqueta y descripción corta),
   - el **resumen legible por pedido** (ver §5),
   - el **enlace de tracking** construido según reglas fijas (ver §6), si la configuración y los datos lo permiten.
6. Cada consulta relevante queda **registrada en historial/log** (ver `architecture.md`) para auditoría y depuración, sin sustituir el sistema de origen de pedidos.

Flujos secundarios del MVP:

- **`/configuracion`**: definir o ajustar parámetros persistentes (URLs base, plantillas, credenciales referenciadas por nombre de archivo, etc., según arquitectura).
- **`/historial`**: listar consultas recientes registradas localmente (solo lectura en MVP salvo decisión futura de purga).

---

## 3. Reglas de negocio (MVP)

| ID | Regla |
|----|--------|
| R1 | Un **pedido** se identifica de forma **única** en el contexto de la consulta mediante el identificador introducido por el usuario. |
| R2 | Los estados **expuestos en la interfaz** se limitan a la taxonomía **Estado 2, Estado 3 y Estado 4**; cualquier otro código interno debe **agruparse o traducirse** a uno de estos tres antes de mostrarse (o mostrarse como “pendiente de clasificar” solo si se acuerda una excepción documentada). |
| R3 | El **resumen legible** se construye siempre con los mismos campos y orden definidos en §5; no se muestran datos crudos del proveedor sin normalizar. |
| R4 | El **enlace de tracking** solo se muestra si la **plantilla** y los **datos mínimos** requeridos están presentes; en caso contrario se muestra un mensaje explícito de “enlace no disponible”. |
| R5 | Las **credenciales y secretos** no se almacenan en el documento HTML ni en respuestas cache; viven en **configuración persistente** del servidor (archivos fuera de la raíz pública o variables de entorno del host, según `architecture.md`). |
| R6 | Toda **consulta** que llegue a ejecutarse contra la capa de integración (aunque en el MVP sea simulada o mock) debe **registrarse** con marca temporal, identificador consultado y resultado agregado (éxito/error/código de estado mapeado). |
| R7 | No se implementa en este MVP **autenticación de usuarios final** salvo que el despliegue lo imponga a nivel de servidor (HTTP Basic, VPN, etc.); si se añade, se documentará como incremento. |

---

## 4. Mapeo de estados 2, 3 y 4

Los **códigos internos** (procedentes del sistema de pedidos o de reglas futuras) se **normalizan** a tres estados públicos:

| Estado público | Código UI | Significado operativo | Ejemplo de etiqueta (editable en copy) |
|----------------|-----------|------------------------|----------------------------------------|
| **Estado 2** | `2` | Pedido **recibido / en preparación** — aún no despachado al transportista. | “En preparación” |
| **Estado 3** | `3` | Pedido **en tránsito** — confiado al transportista o en reparto. | “En camino” |
| **Estado 4** | `4` | Pedido **entregado o cerrado** positivamente (incluye entrega confirmada o cierre logístico equivalente). | “Entregado” |

**Reglas de mapeo:**

- La tabla exacta **código interno → {2,3,4}** se mantendrá en **configuración versionada** o en un archivo de reglas legible por PHP; en ausencia de coincidencia, el MVP debe mostrar **error de mapeo** o estado “desconocido” según se acuerde en `acceptance-criteria.md` (por defecto: mensaje claro sin inventar estado).
- Los estados **1** u otros numéricos **no** forman parte de la superficie pública del MVP; si el origen envía “1”, se mapea a **Estado 2** solo si la tabla de configuración lo define así.

---

## 5. Definición del resumen legible por pedido

El **resumen** es un bloque de texto y/o lista **siempre en el mismo orden**, independiente del proveedor, construido a partir de datos normalizados:

1. **Referencia del pedido** — igual al identificador normalizado.
2. **Estado público** — uno de: Estado 2, Estado 3, Estado 4 (con etiqueta humana).
3. **Fecha/hora última actualización** — en zona horaria configurada (por defecto la del servidor hasta que exista preferencia de usuario).
4. **Transportista o canal** — texto corto; si no hay dato, “—”.
5. **Código de seguimiento externo** — si existe; si no, “—”.
6. **Nota breve** — mensaje operativo opcional (máx. ~160 caracteres en MVP) para incidencias simples.

Formato visual sugerido (no obligatorio en esta fase): lista con etiquetas, tipografía legible, sin tablas densas en móvil.

---

## 6. Regla de construcción del enlace de tracking

**Entradas:**

- `base_url` — URL absoluta definida en configuración (sin barra final obligatoria; el sistema la normaliza).
- `path_template` — plantilla con **marcadores**; en el MVP se define `{order_id}` y opcionalmente `{tracking_code}`.

**Algoritmo (lógica documentada; implementación posterior):**

1. Normalizar `base_url` (trim, `https://` obligatorio en producción salvo entorno local).
2. Sustituir cada marcador por el valor **URL-encoded** correspondiente al pedido actual.
3. Concatenar `base_url` + `path_template` ya sustituida; validar con `filter_var($url, FILTER_VALIDATE_URL)`.
4. Si falta algún marcador requerido o la URL no valida, **no** mostrar enlace; mostrar aviso según R4.

**Ejemplo:**

- `base_url` = `https://carrier.example.com/track`
- `path_template` = `?ref={order_id}&code={tracking_code}`
- Resultado: `https://carrier.example.com/track?ref=ABC123&code=XYZ789`

---

## 7. Lista de páginas

| Ruta | Propósito |
|------|-----------|
| **`/`** | Punto de entrada: bienvenida, accesos a consultas, configuración e historial; puede incluir texto de ayuda mínimo. |
| **`/consultas`** | Formulario de consulta + área de resultados (estado mapeado, resumen, enlace). |
| **`/configuracion`** | Formularios o paneles para editar parámetros persistentes (plantilla de URL, mapeo de estados, rutas de log, etc.). |
| **`/historial`** | Lista de consultas registradas con filtros básicos (por fecha o por identificador) en MVP incremental si el tiempo lo permite; mínimo: tabla/lista paginada simple. |

---

## 8. Componentes de interfaz principales

- **Cabecera global** — título del producto y navegación a las cuatro secciones.
- **Formulario de consulta** — campo(s) de entrada, botón enviar, mensajes de validación.
- **Panel de resultado** — estado (badge o destacado), resumen legible estructurado, enlace externo con `target="_blank"` y `rel="noopener noreferrer"`.
- **Alertas / mensajes** — éxito, error de red, error de mapeo, configuración incompleta.
- **Formularios de configuración** — agrupados por sección (URL, mapeos, registro).
- **Tabla o lista de historial** — columnas: fecha, identificador, estado mapeado, éxito/error.
- **Pie de página opcional** — versión de la app y enlace a documentación interna.

---

## 9. Alcance explícito fuera del MVP

- Lógica de negocio real con APIs de terceros **no** incluida en esta especificación más allá de los contratos descritos.
- Base de datos relacional **no** requerida para esta fase documental; la persistencia concreta se describe en `architecture.md` (archivos / SQLite futuro, etc.).

---

*Documento vivo: ajustar mapeos y copy con el negocio real antes de implementación.*
