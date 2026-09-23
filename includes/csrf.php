<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): void
{
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(false, 'Your form expired. Please try again.', null, 419);
    }
}