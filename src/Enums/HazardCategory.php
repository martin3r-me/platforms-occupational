<?php

namespace Platform\Occupational\Enums;

enum HazardCategory: string
{
    case Mechanical = 'mechanical';
    case Electrical = 'electrical';
    case HazardousSubstances = 'hazardous_substances';
    case Biological = 'biological';
    case FireExplosion = 'fire_explosion';
    case Thermal = 'thermal';
    case Physical = 'physical';               // Lärm/Vibration/Strahlung/Klima
    case WorkEnvironment = 'work_environment';
    case PhysicalStrain = 'physical_strain';
    case MentalStrain = 'mental_strain';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mechanical          => 'Mechanisch',
            self::Electrical          => 'Elektrisch',
            self::HazardousSubstances => 'Gefahrstoffe',
            self::Biological          => 'Biologische Arbeitsstoffe',
            self::FireExplosion       => 'Brand/Explosion',
            self::Thermal             => 'Thermisch',
            self::Physical            => 'Physikalisch',
            self::WorkEnvironment     => 'Arbeitsumgebung',
            self::PhysicalStrain      => 'Physische Belastung',
            self::MentalStrain        => 'Psychische Belastung',
            self::Other               => 'Sonstige',
        };
    }
}
