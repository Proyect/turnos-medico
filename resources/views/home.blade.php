@extends('layouts.app')

@section('content')
<section class="app-hero p-4 p-lg-5 mb-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-8">
      <h1 class="hero-title">Gestioná turnos de forma clara y rápida</h1>
      <p class="hero-subtitle mb-0">
        Plataforma simple para pacientes, recepción y médicos. Todo el circuito del turno en una sola interfaz.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end">
      <a href="/paciente" class="btn btn-primary me-2">Solicitar turno</a>
      <a href="/admin" class="btn btn-soft">Ver recepción</a>
    </div>
  </div>
</section>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card h-100 service-card">
      <div class="card-body d-flex flex-column">
        <span class="service-icon">P</span>
        <h5 class="card-title">Paciente</h5>
        <p class="card-text muted-help">Solicitá tu turno en menos de un minuto con especialidad, médico, fecha y hora.</p>
        <a href="/paciente" class="btn btn-primary mt-auto">Ir a Paciente</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 service-card">
      <div class="card-body d-flex flex-column">
        <span class="service-icon">A</span>
        <h5 class="card-title">Administrador</h5>
        <p class="card-text muted-help">Consultá el listado del día y registrá asistencia y pagos desde el panel de recepción.</p>
        @if(session('role')==='admin')
          <a href="/admin" class="btn btn-success mt-auto">Ir al Panel</a>
        @else
          <a href="/login/admin" class="btn btn-outline-primary mt-auto">Iniciar sesión</a>
        @endif
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 service-card">
      <div class="card-body d-flex flex-column">
        <span class="service-icon">M</span>
        <h5 class="card-title">Médico</h5>
        <p class="card-text muted-help">Visualizá tus pacientes del día con prioridad para turnos ya abonados.</p>
        @if(session('role')==='doctor')
          <a href="/medico" class="btn btn-success mt-auto">Ir al Panel</a>
        @else
          <a href="/login/medico" class="btn btn-outline-primary mt-auto">Iniciar sesión</a>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
