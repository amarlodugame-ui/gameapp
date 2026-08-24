<?php

return [
    'lock_seconds' => 5,

    // If you want EXACT period_id style:
    // yyyymmdd + (game code) + (4-digit sequence)
    // You gave 30s code as 10005. For avoiding collisions across 4 game types,
    // keep different codes per type.
    'game_types' => [
        '30s' => ['interval' => 30,  'code' => '10005'],
        '1m'  => ['interval' => 60,  'code' => '10006'],
        '3m'  => ['interval' => 180, 'code' => '10007'],
        '5m'  => ['interval' => 300, 'code' => '10008'],
    ],

    // Server-side multipliers (never trust multiplier from client)
    'multipliers' => [
        'color' => [
            'red'    => 2.00,
            'green'  => 2.00,
            'violet' => 4.50,
        ],
        'bigsmall' => [
            'big'   => 2.00,
            'small' => 2.00,
        ],
        'number' => [
            0 => 9.00, 1 => 9.00, 2 => 9.00, 3 => 9.00, 4 => 9.00,
            5 => 9.00, 6 => 9.00, 7 => 9.00, 8 => 9.00, 9 => 9.00,
        ],
    ],

    // Update this if your user balance column name is different.
    'wallet' => [
        'user_balance_column' => 'balance',
        'currency_symbol' => '৳',
    ],
];
