# AulaFix · Tablón de incidencias del aula
Proyecto intermodular 0612 (cliente) + 0613 (servidor) · CFGS DAW. PHP 8.3, PDO, MySQL 8 y JavaScript en módulos, sin librerías.

## Arranque
**Opción A · Local** (PHP 8.3 con `pdo_mysql` y MySQL 8 en marcha)
```bash
# Opcional: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (por defecto 127.0.0.1 / aulafix / root / sin clave)
php database/instalar.php            # crea la base, las tablas y los datos de ejemplo
php -S localhost:8000 -t public      # servidor integrado; solo public/ es accesible
```
Abre http://localhost:8000

**Opción B · Docker**
```bash
docker compose up -d --build
docker compose exec web php database/instalar.php
```
Web: http://localhost:8080 · phpMyAdmin: http://localhost:8081

## Usuarios de prueba
| Rol | Correo | Contraseña |
|---|---|---|
| Profesor | profesor@aulafix.test | Profesor2026 |
| Alumno | alumno@aulafix.test | Alumno20261 |
| Alumno | marcos@aulafix.test | Alumno20262 |

Quien se registra desde `registro.php` es siempre **alumno**.

## Cómo funciona (recorrido de una petición)
El navegador pide `incidencias.php` → PHP comprueba la sesión, consulta MySQL con PDO y genera HTML + un bloque `<script type="application/json">` con los datos → el navegador carga `js/main.js` (módulo), lee el JSON con `JSON.parse`, y sustituye la tabla de PHP por el panel interactivo. Sin JavaScript se sigue viendo la tabla de PHP (mejora progresiva).

## Decisiones técnicas
- **Solo `public/` accesible**: `config.php`, `includes/` y `database/` quedan fuera del DocumentRoot.
- **Configuración en `config.php`**, con variables de entorno para no subir contraseñas.
- **Escape en la salida** (`e()`), no al guardar: el dato se guarda tal cual y cada destino lo protege según su contexto. El JSON lleva `JSON_HEX_*` para que un `</script>` en un texto no pueda cerrar la etiqueta.
- **Transacción** al cambiar el estado: sin ella, un fallo entre el `UPDATE` y el `INSERT` del historial dejaría datos incoherentes.
- **Argon2id**, regeneración del id de sesión al entrar, cookie de sesión `HttpOnly` + `SameSite=Lax`, mismo mensaje de error para correo inexistente y clave errónea.
- **Cliente**: módulos ES (`incidencia.js`, `vistas.js`, `fechas.js`, `preferencias.js`, `nueva.js`, `main.js`), sin librerías. `ordenar()` trabaja sobre una copia para no alterar el array original. Todo texto de usuario entra con `textContent`.
- **localStorage** solo guarda pestaña, filtros, orden y el borrador de la descripción (nada sensible), con `try/catch`.

## Uso de herramientas de IA
He usado Claude y ChatGPT como apoyo puntual para resolver dudas de sintaxis, revisar comentarios PHPDoc/JSDoc y plantear posibles causas de errores. La IA no ha sustituido el desarrollo: la estructura, implementación, integración, pruebas y depuración final ha sido realizado por mi. Todo fragmento sugerido por IA fue revisado, adaptado y probado antes de incorporarlo.

## Lista de comprobación
Ver anexo del enunciado. Prueba rápida de XSS: crear una incidencia con `<b>hola</b>` → debe verse el texto tal cual.
