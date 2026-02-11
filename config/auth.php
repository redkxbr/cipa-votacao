<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/functions.php';

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

function adminLogin(string $username, string $password): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    return true;
}

function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Faça login para acessar a área administrativa.');
        redirect(url('admin/login.php'));
    }
}

function adminLogout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
    session_regenerate_id(true);
}
