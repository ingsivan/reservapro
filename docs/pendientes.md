# Pendientes

Lo que falta o se puede mejorar, ordenado por prioridad. Cada punto dice qué resuelve y por dónde empezar en el código. Al terminar uno, márcalo aquí y en la hoja de ruta del README.

## Ya hecho

- [x] Modelos, disponibilidad con casos difíciles y reserva sin dobles reservas
- [x] Correos de confirmación, recordatorio y cancelación con enlace firmado
- [x] Panel del negocio: agenda, servicios, horario, días cerrados y ajustes
- [x] CI con GitHub Actions, Docker y demo en Render
- [x] Rediseño de la interfaz: asistente paso a paso, panel lateral de citas, filtros, toasts y ventana de confirmación ([interfaz](interfaz.md))
- [x] Agregar la cita a Google Calendar o descargarla como `.ics`
- [x] Aviso de abierto o cerrado y horario resumido en la página del negocio
- [x] Recuperar contraseña por correo
- [x] Reprogramar una cita desde el enlace del cliente y desde el panel de la cita en la agenda
- [x] Crear citas desde el panel, con correo opcional
- [x] Correos con la marca de ReservaPro y todos los textos en español
- [x] Logo y foto de portada del negocio, subidos desde Ajustes
- [x] Reportes de ingresos y citas por mes y por servicio, con exportación a CSV
- [x] Varios empleados por negocio, cada uno con su agenda, sus servicios y su horario
- [x] Avisos por WhatsApp (confirmación, recordatorio, cambio y cancelación) con la API de WhatsApp Cloud

## Prioridad alta

Funciones que un negocio real pediría el primer día.

Ya están todas hechas (ver arriba). Lo siguiente es la prioridad media.

## Prioridad media

Funciones que hacen crecer el producto.

- [ ] **Pagos en línea o anticipo al reservar** (Stripe o Mercado Pago). La cita quedaría "por confirmar" hasta recibir el pago.
- [ ] **Mover citas arrastrándolas en la agenda.** `AppointmentBooker::reschedule()` ya hace el trabajo; falta el arrastre en el calendario.
- [ ] **Días libres por persona** (vacaciones de un empleado). Hoy los días cerrados aplican a todo el negocio; bastaría un `staff_member_id` opcional en `blocked_dates`.
- [ ] **Acceso para empleados:** que cada persona entre con su cuenta y vea solo su agenda.
- [ ] **Agendar con un clic en un hueco libre del calendario.** Hoy se usa el botón «Nueva cita»; el clic abriría `NewAppointment` con el día y la hora ya elegidos.

## Prioridad baja

Detalles que mejoran la experiencia.

- [ ] **Modo oscuro**, con variantes `dark:` de Tailwind y la paleta `slate` que ya se usa.
- [ ] **Interfaz en inglés.** Hoy los textos están escritos en las vistas. Moverlos a `lang/es` y `lang/en` con `__()`.
- [ ] **Lista de espera** cuando un día está lleno: avisar por correo si se libera un horario.
- [ ] **Reseñas** después de una cita atendida, visibles en la página del negocio.
- [ ] **Instalar como app (PWA)** con un manifiesto e iconos, para que el dueño abra la agenda desde la pantalla de inicio del celular.

## Técnico

- [ ] **Pruebas de navegador** para lo que PHPUnit no ve: toasts, ventana de confirmación, barra inferior del celular y panel lateral. Puede ser con Laravel Dusk o Playwright.
- [ ] **Datos persistentes en la demo.** El plan gratis de Render borra la base SQLite cuando el servicio se duerme. Una base PostgreSQL gratuita la conservaría.
- [ ] **Borrar cuenta y datos personales** desde el perfil del cliente, por privacidad.
- [ ] **Medir y optimizar** la disponibilidad si un negocio tiene miles de citas. Ya hay índices en `appointments (business_id, starts_at)`; el siguiente paso sería guardar en caché los horarios libres de cada día.
