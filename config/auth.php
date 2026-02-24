<?php

use App\Models\User;
use App\Models\Bus;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [

        /*
         * Web authentication (Admin / Company / Employee)
         */
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        /*
         * API authentication (Bus devices via Sanctum)
         * This is what auth:sanctum uses
         */
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'buses',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        /*
         * Human users
         */
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],

        /*
         * Bus devices
         */
        'buses' => [
            'driver' => 'eloquent',
            'model' => Bus::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    */

    'passwords' => [

        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10800,
];
