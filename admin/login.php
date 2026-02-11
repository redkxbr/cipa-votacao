<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

if (isAdminLoggedIn()) {
    redirect(url('admin/dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        setFlash('error', 'Informe usuário e senha.');
    } elseif (!adminLogin($username, $password)) {
        setFlash('error', 'Credenciais inválidas.');
    } else {
        setFlash('success', 'Login efetuado com sucesso.');
        redirect(url('admin/dashboard.php'));
    }
}

$pageTitle = 'Login Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card auth-card">
    <h2>Admin - Login</h2>
    <form method="post" class="vote-form">
        <label>Usuário
            <input type="text" name="username" required>
        </label>
        <label>Senha
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Entrar</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
