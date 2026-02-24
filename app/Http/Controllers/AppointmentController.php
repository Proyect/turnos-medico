<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function create(): View
    {
        $specialties = Specialty::orderBy('name')->get();
        return view('appointments.create', compact('specialties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeInputForValidation($request);

        $data = $request->validate([
            'patient_first_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\'-]+$/u'],
            'patient_last_name'  => ['required', 'string', 'max:100', 'regex:/^[\pL\s\'-]+$/u'],
            'phone'              => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
            'dni'                => ['required', 'string', 'regex:/^\d{7,10}$/'],
            'specialty_id'       => ['required','exists:specialties,id'],
            'doctor_id'          => ['required','exists:doctors,id'],
            'date'               => ['required','date_format:Y-m-d'],
            'time'               => ['required','date_format:H:i'],
        ]);

        $doctor = Doctor::query()
            ->whereKey($data['doctor_id'])
            ->where('specialty_id', $data['specialty_id'])
            ->where('active', true)
            ->first();

        if (!$doctor) {
            return back()
                ->withErrors(['doctor_id' => 'El médico seleccionado no corresponde a la especialidad o está inactivo.'])
                ->withInput();
        }

        try {
            $scheduledAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $data['date'].' '.$data['time'],
                config('app.timezone')
            );
        } catch (\Throwable) {
            return back()
                ->withErrors(['time' => 'La fecha u hora ingresada no es válida.'])
                ->withInput();
        }

        if ($scheduledAt->isPast()) {
            return back()
                ->withErrors(['date' => 'No puedes solicitar turnos en fechas u horarios pasados.'])
                ->withInput();
        }

        if ($scheduledAt->minute % 15 !== 0) {
            return back()
                ->withErrors(['time' => 'La hora debe estar en intervalos de 15 minutos.'])
                ->withInput();
        }

        // Validación: evitar turnos superpuestos por médico en la misma fecha y hora
        $exists = Appointment::where('doctor_id', $doctor->id)
            ->where('scheduled_at', $scheduledAt->format('Y-m-d H:i:s'))
            ->exists();
        if ($exists) {
            return back()
                ->withErrors(['time' => 'Ya existe un turno para ese médico en el horario seleccionado.'])
                ->withInput();
        }

        // Crear el turno y manejar posible conflicto por índice único a nivel BD
        try {
            Appointment::create([
                'patient_first_name' => $data['patient_first_name'],
                'patient_last_name'  => $data['patient_last_name'],
                'phone'              => $data['phone'],
                'dni'                => $data['dni'],
                'specialty_id'       => $data['specialty_id'],
                'doctor_id'          => $doctor->id,
                'scheduled_at'       => $scheduledAt,
                'status'             => Appointment::STATUS_REQUESTED,
            ]);
        } catch (QueryException $e) {
            if (!$this->isUniqueAppointmentConstraintViolation($e)) {
                report($e);

                return back()
                    ->withErrors(['time' => 'No se pudo crear el turno por un error inesperado.'])
                    ->withInput();
            }

            return back()
                ->withErrors(['time' => 'No se pudo crear el turno: el horario ya fue reservado.'])
                ->withInput();
        }

        return redirect()->route('appointments.create')
            ->with('success', 'Turno solicitado con éxito. Preséntate en recepción el día y horario elegido.');
    }

    public function doctorsBySpecialty(Specialty $specialty)
    {
        return response()->json(
            Doctor::where('specialty_id', $specialty->id)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id','name'])
        );
    }

    private function isUniqueAppointmentConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) $e->getCode();
        $message = strtolower($e->getMessage());

        $isUniqueCode = in_array($sqlState, ['23000', '23505'], true);
        $hasAppointmentUniqueHint = str_contains($message, 'appointments_doctor_time_unique')
            || (str_contains($message, 'appointments.doctor_id') && str_contains($message, 'appointments.scheduled_at'));

        return $isUniqueCode && $hasAppointmentUniqueHint;
    }

    private function normalizeInputForValidation(Request $request): void
    {
        $request->merge([
            'patient_first_name' => $this->normalizeWhitespace((string) $request->input('patient_first_name', '')),
            'patient_last_name' => $this->normalizeWhitespace((string) $request->input('patient_last_name', '')),
            'phone' => $this->normalizeWhitespace((string) $request->input('phone', '')),
            'dni' => $this->normalizeDni((string) $request->input('dni', '')),
        ]);
    }

    private function normalizeWhitespace(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized ?? trim($value);
    }

    private function normalizeDni(string $value): string
    {
        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized ?? '';
    }
}
