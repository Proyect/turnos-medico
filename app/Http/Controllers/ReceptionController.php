<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceptionController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());

        $appointments = Appointment::with(['doctor.specialty'])
            ->whereDate('scheduled_at', $date)
            ->orderBy('scheduled_at')
            ->get();

        return view('reception.index', compact('appointments', 'date'));
    }

    public function markArrived(Appointment $appointment): RedirectResponse
    {
        if (!$appointment->canMarkArrived()) {
            return back()->withErrors([
                'status' => "No se puede confirmar asistencia para un turno en estado {$appointment->status_label}.",
            ]);
        }

        $appointment->update(['status' => Appointment::STATUS_ARRIVED]);
        return back()->with('success', 'Asistencia confirmada.');
    }

    public function markPaid(Appointment $appointment): RedirectResponse
    {
        if (!$appointment->canMarkPaid()) {
            return back()->withErrors([
                'status' => "No se puede confirmar pago para un turno en estado {$appointment->status_label}.",
            ]);
        }

        $appointment->update(['status' => Appointment::STATUS_PAID]);
        return back()->with('success', 'Pago confirmado.');
    }
}
