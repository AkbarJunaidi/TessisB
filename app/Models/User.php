<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasPushSubscriptions;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_encrypted',
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
        'password_encrypted',
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
     * Payload hash + salinan terenkripsi (APP_KEY) untuk tampilan Super Admin.
     */
    public static function passwordPayload(string $plain): array
    {
        return [
            'password'           => $plain,
            'password_encrypted' => Crypt::encryptString($plain),
        ];
    }

    /**
     * Password saat ini; null bila belum tercatat atau APP_KEY sudah berganti.
     */
    public function currentPassword(): ?string
    {
        if (empty($this->password_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->password_encrypted);
        } catch (DecryptException) {
            return null;
        }
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
     * Relasi ke Signature (tanda tangan digital) milik user - dipakai
     * saat mengisi tanda tangan otomatis di dokumen seperti Kwitansi.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
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
            ?? \App\Support\RolePermissions::defaultsFor($this->role);
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

