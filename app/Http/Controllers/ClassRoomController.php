<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRoomRequest;
use App\Http\Requests\UpdateClassRoomRequest;
use App\Models\ClassRoom;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{
    public function index(Request $request): View
    {
        $programs = Program::query()
            ->orderBy('name')
            ->get();

        $classRooms = ClassRoom::query()
            ->with('program')
            ->withCount('students')
            ->when($request->filled('program_id'), function ($query) use ($request) {
                $query->where('program_id', $request->integer('program_id'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('class-rooms.index', compact('classRooms', 'programs'));
    }

    public function create(): View
    {
        $programs = Program::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('class-rooms.create', compact('programs'));
    }

    public function store(StoreClassRoomRequest $request): RedirectResponse
    {
        ClassRoom::create($request->validated());

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(ClassRoom $classRoom): View
    {
        $classRoom->load('program')->loadCount('students');

        $students = $classRoom->students()
            ->latest()
            ->paginate(10);

        return view('class-rooms.show', compact('classRoom', 'students'));
    }

    public function edit(ClassRoom $classRoom): View
    {
        $programs = Program::query()
            ->orderBy('name')
            ->get();

        return view('class-rooms.edit', compact('classRoom', 'programs'));
    }

    public function update(UpdateClassRoomRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->update($request->validated());

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        if ($classRoom->students()->exists()) {
            return back()->with('error', 'Kelas tidak bisa dihapus karena masih memiliki santri.');
        }

        $classRoom->delete();

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }
}