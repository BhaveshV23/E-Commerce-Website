<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

function currentUser(): ?array
{
    startSecureSession();

    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function loginUser(array $user): void
{
    startSecureSession();
    regenerateSession();

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function logoutUser(): void
{
    startSecureSession();
    destroySession();
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
