<?php

namespace Platform\Occupational\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Occupational\Models\Employment;

/**
 * Rendert Beschäftigte reich am Betrieb-Org-Entity (dimension_link-Alias
 * "occupational_employment"). Vorbild: ArbmedvvEntityLinkProvider.
 */
class OccupationalEntityLinkProvider implements EntityLinkProvider
{
    public function morphAliases(): array
    {
        return ['occupational_employment'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'occupational_employment' => [
                'label'    => 'Beschäftigte',
                'singular' => 'Beschäftigte:r',
                'icon'     => 'users',
                'route'    => 'occupational.employees.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        if ($morphAlias === 'occupational_employment') {
            $query->with('patient');
        }
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        if ($morphAlias !== 'occupational_employment' || !$model instanceof Employment) {
            return [];
        }

        return [
            'patient'  => $model->patient?->getDisplayName(),
            'position' => $model->position,
            'status'   => $model->active ? 'aktiv' : 'inaktiv',
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'occupational_employment' => [
                ['field' => 'position', 'format' => 'text'],
                ['field' => 'status', 'format' => 'badge'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'occupational_employment') {
            return [];
        }

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $result[$entityId] = [
                'occupational_employees_count' => is_countable($ids) ? count($ids) : 0,
            ];
        }
        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }
}
