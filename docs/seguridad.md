# Seguridad

## Permisos

- El panel (`/panel/*`) exige sesión y rol de administrador con un negocio (`EnsureUserIsAdmin`). Ese middleware se registra como persistente en Livewire, así que también se revisa en cada acción, no solo al cargar la página.
- Cada acción del panel busca los registros a través del negocio del usuario (`$business->appointments()->findOrFail($id)`). Si alguien manda el id de una cita o un servicio de otro negocio, recibe un 404. Hay tests para esto.
- "Mis citas" busca solo entre las citas del usuario con sesión.
- Las propiedades que no deben cambiar desde el navegador (el negocio de la página de reserva, el id que se edita, el código de la cita creada) están marcadas con `#[Locked]`.

## Enlaces firmados

El cliente sin cuenta administra su cita con un enlace de `URL::signedRoute()`. La firma cubre la URL completa, así que no se puede cambiar el código para ver o cancelar la cita de otra persona; cualquier cambio da 403. El formulario de cancelar también va firmado.

El código público de la cita (8 caracteres) no es el id de la base de datos y evita caracteres confusos (0/O, 1/I/L).

## Reservas

- El servidor vuelve a calcular la disponibilidad antes de guardar. El navegador solo elige entre los horarios que el servidor ofreció, y aun así se verifica de nuevo dentro de la transacción.
- Solo se pueden reservar servicios activos del negocio de la página.
- Límite de 5 reservas por hora por IP (`RateLimiter`), para que nadie llene la agenda con reservas falsas.
- Inicio de sesión, registro y el botón de demo tienen límite de 5 intentos por minuto.

## Otros

- Contraseñas con `bcrypt` y la regla `Password::defaults()`.
- Protección CSRF de Laravel en todos los formularios y en las peticiones de Livewire.
- Todo el contenido que escriben los usuarios se muestra escapado con `{{ }}`.
- Detrás de Render se usa `TRUSTED_PROXIES=*` para que los enlaces firmados usen HTTPS y los límites por IP vean la IP real.
