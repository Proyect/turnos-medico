@extends('layouts.app')

@section('content')
<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h5 class="mb-1">Editar usuario</h5>
      <p class="mb-0 muted-help">Actualizá datos, rol, vinculación y estado del usuario seleccionado.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-soft">Volver al listado</a>
  </div>
</div>

<div class="card panel-card">
  <div class="card-header">Datos de {{ $user->name }}</div>
  <div class="card-body p-4">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" id="user-form">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre completo</label>
          <input
            type="text"
            name="name"
            class="form-control"
            value="{{ old('name', $user->name) }}"
            maxlength="100"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input
            type="email"
            name="email"
            class="form-control"
            value="{{ old('email', $user->email) }}"
            maxlength="255"
            required
          >
        </div>

        <div class="col-md-6">
          <label class="form-label">Teléfono (WhatsApp)</label>
          <input
            type="text"
            name="phone"
            class="form-control"
            value="{{ old('phone', $user->phone) }}"
            maxlength="25"
            placeholder="+5491122334455"
          >
          <div class="form-text">Usado para notificaciones de WhatsApp en formato E.164.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select name="role" id="role" class="form-select" required>
            <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrador</option>
            <option value="doctor" @selected(old('role', $user->role) === 'doctor')>Médico</option>
          </select>
        </div>

        <div class="col-md-6" id="doctor-wrap">
          <label class="form-label">Médico vinculado</label>
          <select name="doctor_id" id="doctor-id" class="form-select">
            <option value="">Seleccione...</option>
            @foreach($doctors as $doctor)
              <option value="{{ $doctor->id }}" @selected((int) old('doctor_id', $user->doctor_id) === $doctor->id)>
                {{ $doctor->name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Si el rol es médico, este campo es obligatorio.</div>
        </div>

        <div class="col-12">
          <input type="hidden" name="active" value="0">
          <div class="form-check form-switch">
            <input
              class="form-check-input"
              type="checkbox"
              role="switch"
              id="active"
              name="active"
              value="1"
              @checked((string) old('active', $user->active ? '1' : '0') === '1')
            >
            <label class="form-check-label" for="active">Usuario activo</label>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
        <a href="{{ route('admin.users.notify.create', $user) }}" class="btn btn-outline-secondary">Notificar</a>
        <a href="{{ route('admin.users.password.edit', $user) }}" class="btn btn-outline-dark">Resetear clave</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const roleSelect = document.getElementById('role');
  const doctorWrap = document.getElementById('doctor-wrap');
  const doctorInput = document.getElementById('doctor-id');

  function syncDoctorField() {
    const isDoctor = roleSelect.value === 'doctor';
    doctorWrap.style.display = isDoctor ? '' : 'none';
    doctorInput.required = isDoctor;

    if (!isDoctor) {
      doctorInput.value = '';
    }
  }

  roleSelect.addEventListener('change', syncDoctorField);
  syncDoctorField();
</script>
@endpush
