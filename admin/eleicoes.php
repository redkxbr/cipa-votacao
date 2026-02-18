<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();
$errors = [];

$gerencias = $pdo->query("SELECT DISTINCT gerencia FROM eleitores_autorizados WHERE gerencia <> '' ORDER BY gerencia")->fetchAll(PDO::FETCH_COLUMN);
$supervisoes = $pdo->query("SELECT DISTINCT supervisao FROM eleitores_autorizados WHERE supervisao <> '' ORDER BY supervisao")->fetchAll(PDO::FETCH_COLUMN);
$nomeCcs = $pdo->query("SELECT DISTINCT nome_cc FROM eleitores_autorizados WHERE nome_cc <> '' ORDER BY nome_cc")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
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
      $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $nome)) ?? 'eleicao', '-'));
      if ($baseSlug === '') $baseSlug = 'eleicao';
      $slug = $baseSlug;
      $i = 1;
      while (true) {
        $chk = $pdo->prepare('SELECT 1 FROM eleicoes WHERE slug = :s LIMIT 1');
        $chk->execute(['s' => $slug]);
        if (!$chk->fetch()) break;
        $i++;
        $slug = $baseSlug . '-' . $i;
      }

      $ins = $pdo->prepare('INSERT INTO eleicoes (nome, slug, descricao, justificativa_negacao, periodo_inicio, periodo_fim) VALUES (:n,:s,:d,:j,:i,:f)');
      $ins->execute([
        'n' => $nome,
        's' => $slug,
        'd' => $descricao,
        'j' => $justificativa,
        'i' => str_replace('T', ' ', $inicio) . ':00',
        'f' => str_replace('T', ' ', $fim) . ':00',
      ]);
      $eleicaoId = (int)$pdo->lastInsertId();

      $permStmt = $pdo->prepare('INSERT IGNORE INTO eleicao_permissoes (eleicao_id, tipo, valor) VALUES (:e,:t,:v)');
      foreach ($selGerencias as $v) $permStmt->execute(['e' => $eleicaoId, 't' => 'gerencia', 'v' => $v]);
      foreach ($selSupervisoes as $v) $permStmt->execute(['e' => $eleicaoId, 't' => 'supervisao', 'v' => $v]);
      foreach ($selNomecc as $v) $permStmt->execute(['e' => $eleicaoId, 't' => 'nome_cc', 'v' => $v]);

      $pdo->commit();
      flash('success', 'Eleição criada com sucesso.');
      redirect(url('admin/eleicoes.php'));
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = 'Erro ao criar eleição: ' . $e->getMessage();
    }
  }
}

$list = $pdo->query('SELECT e.*, (SELECT COUNT(*) FROM votos v WHERE v.eleicao_id = e.id) total_votos FROM eleicoes e ORDER BY e.created_at DESC')->fetchAll();

$pageTitle = 'Gerenciar Eleições';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Eleições</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>">Voltar</a>
</div>

<div class="card p-3 mb-3">
  <h2 class="h5">Criar nova eleição</h2>
  <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
  <form method="post" class="row g-3">
    <input type="hidden" name="action" value="create">
    <div class="col-md-6"><label class="form-label">Nome da eleição</label><input class="form-control" name="nome" placeholder="Eleição CIPA Friato 2026" required></div>
    <div class="col-md-3"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="periodo_inicio" required></div>
    <div class="col-md-3"><label class="form-label">Fim</label><input class="form-control" type="datetime-local" name="periodo_fim" required></div>
    <div class="col-12"><label class="form-label">Descrição/Texto público</label><textarea class="form-control" name="descricao" rows="2" placeholder="Eleição da CIPA 2026 da empresa Friato..."></textarea></div>
    <div class="col-12"><label class="form-label">Justificativa quando CPF não tiver permissão</label><textarea class="form-control" name="justificativa_negacao" rows="2" placeholder="Você não faz parte dos grupos habilitados nesta eleição."></textarea></div>

    <div class="col-md-4">
      <label class="form-label">Gerência permitida</label>
      <select class="form-select" name="gerencia[]" multiple size="6">
        <?php foreach($gerencias as $g): ?><option value="<?= e($g) ?>"><?= e($g) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Supervisão permitida</label>
      <select class="form-select" name="supervisao[]" multiple size="6">
        <?php foreach($supervisoes as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Nome CC permitido</label>
      <select class="form-select" name="nome_cc[]" multiple size="6">
        <?php foreach($nomeCcs as $n): ?><option value="<?= e($n) ?>"><?= e($n) ?></option><?php endforeach; ?>
      </select>
    </div>

    <div class="col-12"><button class="btn btn-friato" type="submit">Criar eleição</button></div>
  </form>
</div>

<div class="card p-3 table-responsive">
  <h2 class="h5">Eleições criadas</h2>
  <table class="table table-hover align-middle">
    <thead><tr><th>Nome</th><th>Período</th><th>Votos</th><th>Link de votação</th></tr></thead>
    <tbody>
      <?php foreach($list as $e): ?>
      <tr>
        <td><strong><?= e($e['nome']) ?></strong><br><small class="text-muted"><?= e($e['descricao'] ?? '') ?></small></td>
        <td><?= e($e['periodo_inicio']) ?><br><?= e($e['periodo_fim']) ?></td>
        <td><?= (int)$e['total_votos'] ?></td>
        <td>
          <?php $link = url('public/votar.php?eleicao=' . urlencode($e['slug'])); ?>
          <input class="form-control form-control-sm" value="<?= e($link) ?>" readonly onclick="this.select();">
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
