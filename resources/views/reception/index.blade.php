@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5>Recepción - Turnos del día</h5>
  <form method="GET" action="{{ route('reception.index') }}" class="d-flex align-items-center gap-2">
    <input type="date" name="date" class="form-control" value="{{ $date }}">
    <button class="btn btn-outline-primary" type="submit">Ver</button>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
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
          <td class="d-flex gap-2">
            @if($a->canMarkArrived())
              <form method="POST" action="{{ route('reception.arrived', $a) }}">
                @csrf
                <button class="btn btn-sm btn-warning" type="submit">Confirmar asistencia</button>
              </form>
            @endif
            @if($a->canMarkPaid())
              <form method="POST" action="{{ route('reception.paid', $a) }}">
                @csrf
                <button class="btn btn-sm btn-success" type="submit">Confirmar pago</button>
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
@endsection
