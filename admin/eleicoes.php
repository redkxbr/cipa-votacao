<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();
$errors = [];

$hierRows = $pdo->query("SELECT DISTINCT gerencia, supervisao, nome_cc FROM eleitores_autorizados WHERE gerencia <> '' ORDER BY gerencia, supervisao, nome_cc")->fetchAll();
$gerencias = array_values(array_unique(array_map(static fn($r) => (string)$r['gerencia'], $hierRows)));
sort($gerencias);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
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
  <form method="post" class="row g-3" id="electionForm">
    <input type="hidden" name="action" value="create">
    <div class="col-md-6"><label class="form-label">Nome da eleição</label><input class="form-control" name="nome" placeholder="Eleição CIPA Friato 2026" required></div>
    <div class="col-md-3"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="periodo_inicio" required></div>
    <div class="col-md-3"><label class="form-label">Fim</label><input class="form-control" type="datetime-local" name="periodo_fim" required></div>
    <div class="col-12"><label class="form-label">Descrição/Texto público</label><textarea class="form-control" name="descricao" rows="2" placeholder="Eleição da CIPA 2026 da empresa Friato..."></textarea></div>
    <div class="col-12"><label class="form-label">Justificativa quando CPF não tiver permissão</label><textarea class="form-control" name="justificativa_negacao" rows="2" placeholder="Você não faz parte dos grupos habilitados nesta eleição."></textarea></div>

    <div class="col-12">
      <label class="form-label">Gerência permitida</label>
      <div class="row g-2">
        <div class="col-5"><select id="gerencia_left" class="form-select" multiple size="12"></select></div>
        <div class="col-2 d-grid gap-1"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="gerencia:add">&gt;&gt;</button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="gerencia:remove">&lt;&lt;</button></div>
        <div class="col-5"><select id="gerencia_right" class="form-select" multiple size="12"></select></div>
      </div>
      <small class="text-muted">Selecione primeiro as gerências permitidas.</small>
      <select name="gerencia[]" id="gerencia_hidden" multiple class="d-none"></select>
    </div>

    <div class="col-12">
      <label class="form-label">Supervisão permitida</label>
      <div class="row g-2">
        <div class="col-5"><select id="supervisao_left" class="form-select" multiple size="12"></select></div>
        <div class="col-2 d-grid gap-1"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="supervisao:add">&gt;&gt;</button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="supervisao:remove">&lt;&lt;</button></div>
        <div class="col-5"><select id="supervisao_right" class="form-select" multiple size="12"></select></div>
      </div>
      <small class="text-muted">Lista dinâmica baseada nas gerências selecionadas.</small>
      <select name="supervisao[]" id="supervisao_hidden" multiple class="d-none"></select>
    </div>

    <div class="col-12">
      <label class="form-label">Nome CC permitido</label>
      <div class="row g-2">
        <div class="col-5"><select id="nomecc_left" class="form-select" multiple size="12"></select></div>
        <div class="col-2 d-grid gap-1"><button class="btn btn-outline-secondary btn-sm" type="button" data-move="nomecc:add">&gt;&gt;</button><button class="btn btn-outline-secondary btn-sm" type="button" data-move="nomecc:remove">&lt;&lt;</button></div>
        <div class="col-5"><select id="nomecc_right" class="form-select" multiple size="12"></select></div>
      </div>
      <small class="text-muted">Lista dinâmica baseada em gerência + supervisão.</small>
      <select name="nome_cc[]" id="nomecc_hidden" multiple class="d-none"></select>
    </div>

    <div class="col-12"><button class="btn btn-friato" type="submit">Criar eleição</button></div>
  </form>
</div>

<div class="card p-3 table-responsive">
  <h2 class="h5">Eleições criadas</h2>
  <table class="table table-hover align-middle">
    <thead><tr><th>Nome</th><th>Período</th><th>Votos</th><th>Link de votação</th><th>Ações</th></tr></thead>
    <tbody>
      <?php foreach($list as $e): ?>
      <tr>
        <td><strong><?= e($e['nome']) ?></strong><br><small class="text-muted"><?= e($e['descricao'] ?? '') ?></small></td>
        <td><?= e($e['periodo_inicio']) ?><br><?= e($e['periodo_fim']) ?></td>
        <td><?= (int)$e['total_votos'] ?></td>
        <td><?php $link = url('public/votar.php?eleicao=' . urlencode($e['slug'])); ?><input class="form-control form-control-sm" value="<?= e($link) ?>" readonly onclick="this.select();"></td>
        <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/eleicao_editar.php?id=' . (int)$e['id'])) ?>">Editar</a> <a class="btn btn-sm btn-outline-danger" href="<?= e(url('admin/eleicao_excluir.php?id=' . (int)$e['id'])) ?>">Excluir</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
(() => {
  const rows = <?= json_encode($hierRows, JSON_UNESCAPED_UNICODE) ?>;
  const allGerencias = [...new Set(rows.map(r => r.gerencia).filter(Boolean))].sort();

  const state = { gerencia: [], supervisao: [], nomecc: [] };

  const el = {
    gerencia: { left: document.getElementById('gerencia_left'), right: document.getElementById('gerencia_right'), hidden: document.getElementById('gerencia_hidden') },
    supervisao: { left: document.getElementById('supervisao_left'), right: document.getElementById('supervisao_right'), hidden: document.getElementById('supervisao_hidden') },
    nomecc: { left: document.getElementById('nomecc_left'), right: document.getElementById('nomecc_right'), hidden: document.getElementById('nomecc_hidden') },
  };

  function optionsFrom(values, selected) {
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
    el[level].left.innerHTML = optionsFrom(leftVals, selected);
    el[level].right.innerHTML = optionsFrom(selected, selected);
    el[level].hidden.innerHTML = selected.map(v => `<option value="${String(v).replace(/"/g,'&quot;')}" selected>${v}</option>`).join('');
  }

  function renderAll() {
    renderLevel('gerencia', allGerencias);

    const supAvail = availableSupervisoes();
    state.supervisao = state.supervisao.filter(v => supAvail.includes(v));
    renderLevel('supervisao', supAvail);

    const ccAvail = availableNomecc();
    state.nomecc = state.nomecc.filter(v => ccAvail.includes(v));
    renderLevel('nomecc', ccAvail);
  }

  function getSelectedValues(selectEl) {
    return [...selectEl.selectedOptions].map(o => o.value);
  }

  document.querySelectorAll('[data-move]').forEach(btn => {
    btn.addEventListener('click', () => {
      const [level, dir] = btn.dataset.move.split(':');
      if (!level || !dir) return;
      if (dir === 'add') {
        const vals = getSelectedValues(el[level].left);
        state[level] = uniqueSorted(state[level].concat(vals));
      } else {
        const vals = getSelectedValues(el[level].right);
        state[level] = state[level].filter(v => !vals.includes(v));
      }
      renderAll();
    });
  });

  renderAll();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
