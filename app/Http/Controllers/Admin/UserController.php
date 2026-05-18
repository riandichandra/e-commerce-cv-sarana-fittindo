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
    public function index()
    {
        $pagePath = 'ADMIN/USERS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Users';

        $roles = Role::with(['users' => fn ($query) => $query->latest()])
            ->orderBy('id')
            ->get();

        return view('admin.users.index', compact('pagePath', 'pageName', 'roles'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/USERS/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Create User';
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

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active');

        User::create($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $pagePath = 'ADMIN/USERS/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit User';
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

        $validated['is_active'] = $request->boolean('is_active');

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }
}
