<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'phone',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function teacherProfile(): HasMany|BelongsTo
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function parentProfile(): HasMany|BelongsTo
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function studentProfile(): HasMany|BelongsTo
    {
        return $this->hasOne(Student::class);
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function unreadSystemNotifications(): HasMany
    {
        return $this->systemNotifications()->whereNull('read_at');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->name, $roles, true);
    }

    public function roleName(): ?string
    {
        return $this->role?->name;
    }

    public function roleDisplayName(): ?string
    {
        return $this->role?->display_name ?? $this->role?->name;
    }

    public function accessibleStudentIds(): Collection
    {
        if ($this->hasAnyRole(['super_admin', 'admin'])) {
            return Student::query()
                ->pluck('id');
        }

        if ($this->hasRole('teacher')) {
            $teacherId = $this->teacherProfile?->id;

            if (! $teacherId) {
                return collect();
            }

            return Student::query()
                ->where('teacher_id', $teacherId)
                ->pluck('id');
        }

        if ($this->hasRole('parent')) {
            $parent = $this->parentProfile;

            if (! $parent) {
                return collect();
            }

            return $parent->students()
                ->pluck('students.id');
        }

        if ($this->hasRole('student')) {
            $studentId = $this->studentProfile?->id;

            return $studentId ? collect([$studentId]) : collect();
        }

        return collect();
    }
}