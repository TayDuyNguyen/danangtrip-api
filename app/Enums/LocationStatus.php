<?php

namespace App\Enums;

enum LocationStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /**
     * Get all values of the enum.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
