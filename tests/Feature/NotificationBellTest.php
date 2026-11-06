<?php

namespace Tests\Feature;

use App\Models\HafalanTarget;
use App\Models\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function notify(array $attributes = []): SystemNotification
    {
        return SystemNotification::create($attributes + [
            'user_id' => $this->teacherUser->id,
            'type' => 'hafalan_attention',
            'title' => 'Hafalan perlu tindak lanjut',
            'message' => 'Santri Test perlu mengulang setoran.',
            'action_url' => '/hafalan-records',
        ]);
    }

    #[Test]
    public function header_bell_shows_unread_count_and_latest_notifications(): void
    {
        $this->notify();
        $this->notify(['title' => 'Target hafalan terlambat']);

        $this->actingAs($this->teacherUser)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-bell-count', false)
            ->assertSee('Notifikasi (2 belum dibaca)')
            ->assertSee('Target hafalan terlambat')
            ->assertSee('Tandai semua dibaca');
    }

    #[Test]
    public function bell_without_unread_has_no_badge(): void
    {
        $this->actingAs($this->teacherUser)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('data-bell-count', false)
            ->assertSee('Belum ada notifikasi.');
    }

    #[Test]
    public function opening_a_notification_marks_it_read_and_goes_to_its_page(): void
    {
        $notification = $this->notify();

        $this->actingAs($this->teacherUser)->get(route('system-notifications.open', $notification))
            ->assertRedirect('/hafalan-records');
        $this->assertFalse($notification->fresh()->isUnread());

        $external = $this->notify(['action_url' => 'https://contoh.test/phishing']);
        $this->actingAs($this->teacherUser)->get(route('system-notifications.open', $external))
            ->assertRedirect(route('system-notifications.show', $external));

        $this->actingAs($this->parentUser)->get(route('system-notifications.open', $notification))->assertForbidden();
    }

    #[Test]
    public function daily_generator_notifies_teacher_of_recently_missed_targets_only(): void
    {
        $base = ['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->surah->id, 'ayah' => 5];
        // tad:sync-completed-targets (01:00) sudah mengubah target lewat tenggat menjadi 'missed'.
        HafalanTarget::create($base + ['target_date' => today()->subDays(2), 'status' => 'missed']);
        HafalanTarget::create($base + ['target_date' => today()->subDays(40), 'status' => 'missed']);

        Artisan::call('notifications:generate');
        Artisan::call('notifications:generate');

        $teacherNotifications = SystemNotification::where('user_id', $this->teacherUser->id)->where('type', 'target_overdue')->get();
        $this->assertCount(1, $teacherNotifications, 'Hanya target 14 hari terakhir, dan tidak dobel bila dijalankan ulang.');
        $this->assertSame(0, SystemNotification::whereIn('user_id', [$this->admin->id, $this->superAdmin->id])->count(), 'Admin tidak dibanjiri notifikasi seluruh sekolah.');
    }
}
