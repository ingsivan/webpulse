# Despliegue

El repositorio incluye un `Dockerfile` y un blueprint de Render (`render.yaml`) para publicar una demo gratuita.

## Cómo está armada la imagen

El `Dockerfile` tiene tres etapas:

1. **vendor:** instala las dependencias de PHP sin paquetes de desarrollo.
2. **assets:** compila Tailwind y JavaScript con Vite.
3. **runtime:** `php:8.3-cli-alpine` con el código, las dependencias y los assets.

Al arrancar, `docker/start.sh`:

1. Crea la base SQLite si no existe y la llena con datos de demostración (desactívalo con `SEED_DEMO_DATA=false`).
2. Corre las migraciones y `php artisan optimize`.
3. Lanza el worker de la cola y el scheduler en segundo plano.
4. Sirve la web con `php artisan serve` y 4 workers (`WEB_WORKERS`).

Todo en un solo contenedor, porque el plan gratuito de Render no incluye workers aparte. Para más tráfico conviene separar web, cola y scheduler, y usar PHP-FPM o FrankenPHP.

CI construye esta imagen y comprueba que `/up` y la página de inicio respondan en cada PR.

## Render (gratis)

1. Entra a [render.com](https://render.com) con tu cuenta de GitHub.
2. **New → Blueprint** y elige el repositorio `webpulse`. Render lee `render.yaml`.
3. Completa las variables que pide:
   - `APP_KEY`: genera una en tu máquina con `php artisan key:generate --show`.
   - `APP_URL`: la dirección que Render te asigna, por ejemplo `https://webpulse.onrender.com`.
4. Crea el servicio. El primer build tarda unos minutos.

Limitaciones del plan gratuito:

- El servicio se duerme tras unos minutos sin visitas y tarda en despertar.
- El disco no es persistente: la base SQLite se reinicia en cada despliegue (y vuelve con los datos de demostración). Para guardar datos, usa una base gestionada (abajo).
- Mientras duerme, el monitoreo programado no corre.

## Base de datos persistente

Cambia estas variables para usar MySQL o PostgreSQL gestionado (por ejemplo Aiven, Neon o Supabase):

```env
DB_CONNECTION=mysql   # o pgsql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=webpulse
DB_USERNAME=...
DB_PASSWORD=...
```

> La imagen incluye la extensión `pdo_mysql`. Para PostgreSQL agrega `pdo_pgsql` en el `Dockerfile`.

## Correo

Por defecto los correos van al log. Para enviarlos de verdad, configura un proveedor SMTP (Resend, Mailgun, Brevo, etc.):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=tu-api-key
MAIL_FROM_ADDRESS=alertas@tu-dominio.com
```

## Variables de entorno

| Variable | Para qué |
|----------|----------|
| `APP_KEY` | Clave de cifrado de Laravel. Obligatoria. |
| `APP_URL` | URL pública; se usa en los enlaces de los correos. |
| `TRUSTED_PROXIES` | `*` detrás de un proxy como Render, para que HTTPS y los límites por IP funcionen. |
| `SEED_DEMO_DATA` | `false` para no cargar datos de demostración en una base nueva. |
| `WEB_WORKERS` | Procesos del servidor web (4 por defecto). |
| `WEBPULSE_MAX_LINKS` / `WEBPULSE_MAX_IMAGES` | Enlaces e imágenes revisados por auditoría (25 y 15). |

## Otras plataformas

La imagen funciona en cualquier servicio que corra contenedores (Railway, Fly.io, Koyeb, un VPS con Docker). También puedes usar [Laravel Cloud](https://cloud.laravel.com), que ejecuta la cola y el scheduler por ti.
