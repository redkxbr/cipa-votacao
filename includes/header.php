<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Votação CIPA - Friato';
$flash = pullFlash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= e(url('public/assets/css/style.css')) ?>" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg friato-navbar py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('public/index.php')) ?>">
      <?= logoOrPlaceholder('logo-friato.png', 'Logo Friato', 'logo-sm') ?>
      <span class="fw-bold text-white">CIPA Friato</span>
    </a>
    <div class="d-flex gap-2 ms-auto">
      <a class="btn btn-light btn-sm" href="<?= e(url('public/index.php')) ?>">Início</a>
      <a class="btn btn-warning btn-sm" href="<?= e(url('public/votar.php')) ?>">Votar</a>
      <a class="btn btn-outline-light btn-sm" href="<?= e(url('admin/login.php')) ?>">Admin</a>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php if ($flash): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({icon:'<?= e($flash['type']) ?>',text:'<?= e($flash['message']) ?>',confirmButtonColor:'#DA291C'});
  });
</script>
<?php endif; ?>
