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
        $date = $request->query('date', now()->toDateString());
        $doctorId = (int) ($request->user()?->doctor_id ?? 0);
        $doctors = Doctor::with('specialty')->where('id', $doctorId)->get();

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
