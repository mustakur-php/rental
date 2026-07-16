<?php

return [
    'currency' => 'SAR',

    'vat_rate' => 15,

    'payment' => [
        'grace_period_days' => 10,
        'allow_partial_payments' => true,
        'allow_overpayment' => false,
    ],

    'notifications' => [
        'due_before_days' => [15, 7, 1],
        'contract_expiry_before_days' => [60, 30, 15],
        'persistent' => true,
    ],

    'ui' => [
        'default_units_view' => 'cards',
        'available_units_views' => ['cards', 'table', 'map'],
        'style' => 'premium-soft-ui',
    ],
];
