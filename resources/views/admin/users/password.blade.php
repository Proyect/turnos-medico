@extends('layouts.app')

@section('content')
<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h5 class="mb-1">Resetear contraseña</h5>
      <p class="mb-0 muted-help">Actualizá la clave del usuario seleccionado de forma segura.</p>
    </div>
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-soft">Volver a edición</a>
  </div>
</div>

<div class="card panel-card">
  <div class="card-header">Actualizar clave de {{ $user->name }}</div>
  <div class="card-body p-4">
    <form method="POST" action="{{ route('admin.users.password.update', $user) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nueva clave</label>
          <input type="password" name="password" class="form-control" minlength="8" required>
          <div class="form-text">Mínimo 8 caracteres.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Confirmar nueva clave</label>
          <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Guardar nueva clave</button>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
@endsection
