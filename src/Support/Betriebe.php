<?php

namespace Platform\Occupational\Support;

use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationEntityType;

/**
 * Betriebe — spiegelt den UMWELT-Teilbaum aus dem Organization-Graphen: die externen
 * Kunden-Knoten (external_customer) UND alles darunter (Abteilungen, egal welcher
 * Untertyp). Man kann jeden Knoten wählen — Firma ODER Abteilung. Loser Bezug per
 * entity_id; KEIN Hardcoden von Abteilungs-Typen (die sind instanz-spezifisch).
 */
class Betriebe
{
    /**
     * Eingerückte Auswahl als value/label-Liste (ID-Selects brauchen das Array-Format —
     * integer-Keys würde x-nx-input-select als Skalar behandeln).
     *
     * @return array<int,array{value:int,label:string}>
     */
    public static function options(int $teamId): array
    {
        $customerTypeId = OrganizationEntityType::query()->where('code', 'external_customer')->value('id');
        if (!$customerTypeId) {
            return [];
        }

        $entities = OrganizationEntity::query()
            ->forTeam($teamId)
            ->get(['id', 'name', 'parent_entity_id', 'entity_type_id']);

        $byParent = $entities->groupBy('parent_entity_id');

        // Wurzeln = Kunden-Knoten (external_customer).
        $roots = $entities->where('entity_type_id', (int) $customerTypeId)->sortBy('name')->values();

        $out = [];
        $walk = function ($node, int $depth) use (&$walk, $byParent, &$out): void {
            $out[] = ['value' => (int) $node->id, 'label' => str_repeat('— ', $depth) . $node->name];
            foreach (($byParent[$node->id] ?? collect())->sortBy('name') as $child) {
                $walk($child, $depth + 1);
            }
        };

        foreach ($roots as $root) {
            $walk($root, 0);
        }

        return $out;
    }

    public static function name(?int $entityId): ?string
    {
        if (!$entityId) {
            return null;
        }

        return OrganizationEntity::query()->whereKey($entityId)->value('name');
    }
}
