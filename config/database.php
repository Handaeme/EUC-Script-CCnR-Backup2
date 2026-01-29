<?php
return [
    'host' => getenv('DB_HOST') ?: 'LAPTOP-T9BEF7E1\SQLEXPRESS',
    'dbname' => getenv('DB_NAME') ?: 'CITRA',
    'user' => getenv('DB_USER') ?: '', // Leave empty for Windows Authentication
    'pass' => getenv('DB_PASS') ?: '',
    'options' => [
        "Database" => "CITRA",
        "CharacterSet" => "UTF-8",
        // "UID" => "sa", "PWD" => "your_password" // Uncomment if using SQL Auth
    ]
];
