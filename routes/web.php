<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;

// Pantalla de inicio
Route::get('/', function () {
    return view('home');
});

// Paciente: formulario para solicitar turno
Route::get('/paciente', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/paciente/appointments', [AppointmentController::class, 'store'])->name('appointments.store');

// Small JSON endpoint for dependent doctor dropdown
Route::get('/api/specialties/{specialty}/doctors', [AppointmentController::class, 'doctorsBySpecialty'])
    ->name('api.specialties.doctors');

// Auth (login/logout) para admin y médico
Route::get('/login/{role}', [AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login/{role}', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Administrador/Recepción: listar por fecha, marcar llegada y pago
Route::prefix('/admin')->middleware('admin')->group(function () {
    Route::get('/', [ReceptionController::class, 'index'])->name('reception.index');
    Route::post('/appointments/{appointment}/arrived', [ReceptionController::class, 'markArrived'])->name('reception.arrived');
    Route::post('/appointments/{appointment}/paid', [ReceptionController::class, 'markPaid'])->name('reception.paid');

    Route::prefix('/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::get('/{user}/password', [AdminUserController::class, 'editPassword'])->name('admin.users.password.edit');
        Route::put('/{user}/password', [AdminUserController::class, 'updatePassword'])->name('admin.users.password.update');
        Route::patch('/{user}/active', [AdminUserController::class, 'toggleActive'])->name('admin.users.toggle-active');
    });
});

// Médico: ver pacientes del día (arrived/paid)
Route::prefix('/medico')->middleware('doctor')->group(function () {
    Route::get('/', [DoctorController::class, 'index'])->name('doctor.index');
});
