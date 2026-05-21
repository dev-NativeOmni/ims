<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::query()
            ->withCount('classRooms')
            ->latest()
            ->paginate(10);

        return view('programs.index', compact('programs'));
    }

    public function create(): View
    {
        return view('programs.create');
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::create($request->validated());

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program berhasil ditambahkan.');
    }

    public function show(Program $program): View
    {
        $program->loadCount('classRooms');

        $classRooms = $program->classRooms()
            ->withCount('students')
            ->latest()
            ->paginate(10);

        return view('programs.show', compact('program', 'classRooms'));
    }

    public function edit(Program $program): View
    {
        return view('programs.edit', compact('program'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->classRooms()->exists()) {
            return back()->with('error', 'Program tidak bisa dihapus karena masih memiliki kelas.');
        }

        $program->delete();

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program berhasil dihapus.');
    }
}