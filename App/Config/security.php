<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Token Configuration
    |--------------------------------------------------------------------------
    |
    | JWT token settings used by TokenManager, authentication, API sessions, etc.
    | Supported algorithms:
    |   - HS256 (HMAC)
    |   - RS256 (Public/Private Key)
    |   - ES256 (Elliptic Curve)
    |
    */

    // HS256 secret (used only if algo = HS256)
    'token_secret' => getenv('YANTRA_JWT_SECRET') ?: 'your_256bit_secret_key_here',

    // JWT Algorithm (HS256 or RS256)
    'token_algo' => getenv('YANTRA_JWT_ALGO') ?: 'RS256',

    // Private key for RS256 (absolute path or direct string)
    'private_key' => BASEPATH . '/App/Keys/jwt_private.pem',

    // Public key for RS256
    'public_key'  => BASEPATH . '/App/Keys/jwt_public.pem',

    // Token expiration (seconds)
    'token_exp' => 3600, // 1 hour

    // Token issuer
    'issuer' => 'yantra.local',

    // Refresh token expiration
    'refresh_exp' => 604800, // 7 days



    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    'csrf' => [
        'enabled'   => true,
        'token_key' => '_csrf_token',
        'lifetime'  => 7200, // 2 hours
    ],



    /*
    |--------------------------------------------------------------------------
    | Password Hashing
    |--------------------------------------------------------------------------
    */

    'password' => [
        'algo' => PASSWORD_DEFAULT,
        'options' => [
            'cost' => 12,
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */

    'session' => [
        'secure'   => true,  // only over HTTPS
        'httponly' => true,  // JS cannot access cookies
        'same_site' => 'Strict',
    ],

];
