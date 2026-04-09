<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';
    case CASH = 'cash';
    case MOMO = 'momo';
    case VNPAY = 'vnpay';
    case ZALOPAY = 'zalopay';

    /**
     * Get all values of the enum.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
