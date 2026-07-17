<?php

namespace App\Http\Controllers;

use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $query = User::query()->with('role');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();
        $roles = Role::orderBy('display_name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function edit(User $user): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('display_name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role_id' => $validated['role_id'],
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
                $userData['plain_password'] = $validated['password'];
            }

            $user->update($userData);

            $role = Role::find($validated['role_id']);
            if ($role) {
                if ($role->name === 'teacher' && ! $user->teacherProfile()->exists()) {
                    TeacherProfile::create(['user_id' => $user->id]);
                } elseif ($role->name === 'parent' && ! $user->parentProfile()->exists()) {
                    ParentProfile::create(['user_id' => $user->id]);
                } elseif ($role->name === 'student' && ! $user->studentProfile()->exists()) {
                    Student::create([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'status' => 'active',
                    ]);
                }
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Data user '.$user->username.' berhasil diperbarui.');
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('display_name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:6',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role_id' => $validated['role_id'],
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'status' => $validated['status'],
            ]);

            $role = Role::find($validated['role_id']);
            if ($role) {
                if ($role->name === 'teacher') {
                    TeacherProfile::create(['user_id' => $user->id]);
                } elseif ($role->name === 'parent') {
                    ParentProfile::create(['user_id' => $user->id]);
                } elseif ($role->name === 'student') {
                    Student::create([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'status' => 'active',
                    ]);
                }
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User baru berhasil dibuat.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if (auth()->id() === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $username = $user->username;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User '.$username.' berhasil dihapus.');
    }

    private function authorizeSuperAdmin(): void
    {
        if (! auth()->user()->hasRole('super_admin')) {
            abort(403, 'Aksi ini hanya dapat diakses oleh Super Admin.');
        }
    }
}
