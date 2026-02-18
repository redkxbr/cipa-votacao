<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();

$winner = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sortear') {
  $stmt = $pdo->query('SELECT v.codigo_sorteio, v.eleitor_nome, v.eleitor_cpf, v.eleitor_empresa, v.eleitor_turno, v.eleitor_setor, c.nome candidato_nome
    FROM votos v
    INNER JOIN candidatos c ON c.id = v.candidato_id
    ORDER BY RAND() LIMIT 1');
  $winner = $stmt->fetch();
  if (!$winner) {
    flash('warning', 'Ainda não há votos para realizar sorteio.');
    redirect(url('admin/sorteio.php'));
  }
}

$pageTitle = 'Sorteio';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Sorteio de prêmio</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>">Voltar</a>
</div>

<div class="card p-3 mb-3">
  <h2 class="h5">Sortear eleitor com base no código dos votantes</h2>
  <form method="post">
    <input type="hidden" name="action" value="sortear">
    <button class="btn btn-friato" type="submit">Realizar sorteio agora</button>
  </form>
</div>

<?php if ($winner): ?>
<div class="card p-4">
  <h2 class="h4 text-danger">Resultado do sorteio</h2>
  <p class="mb-1"><strong>Código sorteado:</strong> <span class="badge text-bg-dark"><?= e($winner['codigo_sorteio']) ?></span></p>
  <p class="mb-1"><strong>Eleitor:</strong> <?= e($winner['eleitor_nome']) ?> (<?= e(formatCpf($winner['eleitor_cpf'])) ?>)</p>
  <p class="mb-1"><strong>Empresa:</strong> <?= e($winner['eleitor_empresa']) ?></p>
  <p class="mb-1"><strong>Turno:</strong> <?= e($winner['eleitor_turno']) ?></p>
  <p class="mb-1"><strong>Setor:</strong> <?= e($winner['eleitor_setor']) ?></p>
  <p class="mb-0"><strong>Voto em:</strong> <?= e($winner['candidato_nome']) ?></p>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
