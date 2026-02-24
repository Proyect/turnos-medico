@extends('layouts.app')

@section('content')
<div class="row">
  <div class="col-12 col-md-6 mx-auto">
    <div class="card">
      <div class="card-header">Iniciar sesión - {{ $role === 'admin' ? 'Administrador' : 'Médico' }}</div>
      <div class="card-body">
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
                Definir la variable ADMIN_PASS en el archivo .env
              @else
                Definir la variable DOCTOR_PASS en el archivo .env
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
