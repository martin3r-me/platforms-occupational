<?php

namespace Platform\Occupational\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Exposure — Ausprägung/Gefährdungsfaktor an einer erbrachten Leistung, mit eigenem Recall.
 *
 * service_id ist eine LOSE Referenz auf encounter_services (occupational sitzt auf encounter).
 *
 * @ai.description Gefährdungsfaktor je erbrachter Leistung mit eigenem Fristen-Recall.
 */
class Exposure extends Model
{
    protected $table = 'occupational_exposures';

    protected $fillable = [
        'team_id',
        'service_id',
        'hazard_id',
        'label',
        'type',
        'participation',
        'next_due',
    ];

    protected $casts = [
        'next_due' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->team_id) && auth()->check()) {
                $model->team_id = auth()->user()->currentTeam?->id;
            }
        });
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where($this->getTable() . '.team_id', $teamId);
    }

    public function scopeDue(Builder $query, $asOf = null, int $lookaheadDays = 30): Builder
    {
        $threshold = ($asOf ? Carbon::parse($asOf) : Carbon::now())
            ->copy()->addDays($lookaheadDays)->endOfDay();

        return $query->whereNotNull('next_due')->where('next_due', '<=', $threshold);
    }

    public function hazard(): BelongsTo
    {
        return $this->belongsTo(Hazard::class, 'hazard_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\Platform\Encounter\Models\Service::class, 'service_id');
    }
}
