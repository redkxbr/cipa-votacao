<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();
$errors = [];

if (($_GET['action'] ?? '') === 'template') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="modelo_eleitores.csv"');
  $out = fopen('php://output', 'wb');
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, ['nome', 'cpf', 'empresa', 'gerencia', 'supervisao', 'nome_cc'], ',');
  fputcsv($out, ['Maria da Silva', '12345678909', 'Friato', 'INDUSTRIALIZADOS', 'SUPERVISOR RH', 'RECEBIMENTO EMPANADOS'], ',');
  fclose($out);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
  try {
    if (empty($_FILES['csv']['name'])) throw new RuntimeException('Selecione um arquivo para importar.');
    if (($_FILES['csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no upload do arquivo.');

    $ext = strtolower(pathinfo((string)$_FILES['csv']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt'], true)) throw new RuntimeException('Formato inválido. Envie CSV (.csv). Para XLS/XLSX, exporte como CSV.');

    $delimiter = ($_POST['delimiter'] ?? ',') === ';' ? ';' : ',';
    $hasHeader = isset($_POST['has_header']) && $_POST['has_header'] === '1';

    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    if (!$fh) throw new RuntimeException('Não foi possível ler o arquivo.');

    $pdo->beginTransaction();

    $idx = ['nome' => 0, 'cpf' => 1, 'empresa' => 2, 'gerencia' => 3, 'supervisao' => 4, 'nome_cc' => 5];

    if ($hasHeader) {
      $header = fgetcsv($fh, 0, $delimiter);
      if (!$header) throw new RuntimeException('CSV vazio.');
      $map = array_map(static fn($h) => strtolower(trim((string)$h)), $header);
      foreach (array_keys($idx) as $key) {
        if (!in_array($key, $map, true)) throw new RuntimeException('Cabeçalho inválido. Esperado: nome, cpf, empresa, gerencia, supervisao, nome_cc.');
        $idx[$key] = (int)array_search($key, $map, true);
      }
    }

    $upsert = $pdo->prepare('INSERT INTO eleitores_autorizados (nome, cpf, empresa, gerencia, supervisao, nome_cc)
      VALUES (:n,:c,:e,:g,:s,:cc)
      ON DUPLICATE KEY UPDATE nome = VALUES(nome), empresa = VALUES(empresa), gerencia = VALUES(gerencia), supervisao = VALUES(supervisao), nome_cc = VALUES(nome_cc)');

    $imported = 0;
    $ignored = 0;
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
      $nome = trim((string)($row[$idx['nome']] ?? ''));
      $cpf = digits((string)($row[$idx['cpf']] ?? ''));
      $empresa = trim((string)($row[$idx['empresa']] ?? ''));
      $gerencia = trim((string)($row[$idx['gerencia']] ?? ''));
      $supervisao = trim((string)($row[$idx['supervisao']] ?? ''));
      $nomeCc = trim((string)($row[$idx['nome_cc']] ?? ''));

      if ($nome === '' || $empresa === '' || !validateCPF($cpf)) {
        $ignored++;
        continue;
      }
      $upsert->execute(['n' => $nome, 'c' => $cpf, 'e' => $empresa, 'g' => $gerencia, 's' => $supervisao, 'cc' => $nomeCc]);
      $imported++;
    }
    fclose($fh);
    $pdo->commit();

    flash('success', 'Importação concluída. Válidos: ' . $imported . ' | Ignorados: ' . $ignored);
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
  $where = ' WHERE nome LIKE :q OR cpf LIKE :q OR empresa LIKE :q OR gerencia LIKE :q OR supervisao LIKE :q OR nome_cc LIKE :q ';
  $params['q'] = '%' . $search . '%';
}
$stmt = $pdo->prepare('SELECT * FROM eleitores_autorizados' . $where . ' ORDER BY nome ASC LIMIT 700');
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
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
    <h2 class="h5 mb-0">Importar eleitores</h2>
    <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/eleitores.php?action=template')) ?>">Baixar modelo CSV</a>
  </div>

  <p class="small text-muted mb-3">
    Ordem esperada das colunas: <strong>nome, cpf, empresa, gerencia, supervisao, nome_cc</strong>.
    Você pode importar com ou sem cabeçalho. Se estiver em XLS/XLSX, exporte para CSV.
  </p>

  <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
    <input type="hidden" name="action" value="import">
    <div class="col-md-6"><input type="file" name="csv" class="form-control" accept=".csv,text/csv,.txt" required></div>
    <div class="col-md-2">
      <label class="form-label small mb-1">Separador</label>
      <select class="form-select" name="delimiter"><option value=",">Vírgula (,)</option><option value=";">Ponto e vírgula (;)</option></select>
    </div>
    <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="has_header" id="has_header" value="1" checked><label class="form-check-label" for="has_header">1ª linha cabeçalho</label></div></div>
    <div class="col-md-2"><button class="btn btn-friato w-100" type="submit">Importar</button></div>
  </form>
</div>

<div class="card p-3 table-responsive">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-10"><input class="form-control" name="q" placeholder="Buscar por nome, CPF, empresa, gerência, supervisão, nome cc" value="<?= e($search) ?>"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Buscar</button></div>
  </form>

  <table class="table table-hover align-middle">
    <thead><tr><th>Nome</th><th>CPF</th><th>Empresa</th><th>Gerência</th><th>Supervisão</th><th>Nome CC</th><th>Cadastrado em</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['nome']) ?></td>
        <td><?= e(formatCpf($r['cpf'])) ?></td>
        <td><?= e($r['empresa']) ?></td>
        <td><?= e($r['gerencia']) ?></td>
        <td><?= e($r['supervisao']) ?></td>
        <td><?= e($r['nome_cc']) ?></td>
        <td><?= e($r['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
