@extends('layouts.app')

@section('content')
<div class="row g-4 align-items-stretch">
  <div class="col-12 col-lg-5">
    <div class="panel-card h-100 p-4">
      <h5 class="mb-3">Acceso {{ $role === 'admin' ? 'Administrador' : 'Médico' }}</h5>
      <p class="muted-help mb-3">
        @if($role === 'admin')
          Desde este panel podés controlar asistencia y cobros diarios.
        @else
          Ingresá para visualizar tus pacientes y su estado del día.
        @endif
      </p>
      <ul class="small ps-3 mb-0">
        <li class="mb-2">Sesión segura con regeneración de credenciales de sesión.</li>
        <li class="mb-2">Control de intentos para prevenir fuerza bruta.</li>
        <li>Usa credenciales definidas en variables de entorno.</li>
      </ul>
    </div>
  </div>
  <div class="col-12 col-lg-7">
    <div class="card panel-card h-100">
      <div class="card-header">Iniciar sesión - {{ $role === 'admin' ? 'Administrador' : 'Médico' }}</div>
      <div class="card-body p-4">
        <form method="POST" action="{{ route('login.perform', $role) }}">
          @csrf
          @if($role === 'medico')
            <div class="mb-3">
              <label class="form-label">Médico</label>
              <select name="doctor_id" class="form-select" required>
                <option value="">Seleccione...</option>
                @foreach($doctors as $d)
                  <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
              </select>
            </div>
          @endif
          <div class="mb-3">
            <label class="form-label">Clave</label>
            <input type="password" name="password" class="form-control" required>
            <div class="form-text">
              @if($role==='admin')
                Definir la variable ADMIN_PASS en el archivo .env.
              @else
                Definir la variable DOCTOR_PASS en el archivo .env.
              @endif
            </div>
          </div>
          <button class="btn btn-primary" type="submit">Ingresar</button>
          <a href="/paciente" class="btn btn-link">Volver</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
