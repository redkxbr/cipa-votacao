<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();

$empresa = trim((string)($_GET['empresa'] ?? ''));
$setor = trim((string)($_GET['setor'] ?? ''));
$codigo = trim((string)($_GET['codigo'] ?? ''));
$di = trim((string)($_GET['data_inicio'] ?? ''));
$df = trim((string)($_GET['data_fim'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$export = trim((string)($_GET['export'] ?? ''));

$where=[];$params=[];
if ($empresa !== '') {$where[]='v.eleitor_empresa LIKE :empresa';$params['empresa']='%'.$empresa.'%';}
if ($setor !== '') {$where[]='v.eleitor_setor LIKE :setor';$params['setor']='%'.$setor.'%';}
if ($codigo !== '') {$where[]='v.codigo_sorteio = :codigo';$params['codigo']=$codigo;}
if ($di !== '') {$where[]='DATE(v.created_at)>=:di';$params['di']=$di;}
if ($df !== '') {$where[]='DATE(v.created_at)<=:df';$params['df']=$df;}
$whereSql = $where ? (' WHERE '.implode(' AND ',$where)) : '';

$ranking = $pdo->query('SELECT c.nome, c.turno, c.setor, COUNT(v.id) total FROM candidatos c LEFT JOIN votos v ON v.candidato_id=c.id GROUP BY c.id,c.nome,c.turno,c.setor ORDER BY total DESC')->fetchAll();

$countSql = 'SELECT COUNT(*) FROM votos v' . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = 'SELECT v.*, c.nome candidato_nome, c.turno candidato_turno, c.setor candidato_setor FROM votos v INNER JOIN candidatos c ON c.id=v.candidato_id' . $whereSql . ' ORDER BY v.created_at DESC';
if ($export === '') $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if ($export !== '') {
  header('Content-Type: text/csv; charset=UTF-8');
  $out = fopen('php://output', 'wb');
  fwrite($out, "\xEF\xBB\xBF");

  if ($export === 'votos') {
    header('Content-Disposition: attachment; filename="relatorio_votos.csv"');
    fputcsv($out, ['ID','Eleitor','CPF','Turno','Telefone','Empresa','Setor','Candidato','Código','Data'], ';');
    foreach ($rows as $r) {
      fputcsv($out, [$r['id'],$r['eleitor_nome'],$r['eleitor_cpf'],$r['eleitor_turno'],$r['eleitor_telefone'],$r['eleitor_empresa'],$r['eleitor_setor'],$r['candidato_nome'],$r['codigo_sorteio'],$r['created_at']], ';');
    }
  } elseif ($export === 'candidatos') {
    header('Content-Disposition: attachment; filename="relatorio_candidatos_votos.csv"');
    fputcsv($out, ['Candidato','Turno','Setor','Total votos'], ';');
    foreach ($ranking as $r) {
      fputcsv($out, [$r['nome'],$r['turno'],$r['setor'],$r['total']], ';');
    }
  }
  fclose($out);
  exit;
}

$pageTitle = 'Relatório de Votos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Relatórios</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>">Voltar</a>
</div>

<div class="card p-3 mb-3 table-responsive">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
    <h2 class="h5 mb-0">Ranking de candidatos</h2>
    <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/votos.php?export=candidatos')) ?>">Exportar candidatos + votos (CSV)</a>
  </div>
  <table class="table"><thead><tr><th>Candidato</th><th>Turno</th><th>Setor</th><th>Votos</th></tr></thead><tbody><?php foreach($ranking as $r): ?><tr><td><?= e($r['nome']) ?></td><td><?= e($r['turno']) ?></td><td><?= e($r['setor']) ?></td><td><?= (int)$r['total'] ?></td></tr><?php endforeach; ?></tbody></table>
</div>

<div class="card p-3 table-responsive">
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-2"><input class="form-control" name="empresa" placeholder="Empresa" value="<?= e($empresa) ?>"></div>
    <div class="col-md-2"><input class="form-control" name="setor" placeholder="Setor" value="<?= e($setor) ?>"></div>
    <div class="col-md-2"><input class="form-control" name="codigo" placeholder="Código sorteio" value="<?= e($codigo) ?>"></div>
    <div class="col-md-2"><input class="form-control" type="date" name="data_inicio" value="<?= e($di) ?>"></div>
    <div class="col-md-2"><input class="form-control" type="date" name="data_fim" value="<?= e($df) ?>"></div>
    <div class="col-md-2 d-flex gap-2"><button class="btn btn-friato w-100" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/votos.php')) ?>">Limpar</a></div>
  </form>

  <a class="btn btn-sm btn-outline-dark mb-2" href="<?= e(url('admin/votos.php?' . http_build_query(array_merge($_GET,['export'=>'votos'])))) ?>">Exportar votos (CSV)</a>
  <table class="table table-hover align-middle">
    <thead><tr><th>ID</th><th>Eleitor</th><th>CPF</th><th>Turno</th><th>Telefone</th><th>Empresa</th><th>Setor</th><th>Candidato</th><th>Código</th><th>Data</th></tr></thead>
    <tbody><?php foreach($rows as $v): ?><tr><td><?= (int)$v['id'] ?></td><td><?= e($v['eleitor_nome']) ?></td><td><?= e(formatCpf($v['eleitor_cpf'])) ?></td><td><?= e($v['eleitor_turno']) ?></td><td><?= e($v['eleitor_telefone']) ?></td><td><?= e($v['eleitor_empresa']) ?></td><td><?= e($v['eleitor_setor']) ?></td><td><?= e($v['candidato_nome']) ?></td><td><?= e($v['codigo_sorteio']) ?></td><td><?= e($v['created_at']) ?></td></tr><?php endforeach; ?></tbody>
  </table>

  <nav><ul class="pagination">
    <?php for($p=1;$p<=$totalPages;$p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= e(url('admin/votos.php?' . http_build_query(array_merge($_GET,['page'=>$p])))) ?>"><?= $p ?></a></li>
    <?php endfor; ?>
  </ul></nav>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
