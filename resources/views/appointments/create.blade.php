@extends('layouts.app')

@section('content')
<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h5 class="mb-1">Solicitud de turno</h5>
      <p class="mb-0 muted-help">Seleccioná especialidad, médico y horario. Los turnos se reservan cada 15 minutos.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="/admin" class="btn btn-soft btn-sm">Recepción</a>
      <a href="/medico" class="btn btn-soft btn-sm">Panel Médico</a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 col-lg-8 mx-auto">
    <div class="card panel-card">
      <div class="card-header">Solicitar turno</div>
      <div class="card-body p-4">
        <form method="POST" action="{{ route('appointments.store') }}" id="turno-form">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombre</label>
              <input
                type="text"
                name="patient_first_name"
                class="form-control"
                value="{{ old('patient_first_name') }}"
                maxlength="100"
                autocomplete="given-name"
                required
              >
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellido</label>
              <input
                type="text"
                name="patient_last_name"
                class="form-control"
                value="{{ old('patient_last_name') }}"
                maxlength="100"
                autocomplete="family-name"
                required
              >
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input
                type="text"
                name="phone"
                class="form-control"
                value="{{ old('phone') }}"
                maxlength="20"
                inputmode="tel"
                pattern="\+?[0-9\s\-\(\)]{7,20}"
                placeholder="+54 11 1234-5678"
                autocomplete="tel"
                required
              >
              <div class="form-text">Para WhatsApp, usar prefijo internacional (ejemplo: +5491122334455).</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email (opcional)</label>
              <input
                type="email"
                name="patient_email"
                class="form-control"
                value="{{ old('patient_email') }}"
                maxlength="255"
                placeholder="paciente@email.com"
                autocomplete="email"
              >
            </div>
            <div class="col-md-6">
              <label class="form-label">DNI</label>
              <input
                type="text"
                name="dni"
                class="form-control"
                value="{{ old('dni') }}"
                maxlength="10"
                inputmode="numeric"
                pattern="[0-9]{7,10}"
                placeholder="12345678"
                required
              >
            </div>

            <div class="col-md-6">
              <label class="form-label">Especialidad</label>
              <select name="specialty_id" id="specialty" class="form-select" required>
                <option value="">Seleccione...</option>
                @foreach($specialties as $s)
                  <option value="{{ $s->id }}" @selected(old('specialty_id')==$s->id)>{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Médico</label>
              <select name="doctor_id" id="doctor" class="form-select" required>
                <option value="">Seleccione una especialidad primero</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Día</label>
              <input
                type="date"
                name="date"
                class="form-control"
                value="{{ old('date', now()->toDateString()) }}"
                min="{{ now()->toDateString() }}"
                required
              >
            </div>
            <div class="col-md-6">
              <label class="form-label">Hora</label>
              <input
                type="time"
                name="time"
                class="form-control"
                value="{{ old('time', '09:00') }}"
                step="900"
                required
              >
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Reservar turno</button>
            <a href="/" class="btn btn-outline-secondary">Volver al inicio</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const specialtySelect = document.getElementById('specialty');
  const doctorSelect = document.getElementById('doctor');

  async function loadDoctors(specialtyId, selectedId = null) {
    doctorSelect.disabled = true;
    doctorSelect.innerHTML = '<option value="">Cargando...</option>';

    if (!specialtyId) {
      doctorSelect.innerHTML = '<option value="">Seleccione una especialidad primero</option>';
      doctorSelect.disabled = false;
      return;
    }

    try {
      const res = await fetch(`/api/specialties/${specialtyId}/doctors`, {
        headers: { Accept: 'application/json' },
      });

      if (!res.ok) {
        throw new Error('No se pudieron cargar médicos');
      }

      const doctors = await res.json();
      if (!Array.isArray(doctors) || doctors.length === 0) {
        doctorSelect.innerHTML = '<option value="">No hay médicos activos</option>';
        return;
      }

      const hasSelectedId = selectedId !== null && selectedId !== undefined;
      doctorSelect.innerHTML = '<option value="">Seleccione...</option>' + doctors.map(d => {
        const sel = hasSelectedId && Number(selectedId) === Number(d.id) ? 'selected' : '';
        return `<option value="${d.id}" ${sel}>${d.name}</option>`;
      }).join('');
    } catch (_error) {
      doctorSelect.innerHTML = '<option value="">No se pudieron cargar médicos</option>';
    } finally {
      doctorSelect.disabled = false;
    }
  }

  specialtySelect.addEventListener('change', (e) => {
    loadDoctors(e.target.value);
  });

  // precargar si viene old()
  @if(old('specialty_id'))
    loadDoctors({{ old('specialty_id') }}, {{ old('doctor_id') ? (int) old('doctor_id') : 'null' }});
  @endif
</script>
@endpush
