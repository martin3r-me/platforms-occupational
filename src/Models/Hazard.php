<?php

namespace Platform\Occupational\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Platform\Occupational\Enums\HazardCategory;
use Platform\Occupational\Enums\HazardRisk;
use Platform\Occupational\Enums\HazardStatus;

/**
 * Hazard — einzelne Gefährdung einer Beurteilung.
 *
 * `catalog()` verweist optional per morphMap auf die empfohlene Vorsorge (Katalog-Eintrag).
 *
 * @ai.description Einzelne Gefährdung mit Maßnahme/Frist und optionaler Katalog-Empfehlung.
 */
class Hazard extends Model
{
    protected $table = 'occupational_hazards';

    protected $fillable = [
        'team_id',
        'risk_assessment_id',
        'category',
        'description',
        'risk',
        'measures',
        'responsible',
        'deadline',
        'status',
        'effectiveness_checked_at',
        'effective',
        'catalog_type',
        'catalog_id',
    ];

    protected $casts = [
        'category'                 => HazardCategory::class,
        'risk'                     => HazardRisk::class,
        'status'                   => HazardStatus::class,
        'deadline'                 => 'date',
        'effectiveness_checked_at' => 'date',
        'effective'                => 'boolean',
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

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', HazardStatus::Done->value);
    }

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class, 'risk_assessment_id');
    }

    public function catalog(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'catalog_type', 'catalog_id');
    }

    public function isOverdue(): bool
    {
        return $this->deadline
            && $this->status !== HazardStatus::Done
            && $this->deadline->lt(Carbon::now()->startOfDay());
    }
}
