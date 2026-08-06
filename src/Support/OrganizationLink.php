<?php

namespace Platform\Occupational\Support;

use Platform\Organization\Services\DimensionLinkService;
use Platform\Organization\Models\OrganizationDimensionDefinition;
use Platform\Organization\Models\OrganizationDimensionValue;
use Platform\Organization\Models\OrganizationDimensionLink;

/**
 * OrganizationLink — hält den dimension_link eines occupational-Objekts auf seinen
 * Betrieb-Org-Entity synchron (genau EIN Betrieb je Objekt). Setzt/ändert/entfernt den
 * Link in der "entity"-Dimension. Fehlertolerant (blockiert Saves nicht, falls Org fehlt).
 */
class OrganizationLink
{
    public static function sync(string $contextAlias, int $contextId, ?int $entityId, ?int $teamId, ?int $userId = null): void
    {
        try {
            $def = OrganizationDimensionDefinition::findByKey('entity');
            if (!$def) {
                return;
            }

            $resolvedType = DimensionLinkService::resolveContextType($contextAlias);

            // Bestehende entity-Links dieses Kontexts entfernen (Ein-Betrieb-Semantik).
            OrganizationDimensionLink::query()
                ->where('dimension_definition_id', $def->id)
                ->where('linkable_type', $resolvedType)
                ->where('linkable_id', $contextId)
                ->delete();

            if (!$entityId) {
                return;
            }

            $dimValue = OrganizationDimensionValue::query()
                ->where('dimension_definition_id', $def->id)
                ->where('metadata->source_entity_id', $entityId)
                ->first();
            if (!$dimValue) {
                return;
            }

            (new DimensionLinkService())->link('entity', $contextAlias, $contextId, $dimValue->id, [
                'is_primary'         => true,
                'team_id'            => $teamId,
                'created_by_user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            // Org-Modul nicht verfügbar / Sync-Fehler — Save nicht blockieren.
        }
    }
}
