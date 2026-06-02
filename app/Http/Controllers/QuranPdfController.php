<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class QuranPdfController extends Controller
{
    public function index(): View
    {
        return view('quran.pdf');
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:51200', // max 50MB
        ]);

        $file = $request->file('pdf_file');

        try {
            // Ensure directory exists
            File::ensureDirectoryExists(public_path('pdf'));

            // Move the file
            $file->move(public_path('pdf'), 'quran.pdf');

            return back()->with('success', 'Mushaf PDF berhasil diunggah dan disimpan secara lokal.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengunggah file: ' . $e->getMessage());
        }
    }
}
