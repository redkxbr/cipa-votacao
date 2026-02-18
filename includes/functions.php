<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function validateCPF(string $cpf): bool
{
    $cpf = digits($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $d = ((10 * $sum) % 11) % 10;
        if ((int) $cpf[$t] !== $d) {
            return false;
        }
    }
    return true;
}

function validatePhone(string $phone): bool
{
    $len = strlen(digits($phone));
    return $len >= 10 && $len <= 11;
}

function maskCpfPublic(string $cpf): string
{
    return '***.***.***-**';
}

function formatCpf(string $cpf): string
{
    $cpf = str_pad(digits($cpf), 11, '0', STR_PAD_LEFT);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function isAdminLogged(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdminLogged()) {
        flash('warning', 'Faça login para acessar o painel.');
        redirect(url('admin/login.php'));
    }
}

function doAdminLogin(string $username, string $password): bool
{
    $stmt = pdo()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
    $stmt->execute(['u' => $username]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    return true;
}

function uploadCandidatePhoto(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload da imagem.');
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('A imagem deve ter no máximo 2MB.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato inválido. Use JPG ou PNG.');
    }

    $dir = __DIR__ . '/../public/uploads/candidatos';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $name = 'cand_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return $name;
}

function uniqueLotteryCode(PDO $pdo): string
{
    for ($i = 0; $i < 40; $i++) {
        $code = (string) random_int(10000, 99999);
        $q = $pdo->prepare('SELECT 1 FROM votos WHERE codigo_sorteio = :c LIMIT 1');
        $q->execute(['c' => $code]);
        if (!$q->fetch()) {
            return $code;
        }
    }
    throw new RuntimeException('Não foi possível gerar código único.');
}

function logoOrPlaceholder(string $path, string $label, string $class = 'logo'): string
{
    $file = basename($path);
    $full = __DIR__ . '/../public/assets/img/' . $file;

    $isRealImage = is_file($full) && @getimagesize($full) !== false;
    if ($isRealImage) {
        return '<img class="' . e($class) . '" src="' . e(url('public/assets/img/' . $file)) . '" alt="' . e($label) . '">';
    }

    return '<div class="logo-placeholder ' . e($class) . '">' . e($label) . '</div>';
}
