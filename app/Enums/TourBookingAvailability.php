<?php

namespace App\Enums;

enum TourBookingAvailability: string
{
    case OPEN = 'open';
    case SOLD_OUT = 'sold_out';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
