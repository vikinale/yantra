<?php
return [
    'name'      => 'Yantra',
    'site'      => 'yantra',
    'base_url'  => 'http://localhost/yantra',
    'content'     => 'content',
    'theme'     => 'camping',
    'plugins'   => ['AngleSmartApi'],
    'language'  => 'en',
    'timezone'  => 'UTC',
    'charset'   => 'UTF-8',
    'default_controller' => 'Home',
    'default_method'     => 'index',
    'ai'        => [
        'api_key' => 'your_yantra_ai_api_key_here',
        'model'   => 'gpt-4.1-mini'
    ],
    'proxyIPs'  => [],
    'email'     => [
        'from_email' => 'no-reply@pawnacamping.com', // Default sender email
        'from_name' => 'Pawna Camping', // Default sender name
        'userAgent'=>'Yantra',
        'protocol'=>'mail',
        'mailPath'=>'/usr/sbin/sendmail',
        'SMTPUser'=>'',
        'SMTPPass'=>'',
        'SMTPPort'=>25,
        'SMTPTimeout'=>5,
        'SMTPKeepAlive '=>false,
        'SMTPCrypto'=>'tls',
        'wordWrap'=>true,
        'wrapChars '=>76,
        'mailType'=>'text',
        'charset'=>'UTF-8',
        'validate'=>false,
        'priority'=>3,
        'CRLF'=>'\r\n',
        'newline'=>'\r\n',
        'BCCBatchMode'=>false,
        'BCCBatchSize'=>200,
        'DSN'=>false
    ]
];