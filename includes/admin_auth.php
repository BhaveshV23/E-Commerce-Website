<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Admin route guard.
 *
 * Teaching note:
 * Every privileged /admin page should call requireAdmin() before doing any
 * database work or rendering protected content.
 */
function requireAdmin(): void
{
    startSecureSession();

    $user = currentUser();

    if ($user === null || ($user['role'] ?? '') !== 'admin') {
        header('Location: ../public/login.php');
        exit;
    }
}

function currentAdmin(): array
{
    requireAdmin();

    return currentUser() ?? [];
}
