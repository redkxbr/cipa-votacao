<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = '/' . ltrim($path, '/');
    return ($base === '' ? '' : $base) . $path;
}
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function onlyDigits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function validarCPF(string $cpf): bool
{
    $cpf = onlyDigits($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += (int) $cpf[$i] * (($t + 1) - $i);
        }

        $digit = ((10 * $sum) % 11) % 10;
        if ((int) $cpf[$t] !== $digit) {
            return false;
        }
    }

    return true;
}

function validarTelefone(string $telefone): bool
{
    $telefone = onlyDigits($telefone);
    $len = strlen($telefone);
    return $len >= 10 && $len <= 11;
}

function formatCpfMaskPublic(string $cpf): string
{
    return '***.***.***-**';
}

function formatCpf(string $cpf): string
{
    $cpf = str_pad(onlyDigits($cpf), 11, '0', STR_PAD_LEFT);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function generateUniqueLotteryCode(PDO $pdo): string
{
    $attempts = 0;

    do {
        $attempts++;
        $codigo = str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM votos WHERE codigo_sorteio = :codigo');
        $stmt->execute(['codigo' => $codigo]);
        $exists = (int) $stmt->fetchColumn() > 0;
    } while ($exists && $attempts < 30);

    if ($exists) {
        throw new RuntimeException('Não foi possível gerar um código único de sorteio.');
    }

    return $codigo;
}

function saveCandidatePhoto(array $file, string $uploadDir, string $prefix = 'candidato'): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload da foto.');
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('A foto deve ter no máximo 2MB.');
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedMime[$mime])) {
        throw new RuntimeException('Formato de foto inválido. Use JPG ou PNG.');
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Não foi possível criar a pasta de upload.');
    }

    $extension = $allowedMime[$mime];
    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = rtrim($uploadDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Não foi possível salvar a foto enviada.');
    }

    return $filename;
}
