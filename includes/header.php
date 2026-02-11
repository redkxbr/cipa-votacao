<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Votação CIPA - Friato';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('public/assets/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1>CIPA Friato</h1>
        <nav>
            <a href="<?= e(url('public/index.php')) ?>">Início</a>
            <a href="<?= e(url('public/votar.php')) ?>">Votar</a>
            <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
        </nav>
    </div>
</header>
<main class="container">
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
