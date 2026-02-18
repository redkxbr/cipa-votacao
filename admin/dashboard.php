<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = pdo();
$totalVotes = (int)$pdo->query('SELECT COUNT(*) FROM votos')->fetchColumn();
$activeCandidates = (int)$pdo->query('SELECT COUNT(*) FROM candidatos WHERE ativo=1')->fetchColumn();
$lastVote = $pdo->query('SELECT created_at FROM votos ORDER BY id DESC LIMIT 1')->fetchColumn();

$rows = $pdo->query('SELECT c.nome, COUNT(v.id) total FROM candidatos c LEFT JOIN votos v ON v.candidato_id=c.id GROUP BY c.id,c.nome ORDER BY total DESC')->fetchAll();
$labels = array_column($rows, 'nome');
$values = array_map('intval', array_column($rows, 'total'));

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Dashboard</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/candidatos.php')) ?>">Candidatos</a>
    <a class="btn btn-outline-secondary" href="<?= e(url('admin/votos.php')) ?>">Votos</a>
    <a class="btn btn-friato" href="<?= e(url('admin/logout.php')) ?>">Sair</a>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card kpi-card p-3"><small>Total votos</small><div class="h2 mb-0"><?= $totalVotes ?></div></div></div>
  <div class="col-md-4"><div class="card kpi-card p-3"><small>Candidatos ativos</small><div class="h2 mb-0"><?= $activeCandidates ?></div></div></div>
  <div class="col-md-4"><div class="card kpi-card p-3"><small>Último voto</small><div class="h6 mb-0"><?= e($lastVote ?: 'Sem votos') ?></div></div></div>
</div>
<div class="card p-3">
  <h2 class="h5">Votos por candidato</h2>
  <canvas id="chartVotes" height="100"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartVotes'), {
  type: 'bar',
  data: { labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>, datasets: [{ label: 'Votos', data: <?= json_encode($values) ?>, backgroundColor: '#DA291C' }] },
  options: { plugins: { legend: { display: false } } }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
