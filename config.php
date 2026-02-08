<?php
// Load .env file
$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die('Error: .env file not found. Please create .env file with Brevo credentials.');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (strpos($line, '=') === false || strpos($line, '#') === 0) {
        continue;
    }
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    
    define($key, $value);
}

// Validate required constants
$required = ['BREVO_SMTP_HOST', 'BREVO_SMTP_PORT', 'BREVO_SMTP_USERNAME', 'BREVO_SMTP_PASSWORD', 'FROM_MAIL', 'FROM_NAME', 'TO_MAIL'];
foreach ($required as $const) {
    if (!defined($const)) {
        die("Error: Missing configuration $const in .env file");
    }
}