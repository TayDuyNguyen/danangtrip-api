<?php

namespace App\Enums;

enum TourScheduleBookingAvailability: string
{
    case OPEN = 'open';
    case SOLD_OUT = 'sold_out';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
