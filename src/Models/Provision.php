<?php

namespace Platform\Occupational\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Symfony\Component\Uid\UuidV7;
use Platform\Occupational\Enums\CareType;

/**
 * Provision — arbeitsmedizinische Vorsorge (ArbMedVV): die betriebsärztliche PFLICHT/REGEL,
 * die einen Termin auslöst. Person-verankert (patient_id, lose). Verweist per morphMap auf
 * den Katalog-Anlass (arbmedvv_occasion — die gesetzliche Grundlage). `next_due_at` = Recall.
 *
 * @ai.description Vorsorge-Pflicht (Pflicht/Angebot/Wunsch/nachgehend) einer Person, Anlass aus Katalog.
 */
class Provision extends Model
{
    protected $table = 'occupational_provisions';

    protected $fillable = [
        'uuid',
        'team_id',
        'patient_id',
        'occasion_type',
        'occasion_id',
        'type',
        'interval_months',
        'last_done_at',
        'next_due_at',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'type'            => CareType::class,
        'interval_months' => 'integer',
        'last_done_at'    => 'date',
        'next_due_at'     => 'date',
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

            if (empty($model->type)) {
                $model->type = CareType::Mandatory->value;
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

        return $query->whereNotNull('next_due_at')->where('next_due_at', '<=', $threshold);
    }

    public function isOverdue(): bool
    {
        return $this->next_due_at && $this->next_due_at->lt(Carbon::now()->startOfDay());
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\Platform\Patient\Models\Patient::class, 'patient_id');
    }

    /**
     * Katalog-Anlass (arbmedvv_occasion) per morphMap — katalog-agnostisch.
     */
    public function occasion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'occasion_type', 'occasion_id');
    }
}
