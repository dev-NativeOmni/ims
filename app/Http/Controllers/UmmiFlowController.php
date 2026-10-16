<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Setting;
use App\Services\StudentProgressService;
use App\Services\UmmiProsemService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Alur Pembelajaran Ummi (Kelas 10): timer per sesi supaya musyrif tidak lewat waktu, dan target pertemuan
 * hari ini dari Prosem (UmmiProsemService::meetingFor). Durasi sesi bisa diatur koordinator/admin.
 */
class UmmiFlowController extends Controller
{
    public const SETTING_KEY = 'ummi_flow_minutes';

    /** Sesi bawaan: Pembukaan 5, Hafalan 20 (Muraja'ah 10 + Ziyadah 10), Klasikal 40, Penutup 5. */
    public const SESSIONS = [
        ['key' => 'pembukaan', 'label' => 'Pembukaan', 'group' => null, 'minutes' => 5],
        ['key' => 'murojaah', 'label' => "Muraja'ah", 'group' => 'Hafalan', 'minutes' => 10],
        ['key' => 'ziyadah', 'label' => 'Ziyadah', 'group' => 'Hafalan', 'minutes' => 10],
        ['key' => 'klasikal', 'label' => 'Klasikal peraga & baca simak', 'group' => null, 'minutes' => 40],
        ['key' => 'penutup', 'label' => 'Penutup', 'group' => null, 'minutes' => 5],
    ];

    public function __construct(
        private readonly StudentProgressService $access,
        private readonly UmmiProsemService $prosem,
    ) {}

    /** Sesi dengan durasi dari pengaturan. */
    public static function sessions(): array
    {
        $saved = json_decode((string) Setting::get(self::SETTING_KEY, '{}'), true) ?: [];

        return array_map(fn ($s) => ['minutes' => max(1, (int) ($saved[$s['key']] ?? $s['minutes']))] + $s, self::SESSIONS);
    }

    public static function canConfigure($user): bool
    {
        return (bool) $user?->hasAnyRole(['super_admin', 'admin', 'coordinator_tahfizh']);
    }

    public function index(Request $request): View
    {
        $classIds = $this->access->visibleStudentQuery($request->user())->where('status', 'active')->pluck('class_room_id')->unique();
        $classes = ClassRoom::query()->with('program')->whereIn('id', $classIds)->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeTen())->values();
        $class = $classes->firstWhere('id', $request->integer('class_room_id')) ?? $classes->first();
        $today = Carbon::today();

        return view('ummi-flow.index', [
            'classes' => $classes,
            'class' => $class,
            'today' => $today,
            'meeting' => $class ? $this->prosem->meetingFor($class, $today) : null,
            'sessions' => self::sessions(),
            'storageKey' => 'ummiFlow:'.$request->user()->id.':'.($class?->id ?? 0).':'.$today->toDateString(),
            'canConfigure' => self::canConfigure($request->user()),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless(self::canConfigure($request->user()), 403);
        $rules = collect(self::SESSIONS)->mapWithKeys(fn ($s) => ["minutes.{$s['key']}" => ['required', 'integer', 'between:1,120']])->all();
        $validated = $request->validate($rules);
        Setting::set(self::SETTING_KEY, json_encode(array_map('intval', $validated['minutes'])));

        return back()->with('success', 'Durasi sesi disimpan (total '.array_sum($validated['minutes']).' menit).');
    }
}
