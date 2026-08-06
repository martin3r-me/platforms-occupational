<?php

namespace Platform\Occupational\Enums;

enum HazardStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Offen',
            self::InProgress => 'In Umsetzung',
            self::Done       => 'Erledigt',
        };
    }
}
