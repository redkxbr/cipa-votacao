<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
requireAdminLogin();

$pdo = getPDO();
$totalVotes = (int) $pdo->query('SELECT COUNT(*) FROM votos')->fetchColumn();
$totalCandidates = (int) $pdo->query('SELECT COUNT(*) FROM candidatos')->fetchColumn();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Painel Administrativo</h2>
    <p>Bem-vindo, <strong><?= e($_SESSION['admin_username'] ?? 'admin') ?></strong>.</p>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total de votos</h3>
            <p><?= $totalVotes ?></p>
        </div>
        <div class="stat-card">
            <h3>Total de candidatos</h3>
            <p><?= $totalCandidates ?></p>
        </div>
    </div>

    <div class="admin-links">
        <a class="btn btn-primary" href="<?= e(url('admin/candidatos.php')) ?>">Gerenciar candidatos</a>
        <a class="btn btn-primary" href="<?= e(url('admin/votos.php')) ?>">Relatórios de votos</a>
        <a class="btn" href="<?= e(url('admin/logout.php')) ?>">Sair</a>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
