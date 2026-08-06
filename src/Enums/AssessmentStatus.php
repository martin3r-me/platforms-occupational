<?php

namespace Platform\Occupational\Enums;

enum AssessmentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Revision = 'revision';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Entwurf',
            self::Active   => 'Aktiv',
            self::Revision => 'Überarbeitung',
        };
    }
}
