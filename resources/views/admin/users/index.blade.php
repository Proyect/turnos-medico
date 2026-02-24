@extends('layouts.app')

@section('content')
<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <h5 class="mb-1">Gestión de usuarios</h5>
      <p class="mb-0 muted-help">Administrá cuentas de administradores y médicos, con control de estado activo.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('reception.index') }}" class="btn btn-soft">Volver a recepción</a>
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Nuevo usuario</a>
    </div>
  </div>

  <div class="stats-grid mt-3">
    <div class="stat-card">
      <div class="stat-label">Usuarios totales</div>
      <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Usuarios activos</div>
      <div class="stat-value">{{ $stats['active'] }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Administradores</div>
      <div class="stat-value">{{ $stats['admins'] }}</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Usuarios médicos</div>
      <div class="stat-value">{{ $stats['doctors'] }}</div>
    </div>
  </div>
</div>

<div class="toolbar-card mb-3">
  <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label mb-1">Buscar</label>
      <input
        type="text"
        name="q"
        class="form-control"
        value="{{ $q }}"
        placeholder="Nombre o email"
      >
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Rol</label>
      <select name="role" class="form-select">
        <option value="">Todos</option>
        <option value="admin" @selected($role === 'admin')>Administrador</option>
        <option value="doctor" @selected($role === 'doctor')>Médico</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label mb-1">Estado</label>
      <select name="status" class="form-select">
        <option value="all" @selected($status === 'all')>Todos</option>
        <option value="active" @selected($status === 'active')>Activos</option>
        <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary" type="submit">Filtrar</button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Limpiar</a>
    </div>
  </form>
</div>

<div class="table-panel">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Médico vinculado</th>
          <th>Estado</th>
          <th>Auditoría</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $item)
          <tr>
            <td class="fw-semibold">{{ $item->name }}</td>
            <td>{{ $item->email }}</td>
            <td>
              <span class="badge text-bg-{{ $item->role === 'admin' ? 'primary' : 'info' }}">
                {{ $item->role === 'admin' ? 'Administrador' : 'Médico' }}
              </span>
            </td>
            <td>{{ $item->doctor?->name ?? '-' }}</td>
            <td>
              <span class="badge text-bg-{{ $item->active ? 'success' : 'secondary' }}">
                {{ $item->active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="small">
              <div><span class="text-secondary">Creó:</span> {{ $item->creator?->name ?? 'Sistema' }}</div>
              <div><span class="text-secondary">Editó:</span> {{ $item->updater?->name ?? '-' }}</div>
              @if($item->deactivated_at)
                <div>
                  <span class="text-secondary">Desactivó:</span> {{ $item->deactivator?->name ?? '-' }}
                </div>
                <div>
                  <span class="text-secondary">Fecha:</span> {{ $item->deactivated_at->format('d/m/Y H:i') }}
                </div>
              @endif
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                <a href="{{ route('admin.users.edit', $item) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                <a href="{{ route('admin.users.password.edit', $item) }}" class="btn btn-sm btn-outline-dark">Clave</a>
                <form method="POST" action="{{ route('admin.users.toggle-active', $item) }}">
                  @csrf
                  @method('PATCH')
                  <button
                    type="submit"
                    class="btn btn-sm {{ $item->active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                  >
                    {{ $item->active ? 'Desactivar' : 'Activar' }}
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-4">No hay usuarios para los filtros seleccionados.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $users->links() }}
</div>
@endsection
