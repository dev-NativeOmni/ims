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
            'holidaysText' => implode("\n", Setting::getNationalHolidays((int)date('Y'))),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:2048',
            'nama_instansi' => 'nullable|string|max:255',
            'login_bg' => 'nullable|image|max:5120',
            'holidays' => 'nullable|string',
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

        if ($request->has('holidays')) {
            $lines = explode("\n", str_replace("\r", "", $request->input('holidays', '')));
            $dates = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $line)) {
                    $dates[] = $line;
                }
            }
            
            $groupedByYear = [];
            foreach ($dates as $date) {
                $year = (int) date('Y', strtotime($date));
                $groupedByYear[$year][] = $date;
            }
            
            $currentYear = (int) date('Y');
            if (!isset($groupedByYear[$currentYear])) {
                $groupedByYear[$currentYear] = [];
            }
            
            foreach ($groupedByYear as $year => $yearDates) {
                Setting::set("national_holidays_{$year}", json_encode(array_values(array_unique($yearDates))));
            }
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
        $input = $request->input('categories', []);

        // Validate: must have at least 1 category, max 10
        if (count($input) < 1 || count($input) > 10) {
            return back()->withErrors(['categories' => 'Jumlah kategori minimal 1 dan maksimal 10.'])->withInput();
        }

        $rules = [];
        foreach ($input as $catIdx => $cat) {
            $rules["categories.{$catIdx}.title"] = 'required|string|max:255';
            $rules["categories.{$catIdx}.desc"] = 'required|string|max:1000';

            $questions = $cat['questions'] ?? [];
            foreach ($questions as $qIdx => $_) {
                $rules["categories.{$catIdx}.questions.{$qIdx}"] = 'required|string|max:500';
            }
        }

        $validated = $request->validate($rules);

        // Normalize: ensure questions are plain arrays (not keyed by q-number)
        $toSave = [];
        foreach ($validated['categories'] as $catIdx => $cat) {
            $toSave[] = [
                'title' => $cat['title'],
                'desc' => $cat['desc'],
                'questions' => array_values($cat['questions']),
            ];
        }

        Setting::set('adab_questions', json_encode($toSave));

        return redirect()->route('settings.adab')
            ->with('success', 'Daftar pertanyaan kuisioner adab berhasil diperbarui.');
    }
}
