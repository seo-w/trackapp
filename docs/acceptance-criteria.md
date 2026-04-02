# Criterios de aceptación — MVP TrackApp

Los criterios siguientes son **verificables** cuando exista implementación. Hasta entonces sirven como **definición de hecho** para el MVP documentado en `product-spec.md` y `architecture.md`.

---

## A1. Páginas y rutas

- [ ] **A1.1** Existe la ruta **`/`** con contenido propio y enlaces o botones de navegación hacia `/consultas`, `/configuracion` e `/historial`.
- [ ] **A1.2** Existe **`/consultas`** con formulario de consulta y zona de resultados.
- [ ] **A1.3** Existe **`/configuracion`** con capacidad de guardar y recargar parámetros persistentes (sin BD).
- [ ] **A1.4** Existe **`/historial`** que lista registros persistidos de consultas (vacío al inicio es aceptable).

---

## A2. Flujo de consulta (sin integración real aún)

- [ ] **A2.1** Enviar el formulario en `/consultas` con identificador válido muestra **un panel de resultado** con estado mapeado (2, 3 o 4), **resumen legible** según el orden definido en la especificación, y sin datos crudos no normalizados.
- [ ] **A2.2** Si la capa de datos (stub) indica un **código interno no mapeado**, la UI muestra un **mensaje explícito** (“Estado no mapeado” o equivalente) y **no** inventa un estado 2/3/4.
- [ ] **A2.3** Tras cada consulta procesada, se **escribe una línea** en el log de consultas con marca temporal e identificador.

---

## A3. Estados 2, 3 y 4

- [ ] **A3.1** Para cada estado público (2, 3, 4) existe **etiqueta** visible acorde a la tabla de producto.
- [ ] **A3.2** El mapeo **interno → 2|3|4** es **configurable** vía persistencia descrita en arquitectura (archivo en `storage/` o equivalente).
- [ ] **A3.3** Cambiar el mapeo en configuración **se refleja** en consultas posteriores sin redeploy de código.

---

## A4. Resumen legible y enlace de tracking

- [ ] **A4.1** El resumen incluye, **en el orden acordado**: referencia, estado público, fecha/hora, transportista/canal, código externo, nota breve (pueden aparecer como “—” si no hay dato).
- [ ] **A4.2** El **enlace de tracking** se construye con `base_url` + plantilla y sustitución URL-encoded; si falta dato obligatorio o la URL no valida, se muestra **estado de enlace no disponible**.
- [ ] **A4.3** Los enlaces generados abren en **nueva pestaña** con `rel="noopener noreferrer"`.

---

## A5. Configuración y logs

- [ ] **A5.1** La configuración persistida **sobrevive** al reinicio del servidor (archivo en disco u otro mecanismo acordado).
- [ ] **A5.2** Los logs no se pierden silenciosamente en error de escritura: se reporta fallo visible en entorno de desarrollo o se registra en log PHP según política definida.
- [ ] **A5.3** Los secretos **no** aparecen en el HTML de `/configuracion` ni en respuestas JSON/HTML de consulta (cuando existan secretos).

---

## A6. Arquitectura y restricciones

- [ ] **A6.1** La aplicación corre con **PHP** sin framework obligatorio y **sin dependencia de Node.js** para servir ni construir el núcleo del MVP.
- [ ] **A6.2** El **document root** del servidor apunta solo a **`public/`** (o equivalente documentado).
- [ ] **A6.3** No se introduce **base de datos** (MySQL, PostgreSQL, etc.) en el MVP; persistencia solo por **archivos** (u opción explícita equivalente aprobada).

---

## A7. Calidad mínima de experiencia

- [ ] **A7.1** Navegación coherente entre las cuatro rutas sin enlaces rotos.
- [ ] **A7.2** Formularios muestran **errores de validación** comprensibles (campo vacío, caracteres no permitidos, etc.).
- [ ] **A7.3** La interfaz es **usable en viewport móvil** (sin exigir framework CSS; basta layout legible).

---

## Definición de “MVP listo”

El MVP se considera **listo para entrega interna** cuando **todos** los criterios A1–A7 marcables están cumplidos en un entorno de prueba, con la integración de pedidos **stub** claramente identificada y plan de sustitución por integración real documentado.

---

*Versión alineada con `docs/product-spec.md` y `docs/architecture.md`. No incluye criterios de carga ni SLA.*
