<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'permission_overrides',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'last_login_at'         => 'datetime',
            'permission_overrides'  => 'array',
        ];
    }

    /**
     * Relasi ke Activity Log.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Relasi ke Project yang dibuat user.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Relasi ke Task yang ditugaskan kepada user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Relasi ke File milik user.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * Relasi ke Folder yang dibuat user.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'created_by');
    }

    /**
     * Memeriksa apakah user memiliki salah satu role yang diberikan.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Memeriksa apakah user adalah Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Memeriksa apakah user adalah Admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Memeriksa apakah user adalah Employee.
     */
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    /**
     * Memeriksa apakah akun masih aktif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Memeriksa apakah akun dinonaktifkan.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Memeriksa apakah user memiliki custom permission (override role).
     */
    public function hasCustomPermission(): bool
    {
        return !is_null($this->permission_overrides);
    }

    /**
     * Permission efektif user: pakai override jika ada,
     * jika tidak pakai default Role dari config/permissions.php.
     */
    public function getEffectivePermissions(): array
    {
        return $this->permission_overrides
            ?? config("permissions.role_defaults.{$this->role}", []);
    }

    /**
     * Memeriksa apakah user memiliki permission tertentu
     * (module.action), berdasarkan permission efektifnya.
     */
    public function hasPermission(string $module, string $action): bool
    {
        return (bool) data_get(
            $this->getEffectivePermissions(),
            "{$module}.{$action}",
            false
        );
    }
}

