<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = 'ADMIN/USERS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pengguna';

        $roles = Role::orderBy('id')->get();
        $selectedRoleId = $request->input('role_id');

        $users = User::with('roles')
            ->when($selectedRoleId, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('id', $selectedRoleId)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('pagePath', 'pageName', 'roles', 'users', 'selectedRoleId'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/USERS/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Tambah Pengguna';
        $roles = Role::orderBy('id')->get();

        return view('admin.users.create', compact('pagePath', 'pageName', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $roleId = $validated['role_id'];
        unset($validated['role_id']);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active');

        $user = User::create($validated);
        $user->assignRole(Role::findOrFail($roleId)->name);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $pagePath = 'ADMIN/USERS/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit Pengguna';
        $roles = Role::orderBy('id')->get();

        return view('admin.users.edit', compact('pagePath', 'pageName', 'user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($user->id === Auth::id() && !$request->boolean('is_active')) {
            return back()
                ->withInput()
                ->with('error', 'Akun yang sedang digunakan tidak boleh dinonaktifkan.');
        }

        $roleId = $validated['role_id'];
        unset($validated['role_id']);

        $validated['is_active'] = $request->boolean('is_active');

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->syncRoles(Role::findOrFail($roleId)->name);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }
}
