@extends('layouts.app')

@section('content')
<div class="toolbar-card mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h5 class="mb-1">Enviar notificación</h5>
      <p class="mb-0 muted-help">Canales disponibles: email y WhatsApp (con credenciales autenticadas del proveedor).</p>
    </div>
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-soft">Volver a usuario</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel-card p-4 h-100">
      <h6 class="mb-3">Destino</h6>
      <div class="mb-2"><strong>Nombre:</strong> {{ $user->name }}</div>
      <div class="mb-2"><strong>Email:</strong> {{ $user->email ?: '-' }}</div>
      <div class="mb-2"><strong>Teléfono:</strong> {{ $user->phone ?: '-' }}</div>
      <div class="mb-2"><strong>Estado:</strong> {{ $user->active ? 'Activo' : 'Inactivo' }}</div>
      <hr>
      <p class="small text-secondary mb-0">
        WhatsApp requiere que el teléfono esté en formato internacional E.164 (ej.: +5491122334455)
        y que Twilio esté configurado en variables de entorno.
      </p>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card panel-card h-100">
      <div class="card-header">Redactar notificación</div>
      <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.users.notify.store', $user) }}">
          @csrf

          <div class="mb-3">
            <label class="form-label d-block">Canales</label>
            <div class="form-check form-check-inline">
              <input
                class="form-check-input"
                type="checkbox"
                id="channel-email"
                name="channels[]"
                value="email"
                @checked(in_array('email', (array) old('channels', ['email']), true))
              >
              <label class="form-check-label" for="channel-email">Email</label>
            </div>
            <div class="form-check form-check-inline">
              <input
                class="form-check-input"
                type="checkbox"
                id="channel-whatsapp"
                name="channels[]"
                value="whatsapp"
                @checked(in_array('whatsapp', (array) old('channels', []), true))
              >
              <label class="form-check-label" for="channel-whatsapp">WhatsApp</label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Asunto (para email)</label>
            <input
              type="text"
              name="subject"
              class="form-control"
              maxlength="150"
              value="{{ old('subject') }}"
              placeholder="Recordatorio de agenda"
            >
          </div>

          <div class="mb-3">
            <label class="form-label">Mensaje</label>
            <textarea
              name="message"
              rows="6"
              class="form-control"
              maxlength="2000"
              required
            >{{ old('message') }}</textarea>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Enviar notificación</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="table-panel mt-4">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Canal</th>
          <th>Destino</th>
          <th>Estado</th>
          <th>Enviado por</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          <tr>
            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ strtoupper($log->channel) }}</td>
            <td>{{ $log->recipient ?? '-' }}</td>
            <td>
              <span class="badge text-bg-{{ $log->status === 'sent' ? 'success' : 'danger' }}">
                {{ $log->status === 'sent' ? 'Enviado' : 'Fallido' }}
              </span>
            </td>
            <td>{{ $log->sender?->name ?? 'Sistema' }}</td>
            <td class="small">
              @if($log->error_message)
                <span class="text-danger">{{ $log->error_message }}</span>
              @elseif($log->provider_message_id)
                ID: {{ $log->provider_message_id }}
              @else
                -
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center py-4">No hay notificaciones registradas para este usuario.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
