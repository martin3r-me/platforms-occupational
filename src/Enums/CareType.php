<?php

namespace Platform\Occupational\Enums;

/**
 * CareType — Vorsorgeart nach ArbMedVV.
 */
enum CareType: string
{
    case Mandatory = 'mandatory'; // Pflichtvorsorge (§4)
    case Offered = 'offered';     // Angebotsvorsorge (§5)
    case Request = 'request';     // Wunschvorsorge (§5a)
    case FollowUp = 'follow_up';  // nachgehende Vorsorge (§6)

    public function label(): string
    {
        return match ($this) {
            self::Mandatory => 'Pflichtvorsorge',
            self::Offered   => 'Angebotsvorsorge',
            self::Request   => 'Wunschvorsorge',
            self::FollowUp  => 'Nachgehende Vorsorge',
        };
    }
}
