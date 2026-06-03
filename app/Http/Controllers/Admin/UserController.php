<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Show create user form
     */

  public function index(Request $request)
{
    $users = User::with(['roles', 'company'])
        ->when($request->company_id, fn ($q) =>
            $q->where('company_id', $request->company_id)
        )
        ->when($request->status, fn ($q) =>
            $q->where('status', $request->status)
        )
        ->when($request->role, fn ($q) =>
            $q->whereHas('roles', fn ($r) =>
                $r->where('name', $request->role)
            )
        )
        ->orderBy('created_at', 'desc')
        ->get();

    return view('admin.users.index', [
        'users'     => $users,
        'companies' => Company::orderBy('name')->get(),
        'roles'     => Role::orderBy('name')->get(),
        'isArchive' => false,
    ]);
}

    public function archive(Request $request)
    {
        $users = User::onlyTrashed()->with(['roles', 'company'])
            ->when($request->company_id, fn ($q) =>
                $q->where('company_id', $request->company_id)
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->role, fn ($q) =>
                $q->whereHas('roles', fn ($r) =>
                    $r->where('name', $request->role)
                )
            )
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin.users.index', [
            'users'     => $users,
            'companies' => Company::orderBy('name')->get(),
            'roles'     => Role::orderBy('name')->get(),
            'isArchive' => true,
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'companies'));
    }

    /**
     * Store new user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'role'        => 'required|exists:roles,name',
            'company_id'  => 'nullable|exists:companies,id',
            'status'      => 'required|in:active,inactive',
        ]);

        $user = User::create([
            'first_name'  => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name'   => $validated['last_name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => $validated['role'],
            'company_id'  => $validated['company_id'] ?? null,
            'status'      => $validated['status'],
        ]);

        // Assign Spatie role
        $user->assignRole($validated['role']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles', 'companies'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'password'    => 'nullable|string|min:8|confirmed',
            'role'        => 'required|exists:roles,name',
            'company_id'  => 'nullable|exists:companies,id',
            'status'      => 'required|in:active,inactive,suspended',
        ]);

        $payload = [
            'first_name'  => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name'   => $validated['last_name'],
            'email'       => $validated['email'],
            'role'        => $validated['role'],
            'company_id'  => $validated['company_id'] ?? null,
            'status'      => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->update(['status' => 'inactive']);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->update(['status' => 'active']);

        return redirect()
            ->route('admin.users.archive')
            ->with('success', 'User restored successfully.');
    }
}
