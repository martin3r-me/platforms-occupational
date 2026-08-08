<?php

namespace Platform\Occupational\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Symfony\Component\Uid\UuidV7;

/**
 * ActivityAssignment — Beschäftigung ↔ Tätigkeit (customer_activities, lose), temporal.
 * `valid_until` NULL = aktuell; Entfernen = weichschließen (Historie bleibt).
 */
class ActivityAssignment extends Model
{
    protected $table = 'occupational_employment_activities';

    protected $fillable = [
        'uuid',
        'team_id',
        'employment_id',
        'patient_id',
        'activity_id',
        'valid_from',
        'valid_until',
        'created_by_user_id',
    ];

    protected $casts = [
        'valid_from'  => 'date',
        'valid_until' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = (string) UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
            if (empty($model->team_id) && auth()->check()) {
                $model->team_id = auth()->user()->currentTeam?->id;
            }
            if (empty($model->valid_from)) {
                $model->valid_from = Carbon::now()->toDateString();
            }
        });
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where($this->getTable() . '.team_id', $teamId);
    }

    /** Aktuell gültige Zuordnungen. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('valid_until');
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class, 'employment_id');
    }
}
