<?php

/**
 * Occupational — Betriebsmedizin-Fachmodul (Alltagslogik).
 *
 * Sitzt auf [patient] + [encounter] und konsumiert Katalog-Module (arbmedvv, …).
 * Enthält: Employment (Patient↔Firma, mit Start/Ende, LOSE gekoppelt),
 * Gefährdungsbeurteilung + Gefährdung, Ausprägung, VorsorgeArt.
 *
 * Konvention: englische Identifier, deutsche Anzeige-Labels.
 */

return [
    'routing' => [
        'mode'   => env('OCCUPATIONAL_MODE', 'path'),
        'prefix' => 'occupational',
    ],

    'guard' => 'web',

    'navigation' => [
        'route' => 'occupational.dashboard',
        'icon'  => 'heroicon-o-briefcase',
        'order' => 36,
    ],

    'sidebar' => [
        [
            'group' => 'Arbeitsmedizin',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'occupational.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
                [
                    'label' => 'Beschäftigte',
                    'route' => 'occupational.employees.index',
                    'icon'  => 'heroicon-o-users',
                ],
                // 'Gefährdungsbeurteilungen' folgt als nächste Entität.
            ],
        ],
    ],
];
