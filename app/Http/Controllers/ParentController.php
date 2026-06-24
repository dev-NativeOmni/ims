<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParentRequest;
use App\Http\Requests\UpdateParentRequest;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParentController extends Controller
{
    public function index(Request $request): View
    {
        $parents = ParentProfile::query()
            ->with('user')
            ->withCount('students')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('parents.index', compact('parents'));
    }

    public function create(): View
    {
        return view('parents.create');
    }

    public function store(StoreParentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $parentRole = Role::where('name', 'parent')->firstOrFail();

            $user = User::create([
                'role_id' => $parentRole->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
            ]);

            ParentProfile::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return redirect()
            ->route('parents.index')
            ->with('success', 'Data orangtua/wali berhasil ditambahkan.');
    }

    public function show(ParentProfile $parent): View
    {
        $parent->load([
            'user',
            'students.classRoom.program',
            'students.teacher.user',
        ])->loadCount('students');

        return view('parents.show', compact('parent'));
    }

    public function edit(ParentProfile $parent): View
    {
        $parent->load('user');

        return view('parents.edit', compact('parent'));
    }

    public function update(UpdateParentRequest $request, ParentProfile $parent): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $parent) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $parent->user()->update($userData);

            $parent->update([
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return redirect()
            ->route('parents.index')
            ->with('success', 'Data orangtua/wali berhasil diperbarui.');
    }

    public function destroy(ParentProfile $parent): RedirectResponse
    {
        if ($parent->students()->exists()) {
            return back()->with('error', 'Orangtua/wali tidak bisa dihapus karena masih terhubung dengan santri.');
        }

        DB::transaction(function () use ($parent) {
            $user = $parent->user;

            $parent->delete();

            if ($user) {
                $user->delete();
            }
        });

        return redirect()
            ->route('parents.index')
            ->with('success', 'Data orangtua/wali berhasil dihapus.');
    }
}