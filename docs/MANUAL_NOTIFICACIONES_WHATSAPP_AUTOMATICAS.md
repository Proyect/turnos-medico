# Manual operativo: notificaciones automaticas por WhatsApp y Email

## 1) Objetivo

Este manual explica como dejar funcionando el envio automatico de notificaciones de turnos:

- WhatsApp (via Twilio, autenticado con `account_sid` + `auth_token`)
- Email (via mailer configurado en Laravel)

Incluye configuracion, pruebas y troubleshooting.

---

## 2) Como funciona hoy en el sistema

### Eventos que disparan notificaciones

El sistema notifica automaticamente en estos puntos:

1. `appointment_requested` (cuando se crea el turno)
2. `appointment_arrived` (cuando recepcion confirma llegada)
3. `appointment_paid` (cuando recepcion confirma pago)

### Canales

- **Email**: se envia si `patient_email` tiene valor y el canal email esta habilitado.
- **WhatsApp**: se envia si:
  - el canal WhatsApp esta habilitado,
  - Twilio esta configurado correctamente,
  - el telefono del paciente es valido para WhatsApp (formato internacional).

### Registro de auditoria

Todos los intentos quedan en base de datos:

- Tabla: `appointment_notification_logs`
- Estado: `sent` o `failed`
- Campos utiles: `event`, `channel`, `recipient`, `provider_message_id`, `error_message`, `sent_at`

Importante: si falla un proveedor externo, el flujo clinico no se corta (el turno igual se crea/actualiza).

---

## 3) Prerrequisitos

1. PHP 8.2+, Composer, base de datos lista.
2. Cuenta Twilio activa.
3. Canal WhatsApp en Twilio:
   - Sandbox (pruebas), o
   - Numero aprobado en produccion.
4. Mailer configurado (SMTP/Mailgun/Resend/etc.) si queres email real.

---

## 4) Configurar Twilio WhatsApp (autenticado)

### Paso 4.1 - Obtener credenciales

Desde Twilio Console:

- `Account SID` -> `TWILIO_ACCOUNT_SID`
- `Auth Token` -> `TWILIO_AUTH_TOKEN`
- Remitente WhatsApp (`From`) -> `TWILIO_WHATSAPP_FROM`
  - Ejemplo sandbox: `whatsapp:+14155238886`

### Paso 4.2 - Si usas Sandbox

Cada telefono de prueba debe unirse al sandbox enviando el codigo que Twilio indica
(por ejemplo, un mensaje tipo `join <codigo>` al numero sandbox).

Si no se une, Twilio rechaza envios al destinatario.

---

## 5) Variables de entorno requeridas

Editar `.env`:

```env
# Twilio WhatsApp
TWILIO_WHATSAPP_ENABLED=true
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_WHATSAPP_FROM="whatsapp:+14155238886"
TWILIO_API_BASE_URL=https://api.twilio.com/2010-04-01

# Automatizacion de notificaciones de turnos
APPOINTMENT_NOTIFICATIONS_ENABLED=true
APPOINTMENT_NOTIFICATIONS_EMAIL_ENABLED=true
APPOINTMENT_NOTIFICATIONS_WHATSAPP_ENABLED=true
```

Para email real (opcional pero recomendado):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=secreto
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="notificaciones@tu-dominio.com"
MAIL_FROM_NAME="Centro Medico del Milagro"
```

---

## 6) Aplicar cambios y limpiar cache

Luego de actualizar `.env`:

```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

Si usas cache de config en produccion:

```bash
php artisan config:cache
```

---

## 7) Datos de entrada que debe completar el paciente

En formulario de turno (`/paciente`):

- `phone` (obligatorio): ideal en formato internacional (`+5491122334455`)
- `patient_email` (opcional): necesario para canal email automatico

Recomendacion operativa:

- Estandarizar carga de telefono con prefijo pais.
- Si no hay email, WhatsApp sigue funcionando si el telefono es valido.

---

## 8) Prueba end-to-end recomendada

### Paso 8.1 - Crear turno

1. Ir a `/paciente`
2. Cargar telefono en formato internacional
3. Cargar email valido
4. Confirmar solicitud

Resultado esperado:

- Se crea el turno
- Se registran logs de `appointment_requested`
  - `channel=email` -> `sent` (si mailer responde OK)
  - `channel=whatsapp` -> `sent` (si Twilio responde OK)

### Paso 8.2 - Confirmar asistencia

1. Ingresar como admin
2. Ir a `/admin`
3. Click en "Confirmar asistencia"

Resultado esperado:

- Estado del turno cambia a `arrived`
- Nuevos logs con `event=appointment_arrived`

### Paso 8.3 - Confirmar pago

1. En el mismo turno, click en "Confirmar pago"

Resultado esperado:

- Estado del turno cambia a `paid`
- Nuevos logs con `event=appointment_paid`

---

## 9) Verificacion rapida en base de datos

Ejemplo SQL:

```sql
SELECT
  id,
  appointment_id,
  event,
  channel,
  recipient,
  status,
  provider_message_id,
  error_message,
  sent_at,
  created_at
FROM appointment_notification_logs
ORDER BY id DESC
LIMIT 50;
```

---

## 10) Troubleshooting

### A) No se envia WhatsApp y no aparece log

Causas comunes:

- `APPOINTMENT_NOTIFICATIONS_WHATSAPP_ENABLED=false`
- `TWILIO_WHATSAPP_ENABLED=false`
- faltan credenciales Twilio

Accion:

1. Verificar `.env`
2. Ejecutar `php artisan config:clear`
3. Repetir prueba

### B) Hay log WhatsApp con `failed`

Revisar `error_message` y `provider_response`.

Casos comunes:

- Telefono invalido (sin prefijo internacional)
- Destino no unido al sandbox
- Credenciales invalidas
- Restriccion de cuenta/proveedor

### C) Email no llega

Revisar:

- Configuracion SMTP
- `MAIL_FROM_ADDRESS`
- carpeta de spam
- logs de Laravel (`storage/logs/laravel.log`)

### D) El turno se crea pero no sale notificacion

Puede pasar si falla canal externo. Es comportamiento esperado:

- la operacion clinica no se bloquea,
- el detalle queda en `appointment_notification_logs`.

---

## 11) Checklist de salida a produccion

- [ ] Credenciales Twilio reales cargadas en secret manager (no en git)
- [ ] `TWILIO_WHATSAPP_ENABLED=true`
- [ ] `APPOINTMENT_NOTIFICATIONS_ENABLED=true`
- [ ] Mailer productivo configurado
- [ ] Prueba real de los 3 eventos (`requested`, `arrived`, `paid`)
- [ ] Monitoreo de errores de `appointment_notification_logs`
- [ ] Politica de rotacion de secretos (Auth Token/SMTP)

---

## 12) Archivos tecnicos relacionados

- `app/Services/Notifications/AppointmentNotificationService.php`
- `app/Services/WhatsApp/TwilioWhatsAppClient.php`
- `app/Http/Controllers/AppointmentController.php`
- `app/Http/Controllers/ReceptionController.php`
- `database/migrations/2026_02_24_000900_add_patient_email_to_appointments_table.php`
- `database/migrations/2026_02_24_001000_create_appointment_notification_logs_table.php`
- `config/notifications.php`
- `config/services.php`
