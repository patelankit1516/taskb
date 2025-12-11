<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discount Stacking Strategy
    |--------------------------------------------------------------------------
    |
    | Determines how multiple discounts are combined when applied to a user.
    | Options: 'sequential', 'best', 'all'
    | - sequential: Apply discounts in order, each on the reduced amount
    | - best: Apply only the best single discount
    | - all: Apply all discounts independently (sum them up)
    |
    */
    'stacking_strategy' => env('DISCOUNT_STACKING_STRATEGY', 'sequential'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Percentage Cap
    |--------------------------------------------------------------------------
    |
    | The maximum total discount percentage that can be applied.
    | This prevents users from getting discounts exceeding 100%.
    | Value should be between 0 and 100.
    |
    */
    'max_percentage_cap' => env('DISCOUNT_MAX_PERCENTAGE_CAP', 100),

    /*
    |--------------------------------------------------------------------------
    | Rounding Mode
    |--------------------------------------------------------------------------
    |
    | Determines how discount amounts are rounded.
    | Options: 'up', 'down', 'half_up', 'half_down', 'half_even'
    | Uses PHP_ROUND_* constants internally
    |
    */
    'rounding_mode' => env('DISCOUNT_ROUNDING_MODE', 'half_up'),

    /*
    |--------------------------------------------------------------------------
    | Rounding Precision
    |--------------------------------------------------------------------------
    |
    | Number of decimal places to round discount amounts to.
    | Typically 2 for currency calculations.
    |
    */
    'rounding_precision' => env('DISCOUNT_ROUNDING_PRECISION', 2),

    /*
    |--------------------------------------------------------------------------
    | Enable Audit Trail
    |--------------------------------------------------------------------------
    |
    | Whether to log all discount operations in the audit table.
    | Recommended for production environments.
    |
    */
    'enable_audit' => env('DISCOUNT_ENABLE_AUDIT', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Events
    |--------------------------------------------------------------------------
    |
    | Whether to queue discount events for asynchronous processing.
    | Requires queue configuration in your Laravel application.
    |
    */
    'queue_events' => env('DISCOUNT_QUEUE_EVENTS', false),
];
