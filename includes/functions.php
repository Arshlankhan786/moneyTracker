<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function json_response(bool $success, string $message, mixed $data = null, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_auth(): int
{
    $id = current_user_id();
    if (!$id) {
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            json_response(false, 'Your session has expired. Please login again.', null, 401);
        }
        redirect('login.php');
    }
    return $id;
}

function current_user(): ?array
{
    $id = current_user_id();
    if (!$id) return null;
    $stmt = database()->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function rupees(float|int|string $amount): string
{
    return '₹' . number_format((float) $amount, 2, '.', ',');
}

function readable_date(string $date): string
{
    return date('j F Y', strtotime($date));
}

function month_range(?string $from = null, ?string $to = null): array
{
    $today = new DateTimeImmutable('today');
    $fromDate = $from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : $today->modify('first day of this month')->format('Y-m-d');
    $toDate = $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : $today->modify('last day of this month')->format('Y-m-d');
    if ($fromDate > $toDate) [$fromDate, $toDate] = [$toDate, $fromDate];
    return [$fromDate, $toDate];
}

function validate_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function category_for_user(int $categoryId, int $userId, ?string $type = null): ?array
{
    $sql = 'SELECT id, name, type, icon, color, is_active FROM categories WHERE id = ? AND user_id = ?';
    $params = [$categoryId, $userId];
    if ($type) { $sql .= ' AND type = ?'; $params[] = $type; }
    $stmt = database()->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

function period_summary(int $userId, string $from, string $to): array
{
    $stmt = database()->prepare(
        'SELECT COALESCE(SUM(CASE WHEN type = "income" THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END), 0) AS expense
         FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?'
    );
    $stmt->execute([$userId, $from, $to]);
    $row = $stmt->fetch() ?: ['income' => 0, 'expense' => 0];
    $row['net'] = (float) $row['income'] - (float) $row['expense'];
    return array_map('floatval', $row);
}

function all_time_balance(int $userId): float
{
    $stmt = database()->prepare('SELECT COALESCE(SUM(CASE WHEN type = "income" THEN amount ELSE -amount END), 0) FROM transactions WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (float) $stmt->fetchColumn();
}

function user_categories(int $userId, ?string $type = null, bool $activeOnly = true): array
{
    $sql = 'SELECT id, name, type, icon, color, is_active, created_at FROM categories WHERE user_id = ?';
    $params = [$userId];
    if ($type) { $sql .= ' AND type = ?'; $params[] = $type; }
    if ($activeOnly) $sql .= ' AND is_active = 1';
    $stmt = database()->prepare($sql . ' ORDER BY type, name');
    $stmt->execute($params);
    return $stmt->fetchAll();
}