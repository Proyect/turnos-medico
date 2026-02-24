@extends('layouts.app')

@section('content')
@php
  $requestedCount = $appointments->where('status', \App\Models\Appointment::STATUS_REQUESTED)->count();
  $arrivedCount = $appointments->where('status', \App\Models\Appointment::STATUS_ARRIVED)->count();
  $paidCount = $appointments->where('status', \App\Models\Appointment::STATUS_PAID)->count();
@endphp

<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <h5 class="mb-1">Recepción - Turnos del día</h5>
      <p class="mb-0 muted-help">Gestioná asistencia y pagos con visibilidad del estado en tiempo real.</p>
    </div>
    <div class="d-flex form-inline-stack align-items-center gap-2">
      <form method="GET" action="{{ route('reception.index') }}" class="d-flex form-inline-stack align-items-center gap-2">
        <input type="date" name="date" class="form-control" value="{{ $date }}">
        <button class="btn btn-primary" type="submit">Actualizar</button>
      </form>
      <a href="{{ route('admin.users.index') }}" class="btn btn-soft">Usuarios</a>
    </div>
  </div>

  <div class="stats-grid mt-3">
    <div class="stat-card">
      <div class="stat-label">Turnos del día</div>
      <div class="stat-value">{{ $appointments->count() }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pendientes de llegada</div>
      <div class="stat-value">{{ $requestedCount }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Asistencias confirmadas</div>
      <div class="stat-value">{{ $arrivedCount }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pagos confirmados</div>
      <div class="stat-value">{{ $paidCount }}</div>
    </div>
  </div>
</div>

<div class="table-panel">
  <div class="table-responsive">
  <table class="table align-middle">
    <thead>
      <tr>
        <th>Hora</th>
        <th>Paciente</th>
        <th>DNI</th>
        <th>Teléfono</th>
        <th>Especialidad</th>
        <th>Médico</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse($appointments as $a)
        <tr>
          <td>{{ $a->scheduled_at->format('H:i') }}</td>
          <td>{{ $a->patient_last_name }}, {{ $a->patient_first_name }}</td>
          <td>{{ $a->dni }}</td>
          <td>{{ $a->phone }}</td>
          <td>{{ $a->doctor->specialty->name }}</td>
          <td>{{ $a->doctor->name }}</td>
          <td>
            <span class="badge text-bg-{{ $a->status_badge_class }}">{{ $a->status_label }}</span>
          </td>
          <td class="d-flex gap-2 flex-wrap">
            @if($a->canMarkArrived())
              <form method="POST" action="{{ route('reception.arrived', $a) }}">
                @csrf
                <button class="btn btn-sm btn-warning" type="submit">Asistencia</button>
              </form>
            @endif
            @if($a->canMarkPaid())
              <form method="POST" action="{{ route('reception.paid', $a) }}">
                @csrf
                <button class="btn btn-sm btn-success" type="submit">Pago</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center">No hay turnos para la fecha seleccionada.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
@endsection
