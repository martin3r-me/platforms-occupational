<?php

namespace Platform\Occupational\Support;

use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationEntityType;

/**
 * Betriebe — Auswahl der Betriebe als Org-Entities (external_customer) aus dem
 * Organization-Graphen. occupational sitzt auf organization; loser Bezug per entity_id.
 */
class Betriebe
{
    protected static function typeId(): ?int
    {
        return OrganizationEntityType::query()->where('code', 'external_customer')->value('id');
    }

    /**
     * @return array<int,string>  entity_id => name
     */
    public static function options(int $teamId): array
    {
        $typeId = self::typeId();
        if (!$typeId) {
            return [];
        }

        return OrganizationEntity::query()
            ->forTeam($teamId)
            ->where('entity_type_id', $typeId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function name(?int $entityId): ?string
    {
        if (!$entityId) {
            return null;
        }

        return OrganizationEntity::query()->whereKey($entityId)->value('name');
    }
}
