<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');
        $status = $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $usersQuery = User::query()
            ->with([
                'doctor:id,name',
                'creator:id,name',
                'updater:id,name',
                'deactivator:id,name',
            ])
            ->orderBy('name');

        if (in_array($role, [User::ROLE_ADMIN, User::ROLE_DOCTOR], true)) {
            $usersQuery->where('role', $role);
        }

        if ($status === 'active') {
            $usersQuery->where('active', true);
        } elseif ($status === 'inactive') {
            $usersQuery->where('active', false);
        }

        if ($q !== '') {
            $usersQuery->where(function ($query) use ($q): void {
                $query
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $usersQuery->paginate(15)->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('active', true)->count(),
            'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'doctors' => User::query()->where('role', User::ROLE_DOCTOR)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats', 'role', 'status', 'q'));
    }

    public function create(): View
    {
        $doctors = Doctor::query()
            ->where('active', true)
            ->whereDoesntHave('user', fn ($query) => $query->where('role', User::ROLE_DOCTOR))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.users.create', compact('doctors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $actorId = (int) $request->user()->id;
        $active = (bool) ($data['active'] ?? true);

        User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $this->normalizePhone($data['phone'] ?? null),
            'role' => $data['role'],
            'doctor_id' => $data['role'] === User::ROLE_DOCTOR ? (int) $data['doctor_id'] : null,
            'active' => $active,
            'created_by' => $actorId,
            'updated_by' => $actorId,
            'deactivated_by' => $active ? null : $actorId,
            'deactivated_at' => $active ? null : now(),
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $doctors = Doctor::query()
            ->where('active', true)
            ->where(function ($query) use ($user): void {
                $query
                    ->whereDoesntHave('user', fn ($userQuery) => $userQuery->where('role', User::ROLE_DOCTOR))
                    ->orWhere('id', $user->doctor_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.users.edit', compact('user', 'doctors'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatePayload($request, $user);
        $newRole = $data['role'];
        $newActive = (bool) ($data['active'] ?? true);
        $actorId = (int) $request->user()->id;
        $isSelf = (int) $request->user()->id === (int) $user->id;

        if ($isSelf && (!$newActive || $newRole !== User::ROLE_ADMIN)) {
            return back()
                ->withErrors(['auth' => 'No puedes desactivarte ni cambiar tu propio rol de administrador.'])
                ->withInput();
        }

        if (
            $user->isAdmin()
            && (!$newActive || $newRole !== User::ROLE_ADMIN)
            && !$this->hasAnotherActiveAdmin($user)
        ) {
            return back()
                ->withErrors(['auth' => 'Debe existir al menos un administrador activo en el sistema.'])
                ->withInput();
        }

        $payload = [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $this->normalizePhone($data['phone'] ?? null),
            'role' => $newRole,
            'doctor_id' => $newRole === User::ROLE_DOCTOR ? (int) $data['doctor_id'] : null,
            'active' => $newActive,
            'updated_by' => $actorId,
        ];

        if ($user->active && !$newActive) {
            $payload['deactivated_by'] = $actorId;
            $payload['deactivated_at'] = now();
        } elseif (!$user->active && $newActive) {
            $payload['deactivated_by'] = null;
            $payload['deactivated_at'] = null;
        }

        $user->update($payload);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $isSelf = (int) $request->user()->id === (int) $user->id;
        $actorId = (int) $request->user()->id;
        if ($isSelf) {
            return back()->withErrors(['auth' => 'No puedes desactivar tu propio usuario.']);
        }

        $newActive = !$user->active;
        if ($user->isAdmin() && !$newActive && !$this->hasAnotherActiveAdmin($user)) {
            return back()->withErrors(['auth' => 'Debe existir al menos un administrador activo en el sistema.']);
        }

        $user->update([
            'active' => $newActive,
            'updated_by' => $actorId,
            'deactivated_by' => $newActive ? null : $actorId,
            'deactivated_at' => $newActive ? null : now(),
        ]);

        $message = $newActive ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';

        return back()->with('success', $message);
    }

    public function editPassword(User $user): View
    {
        return view('admin.users.password', compact('user'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => $data['password'],
            'updated_by' => (int) $request->user()->id,
        ]);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'La contraseña del usuario fue actualizada.');
    }

    private function validatePayload(Request $request, ?User $user = null): array
    {
        $isCreate = $user === null;

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^\+[1-9]\d{6,14}$/'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_DOCTOR])],
            'doctor_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_DOCTOR),
                'nullable',
                'integer',
                Rule::exists('doctors', 'id')->where(fn ($query) => $query->where('active', true)),
                Rule::unique('users', 'doctor_id')->ignore($user?->id),
            ],
            'active' => ['sometimes', 'boolean'],
        ];

        if ($isCreate) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules, [
            'doctor_id.unique' => 'El médico seleccionado ya tiene un usuario asignado.',
            'phone.regex' => 'El teléfono debe estar en formato internacional E.164, por ejemplo +5491122334455.',
        ]);
    }

    private function hasAnotherActiveAdmin(User $excluding): bool
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('active', true)
            ->where('id', '!=', $excluding->id)
            ->exists();
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $trimmed = trim($phone);
        if ($trimmed === '') {
            return null;
        }

        $normalized = preg_replace('/[\s\-\(\)]/', '', $trimmed);

        return $normalized ?: null;
    }
}
