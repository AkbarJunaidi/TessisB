<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomRole extends Model
{
    /** Role dasar yang boleh dipilih untuk role kustom. Super Admin tidak bisa ditiru. */
    public const BASE_ROLES = [
        'employee' => 'Employee',
        'admin'    => 'Admin',
    ];

    /** Nama dan kunci milik role bawaan, tidak boleh dipakai role kustom. */
    public const RESERVED = ['super_admin', 'admin', 'employee'];

    protected $fillable = ['name', 'slug', 'base_role', 'description', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    /** Jumlah aksi izin yang aktif di role ini. */
    public function activePermissionCount(): int
    {
        return collect($this->permissions ?? [])->flatten()->filter()->count();
    }

    /** Kunci unik dari nama (huruf kecil, garis bawah), tidak bentrok dengan role bawaan atau role lain. */
    public static function makeSlug(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'role';
        $slug = $base;
        $i = 2;

        while (in_array($slug, self::RESERVED, true) || static::where('slug', $slug)->exists()) {
            $slug = "{$base}_{$i}";
            $i++;
        }

        return $slug;
    }
}
