<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment session timeout (minutes)
    |--------------------------------------------------------------------------
    |
    | A single payment QR / checkout session expires after this duration.
    | Customers can start a new payment attempt while the booking is still held.
    |
    */
    'payment_session_minutes' => (int) env('BOOKING_PAYMENT_SESSION_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Unpaid booking hold timeout (minutes)
    |--------------------------------------------------------------------------
    |
    | Pending bookings without successful payment are auto-cancelled after this
    | duration. Seats are released and the customer is notified in-app.
    |
    */
    'unpaid_expiry_minutes' => (int) env('BOOKING_UNPAID_EXPIRY_MINUTES', 60),
];
