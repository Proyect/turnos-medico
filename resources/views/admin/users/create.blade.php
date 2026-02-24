@extends('layouts.app')

@section('content')
<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h5 class="mb-1">Nuevo usuario</h5>
      <p class="mb-0 muted-help">Creá cuentas de administrador o vinculá un usuario a un médico activo.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-soft">Volver al listado</a>
  </div>
</div>

<div class="card panel-card">
  <div class="card-header">Datos del usuario</div>
  <div class="card-body p-4">
    <form method="POST" action="{{ route('admin.users.store') }}" id="user-form">
      @csrf

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre completo</label>
          <input type="text" name="name" class="form-control" value="{{ old('name') }}" maxlength="100" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select name="role" id="role" class="form-select" required>
            <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
            <option value="doctor" @selected(old('role') === 'doctor')>Médico</option>
          </select>
        </div>

        <div class="col-md-6" id="doctor-wrap">
          <label class="form-label">Médico vinculado</label>
          <select name="doctor_id" id="doctor-id" class="form-select">
            <option value="">Seleccione...</option>
            @foreach($doctors as $doctor)
              <option value="{{ $doctor->id }}" @selected((int) old('doctor_id') === $doctor->id)>
                {{ $doctor->name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Solo se listan médicos activos sin usuario asignado.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Clave</label>
          <input type="password" name="password" class="form-control" minlength="8" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Confirmar clave</label>
          <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
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
              @checked(old('active', '1') === '1')
            >
            <label class="form-check-label" for="active">Usuario activo</label>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Crear usuario</button>
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
