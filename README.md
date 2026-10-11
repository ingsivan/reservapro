# ReservaPro

[![Tests](https://github.com/ingsivan/reservapro/actions/workflows/tests.yml/badge.svg)](https://github.com/ingsivan/reservapro/actions/workflows/tests.yml)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777bb4)
![Laravel 13](https://img.shields.io/badge/Laravel-13-ff2d20)
![Livewire 4](https://img.shields.io/badge/Livewire-4-fb70a9)
![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-38bdf8)

**Sistema de reservaciones en línea para negocios de servicios.** Cada negocio tiene su página (`/reservar/mi-barberia`) donde los clientes eligen servicio, día y hora libre. El dueño ve todo en una agenda semanal, confirma o cancela citas, y nadie puede reservar dos veces el mismo horario.

![Agenda semanal del administrador](docs/screenshots/agenda.png)

> **Demo:** _pendiente de publicar en Render._ Entra como administrador con el botón **"Entrar como admin demo"** o con **admin@reservapro.test** / **password**. Cliente demo: **cliente@reservapro.test** / **password**.

## Funciones

- **Reserva pública en tres pasos**, sin crear cuenta: servicio → día y hora → datos. Solo se muestran horarios que de verdad están libres.
- **Cero dobles reservas:** cada reserva se guarda en una transacción con bloqueo de fila y la disponibilidad se vuelve a verificar en el servidor.
- **Horarios flexibles:** varios turnos por día, turnos que cruzan la medianoche, días cerrados, intervalo entre horarios, anticipación mínima y días máximos hacia adelante.
- **Zonas horarias reales:** cada negocio tiene la suya; las citas se guardan en UTC y respetan el horario de verano.
- **Correos automáticos:** confirmación al cliente, aviso al negocio, recordatorio 24 horas antes y avisos de cancelación, enviados por la cola.
- **Cancelación con enlace firmado:** el cliente cancela desde el correo, sin cuenta, dentro del plazo que defina el negocio.
- **Confirmación manual opcional:** las citas pueden quedar "por confirmar" hasta que el negocio las apruebe.
- **Panel del administrador:** agenda semanal tipo calendario, estadísticas de la semana, CRUD de servicios, horario semanal, días bloqueados y ajustes.
- **Cuentas de cliente:** quien se registra ve sus citas en "Mis citas" y puede cancelarlas.
- **Negocio demo** con dos semanas de citas alrededor de la fecha actual.

| Reserva | Confirmación | En el celular |
|---------|--------------|---------------|
| ![Página de reserva](docs/screenshots/booking.png) | ![Cita confirmada](docs/screenshots/booked.png) | ![Reserva en móvil](docs/screenshots/booking-mobile.png) |

| Inicio | Servicios | Horario |
|--------|-----------|---------|
| ![Inicio](docs/screenshots/home.png) | ![Servicios](docs/screenshots/services.png) | ![Horario y días cerrados](docs/screenshots/schedule.png) |

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Livewire 4 (con Alpine), Blade, Tailwind CSS 4, Vite |
| Base de datos | SQLite en local y en la demo; MySQL o PostgreSQL en producción |
| Trabajo en segundo plano | Colas (correos) y Scheduler (recordatorios) de Laravel |
| Pruebas | PHPUnit (81 tests), Pint |
| CI/CD | GitHub Actions, Docker, Render |

La única dependencia además de Laravel es Livewire. El calendario de la agenda está hecho con Tailwind, sin librerías de JavaScript.

## Ejecutar en local

Necesitas PHP 8.3+, Composer y Node 22+.

```bash
git clone https://github.com/ingsivan/reservapro.git
cd reservapro
cp .env.example .env
touch database/database.sqlite
composer setup              # instala dependencias, genera la clave, migra y compila assets
php artisan db:seed         # negocio demo con citas (opcional)
composer run dev            # servidor, cola y Vite a la vez
```

Abre <http://localhost:8000> y pulsa **"Reservar en el negocio demo"** o **"Entrar como admin demo"**.

Para que se envíen los recordatorios en local, deja corriendo también:

```bash
php artisan schedule:work
```

Los correos se escriben en `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Pruebas

```bash
php artisan test        # 81 tests
vendor/bin/pint --test  # estilo de código
```

Los tests fijan la fecha con `travelTo()` y cubren los casos difíciles: turnos partidos, horarios que cruzan la medianoche, zonas horarias, cambio de horario de verano, citas seguidas o encimadas, días bloqueados, dobles reservas, permisos entre negocios y el flujo completo de Livewire. CI los corre en PHP 8.3, 8.4 y 8.5, y además construye la imagen Docker y comprueba que arranca.

## Documentación

- [Arquitectura](docs/arquitectura.md): modelo de datos, cálculo de disponibilidad y cómo se evitan las dobles reservas.
- [Reglas de negocio](docs/reglas-de-negocio.md): estados de una cita, quién puede cancelar y qué correos se envían.
- [Seguridad](docs/seguridad.md): permisos, enlaces firmados y límites de uso.
- [Despliegue](docs/despliegue.md): Docker y Render paso a paso.

## Hoja de ruta

- [x] Modelos `Business`, `Service`, `Schedule`, `BlockedDate` y `Appointment` con roles
- [x] `AvailabilityService` con tests de casos difíciles
- [x] Reserva pública en pasos con Livewire, sin dobles reservas
- [x] Correos de confirmación, recordatorio y cancelación con URL firmada
- [x] Panel del administrador con agenda semanal y CRUD de servicios y horarios
- [x] CI con GitHub Actions y Docker
- [ ] Publicar la demo
- [ ] Varios empleados por negocio, cada uno con su agenda
- [ ] Pagos en línea o anticipo al reservar
- [ ] Recordatorios por WhatsApp

## Qué aprendí

- Calcular disponibilidad es más difícil de lo que parece: zonas horarias, horario de verano y turnos que cruzan la medianoche obligan a separar "hora local del negocio" de "momento en UTC".
- Evitar dobles reservas no se resuelve en la interfaz: hace falta una transacción con bloqueo y volver a validar en el servidor.
- Livewire permite construir pantallas interactivas sin escribir JavaScript, pero hay que cuidar el estado (propiedades bloqueadas, propiedades calculadas y su caché).

## Créditos

Construido por [@ingsivan](https://github.com/ingsivan) con ayuda de Claude. El diseño, las decisiones y las pruebas son míos.
