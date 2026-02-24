@extends('layouts.app')

@section('content')
@php
  $arrivedCount = $appointments->where('status', \App\Models\Appointment::STATUS_ARRIVED)->count();
  $paidCount = $appointments->where('status', \App\Models\Appointment::STATUS_PAID)->count();
@endphp

<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
      <h5 class="mb-1">Panel médico</h5>
      <p class="mb-0 muted-help">Listado de pacientes del día con prioridad visual para turnos pagados.</p>
    </div>
    <form method="GET" action="{{ route('doctor.index') }}" id="filter-form" class="d-flex form-inline-stack gap-2">
      <div>
        <label class="form-label mb-1">Fecha</label>
        <input type="date" name="date" class="form-control" value="{{ $date }}">
      </div>
      <div>
        <label class="form-label mb-1">Médico</label>
        <select name="doctor_id" class="form-select">
          @foreach($doctors as $d)
            <option value="{{ $d->id }}" @selected($doctorId==$d->id)>{{ $d->name }} ({{ $d->specialty->name ?? '' }})</option>
          @endforeach
        </select>
      </div>
      <div class="align-self-end">
        <button class="btn btn-primary" type="submit">Actualizar</button>
      </div>
    </form>
  </div>

  <div class="stats-grid mt-3">
    <div class="stat-card">
      <div class="stat-label">Pacientes en espera</div>
      <div class="stat-value">{{ $arrivedCount }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pacientes abonados</div>
      <div class="stat-value">{{ $paidCount }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total del filtro</div>
      <div class="stat-value">{{ $appointments->count() }}</div>
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
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      @forelse($appointments as $a)
        <tr class="{{ $a->status === \App\Models\Appointment::STATUS_PAID ? 'table-success' : '' }}">
          <td>{{ $a->scheduled_at->format('H:i') }}</td>
          <td>{{ $a->patient_last_name }}, {{ $a->patient_first_name }}</td>
          <td>{{ $a->dni }}</td>
          <td>{{ $a->phone }}</td>
          <td>{{ $a->specialty->name }}</td>
          <td>
            <span class="badge text-bg-{{ $a->status_badge_class }}">{{ $a->status_label }}</span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center">No hay pacientes para el filtro seleccionado.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
  </div>
</div>
@endsection
