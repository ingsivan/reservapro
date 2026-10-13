# Despliegue

El repositorio incluye un `Dockerfile` y un blueprint de Render (`render.yaml`) para publicar una demo gratuita.

## Cómo está armada la imagen

El `Dockerfile` tiene tres etapas:

1. **vendor:** instala las dependencias de PHP sin paquetes de desarrollo.
2. **assets:** compila Tailwind y JavaScript con Vite.
3. **runtime:** `php:8.3-cli-alpine` con el código, las dependencias y los assets.

Al arrancar, `docker/start.sh`:

1. Crea la base SQLite si no existe y la llena con el negocio demo (desactívalo con `SEED_DEMO_DATA=false`).
2. Corre las migraciones y `php artisan optimize`.
3. Lanza el worker de la cola (correos y WhatsApp) y el scheduler (recordatorios) en segundo plano.
4. Sirve la web con `php artisan serve` y 4 workers (`WEB_WORKERS`).

La app instalable (PWA) no necesita configuración: el manifiesto lo genera Laravel y `public/sw.js` se sirve como archivo estático. Los navegadores solo permiten instalarla con HTTPS (Render lo da) o en `localhost`. Si cambias `sw.js` de forma que deba descartar lo guardado, sube la versión en `CACHE`.

Todo en un solo contenedor, porque el plan gratuito de Render no incluye workers aparte. CI construye esta imagen y comprueba que `/up` y la página de inicio respondan en cada PR.

## Render paso a paso (gratis)

Es igual que con WebPulse.

1. **Genera la clave de la app** en tu máquina, dentro de la carpeta del proyecto:

   ```bash
   php artisan key:generate --show
   ```

   Copia el resultado completo, incluido `base64:`.

2. Entra a [dashboard.render.com](https://dashboard.render.com) con tu cuenta de GitHub.
3. Arriba a la derecha, **New → Blueprint**.
4. Elige el repositorio **ingsivan/reservapro** y pulsa **Connect**. Si no aparece, pulsa **Configure account** y dale acceso a Render a ese repositorio.
5. Ponle un nombre al blueprint (por ejemplo `reservapro`). Render muestra el servicio `reservapro` que leyó de `render.yaml`.
6. Llena las dos variables que pide:
   - `APP_KEY`: la clave del paso 1.
   - `APP_URL`: déjala por ahora como `https://reservapro.onrender.com`; la corriges en el paso 8.
7. Pulsa **Apply**. El primer build tarda entre 5 y 10 minutos. Cuando el servicio diga **Live**, ábrelo.
8. Copia la dirección real que Render te asignó (arriba, bajo el nombre del servicio; puede tener letras extra, como `reservapro-ab12.onrender.com`). Ve a **Environment**, cambia `APP_URL` por esa dirección y guarda. Render vuelve a desplegar solo.
9. Prueba la demo: reserva una cita desde "Reservar en el negocio demo" y luego entra con "Entrar como admin demo" para verla en la agenda.

Cada push a `main` despliega de nuevo automáticamente.

Limitaciones del plan gratuito:

- El servicio se duerme tras unos 15 minutos sin visitas; la siguiente visita tarda unos 50 segundos en despertarlo.
- El disco no es persistente: al dormir o al desplegar, la base SQLite vuelve a los datos de demostración, con citas alrededor de la fecha de ese momento, y los logos y portadas que se hayan subido se borran (el negocio demo vuelve a tener los suyos). Para una demo pública eso es una ventaja. En producción, guarda las imágenes en S3 o similar con `FILESYSTEM_DISK`.
- Mientras duerme, no se envían recordatorios.

## Base de datos persistente

Para un negocio real usa MySQL o PostgreSQL gestionado (Aiven, Neon, Supabase…):

```env
DB_CONNECTION=mysql   # o pgsql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=reservapro
DB_USERNAME=...
DB_PASSWORD=...
SEED_DEMO_DATA=false
```

> La imagen incluye `pdo_mysql`. Para PostgreSQL agrega `pdo_pgsql` en el `Dockerfile`.

## Correo

En la demo los correos van al log. Para enviarlos de verdad, configura un proveedor SMTP (Resend, Mailgun, Brevo…):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=tu-api-key
MAIL_FROM_ADDRESS=citas@tu-dominio.com
MAIL_FROM_NAME=ReservaPro
```

## WhatsApp

Sin credenciales, los mensajes de WhatsApp se escriben en el log. Para enviarlos de verdad se usa la API de WhatsApp Cloud de Meta (gratis para empezar; Meta cobra por conversación según el país):

1. En [developers.facebook.com](https://developers.facebook.com) crea una app de tipo **Negocio** y agrégale el producto **WhatsApp**.
2. En **WhatsApp > Configuración de la API** copia el **Identificador del número de teléfono**. Para producción, agrega tu propio número y crea un token permanente con un **usuario del sistema** en el Business Manager (el token de prueba vence en 24 horas).
3. En **WhatsApp Manager > Plantillas de mensajes** crea estas cinco plantillas, categoría **Utilidad**, idioma **Español (MEX)**. El texto de cada una está en `App\Notifications\Messages\WhatsAppMessage::TEMPLATES`, con las variables `{{1}}` a `{{5}}` (nombre, negocio, servicio, fecha y enlace):
   - `cita_confirmada`
   - `cita_solicitada`
   - `recordatorio_cita`
   - `cita_reprogramada`
   - `cita_cancelada`
4. Cuando Meta las apruebe, agrega las variables en Render (**Environment**) y vuelve a desplegar:

```env
WHATSAPP_TOKEN=tu-token
WHATSAPP_PHONE_NUMBER_ID=123456789012345
```

Opcionales: `WHATSAPP_API_VERSION` (por defecto `v21.0`) y `WHATSAPP_TEMPLATE_LANGUAGE` (por defecto `es_MX`, debe coincidir con el idioma de las plantillas). Con el número de prueba de Meta solo puedes escribir a los números que registres como destinatarios.

## Variables de entorno

| Variable | Para qué |
|----------|----------|
| `APP_KEY` | Clave de cifrado de Laravel y de los enlaces firmados. Obligatoria. |
| `APP_URL` | URL pública; se usa en los enlaces de los correos. |
| `TRUSTED_PROXIES` | `*` detrás de un proxy como Render, para HTTPS y límites por IP correctos. |
| `SEED_DEMO_DATA` | `false` para no cargar el negocio demo en una base nueva. |
| `WEB_WORKERS` | Procesos del servidor web (4 por defecto). |
| `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID` | Credenciales de WhatsApp Cloud. Sin ellas, los mensajes van al log. |

## Otras plataformas

La imagen funciona en cualquier servicio de contenedores (Railway, Fly.io, Koyeb, un VPS con Docker). También puedes usar [Laravel Cloud](https://cloud.laravel.com), que ejecuta la cola y el scheduler por ti.
