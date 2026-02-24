<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        // Filtrar por fecha y doctor. Si hay sesión de médico, forzar su ID.
        $date = $request->query('date', now()->toDateString());
        $doctorId = $request->query('doctor_id');
        $sessionDoctorId = session('role') === 'doctor' ? session('doctor_id') : null;

        if ($sessionDoctorId) {
            // Si está logueado como médico, limitar al propio médico
            $doctors = Doctor::with('specialty')->where('id', $sessionDoctorId)->get();
            $doctorId = $sessionDoctorId; // forzar el filtro
        } else {
            $doctors = Doctor::with('specialty')->where('active', true)->orderBy('name')->get();
            if (!$doctorId && $doctors->count() > 0) {
                $doctorId = $doctors->first()->id;
            }
        }

        $appointments = collect();
        if ($doctorId) {
            $appointments = Appointment::with(['doctor', 'specialty'])
                ->where('doctor_id', $doctorId)
                ->whereDate('scheduled_at', $date)
                ->whereIn('status', [Appointment::STATUS_PAID, Appointment::STATUS_ARRIVED])
                ->orderByRaw(
                    'CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END',
                    [Appointment::STATUS_PAID, Appointment::STATUS_ARRIVED]
                )
                ->orderBy('scheduled_at')
                ->get();
        }

        return view('doctor.index', [
            'doctors' => $doctors,
            'appointments' => $appointments,
            'date' => $date,
            'doctorId' => $doctorId,
        ]);
    }
}
