<?php
return [
    'driver' => getenv('MAIL_DRIVER') ?: 'smtp',
    'from_email' => 'no-reply@yourapp.com',
    'from_name' => 'YourApp',
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.mailtrap.io',
        'port' => getenv('SMTP_PORT') ?: 587,
        'username' => getenv('SMTP_USER') ?: null,
        'password' => getenv('SMTP_PASS') ?: null,
        'smtp_secure' => getenv('SMTP_ENCRYPTION') ?: 'tls',
        'auth' => true,
    ],
    'sendgrid' => [
        'api_key' => getenv('SENDGRID_API_KEY') ?: null,
    ],
    'mailgun' => [
        'api_key' => getenv('MAILGUN_API_KEY') ?: null,
        'domain'  => getenv('MAILGUN_DOMAIN') ?: null,
    ],
];