<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'logo' => Setting::get('logo'),
            'nama_instansi' => Setting::get('nama_instansi'),
            'login_bg' => Setting::get('login_bg'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:2048',
            'nama_instansi' => 'nullable|string|max:255',
            'login_bg' => 'nullable|image|max:5120',
        ]);

        if ($request->boolean('reset_logo')) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('logo', null);
        } elseif ($request->hasFile('logo')) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('logo', $path);
        }

        if ($request->has('nama_instansi')) {
            Setting::set('nama_instansi', $request->input('nama_instansi'));
        }

        if ($request->boolean('reset_login_bg')) {
            $oldBg = Setting::get('login_bg');
            if ($oldBg) {
                Storage::disk('public')->delete($oldBg);
            }
            Setting::set('login_bg', null);
        } elseif ($request->hasFile('login_bg')) {
            $oldBg = Setting::get('login_bg');
            if ($oldBg) {
                Storage::disk('public')->delete($oldBg);
            }
            $path = $request->file('login_bg')->store('settings', 'public');
            Setting::set('login_bg', $path);
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function editAdab()
    {
        $categories = Setting::getAdabQuestions();
        return view('settings.adab', compact('categories'));
    }

    public function updateAdab(Request $request)
    {
        $rules = [];
        for ($catIdx = 0; $catIdx < 3; $catIdx++) {
            $rules["categories.{$catIdx}.title"] = 'required|string|max:255';
            $rules["categories.{$catIdx}.desc"] = 'required|string|max:1000';
            
            $startQ = ($catIdx * 5) + 1;
            $endQ = $startQ + 4;
            for ($qIdx = $startQ; $qIdx <= $endQ; $qIdx++) {
                $rules["categories.{$catIdx}.questions.q{$qIdx}"] = 'required|string|max:255';
            }
        }

        $validated = $request->validate($rules);

        Setting::set('adab_questions', json_encode($validated['categories']));

        return redirect()->route('settings.adab')->with('success', 'Daftar pertanyaan kuisioner adab berhasil diperbarui.');
    }
}
