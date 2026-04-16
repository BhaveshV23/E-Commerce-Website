<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';

/**
 * Start a hardened session.
 *
 * Teaching note:
 * - httponly helps prevent JavaScript from reading the cookie.
 * - samesite=Lax reduces cross-site request risk while keeping normal links usable.
 * - secure should be true on HTTPS. Local HTTP development keeps it false.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    }

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT_SECONDS) {
        destroySession();
        session_start();
    }

    $_SESSION['last_activity'] = time();
}

function regenerateSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    }
}

function destroySession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ]);

    session_destroy();
}
