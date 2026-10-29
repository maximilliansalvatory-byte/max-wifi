<?php
/**
 * =====================================================
 * MAX WIFI - Security Helpers
 * =====================================================
 */

function setSecurityHeaders(): void
{
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

function rateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $now = time();
    $data = $_SESSION['rate_limit'][$key] ?? ['count' => 0, 'start' => $now];

    if (($now - $data['start']) > $decaySeconds) {
        $data = ['count' => 0, 'start' => $now];
    }

    $data['count']++;
    $_SESSION['rate_limit'][$key] = $data;

    return $data['count'] <= $maxAttempts;
}

function clearRateLimit(string $key): void
{
    if (isset($_SESSION['rate_limit'][$key])) {
        unset($_SESSION['rate_limit'][$key]);
    }
}