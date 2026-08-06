<?php

namespace Platform\Occupational\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employment — Beschäftigung: Patient ↔ Firma (betriebsmed. Sonderfall).
 *
 * Beide Bezüge sind LOSE gekoppelt: patient_id → patient-Modul, organization_entity_id → organization (Betrieb).
 * Der Arbeitgeber-Begriff lebt ausschließlich hier, nie im fachneutralen patient-Modul.
 *
 * @ai.description Beschäftigungsverhältnis (Patient↔Firma) mit Start/Ende.
 */
class Employment extends Model
{
    protected $table = 'occupational_employments';

    protected $fillable = [
        'team_id',
        'patient_id',
        'organization_entity_id',
        'position',
        'personnel_number',
        'started_at',
        'ended_at',
        'active',
        'first_aider',
        'work_notes',
        'risk',
    ];

    protected $casts = [
        'started_at'  => 'date',
        'ended_at'    => 'date',
        'active'      => 'boolean',
        'first_aider' => 'boolean',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\Platform\Patient\Models\Patient::class, 'patient_id');
    }

    /**
     * Betrieb = Org-Entity (lose, kein DB-FK). occupational sitzt auf organization.
     */
    public function organizationEntity(): BelongsTo
    {
        return $this->belongsTo(\Platform\Organization\Models\OrganizationEntity::class, 'organization_entity_id');
    }
}
