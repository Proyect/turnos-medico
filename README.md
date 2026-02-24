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
   - Configura también `ADMIN_PASS` y `DOCTOR_PASS` para habilitar los accesos por rol.

4. Migraciones y seeders (si aplica)
   ```bash
   php artisan migrate
   # php artisan db:seed   # si definiste seeders
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

## CI/CD

Se incluye un workflow de GitHub Actions para ejecutar pruebas en cada push/PR. Ver `.github/workflows/laravel-ci.yml`.

## Seguridad

- Nunca subas el archivo `.env` (está ignorado por `.gitignore`).
- Revisa y personaliza `.env.example` para documentar variables necesarias sin exponer secretos.
- Las credenciales `ADMIN_PASS` y `DOCTOR_PASS` no tienen valor por defecto: deben definirse explícitamente en cada entorno.

## Licencia

Este proyecto se distribuye bajo la licencia MIT. Ver `LICENSE`.
