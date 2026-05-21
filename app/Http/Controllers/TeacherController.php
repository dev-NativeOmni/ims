<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $teachers = TeacherProfile::query()
            ->with('user')
            ->withCount('students')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('employee_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('teachers.create');
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $teacherRole = Role::where('name', 'teacher')->firstOrFail();

            $user = User::create([
                'role_id' => $teacherRole->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'email_verified_at' => now(),
            ]);

            TeacherProfile::create([
                'user_id' => $user->id,
                'employee_number' => $validated['employee_number'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function show(TeacherProfile $teacher): View
    {
        $teacher->load([
            'user',
            'students.classRoom.program',
        ])->loadCount('students');

        return view('teachers.show', compact('teacher'));
    }

    public function edit(TeacherProfile $teacher): View
    {
        $teacher->load('user');

        return view('teachers.edit', compact('teacher'));
    }

    public function update(UpdateTeacherRequest $request, TeacherProfile $teacher): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $teacher) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $teacher->user()->update($userData);

            $teacher->update([
                'employee_number' => $validated['employee_number'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(TeacherProfile $teacher): RedirectResponse
    {
        if ($teacher->students()->exists()) {
            return back()->with('error', 'Guru tidak bisa dihapus karena masih memiliki santri bimbingan.');
        }

        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;

            $teacher->delete();

            if ($user) {
                $user->delete();
            }
        });

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru berhasil dihapus.');
    }
}