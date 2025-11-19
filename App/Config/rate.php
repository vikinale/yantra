<?php
return [
    // default limit rules
    // identifier_type => [limit, window_seconds, block_seconds_on_threshold]
    // examples: user_id, ip, api_key
    'rules' => [
        'ip' => ['limit' => 100, 'window' => 60, 'block' => 300],        // 100 req/min -> 5m block
        'user' => ['limit' => 500, 'window' => 60, 'block' => 600],      // 500 req/min -> 10m block
        'api_key' => ['limit' => 1000, 'window' => 60, 'block' => 3600], // 1000 req/min -> 1h block
        'login_attempts' => ['limit' => 5, 'window' => 300, 'block' => 900] // 5 attempts /5min -> 15m block
    ],
    // backend: 'redis' or 'db'
    'backend' => getenv('RATE_BACKEND') ?: 'redis',
    // redis connection for RedisRateLimiter
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'auth' => getenv('REDIS_PASS') ?: null,
        'timeout' => 2.5
    ],
    // admin email to notify on repeated abuse, optional
    'admin_notify' => getenv('ADMIN_NOTIFY_EMAIL') ?: null,
];
