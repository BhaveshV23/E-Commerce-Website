<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/newsletter.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$redirect = (string) ($_POST['redirect'] ?? 'index.php#newsletter');
$allowedRedirects = [
    'index.php#newsletter',
    'catalog.php#newsletter',
    'cart.php#newsletter',
];

if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'index.php#newsletter';
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    newsletterFlash('Security check failed. Please try again.', 'error');
    header('Location: ' . $redirect);
    exit;
}

$email = strtolower(cleanText((string) ($_POST['email'] ?? '')));
$emailError = validateEmailAddress($email);

if ($emailError !== null) {
    newsletterFlash($emailError, 'error');
    header('Location: ' . $redirect);
    exit;
}

$subscribed = subscribeWithMailchimp($email);

if ($subscribed) {
    newsletterFlash('Thanks. Check your inbox to confirm your subscription.');
} else {
    queueNewsletterSignup($pdo, $email);
    newsletterFlash('Thanks. Your email has been saved for our newsletter queue.');
}

header('Location: ' . $redirect);
exit;
