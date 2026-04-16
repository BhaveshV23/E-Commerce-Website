<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';

/**
 * Verify Google reCAPTCHA v2.
 *
 * Teaching note:
 * Browser-side reCAPTCHA is only a widget. The server must verify the submitted
 * token with Google before trusting the form attempt.
 */
function verifyRecaptcha(?string $token): bool
{
    if (RECAPTCHA_SECRET_KEY === 'YOUR_RECAPTCHA_V2_SECRET_KEY') {
        // Development mode: replace keys in config/security.php to enforce checks.
        return true;
    }

    if (!is_string($token) || $token === '') {
        return false;
    }

    $postData = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 5,
        ],
    ]);

    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

    if ($response === false) {
        error_log('reCAPTCHA verification request failed.');
        return false;
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) && !empty($decoded['success']);
}
