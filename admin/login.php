<?php
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLogged()) {
  redirect(url('admin/dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim((string)($_POST['username'] ?? ''));
  $pass = (string)($_POST['password'] ?? '');
  if ($user === '' || $pass === '') {
    flash('warning', 'Informe usuário e senha.');
  } elseif (!doAdminLogin($user, $pass)) {
    flash('error', 'Credenciais inválidas.');
  } else {
    flash('success', 'Bem-vindo ao painel administrativo.');
    redirect(url('admin/dashboard.php'));
  }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card hero-card p-4">
      <div class="text-center mb-3"><?= logoOrPlaceholder('logo-friato.png', 'Logo Friato', 'logo-hero mx-auto') ?></div>
      <h1 class="h4 text-center mb-3">Acesso Administrativo</h1>
      <form method="post" class="vstack gap-3">
        <input class="form-control" name="username" placeholder="Usuário" required>
        <input class="form-control" name="password" type="password" placeholder="Senha" required>
        <button class="btn btn-friato" type="submit">Entrar</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
