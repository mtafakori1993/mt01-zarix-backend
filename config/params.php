<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'ghasedak' => [
        'apiKey' => $_ENV['GHASEDAK_API_KEY'] ?? getenv('GHASEDAK_API_KEY') ?: '',
        'baseUrl' => $_ENV['GHASEDAK_BASE_URL'] ?? getenv('GHASEDAK_BASE_URL') ?: 'https://gateway.ghasedak.me/rest/api/v1/webservice',
        'templateName' => $_ENV['GHASEDAK_TEMPLATE_NAME'] ?? getenv('GHASEDAK_TEMPLATE_NAME') ?: 'Ghasedak',
    ],
    'otp' => [
        'length' => (int) ($_ENV['OTP_LENGTH'] ?? getenv('OTP_LENGTH') ?: 4),
        'ttl' => (int) ($_ENV['OTP_TTL'] ?? getenv('OTP_TTL') ?: 120),
    ],
];
