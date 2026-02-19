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

$hierRows = $pdo->query("SELECT DISTINCT gerencia, supervisao, nome_cc FROM eleitores_autorizados WHERE gerencia <> '' ORDER BY gerencia, supervisao, nome_cc")->fetchAll();
$allGerencias = array_values(array_unique(array_map(static fn($r) => (string)$r['gerencia'], $hierRows)));
sort($allGerencias);

function normalizeElectionPermissions(array $rows, array $gerencias, array $supervisoes, array $nomecc): array {
  $selGerencias = array_values(array_unique(array_filter(array_map('trim', $gerencias), static fn($v) => $v !== '')));

  $allowedSupervisoes = [];
  foreach ($rows as $row) {
    $g = (string)($row['gerencia'] ?? '');
    $s = (string)($row['supervisao'] ?? '');
    if ($g !== '' && $s !== '' && in_array($g, $selGerencias, true)) {
      $allowedSupervisoes[$s] = true;
    }
  }
  $selSupervisoes = array_values(array_unique(array_filter(array_map('trim', $supervisoes), static fn($v) => isset($allowedSupervisoes[$v]))));

  $allowedNomecc = [];
  foreach ($rows as $row) {
    $g = (string)($row['gerencia'] ?? '');
    $s = (string)($row['supervisao'] ?? '');
    $c = (string)($row['nome_cc'] ?? '');
    if ($g !== '' && $s !== '' && $c !== '' && in_array($g, $selGerencias, true) && in_array($s, $selSupervisoes, true)) {
      $allowedNomecc[$c] = true;
    }
  }
  $selNomecc = array_values(array_unique(array_filter(array_map('trim', $nomecc), static fn($v) => isset($allowedNomecc[$v]))));

  return [$selGerencias, $selSupervisoes, $selNomecc];
}

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
foreach ($pStmt->fetchAll() as $p) $selected[$p['tipo']][] = $p['valor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim((string)($_POST['nome'] ?? ''));
  $descricao = trim((string)($_POST['descricao'] ?? ''));
  $justificativa = trim((string)($_POST['justificativa_negacao'] ?? ''));
  $inicio = trim((string)($_POST['periodo_inicio'] ?? ''));
  $fim = trim((string)($_POST['periodo_fim'] ?? ''));
  [$selGerencias, $selSupervisoes, $selNomecc] = normalizeElectionPermissions(
    $hierRows,
    (array)($_POST['gerencia'] ?? []),
    (array)($_POST['supervisao'] ?? []),
    (array)($_POST['nome_cc'] ?? [])
  );

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
  <form method="post" class="row g-3" id="editElectionForm">
    <div class="col-md-6"><label class="form-label">Nome da eleição</label><input class="form-control" name="nome" value="<?= e($eleicao['nome']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="periodo_inicio" value="<?= e(date('Y-m-d\\TH:i', strtotime($eleicao['periodo_inicio']))) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Fim</label><input class="form-control" type="datetime-local" name="periodo_fim" value="<?= e(date('Y-m-d\\TH:i', strtotime($eleicao['periodo_fim']))) ?>" required></div>
    <div class="col-12"><label class="form-label">Descrição/Texto público</label><textarea class="form-control" name="descricao" rows="2"><?= e($eleicao['descricao'] ?? '') ?></textarea></div>
    <div class="col-12"><label class="form-label">Justificativa quando CPF não tiver permissão</label><textarea class="form-control" name="justificativa_negacao" rows="2"><?= e($eleicao['justificativa_negacao'] ?? '') ?></textarea></div>

    <div class="col-md-4">
      <label class="form-label">Gerência permitida</label>
      <div class="row g-2">
        <div class="col-5"><select id="gerencia_left" class="form-select" multiple size="12"></select></div>
        <div class="col-2 d-grid gap-1"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="gerencia:add">&gt;&gt;</button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="gerencia:remove">&lt;&lt;</button></div>
        <div class="col-5"><select id="gerencia_right" class="form-select" multiple size="12"></select></div>
      </div>
      <small class="text-muted">Selecione primeiro as gerências permitidas.</small>
      <select name="gerencia[]" id="gerencia_hidden" multiple class="d-none"></select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Supervisão permitida</label>
      <div class="row g-2">
        <div class="col-5"><select id="supervisao_left" class="form-select" multiple size="12"></select></div>
        <div class="col-2 d-grid gap-1"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="supervisao:add">&gt;&gt;</button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="supervisao:remove">&lt;&lt;</button></div>
        <div class="col-5"><select id="supervisao_right" class="form-select" multiple size="12"></select></div>
      </div>
      <small class="text-muted">Lista dinâmica baseada nas gerências selecionadas.</small>
      <select name="supervisao[]" id="supervisao_hidden" multiple class="d-none"></select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Nome CC permitido</label>
      <div class="row g-2">
        <div class="col-5"><select id="nomecc_left" class="form-select" multiple size="12"></select></div>
        <div class="col-2 d-grid gap-1"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="nomecc:add">&gt;&gt;</button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="nomecc:remove">&lt;&lt;</button></div>
        <div class="col-5"><select id="nomecc_right" class="form-select" multiple size="12"></select></div>
      </div>
      <small class="text-muted">Lista dinâmica baseada em gerência + supervisão.</small>
      <select name="nome_cc[]" id="nomecc_hidden" multiple class="d-none"></select>
    </div>

    <div class="col-12 d-flex gap-2">
      <button class="btn btn-friato" type="submit">Salvar alterações</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('admin/eleicoes.php')) ?>">Cancelar</a>
    </div>
  </form>
</div>

<script>
(() => {
  const rows = <?= json_encode($hierRows, JSON_UNESCAPED_UNICODE) ?>;
  const allGerencias = [...new Set(rows.map(r => r.gerencia).filter(Boolean))].sort();

  const state = {
    gerencia: <?= json_encode(array_values($selected['gerencia']), JSON_UNESCAPED_UNICODE) ?>,
    supervisao: <?= json_encode(array_values($selected['supervisao']), JSON_UNESCAPED_UNICODE) ?>,
    nomecc: <?= json_encode(array_values($selected['nome_cc']), JSON_UNESCAPED_UNICODE) ?>,
  };

  const el = {
    gerencia: { left: document.getElementById('gerencia_left'), right: document.getElementById('gerencia_right'), hidden: document.getElementById('gerencia_hidden') },
    supervisao: { left: document.getElementById('supervisao_left'), right: document.getElementById('supervisao_right'), hidden: document.getElementById('supervisao_hidden') },
    nomecc: { left: document.getElementById('nomecc_left'), right: document.getElementById('nomecc_right'), hidden: document.getElementById('nomecc_hidden') },
  };

  function optionsFrom(values) {
    return values.map(v => `<option value="${String(v).replace(/"/g,'&quot;')}">${v}</option>`).join('');
  }
  function uniqueSorted(arr) { return [...new Set(arr.filter(Boolean))].sort(); }

  function availableSupervisoes() {
    if (!state.gerencia.length) return [];
    return uniqueSorted(rows.filter(r => state.gerencia.includes(r.gerencia)).map(r => r.supervisao));
  }
  function availableNomecc() {
    if (!state.gerencia.length || !state.supervisao.length) return [];
    return uniqueSorted(rows.filter(r => state.gerencia.includes(r.gerencia) && state.supervisao.includes(r.supervisao)).map(r => r.nome_cc));
  }

  function renderLevel(level, available) {
    const selected = state[level];
    const leftVals = available.filter(v => !selected.includes(v));
    el[level].left.innerHTML = optionsFrom(leftVals);
    el[level].right.innerHTML = optionsFrom(selected);
    el[level].hidden.innerHTML = selected.map(v => `<option value="${String(v).replace(/"/g,'&quot;')}" selected>${v}</option>`).join('');
  }

  function renderAll() {
    state.gerencia = state.gerencia.filter(v => allGerencias.includes(v));
    renderLevel('gerencia', allGerencias);

    const supAvail = availableSupervisoes();
    state.supervisao = state.supervisao.filter(v => supAvail.includes(v));
    renderLevel('supervisao', supAvail);

    const ccAvail = availableNomecc();
    state.nomecc = state.nomecc.filter(v => ccAvail.includes(v));
    renderLevel('nomecc', ccAvail);
  }

  function selectedVals(sel) { return [...sel.selectedOptions].map(o => o.value); }
  document.querySelectorAll('[data-move]').forEach(btn => {
    btn.addEventListener('click', () => {
      const [level, dir] = btn.dataset.move.split(':');
      if (dir === 'add') state[level] = uniqueSorted(state[level].concat(selectedVals(el[level].left)));
      else state[level] = state[level].filter(v => !selectedVals(el[level].right).includes(v));
      renderAll();
    });
  });

  renderAll();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
