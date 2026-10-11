# Seguridad

WebPulse hace peticiones HTTP a direcciones que escriben personas desconocidas. Eso lo convierte en un objetivo clásico de **SSRF** (*Server-Side Request Forgery*): alguien podría pedirle que "audite" `http://169.254.169.254/` para leer las credenciales del servidor en la nube, o `http://localhost:6379` para hablar con servicios internos. Estas son las defensas.

## SSRF

Todo pasa por `App\Services\Audit\SafeHttpClient`:

1. **Solo `http` y `https`.** `file://`, `ftp://`, `gopher://` y demás se rechazan.
2. **Se resuelve el dominio y se revisan todas sus IPs.** Si cualquiera es privada, de loopback, link-local, reservada, CGNAT (`100.64.0.0/10`), IPv6 local o IPv4 mapeada en IPv6 (`::ffff:127.0.0.1`), la petición no se hace.
3. **Las redirecciones se siguen a mano.** Cada salto se valida igual que la URL original, así que un sitio público no puede redirigir a una IP interna. Máximo 5 saltos.
4. **La conexión se fija a la IP comprobada** con `CURLOPT_RESOLVE`. Esto evita el *DNS rebinding*: que el dominio responda con una IP pública al validar y con una interna al conectar.
5. **Límites de trabajo:** como máximo 25 enlaces y 15 imágenes por auditoría, timeouts de 5 s para conectar y 10 s por respuesta.

La misma comprobación se usa como regla de validación (`App\Rules\SafeUrl`), de modo que el usuario ve el error al instante en lugar de una auditoría fallida.

Los tests en `tests/Feature/SafeHttpClientTest.php` cubren cada caso: loopback, rangos privados, metadatos de la nube, IPv6, dominios que resuelven a IPs internas, redirecciones hacia la red interna y bucles de redirección.

## Límites de uso

| Acción | Límite |
|--------|--------|
| Auditorías (públicas o a demanda) | 3 por minuto y 30 por día, por usuario o por IP |
| Inicio de sesión y registro | 5 intentos por minuto, por correo e IP |
| Sitios monitoreados | 10 por usuario |

En producción detrás de un proxy (Render, Fly, un balanceador), `TRUSTED_PROXIES` debe estar configurado para que el límite use la IP real del visitante.

## Permisos

- Las auditorías del formulario público son públicas. Los sitios de un usuario y sus auditorías solo los ve su dueño.
- Si alguien intenta ver un sitio o una auditoría privada ajena, recibe **404** y no 403, para no confirmar que existe (`Response::denyAsNotFound()` en las policies).
- Editar, borrar o auditar a demanda un sitio requiere ser su dueño (`SitePolicy`).
- Las rutas usan ULIDs en lugar de IDs numéricos, así que no se pueden adivinar.

## Otras defensas

- **XSS:** todo el contenido de terceros (mensajes de los problemas, URLs, títulos) se imprime con `{{ }}`, que escapa HTML. Hay un test que lo comprueba con un mensaje que contiene `<title>`.
- **CSRF:** todos los formularios usan `@csrf`.
- **Asignación masiva:** los modelos declaran sus campos con `#[Fillable]` y los controladores solo guardan datos validados.
- **Sesiones:** se regeneran al entrar y se invalidan al salir. Las contraseñas usan el hash por defecto de Laravel (bcrypt).
- **Enlaces externos** en la interfaz llevan `rel="nofollow noopener"`.
