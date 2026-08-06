<?php

namespace Platform\Occupational\Enums;

enum HazardRisk: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Gering',
            self::Medium => 'Mittel',
            self::High   => 'Hoch',
        };
    }
}
