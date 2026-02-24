<?php

return [
    'appointment' => [
        'enabled' => env('APPOINTMENT_NOTIFICATIONS_ENABLED', true),
        'email_enabled' => env('APPOINTMENT_NOTIFICATIONS_EMAIL_ENABLED', true),
        'whatsapp_enabled' => env('APPOINTMENT_NOTIFICATIONS_WHATSAPP_ENABLED', true),
    ],
];
