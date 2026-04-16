<?php
declare(strict_types=1);

function cleanText(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value) ?? '';

    return $value;
}

function validateName(string $name): ?string
{
    if ($name === '') {
        return 'Name is required.';
    }

    if (strlen($name) < 2 || strlen($name) > 120) {
        return 'Name must be between 2 and 120 characters.';
    }

    return null;
}

function validateEmailAddress(string $email): ?string
{
    if ($email === '') {
        return 'Email is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address.';
    }

    if (strlen($email) > 180) {
        return 'Email must be 180 characters or fewer.';
    }

    return null;
}

function validatePasswordStrength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must include uppercase, lowercase, and number characters.';
    }

    return null;
}
