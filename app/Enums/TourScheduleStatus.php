<?php

namespace App\Enums;

enum TourScheduleStatus: string
{
    case AVAILABLE = 'available';
    case FULL = 'full';
    case CANCELLED = 'cancelled';

    /**
     * Get all values of the enum.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
