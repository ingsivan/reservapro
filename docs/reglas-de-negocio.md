# Reglas de negocio

## Estados de una cita

```mermaid
stateDiagram-v2
    [*] --> Pendiente: reserva (si el negocio confirma a mano)
    [*] --> Confirmada: reserva (confirmación automática)
    Pendiente --> Confirmada: el negocio confirma
    Pendiente --> Cancelada: cliente o negocio
    Confirmada --> Cancelada: cliente o negocio
    Confirmada --> Atendida: después de la hora
    Confirmada --> NoAsistió: después de la hora
    Pendiente --> Atendida: después de la hora
    Pendiente --> NoAsistió: después de la hora
```

| Estado | ¿Ocupa el horario? | Notas |
|--------|-------------------|-------|
| Por confirmar | Sí | Aparta el horario mientras el negocio decide. |
| Confirmada | Sí | |
| Atendida | Sí | Solo se marca cuando la cita ya empezó. |
| No asistió | Sí | Solo se marca cuando la cita ya empezó. |
| Cancelada | No | El horario vuelve a estar disponible. |

## Quién puede hacer qué

| Acción | Cliente sin cuenta | Cliente con cuenta | Dueño del negocio |
|--------|--------------------|--------------------|-------------------|
| Reservar | Sí | Sí (sus datos se llenan solos) | Desde la agenda, para cualquier cliente |
| Ver su cita | Con el enlace del correo | En "Mis citas" y con el enlace | En la agenda |
| Cambiar el horario | Hasta N horas antes | Hasta N horas antes | Siempre que no haya empezado |
| Cancelar | Hasta N horas antes | Hasta N horas antes | Siempre que no haya empezado |
| Confirmar | No | No | Sí |
| Marcar atendida / no asistió | No | No | Sí, cuando ya empezó |

N es el "aviso para cancelar" que define cada negocio (2 horas en la demo).

Cuando el negocio agenda o mueve una cita desde la agenda:

- No aplica la anticipación mínima (solo se ocultan las horas que ya pasaron), para atender a quien llega sin cita.
- Las citas que agenda nacen confirmadas aunque esté encendido "Confirmar cada cita manualmente".
- El correo del cliente es opcional. Sin correo, no se envía confirmación, recordatorio ni avisos.
- Al dueño no le llega el aviso de "nueva cita", porque la creó él.

Al cambiar el horario de una cita:

- Solo se ofrecen horas libres que cumplen las mismas reglas que una reserva nueva (horario, días cerrados, anticipación mínima y ventana de reserva). El horario que la cita ocupa ahora cuenta como libre, así que se puede mover unos minutos.
- La cita conserva su código, su precio y su estado: si estaba "por confirmar", lo sigue estando.
- Si ya se había enviado el recordatorio, se vuelve a enviar para el nuevo horario.

## Ajustes de cada negocio

| Ajuste | Ejemplo | Efecto |
|--------|---------|--------|
| Mostrar horarios cada | 30 min | Cuadrícula de inicios: 10:00, 10:30, 11:00… |
| Anticipación mínima | 1 hora | No se puede reservar algo que empieza en menos de una hora. |
| Se puede reservar hasta | 30 días | Días hacia adelante que se muestran. |
| Cancelación en línea hasta | 2 horas antes | Pasado ese plazo, el cliente debe llamar. |
| Confirmar cada cita manualmente | Apagado | Si se enciende, las reservas nacen "por confirmar". |
| Zona horaria | Ciudad de México | Todas las horas del negocio se interpretan en esta zona. |

## Correos

| Cuándo | Para quién | Contenido |
|--------|-----------|-----------|
| Se reserva | Cliente | Confirmación (o "solicitud recibida") con enlace para ver o cancelar. |
| Se reserva | Dueño | Datos de la cita y enlace a la agenda en ese día. |
| El negocio confirma | Cliente | Aviso de confirmación. |
| El negocio cancela | Cliente | Aviso y enlace para reservar de nuevo. |
| El cliente cancela | Dueño | Aviso de que se liberó el horario. |
| El negocio cambia el horario | Cliente | Horario anterior y nuevo, con el enlace para cambiarlo otra vez o cancelar. |
| El cliente cambia el horario | Dueño | Horario anterior y nuevo, con enlace a la agenda en ese día. |
| Alguien pide recuperar su contraseña | Esa persona | Enlace para elegir una contraseña nueva, válido 60 minutos. |
| 24 h antes (citas confirmadas) | Cliente | Recordatorio con dirección y enlace. No se envía a quien reservó hace menos de 2 horas. |

Los correos se mandan por la cola, así que reservar no espera al servidor de correo. En la demo se escriben en el log (`MAIL_MAILER=log`).

## Página pública

- El aviso de la portada dice "Abierto · cierra a las HH:MM" si la hora actual del negocio cae dentro de una ventana de horario (también la de un turno de ayer que cruzó la medianoche). Si no, dice cuándo vuelve a abrir: hoy, mañana o el día de la semana, saltando los días bloqueados. Si no abre en la próxima semana, solo dice "Cerrado".
- El horario agrupa los días seguidos con las mismas horas ("Lun – Vie").
- Si el negocio subió logo y portada, se muestran arriba; si no, sus iniciales sobre un cuadro oscuro y un degradado turquesa.
- En el selector de días, un día sin horarios libres dice "Cerrado" si el negocio no abre ese día de la semana, o "Sin lugar" si ya se llenó o está bloqueado.
- Los botones de calendario usan las horas en UTC, así que el evento aparece a la hora correcta en cualquier zona horaria. El archivo `.ics` va firmado como el resto de los enlaces de la cita.

## Servicios y horario

- Un servicio con citas registradas no se borra: se oculta, para no romper el historial.
- Quitar una ventana de horario no cancela las citas ya reservadas en ella.
- Bloquear un día con citas avisa al dueño cuántas hay, para que decida si las cancela.
- Dos ventanas del mismo día no pueden encimarse.
