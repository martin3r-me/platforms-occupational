<?php

namespace Platform\Occupational\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Occupational\Enums\AssessmentStatus;

/**
 * RiskAssessment — Gefährdungsbeurteilung je Firma/Arbeitsbereich (§5/6 ArbSchG).
 *
 * @ai.description Gefährdungsbeurteilung eines Betriebs/Arbeitsbereichs.
 */
class RiskAssessment extends Model
{
    protected $table = 'occupational_risk_assessments';

    protected $fillable = [
        'uuid',
        'team_id',
        'organization_entity_id',
        'title',
        'work_area',
        'assessed_on',
        'next_review',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'assessed_on' => 'date',
        'next_review' => 'date',
        'status'      => AssessmentStatus::class,
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

            if (empty($model->status)) {
                $model->status = AssessmentStatus::Draft->value;
            }
        });
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where($this->getTable() . '.team_id', $teamId);
    }

    public function hazards(): HasMany
    {
        return $this->hasMany(Hazard::class, 'risk_assessment_id');
    }

    /**
     * Betrieb = Org-Entity (lose, kein DB-FK).
     */
    public function organizationEntity(): BelongsTo
    {
        return $this->belongsTo(\Platform\Organization\Models\OrganizationEntity::class, 'organization_entity_id');
    }
}
