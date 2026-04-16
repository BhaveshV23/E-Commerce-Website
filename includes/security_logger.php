<?php
declare(strict_types=1);

/**
 * Lightweight file-based security logger.
 *
 * Teaching note:
 * Logs are written as JSON lines so they are readable by humans and easy for
 * scripts to parse. Never log raw passwords, payment data, or session IDs.
 */

const SECURITY_LOG_PATH = __DIR__ . '/../logs/security.log';

function clientIpAddress(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function currentRequestPath(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

    return substr($uri, 0, 500);
}

function sanitizeLogValue(string $value, int $maxLength = 300): string
{
    $value = str_replace(["\r", "\n"], [' ', ' '], $value);

    return substr($value, 0, $maxLength);
}

function writeSecurityLog(string $eventType, array $context = []): void
{
    $logDirectory = dirname(SECURITY_LOG_PATH);

    if (!is_dir($logDirectory)) {
        mkdir($logDirectory, 0755, true);
    }

    $entry = [
        'timestamp' => gmdate('c'),
        'event_type' => $eventType,
        'ip' => clientIpAddress(),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'path' => currentRequestPath(),
        'user_agent' => sanitizeLogValue((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')),
        'context' => $context,
    ];

    $encoded = json_encode($entry, JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        return;
    }

    file_put_contents(SECURITY_LOG_PATH, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function detectSuspiciousValue(string $value): ?string
{
    $patterns = [
        'sql_injection' => '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdrop\b|\bdelete\b|--|#|\/\*|\*\/|;|\bor\b\s+1\s*=\s*1)/i',
        'xss_attempt' => '/(<script|<\/script|javascript:|onerror\s*=|onload\s*=|<iframe|<svg)/i',
        'path_traversal' => '/(\.\.\/|\.\.\\\\|%2e%2e%2f|%2e%2e\\\\)/i',
        'command_injection' => '/(\|\||&&|`|\$\(|\bcat\s+\/etc\/passwd\b|\bcurl\s+|\bwget\s+)/i',
    ];

    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $value)) {
            return $label;
        }
    }

    return null;
}

function logSuspiciousRequestParameters(): void
{
    foreach ($_GET as $key => $value) {
        $flatValue = is_array($value) ? json_encode($value) : (string) $value;
        $flatValue = $flatValue === false ? '' : $flatValue;
        $combined = (string) $key . '=' . $flatValue;
        $reason = detectSuspiciousValue($combined);

        if ($reason !== null) {
            writeSecurityLog('suspicious_url_parameter', [
                'reason' => $reason,
                'parameter' => sanitizeLogValue((string) $key, 80),
                'value_preview' => sanitizeLogValue($flatValue),
            ]);
        }
    }
}
