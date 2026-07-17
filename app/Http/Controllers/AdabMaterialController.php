<?php

namespace App\Http\Controllers;

use App\Models\AdabMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdabMaterialController extends Controller
{
    /**
     * Check if user is authorized to manage adab materials.
     */
    private function authorizeManager(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user && $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'coordinator_tahfizh']),
            403,
            'Anda tidak memiliki akses untuk mengelola materi adab.'
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = AdabMaterial::query()->with('creator');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $materials = $query->latest()->paginate(12)->withQueryString();

        $canManage = false;
        $user = $request->user();
        if ($user && $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'coordinator_tahfizh'])) {
            $canManage = true;
        }

        return view('adab-materials.index', compact('materials', 'canManage'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorizeManager($request);

        return view('adab-materials.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip|max:5120',
            'url_link' => 'nullable|url|max:500',
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'file.max' => 'Ukuran berkas maksimal adalah 5MB.',
            'file.mimes' => 'Format berkas harus berupa pdf, doc, xls, ppt, gambar, atau zip.',
            'url_link.url' => 'Format link/tautan eksternal tidak valid.',
        ]);

        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $filePath = $uploadedFile->store('adab-materials', 'public');
            $fileName = $uploadedFile->getClientOriginalName();
            $fileSize = $uploadedFile->getSize();
        }

        AdabMaterial::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'url_link' => $request->input('url_link'),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('adab-materials.index')
            ->with('success', 'Materi adab berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, AdabMaterial $adabMaterial): View
    {
        $this->authorizeManager($request);

        return view('adab-materials.edit', compact('adabMaterial'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AdabMaterial $adabMaterial): RedirectResponse
    {
        $this->authorizeManager($request);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip|max:5120',
            'url_link' => 'nullable|url|max:500',
        ], [
            'title.required' => 'Judul materi wajib diisi.',
            'file.max' => 'Ukuran berkas maksimal adalah 5MB.',
            'file.mimes' => 'Format berkas harus berupa pdf, doc, xls, ppt, gambar, atau zip.',
            'url_link.url' => 'Format link/tautan eksternal tidak valid.',
        ]);

        $filePath = $adabMaterial->file_path;
        $fileName = $adabMaterial->file_name;
        $fileSize = $adabMaterial->file_size;

        if ($request->boolean('remove_file') && $filePath) {
            Storage::disk('public')->delete($filePath);
            $filePath = null;
            $fileName = null;
            $fileSize = null;
        }

        if ($request->hasFile('file')) {
            if ($adabMaterial->file_path) {
                Storage::disk('public')->delete($adabMaterial->file_path);
            }
            $uploadedFile = $request->file('file');
            $filePath = $uploadedFile->store('adab-materials', 'public');
            $fileName = $uploadedFile->getClientOriginalName();
            $fileSize = $uploadedFile->getSize();
        }

        $adabMaterial->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'url_link' => $request->input('url_link'),
        ]);

        return redirect()->route('adab-materials.index')
            ->with('success', 'Materi adab berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, AdabMaterial $adabMaterial): RedirectResponse
    {
        $this->authorizeManager($request);

        if ($adabMaterial->file_path) {
            Storage::disk('public')->delete($adabMaterial->file_path);
        }

        $adabMaterial->delete();

        return redirect()->route('adab-materials.index')
            ->with('success', 'Materi adab berhasil dihapus.');
    }
}
