<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
  try {
    if (empty($_FILES['csv']['name'])) {
      throw new RuntimeException('Selecione um CSV para importar.');
    }
    if (($_FILES['csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Falha no upload do CSV.');
    }

    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    if (!$fh) {
      throw new RuntimeException('Não foi possível ler o CSV.');
    }

    $pdo->beginTransaction();
    $header = fgetcsv($fh, 0, ',');
    if (!$header) {
      throw new RuntimeException('CSV vazio.');
    }

    $map = array_map(static fn($h) => strtolower(trim((string)$h)), $header);
    $required = ['nome', 'cpf', 'empresa'];
    foreach ($required as $r) {
      if (!in_array($r, $map, true)) {
        throw new RuntimeException('CSV precisa conter colunas: nome, cpf, empresa.');
      }
    }

    $idxNome = array_search('nome', $map, true);
    $idxCpf = array_search('cpf', $map, true);
    $idxEmpresa = array_search('empresa', $map, true);

    $upsert = $pdo->prepare('INSERT INTO eleitores_autorizados (nome, cpf, empresa) VALUES (:n,:c,:e)
      ON DUPLICATE KEY UPDATE nome = VALUES(nome), empresa = VALUES(empresa)');

    $imported = 0;
    while (($row = fgetcsv($fh, 0, ',')) !== false) {
      $nome = trim((string)($row[$idxNome] ?? ''));
      $cpf = digits((string)($row[$idxCpf] ?? ''));
      $empresa = trim((string)($row[$idxEmpresa] ?? ''));
      if ($nome === '' || $empresa === '' || !validateCPF($cpf)) {
        continue;
      }
      $upsert->execute(['n' => $nome, 'c' => $cpf, 'e' => $empresa]);
      $imported++;
    }
    fclose($fh);
    $pdo->commit();

    flash('success', 'Importação concluída. Registros processados: ' . $imported);
    redirect(url('admin/eleitores.php'));
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errors[] = $e->getMessage();
  }
}

$search = trim((string)($_GET['q'] ?? ''));
$params = [];
$where = '';
if ($search !== '') {
  $where = ' WHERE nome LIKE :q OR cpf LIKE :q OR empresa LIKE :q ';
  $params['q'] = '%' . $search . '%';
}
$stmt = $pdo->prepare('SELECT * FROM eleitores_autorizados' . $where . ' ORDER BY nome ASC LIMIT 500');
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Importar Eleitores';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Eleitores autorizados</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>">Voltar</a>
</div>

<div class="card p-3 mb-3">
  <h2 class="h5">Importar CSV (nome,cpf,empresa)</h2>
  <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
    <input type="hidden" name="action" value="import">
    <div class="col-md-8"><input type="file" name="csv" class="form-control" accept=".csv,text/csv" required></div>
    <div class="col-md-4"><button class="btn btn-friato w-100" type="submit">Importar eleitores</button></div>
  </form>
</div>

<div class="card p-3 table-responsive">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-10"><input class="form-control" name="q" placeholder="Buscar por nome, CPF ou empresa" value="<?= e($search) ?>"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Buscar</button></div>
  </form>

  <table class="table table-hover align-middle">
    <thead><tr><th>Nome</th><th>CPF</th><th>Empresa</th><th>Cadastrado em</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['nome']) ?></td>
        <td><?= e(formatCpf($r['cpf'])) ?></td>
        <td><?= e($r['empresa']) ?></td>
        <td><?= e($r['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
