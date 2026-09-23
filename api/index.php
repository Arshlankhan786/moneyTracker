<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');

function action_input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function action_fail(string $message, int $status = 422): never
{
    global $isAjax;
    if (!$isAjax && in_array($_POST['action'] ?? '', ['login', 'register'], true)) {
        $_SESSION['auth_error'] = $message;
        redirect('../' . ($_POST['action'] === 'login' ? 'login.php' : 'register.php'));
    }
    json_response(false, $message, null, $status);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = require_auth();
        [$from, $to] = month_range($_GET['from'] ?? null, $_GET['to'] ?? null);
        if ($action === 'bootstrap') {
            json_response(true, 'MoneyTrack data loaded.', [
                'user' => current_user(),
                'categories' => user_categories($userId),
                'summary' => period_summary($userId, $from, $to),
                'balance' => all_time_balance($userId),
            ]);
        }
        if ($action === 'transactions') {
            $type = in_array($_GET['type'] ?? '', ['income', 'expense'], true) ? $_GET['type'] : null;
            $sql = 'SELECT t.id, t.type, t.amount, t.note, t.transaction_date, c.name category_name, c.icon, c.color FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.transaction_date BETWEEN ? AND ?';
            $params = [$userId, $from, $to];
            if ($type) { $sql .= ' AND t.type=?'; $params[] = $type; }
            $sql .= ' ORDER BY t.transaction_date DESC, t.created_at DESC LIMIT 200';
            $stmt = database()->prepare($sql); $stmt->execute($params);
            json_response(true, 'Activity loaded.', $stmt->fetchAll());
        }
        if ($action === 'insights') {
            $summary = period_summary($userId, $from, $to);
            $stmt = database()->prepare('SELECT c.id, c.name, c.icon, c.color, SUM(t.amount) total FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.type="expense" AND t.transaction_date BETWEEN ? AND ? GROUP BY c.id ORDER BY total DESC');
            $stmt->execute([$userId, $from, $to]);
            $expenses = $stmt->fetchAll();
            $stmt = database()->prepare('SELECT c.id, c.name, c.icon, c.color, SUM(t.amount) total FROM transactions t JOIN categories c ON c.id=t.category_id WHERE t.user_id=? AND t.type="income" AND t.transaction_date BETWEEN ? AND ? GROUP BY c.id ORDER BY total DESC');
            $stmt->execute([$userId, $from, $to]);
            json_response(true, 'Insights loaded.', ['summary' => $summary, 'expenses' => $expenses, 'income' => $stmt->fetchAll()]);
        }
        action_fail('Unknown data request.', 404);
    }

    verify_csrf($_POST['csrf_token'] ?? null);

    if ($action === 'register') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        if ($name === '' || mb_strlen($name) > 120) action_fail('Please enter your full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) action_fail('Please enter a valid email.');
        if (strlen($password) < 8) action_fail('Your password needs at least 8 characters.');
        if ($password !== (string)($_POST['password_confirmation'] ?? '')) action_fail('Passwords do not match.');
        $pdo = database();
        $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1'); $check->execute([$email]);
        if ($check->fetch()) action_fail('An account with that email already exists.');
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        session_regenerate_id(true); $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        if (!$isAjax) redirect('../dashboard.php');
        json_response(true, 'Account created.', ['redirect' => 'dashboard.php']);
    }

    if ($action === 'login') {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $stmt = database()->prepare('SELECT id, password FROM users WHERE email=? LIMIT 1'); $stmt->execute([$email]); $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password'])) action_fail('Email or password is incorrect.', 401);
        session_regenerate_id(true); $_SESSION['user_id'] = (int)$user['id'];
        if (!$isAjax) redirect('../dashboard.php');
        json_response(true, 'Welcome back.', ['redirect' => 'dashboard.php']);
    }

    $userId = require_auth();
    if ($action === 'create_category' || $action === 'update_category') {
        $name = trim((string)($_POST['name'] ?? '')); $type = (string)($_POST['type'] ?? '');
        $icon = trim((string)($_POST['icon'] ?? '💰')); $color = (string)($_POST['color'] ?? '#6366F1'); $id = (int)($_POST['id'] ?? 0);
        if ($name === '' || mb_strlen($name) > 80) action_fail('Please enter a category name.');
        if (!in_array($type, ['income', 'expense'], true)) action_fail('Choose a valid category type.');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) action_fail('Choose a valid category color.');
        if ($action === 'create_category') {
            $stmt = database()->prepare('INSERT INTO categories (user_id, name, type, icon, color) VALUES (?, ?, ?, ?, ?)');
            try { $stmt->execute([$userId, $name, $type, $icon, $color]); } catch (PDOException $exception) { if ($exception->getCode() === '23000') action_fail('You already have a category with that name.'); throw $exception; }
            json_response(true, $name . ' added ✓', ['id' => database()->lastInsertId()]);
        }
        $category = category_for_user($id, $userId); if (!$category) action_fail('That category no longer exists.', 404);
        $stmt = database()->prepare('UPDATE categories SET name=?, type=?, icon=?, color=? WHERE id=? AND user_id=?'); $stmt->execute([$name, $type, $icon, $color, $id, $userId]);
        json_response(true, 'Category updated.');
    }
    if ($action === 'archive_category') {
        $id = (int)($_POST['id'] ?? 0); if (!category_for_user($id, $userId)) action_fail('That category no longer exists.', 404);
        $stmt = database()->prepare('UPDATE categories SET is_active=0 WHERE id=? AND user_id=?'); $stmt->execute([$id, $userId]); json_response(true, 'Category archived.');
    }
    if (in_array($action, ['create_transaction', 'update_transaction'], true)) {
        $type = (string)($_POST['type'] ?? ''); $amount = (float)($_POST['amount'] ?? 0); $categoryId = (int)($_POST['category_id'] ?? 0); $date = (string)($_POST['transaction_date'] ?? ''); $note = trim((string)($_POST['note'] ?? '')); $id = (int)($_POST['id'] ?? 0);
        if (!in_array($type, ['income', 'expense'], true)) action_fail('Choose money in or money out.');
        if ($amount <= 0 || $amount > 9999999999) action_fail('Please enter an amount greater than zero.');
        if (!validate_date($date)) action_fail('Please choose a valid date.');
        $category = category_for_user($categoryId, $userId, $type); if (!$category || !$category['is_active']) action_fail('That category no longer exists.');
        if ($action === 'create_transaction') {
            $stmt = database()->prepare('INSERT INTO transactions (user_id, category_id, type, amount, note, transaction_date) VALUES (?, ?, ?, ?, ?, ?)'); $stmt->execute([$userId, $categoryId, $type, number_format($amount, 2, '.', ''), $note ?: null, $date]); json_response(true, ($type === 'income' ? '+' : '−') . rupees($amount) . ($type === 'income' ? ' added' : ' recorded') . ' successfully.');
        }
        $stmt = database()->prepare('UPDATE transactions SET category_id=?, type=?, amount=?, note=?, transaction_date=? WHERE id=? AND user_id=?'); $stmt->execute([$categoryId, $type, number_format($amount, 2, '.', ''), $note ?: null, $date, $id, $userId]); json_response(true, 'Transaction updated.');
    }
    if ($action === 'delete_transaction') {
        $id = (int)($_POST['id'] ?? 0); $stmt = database()->prepare('DELETE FROM transactions WHERE id=? AND user_id=?'); $stmt->execute([$id, $userId]); if (!$stmt->rowCount()) action_fail('That transaction no longer exists.', 404); json_response(true, 'Transaction deleted.');
    }
    if ($action === 'update_profile') {
        $name = trim((string)($_POST['name'] ?? '')); if ($name === '') action_fail('Please enter your name.'); $stmt = database()->prepare('UPDATE users SET name=? WHERE id=?'); $stmt->execute([$name, $userId]); json_response(true, 'Profile updated.');
    }
    if ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? ''); $new = (string)($_POST['new_password'] ?? ''); $stmt = database()->prepare('SELECT password FROM users WHERE id=?'); $stmt->execute([$userId]); $row = $stmt->fetch(); if (!$row || !password_verify($current, $row['password'])) action_fail('Your current password is incorrect.'); if (strlen($new) < 8) action_fail('Your new password needs at least 8 characters.'); $stmt = database()->prepare('UPDATE users SET password=? WHERE id=?'); $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $userId]); json_response(true, 'Password updated.');
    }
    action_fail('Unknown action.', 404);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    json_response(false, 'Something went wrong. Please try again.', null, 500);
}