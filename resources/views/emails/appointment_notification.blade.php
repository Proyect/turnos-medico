<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 10px;">{{ $subjectLine }}</h2>

    <p style="margin: 0 0 12px;">
        Hola {{ $appointment->patient_first_name }} {{ $appointment->patient_last_name }},
    </p>

    <div style="white-space: pre-line; margin-bottom: 16px;">
        {{ $messageBody }}
    </div>

    <p style="margin: 0;">Centro Médico del Milagro</p>
</body>
</html>
