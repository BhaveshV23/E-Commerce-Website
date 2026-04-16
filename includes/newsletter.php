<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/mailchimp.php';

function mailchimpConfigured(): bool
{
    return MAILCHIMP_API_KEY !== 'REPLACE_WITH_MAILCHIMP_API_KEY'
        && MAILCHIMP_LIST_ID !== 'REPLACE_WITH_AUDIENCE_LIST_ID'
        && strpos(MAILCHIMP_API_KEY, '-') !== false;
}

function queueNewsletterSignup(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO newsletter_queue (email, status, source)
         VALUES (:email, :status, :source)
         ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            source = VALUES(source)'
    );
    $stmt->execute([
        ':email' => $email,
        ':status' => 'queued',
        ':source' => 'website',
    ]);
}

function subscribeWithMailchimp(string $email): bool
{
    if (!mailchimpConfigured()) {
        return false;
    }

    if (!function_exists('curl_init')) {
        error_log('Mailchimp signup skipped because PHP cURL is unavailable.');
        return false;
    }

    $parts = explode('-', MAILCHIMP_API_KEY);
    $dataCenter = end($parts);
    $subscriberHash = md5(strtolower($email));
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . rawurlencode(MAILCHIMP_LIST_ID) . '/members/' . $subscriberHash;

    $payload = json_encode([
        'email_address' => $email,
        'status_if_new' => MAILCHIMP_DOUBLE_OPT_IN ? 'pending' : 'subscribed',
        'status' => MAILCHIMP_DOUBLE_OPT_IN ? 'pending' : 'subscribed',
    ]);

    $ch = curl_init($url);

    if ($ch === false || $payload === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => 'unishop:' . MAILCHIMP_API_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);

    curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return $statusCode >= 200 && $statusCode < 300;
}

function newsletterFlash(string $message, string $type = 'success'): void
{
    $_SESSION['newsletter_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function consumeNewsletterFlash(): ?array
{
    if (empty($_SESSION['newsletter_flash']) || !is_array($_SESSION['newsletter_flash'])) {
        return null;
    }

    $flash = $_SESSION['newsletter_flash'];
    unset($_SESSION['newsletter_flash']);

    return $flash;
}
