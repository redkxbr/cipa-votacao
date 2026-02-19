<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();
$errors = [];

$id = ctype_digit((string)($_GET['id'] ?? '')) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  flash('warning', 'Eleição inválida.');
  redirect(url('admin/eleicoes.php'));
}

$gerencias = $pdo->query("SELECT DISTINCT gerencia FROM eleitores_autorizados WHERE gerencia <> '' ORDER BY gerencia")->fetchAll(PDO::FETCH_COLUMN);
$supervisoes = $pdo->query("SELECT DISTINCT supervisao FROM eleitores_autorizados WHERE supervisao <> '' ORDER BY supervisao")->fetchAll(PDO::FETCH_COLUMN);
$nomeCcs = $pdo->query("SELECT DISTINCT nome_cc FROM eleitores_autorizados WHERE nome_cc <> '' ORDER BY nome_cc")->fetchAll(PDO::FETCH_COLUMN);

$eStmt = $pdo->prepare('SELECT * FROM eleicoes WHERE id = :id LIMIT 1');
$eStmt->execute(['id' => $id]);
$eleicao = $eStmt->fetch();
if (!$eleicao) {
  flash('warning', 'Eleição não encontrada.');
  redirect(url('admin/eleicoes.php'));
}

$pStmt = $pdo->prepare('SELECT tipo, valor FROM eleicao_permissoes WHERE eleicao_id = :id');
$pStmt->execute(['id' => $id]);
$selected = ['gerencia' => [], 'supervisao' => [], 'nome_cc' => []];
foreach ($pStmt->fetchAll() as $p) {
  $selected[$p['tipo']][] = $p['valor'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim((string)($_POST['nome'] ?? ''));
  $descricao = trim((string)($_POST['descricao'] ?? ''));
  $justificativa = trim((string)($_POST['justificativa_negacao'] ?? ''));
  $inicio = trim((string)($_POST['periodo_inicio'] ?? ''));
  $fim = trim((string)($_POST['periodo_fim'] ?? ''));
  $selGerencias = array_values(array_filter(array_map('trim', (array)($_POST['gerencia'] ?? []))));
  $selSupervisoes = array_values(array_filter(array_map('trim', (array)($_POST['supervisao'] ?? []))));
  $selNomecc = array_values(array_filter(array_map('trim', (array)($_POST['nome_cc'] ?? []))));

  if ($nome === '') $errors[] = 'Nome da eleição é obrigatório.';
  if ($inicio === '' || $fim === '') $errors[] = 'Período de votação é obrigatório.';
  if ($inicio !== '' && $fim !== '' && strtotime($inicio) >= strtotime($fim)) $errors[] = 'Período inválido.';

  if (!$errors) {
    try {
      $pdo->beginTransaction();
      $up = $pdo->prepare('UPDATE eleicoes SET nome = :n, descricao = :d, justificativa_negacao = :j, periodo_inicio = :i, periodo_fim = :f WHERE id = :id');
      $up->execute([
        'n' => $nome,
        'd' => $descricao,
        'j' => $justificativa,
        'i' => str_replace('T', ' ', $inicio) . ':00',
        'f' => str_replace('T', ' ', $fim) . ':00',
        'id' => $id,
      ]);

      $pdo->prepare('DELETE FROM eleicao_permissoes WHERE eleicao_id = :id')->execute(['id' => $id]);
      $permStmt = $pdo->prepare('INSERT IGNORE INTO eleicao_permissoes (eleicao_id, tipo, valor) VALUES (:e,:t,:v)');
      foreach ($selGerencias as $v) $permStmt->execute(['e' => $id, 't' => 'gerencia', 'v' => $v]);
      foreach ($selSupervisoes as $v) $permStmt->execute(['e' => $id, 't' => 'supervisao', 'v' => $v]);
      foreach ($selNomecc as $v) $permStmt->execute(['e' => $id, 't' => 'nome_cc', 'v' => $v]);

      $pdo->commit();
      flash('success', 'Eleição atualizada com sucesso.');
      redirect(url('admin/eleicoes.php'));
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = 'Erro ao atualizar eleição: ' . $e->getMessage();
    }
  }

  $selected = ['gerencia' => $selGerencias, 'supervisao' => $selSupervisoes, 'nome_cc' => $selNomecc];
  $eleicao['nome'] = $nome;
  $eleicao['descricao'] = $descricao;
  $eleicao['justificativa_negacao'] = $justificativa;
  $eleicao['periodo_inicio'] = str_replace('T', ' ', $inicio) . ':00';
  $eleicao['periodo_fim'] = str_replace('T', ' ', $fim) . ':00';
}

$pageTitle = 'Editar Eleição';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Editar eleição</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/eleicoes.php')) ?>">Voltar</a>
</div>

<div class="card p-3">
  <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
  <form method="post" class="row g-3">
    <div class="col-md-6"><label class="form-label">Nome da eleição</label><input class="form-control" name="nome" value="<?= e($eleicao['nome']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="periodo_inicio" value="<?= e(date('Y-m-d\TH:i', strtotime($eleicao['periodo_inicio']))) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Fim</label><input class="form-control" type="datetime-local" name="periodo_fim" value="<?= e(date('Y-m-d\TH:i', strtotime($eleicao['periodo_fim']))) ?>" required></div>
    <div class="col-12"><label class="form-label">Descrição/Texto público</label><textarea class="form-control" name="descricao" rows="2"><?= e($eleicao['descricao'] ?? '') ?></textarea></div>
    <div class="col-12"><label class="form-label">Justificativa quando CPF não tiver permissão</label><textarea class="form-control" name="justificativa_negacao" rows="2"><?= e($eleicao['justificativa_negacao'] ?? '') ?></textarea></div>

    <div class="col-md-4">
      <label class="form-label">Gerência permitida</label>
      <select class="form-select" name="gerencia[]" multiple size="7">
        <?php foreach($gerencias as $g): ?><option value="<?= e($g) ?>" <?= in_array($g, $selected['gerencia'], true) ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Supervisão permitida</label>
      <select class="form-select" name="supervisao[]" multiple size="7">
        <?php foreach($supervisoes as $s): ?><option value="<?= e($s) ?>" <?= in_array($s, $selected['supervisao'], true) ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Nome CC permitido</label>
      <select class="form-select" name="nome_cc[]" multiple size="7">
        <?php foreach($nomeCcs as $n): ?><option value="<?= e($n) ?>" <?= in_array($n, $selected['nome_cc'], true) ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 d-flex gap-2">
      <button class="btn btn-friato" type="submit">Salvar alterações</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('admin/eleicoes.php')) ?>">Cancelar</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
