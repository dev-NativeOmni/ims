<?php

namespace App\Models;

use App\Support\ReadOnlyAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id',
        'name',
        'username',
        'avatar',
        'signature_path',
        'password',
        'plain_password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Get all roles assigned to this user.
     * Includes both pivot roles and fallback primary role.
     */
    public function assignedRoles(): Collection
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        if ($roles->isEmpty() && $this->role) {
            $roles = collect([$this->role]);
        } elseif ($this->role && ! $roles->contains('id', $this->role->id)) {
            $roles = $roles->prepend($this->role);
        }

        // Cache on Eloquent relation so subsequent checks in the same request are 0ms in-memory
        if (! $this->relationLoaded('roles')) {
            $this->setRelation('roles', $roles);
        }

        return $roles;
    }

    /** Role peminjaman tampilan akun trial (lihat currentRole()), di-cache per objek user. */
    private ?Role $readOnlyViewRole = null;

    /**
     * Get the currently active role for the user's session.
     * If active_role_id session is set and valid, returns that role.
     * Otherwise returns the primary role.
     *
     * Role trial (ReadOnlyAccess) dikembalikan sebagai role yang dipilih di "Lihat sebagai"
     * (bawaan ReadOnlyAccess::VIEWS_AS) dengan nama tampilan "Trial · ...", jadi seluruh pengecekan
     * role (menu, dashboard, cakupan data) memperlakukannya seperti role itu; batas "lihat saja"
     * dijaga BlockReadOnlyWrites.
     */
    public function currentRole(): ?Role
    {
        $role = $this->selectedRole();

        if ($role?->name !== ReadOnlyAccess::ROLE) {
            return $role;
        }

        // Role yang dipilih di "Lihat sebagai" (TrialViewController); bawaan Kepala Sekolah.
        $viewAsName = (string) session(ReadOnlyAccess::VIEW_ROLE_KEY, ReadOnlyAccess::VIEWS_AS);
        if ($viewAsName === ReadOnlyAccess::ROLE || in_array($viewAsName, ReadOnlyAccess::ACCOUNT_SCOPED_ROLES, true)) {
            $viewAsName = ReadOnlyAccess::VIEWS_AS;
        }

        if ($this->readOnlyViewRole?->name !== $viewAsName) {
            $viewAs = Role::query()->where('name', $viewAsName)->first()
                ?? Role::query()->where('name', ReadOnlyAccess::VIEWS_AS)->first();
            if (! $viewAs) {
                return $role;
            }
            $this->readOnlyViewRole = (clone $viewAs)->forceFill(['display_name' => 'Trial · '.($viewAs->display_name ?: $viewAs->name)]);
        }

        return $this->readOnlyViewRole;
    }

    /**
     * Role yang sedang dipilih di sesi apa adanya (tanpa pemetaan trial).
     */
    private function selectedRole(): ?Role
    {
        $activeRoleId = (int) (session('active_role_id') ?: 0);

        if ($activeRoleId > 0) {
            $matched = $this->assignedRoles()->firstWhere('id', $activeRoleId);
            if ($matched) {
                return $matched;
            }
        }

        return $this->role;
    }

    /**
     * Akun "lihat saja" (role trial): tidak boleh menambah, mengubah, menghapus, mengunggah,
     * mengunduh, atau mencetak (ReadOnlyAccess).
     */
    public function isReadOnly(): bool
    {
        return $this->selectedRole()?->name === ReadOnlyAccess::ROLE;
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function waliKelasClassRoom(): HasOne
    {
        return $this->hasOne(ClassRoom::class, 'wali_kelas_user_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function createdSystemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class, 'created_by');
    }

    public function unreadSystemNotifications(): HasMany
    {
        return $this->systemNotifications()
            ->unread()
            ->published();
    }

    public function adabMaterials(): HasMany
    {
        return $this->hasMany(AdabMaterial::class, 'created_by');
    }

    public function pendampingClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_room_pendamping_adab', 'user_id', 'class_room_id')->withTimestamps();
    }

    public function isAssignedPendampingForClass(int $classRoomId): bool
    {
        return $this->pendampingClasses()->where('class_rooms.id', $classRoomId)->exists()
            || ClassRoom::where('id', $classRoomId)->where('pendamping_adab_id', $this->id)->exists();
    }

    /**
     * Check if the currently active role matches the given role name.
     */
    public function hasRole(string $role): bool
    {
        return $this->currentRole()?->name === $role;
    }

    /**
     * Check if the currently active role matches any of the given role names.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->currentRole()?->name, $roles, true);
    }

    /**
     * Check if the user is assigned the given role name (regardless of current active session).
     */
    public function hasAssignedRole(string $role): bool
    {
        return $this->assignedRoles()->contains('name', $role);
    }

    /**
     * Check if the user is assigned any of the given role names.
     */
    public function hasAnyAssignedRole(array $roles): bool
    {
        return $this->assignedRoles()->whereIn('name', $roles)->isNotEmpty();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
