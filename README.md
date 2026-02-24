<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel Logo" />
</p>

# Sistema de Turnos Médicos — Centro Médico del Milagro

Aplicación Laravel para gestionar turnos médicos: registro de usuarios, agenda de profesionales, turnos, y panel de administración.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- Extensiones PHP típicas de Laravel (pdo, openssl, mbstring, etc.)

## Puesta en marcha (local)

1. Clonar el repositorio
   ```bash
   git clone https://github.com/13996F/turnos-medico.git
   cd turnos-medico
   ```

2. Instalar dependencias
   ```bash
   composer install
   npm install
   ```

3. Variables de entorno
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   - Por defecto `.env.example` usa SQLite. Para usar MySQL, descomenta y configura `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   - Configura `ADMIN_EMAIL`, `ADMIN_PASSWORD` y `DOCTOR_DEFAULT_PASSWORD` para los usuarios creados por seeders.
   - Para WhatsApp configura `TWILIO_WHATSAPP_ENABLED`, `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_WHATSAPP_FROM`.
   - Para notificaciones automáticas de turnos usa: `APPOINTMENT_NOTIFICATIONS_ENABLED`, `APPOINTMENT_NOTIFICATIONS_EMAIL_ENABLED`, `APPOINTMENT_NOTIFICATIONS_WHATSAPP_ENABLED`.

4. Migraciones y seeders (si aplica)
   ```bash
   php artisan migrate --seed
   ```

5. Compilar assets y servir
   ```bash
   npm run dev
   php artisan serve
   ```

## Scripts útiles

- Iniciar servidor: `php artisan serve`
- Ejecutar pruebas: `php artisan test`
- Correcciones de estilo (si se agrega Pint/PHPCS): `composer lint`

## Estructura destacada

- Rutas: `routes/web.php`
- Controladores: `app/Http/Controllers/`
- Middlewares: `app/Http/Middleware/`
- Vistas Blade: `resources/views/`
- Migraciones: `database/migrations/`

## Reglas funcionales vigentes

- Los turnos no se permiten en fechas/horas pasadas.
- Los horarios de turnos se validan en intervalos de 15 minutos.
- `doctor_id` debe pertenecer a la `specialty_id` elegida y estar activo.
- Validaciones de paciente:
  - DNI: solo numérico (7 a 10 dígitos).
  - Teléfono: 7 a 20 caracteres válidos (`+`, dígitos, espacios, guiones y paréntesis).
  - Email: opcional para envío automático de notificaciones por correo.
- Transiciones en recepción:
  - Asistencia: solo desde estado `requested`.
  - Pago: solo desde `requested` o `arrived`.
- Notificaciones automáticas:
  - Al crear turno (`requested`) se intenta enviar por email y/o WhatsApp según datos disponibles.
  - Al confirmar asistencia (`arrived`) y pago (`paid`) se vuelve a notificar.
  - Los envíos se registran con estado y detalle en `appointment_notification_logs`.

## Accesos iniciales (seeders)

- Se crea un usuario administrador con:
  - Email: `ADMIN_EMAIL`
  - Clave: `ADMIN_PASSWORD`
- Se crea un usuario por cada médico activo con:
  - Clave: `DOCTOR_DEFAULT_PASSWORD`
  - Selección por médico en `/login/medico`
- Gestión de usuarios desde UI:
  - Ruta: `/admin/users` (solo administradores)
  - Permite crear, editar, activar y desactivar cuentas.
  - Incluye búsqueda por nombre/email, filtros por rol/estado y reseteo de contraseña.
  - Registra auditoría básica (creación, última edición y desactivación).
  - Permite enviar notificaciones autenticadas por Email y WhatsApp desde `/admin/users/{id}/notify`.

## CI/CD

Se incluye un workflow de GitHub Actions para ejecutar pruebas en cada push/PR. Ver `.github/workflows/laravel-ci.yml`.

## Seguridad

- Nunca subas el archivo `.env` (está ignorado por `.gitignore`).
- Revisa y personaliza `.env.example` para documentar variables necesarias sin exponer secretos.
- Cambia siempre las credenciales de seeders (`ADMIN_PASSWORD`, `DOCTOR_DEFAULT_PASSWORD`) fuera de entornos de desarrollo.
- Nunca expongas `TWILIO_AUTH_TOKEN` ni credenciales SMTP en el repositorio.

## Licencia

Este proyecto se distribuye bajo la licencia MIT. Ver `LICENSE`.
