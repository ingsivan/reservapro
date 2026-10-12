# Interfaz y experiencia de uso

Cómo está construida la interfaz de ReservaPro, qué piezas se reutilizan y qué reglas seguir al agregar pantallas nuevas.

## Principios

1. **Un paso a la vez.** La reserva pide una sola decisión por pantalla y siempre muestra dónde va el cliente y cuánto le falta.
2. **El resumen siempre visible.** Servicio, día, hora y total acompañan al cliente desde que elige el servicio: a la derecha en la computadora y en una barra inferior en el celular.
3. **Nada irreversible sin confirmar.** Cancelar o eliminar abre una ventana que dice qué va a pasar; nada se hace con un solo clic.
4. **Respuesta inmediata.** Cada acción termina en un aviso tipo toast, y los botones que esperan al servidor muestran un estado de carga.
5. **Primero el celular.** Todas las pantallas funcionan a 390 px de ancho; la agenda cambia a un selector de día en pantallas chicas.

## Estilo visual

| Elemento | Valor |
|----------|-------|
| Color principal | `teal-600` (acciones, selección), con degradado `teal-700 → emerald-400` en portadas |
| Neutros | Escala `slate`: fondo `slate-50`, bordes `slate-200`, texto `slate-800` / `slate-500` |
| Estados de cita | Ámbar (por confirmar), verde (confirmada), azul (atendida), gris (no asistió), rojo (cancelada). Los define `AppointmentStatus::badgeClasses()`. |
| Tipografía | La del sistema (`ui-sans-serif`, `system-ui`…), sin fuentes externas |
| Esquinas | `rounded-lg` en botones y campos, `rounded-xl` / `rounded-2xl` en tarjetas |
| Números | `tabular-nums` en horas y montos para que no "bailen" |

Las animaciones (`fade-in`, `slide-in`, `pop-in`) están en `resources/css/app.css` y siempre se usan con `motion-safe:`, así que se desactivan para quien pidió menos movimiento en su sistema.

## Componentes compartidos

Están en `resources/views/components`.

| Componente | Para qué | Ejemplo |
|------------|----------|---------|
| `x-icon` | Iconos SVG de trazo, sin librerías. Sin `class` toma `size-5`. | `<x-icon name="calendar" class="size-4" />` |
| `x-toaster` | Avisos tipo toast. Muestra los mensajes `status` y `error` de la sesión y escucha el evento `toast`. Ya está en los dos layouts. | — |
| `x-confirm-dialog` | Ventana de confirmación (un `<dialog>` nativo). Ya está en los dos layouts. | Ver abajo |
| `x-field` | Etiqueta, campo y error de validación. Acepta `type="textarea"`. | `<x-field name="email" type="email" label="Correo" wire:model="email" />` |
| `x-status-badge` | Etiqueta de color con el estado de una cita. | `<x-status-badge :status="$appointment->status" />` |
| `x-admin.appointment-actions` | Botones según el estado de la cita: confirmar, cancelar, atendida o no asistió. | `<x-admin.appointment-actions :appointment="$appointment" />` |
| `x-logo` | Logo de ReservaPro. | — |

Iconos disponibles: `alert`, `arrow-left`, `arrow-right`, `bell`, `calendar`, `calendar-check`, `calendar-plus`, `check`, `check-circle`, `chevron-left`, `chevron-right`, `clock`, `copy`, `download`, `external`, `hourglass`, `link`, `logout`, `mail`, `map-pin`, `moon`, `pencil`, `phone`, `plus`, `shield`, `sliders`, `sparkles`, `sun`, `sunset`, `tag`, `trash`, `user`, `users`, `wallet` y `x`. Para agregar uno, suma su trazo (en una caja de 24 × 24) al arreglo de `icon.blade.php`.

### Mostrar un aviso

Desde un componente de Livewire que use el trait `ShowsNotices`:

```php
$this->notify('Servicio creado.');
$this->notify('No se pudo cancelar.', isError: true);
```

Desde un controlador, con un mensaje flash:

```php
return redirect()->route('home')->with('status', 'Listo.');
```

### Pedir confirmación

En lugar de `wire:confirm` o `confirm()`, se despacha el evento `confirm-action`. La ventana muestra el título y el mensaje, y al aceptar ejecuta `action`:

```blade
<button type="button"
        x-on:click="$dispatch('confirm-action', {
            title: '¿Eliminar «Corte»?',
            message: 'Ya no aparecerá en tu página de reservas.',
            confirmLabel: 'Eliminar',
            action: () => $wire.delete({{ $service->id }}),
        })">Eliminar</button>
```

`danger: false` cambia el botón rojo por uno verde. Fuera de Livewire, `action` puede enviar un formulario: `() => $refs.cancelForm.submit()`.

## Pantallas

### Reserva pública (`BookingWizard`)

```mermaid
stateDiagram-v2
    [*] --> Servicio
    Servicio --> DiaYHora: elige servicio
    DiaYHora --> TusDatos: elige hora
    DiaYHora --> Servicio: «← Servicio» o barra de progreso
    TusDatos --> DiaYHora: «← Horario»
    TusDatos --> Listo: confirma
    Listo --> Servicio: «Reservar otra cita»
```

- La propiedad `step` guarda el paso visible. `goToStep()` solo deja ir a un paso cuyas decisiones previas ya están tomadas.
- Si alguien gana la hora mientras el cliente llena sus datos, la reserva regresa al paso 2 con el aviso "Ese horario ya no está disponible".
- Cada día muestra cuántos horarios libres tiene, "Cerrado" si el negocio no abre ese día de la semana, o "Sin lugar" si ya se llenó o está bloqueado.
- Al cambiar de paso, la página se desplaza hasta el asistente si quedó fuera de la vista (importante en el celular).
- La confirmación ofrece Google Calendar, un archivo `.ics` (Apple Calendar, Outlook) y el enlace para ver o cancelar.

### Panel del negocio

- **Menú lateral** en pantallas grandes y pestañas con iconos en el celular. Incluye el enlace a la página de reservas con un botón para copiarlo.
- **Agenda:**
  - Métricas de la semana y filtros por estado.
  - Calendario semanal con una línea roja en la hora actual.
  - Lista de citas del día seleccionado.
  - Al hacer clic en una cita se abre un panel lateral con sus datos y acciones; se cierra con Esc o al hacer clic fuera.
  - El filtro queda en la URL (`?estado=pending`).
- **Servicios:** tarjetas con un interruptor Visible / Oculto; el formulario se abre en una ventana.
- **Horario:** ventanas por día con una barra que las dibuja sobre las 24 horas.
- **Ajustes:** tarjeta con el enlace público y los datos del negocio.

### Cliente

- **Tu cita** (enlace del correo): datos, botones de calendario, llamar al negocio y cancelar con confirmación.
- **Mis citas:** próximas con su fecha destacada y anteriores en una lista compacta.
- **Entrar:** muestra las cuentas de prueba; al pulsar una, se llenan los campos.

## Accesibilidad

- Los pasos usan `aria-current="step"`; los días, `role="listbox"` y `aria-selected`; el interruptor de servicios, `role="switch"` y `aria-checked`.
- Las ventanas usan `role="dialog"` y `aria-modal`, se cierran con Esc y mueven el foco adentro al abrirse (al botón de cerrar en el panel de la cita, al primer campo en el formulario de servicios, a «Volver» en la confirmación).
- Los botones que solo tienen icono llevan `aria-label`.
- Los avisos usan `role="status"`, o `role="alert"` si son errores, dentro de una región `aria-live`.

## Capturas del README

Las capturas de `docs/screenshots` se toman con Playwright contra la base de datos de demostración:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve --port=8123
```

Usa una ventana de 1280 × 860 para escritorio y de 390 × 844 para el celular, y vuelve a tomar las capturas cuando cambie una pantalla que aparece en el README.
