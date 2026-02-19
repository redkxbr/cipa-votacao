<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();

$id = ctype_digit((string)($_GET['id'] ?? $_POST['id'] ?? '')) ? (int)($_GET['id'] ?? $_POST['id']) : 0;
if ($id <= 0) {
  flash('warning', 'Eleição inválida.');
  redirect(url('admin/eleicoes.php'));
}

$stmt = $pdo->prepare('SELECT * FROM eleicoes WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$eleicao = $stmt->fetch();
if (!$eleicao) {
  flash('warning', 'Eleição não encontrada.');
  redirect(url('admin/eleicoes.php'));
}

$totalVotosStmt = $pdo->prepare('SELECT COUNT(*) FROM votos WHERE eleicao_id = :id');
$totalVotosStmt->execute(['id' => $id]);
$totalVotos = (int)$totalVotosStmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === '1') {
  if ($totalVotos > 0) {
    flash('error', 'Não é possível excluir eleição com votos registrados.');
    redirect(url('admin/eleicoes.php'));
  }

  try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM eleicao_permissoes WHERE eleicao_id = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM eleicoes WHERE id = :id')->execute(['id' => $id]);
    $pdo->commit();
    flash('success', 'Eleição excluída com sucesso.');
    redirect(url('admin/eleicoes.php'));
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Erro ao excluir eleição: ' . $e->getMessage());
    redirect(url('admin/eleicoes.php'));
  }
}

$pageTitle = 'Excluir Eleição';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0 text-danger">Excluir eleição</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/eleicoes.php')) ?>">Voltar</a>
</div>

<div class="card p-3">
  <p class="mb-2">Você está prestes a excluir:</p>
  <h2 class="h5"><?= e($eleicao['nome']) ?></h2>
  <p class="small text-muted">Período: <?= e($eleicao['periodo_inicio']) ?> até <?= e($eleicao['periodo_fim']) ?></p>
  <p><strong>Total de votos vinculados:</strong> <?= $totalVotos ?></p>

  <?php if ($totalVotos > 0): ?>
    <div class="alert alert-warning">Esta eleição possui votos e não pode ser excluída.</div>
  <?php else: ?>
    <div class="alert alert-danger">A ação é irreversível. Confirme para excluir definitivamente.</div>
    <form method="post" class="d-flex gap-2">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="confirm" value="1">
      <button class="btn btn-danger" type="submit">Confirmar exclusão</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('admin/eleicoes.php')) ?>">Cancelar</a>
    </form>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
