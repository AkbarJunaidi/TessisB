<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $casts = [
        'deadline'   => 'date',
        'event_date' => 'date',
    ];

    protected $fillable = [
        'name',
        'description',
        'deadline',
        'created_by',
        'client',
        'pic',
        'category',
        'event_date',
        'event_time_start',
        'event_time_end',
        'location',
        'address',
        'estimated_duration_minutes',
        'priority',
        'status',
    ];

    /**
     * Relasi One-to-Many: Sebuah Project memiliki banyak Tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * Relasi BelongsTo: Proyek ini diinisiasi oleh seorang User.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi Many-to-Many: Crew/Tim Project (harus user terdaftar),
     * beserta peran mereka di project ini (role_label).
     */
    public function crews(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('role_label')
            ->withTimestamps();
    }

    /**
     * Relasi One-to-Many: Catatan pada project.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    /**
     * Relasi One-to-One: Folder khusus Document Center milik project ini.
     */
    public function folder(): HasOne
    {
        return $this->hasOne(Folder::class);
    }

    /**
     * Relasi One-to-Many: Surat Jalan yang diterbitkan untuk project ini.
     */
    public function suratJalans(): HasMany
    {
        return $this->hasMany(SuratJalan::class);
    }
}
