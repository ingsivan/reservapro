# Guía de uso

Cómo se usa ReservaPro, paso a paso, desde los dos lados: el cliente que reserva y el dueño del negocio. Para probarlo, usa la [demo](https://reservapro.onrender.com) o tu instalación local con los datos de demostración (`php artisan db:seed`).

| Cuenta de prueba | Correo | Contraseña |
|------------------|--------|------------|
| Dueño del negocio | admin@reservapro.test | password |
| Cliente | cliente@reservapro.test | password |

## Para el cliente

### Reservar una cita

No hace falta crear una cuenta.

1. Abre la página del negocio, por ejemplo `/reservar/barberia-demo`. Arriba verás si está abierto ahora y su horario.
2. **Servicio:** elige uno. Cada tarjeta muestra precio y duración.
3. **Día y hora:**
   - Los días muestran cuántos horarios libres quedan. Usa las flechas para ver más días.
   - Las horas se agrupan en mañana, tarde y noche. Elige una.
4. **Tus datos:** escribe tu nombre y correo (teléfono y notas son opcionales) y pulsa **Confirmar reserva**. En el celular, el botón está en la barra inferior.
5. Verás tu código de cita y llegará un correo con los detalles.

Si la reserva dice "Solicitud enviada", el negocio confirma cada cita a mano: te llegará otro correo cuando la apruebe.

Puedes regresar a un paso anterior en cualquier momento con la barra de progreso o con los botones «← Servicio» y «← Horario». No pierdes lo que ya escribiste.

### Guardarla en tu calendario

En la pantalla de confirmación y en la página de tu cita:

- **Google Calendar** abre el evento ya lleno.
- **Apple / Outlook (.ics)** descarga un archivo que abre cualquier calendario.

### Ver o cancelar tu cita

- Abre el enlace **Ver o cancelar mi cita** del correo. No necesitas contraseña: el enlace está firmado y solo sirve para esa cita.
- Pulsa **Cancelar mi cita** y confirma. El negocio recibe un aviso y el horario queda libre.
- Solo puedes cancelar en línea hasta cierto tiempo antes (2 horas en la demo). Después, la página te muestra el teléfono del negocio.

### Con cuenta de cliente

Si te registras como cliente, tus datos se llenan solos al reservar, y en **Mis citas** ves tus citas próximas y anteriores y puedes cancelar.

## Para el dueño del negocio

### Empezar

1. Pulsa **Crear cuenta**, elige "Tengo un negocio" y escribe su nombre. Se crea con un horario de ejemplo.
2. En **Servicios**, crea lo que ofreces.
3. En **Horario**, ajusta los días y horas en que atiendes.
4. En **Ajustes**, copia el enlace de tu página de reservas y compártelo (redes, WhatsApp, Google Maps). También está en el menú lateral.

### Agenda

- **Arriba:** citas de hoy, citas por confirmar, citas de la semana e ingresos estimados. Pulsa "Por confirmar" para ver solo esas.
- **Filtros:** muestra solo las citas de un estado (por confirmar, confirmadas, atendidas…).
- **Calendario:** cada bloque es una cita. La línea roja marca la hora actual. Usa las flechas para cambiar de semana y **Hoy** para regresar.
- **Clic en una cita:** abre un panel con el cliente, su correo y teléfono (con enlace para escribir o llamar), la nota que dejó y el historial de la cita.
- **Acciones:**
  - Antes de la cita: **Confirmar** (si está por confirmar) o **Cancelar**.
  - Después de la hora de inicio: **Atendida** o **No asistió**.
  - Al confirmar o cancelar, el cliente recibe un correo.
- En el celular, toca un día de la fila superior para ver sus citas.

### Servicios

- **Nuevo servicio** abre una ventana con nombre, duración, precio y descripción.
- El interruptor de cada tarjeta lo muestra u oculta en tu página sin borrarlo.
- **Eliminar** borra el servicio. Si ya tiene citas registradas, solo lo oculta para conservar el historial.

### Horario

- Agrega una o varias ventanas por día (por ejemplo, 10:00–14:00 y 16:00–20:00). La barra de cada día las dibuja sobre las 24 horas.
- Si la hora de cierre es menor que la de apertura (22:00–02:00), el turno termina al día siguiente.
- En **Días cerrados**, bloquea festivos o vacaciones. Ese día nadie podrá reservar.

### Ajustes

| Ajuste | Qué hace |
|--------|----------|
| Dirección de tu página | La parte final del enlace: `/reservar/mi-negocio`. |
| Zona horaria | En qué hora se interpretan tus horarios. |
| Mostrar horarios cada | Cada cuántos minutos se ofrece una hora de inicio. |
| Anticipación mínima | Cuánto antes de la cita se puede reservar como mínimo. |
| Se puede reservar hasta | Cuántos días hacia adelante se muestran. |
| Cancelación en línea hasta | Hasta cuántas horas antes puede cancelar el cliente por su cuenta. |
| Confirmar cada cita manualmente | Las reservas quedan "por confirmar" hasta que las apruebes. Mientras tanto, el horario queda apartado. |

Las reglas completas están en [Reglas de negocio](reglas-de-negocio.md).
