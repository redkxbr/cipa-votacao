<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
requireAdminLogin();

$pdo = getPDO();

$empresa = trim($_GET['empresa'] ?? '');
$setor = trim($_GET['setor'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');
$export = ($_GET['export'] ?? '') === 'csv';

$where = [];
$params = [];

if ($empresa !== '') {
    $where[] = 'v.eleitor_empresa LIKE :empresa';
    $params['empresa'] = '%' . $empresa . '%';
}
if ($setor !== '') {
    $where[] = 'v.eleitor_setor LIKE :setor';
    $params['setor'] = '%' . $setor . '%';
}
if ($dataInicio !== '') {
    $where[] = 'DATE(v.created_at) >= :data_inicio';
    $params['data_inicio'] = $dataInicio;
}
if ($dataFim !== '') {
    $where[] = 'DATE(v.created_at) <= :data_fim';
    $params['data_fim'] = $dataFim;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$ranking = $pdo->query('SELECT c.nome, COUNT(v.id) AS total_votos
    FROM candidatos c
    LEFT JOIN votos v ON v.candidato_id = c.id
    GROUP BY c.id, c.nome
    ORDER BY total_votos DESC, c.nome ASC')->fetchAll();

$sqlVotos = 'SELECT v.*, c.nome AS candidato_nome
    FROM votos v
    INNER JOIN candidatos c ON c.id = v.candidato_id
    ' . $whereSql . '
    ORDER BY v.created_at DESC';
$stmt = $pdo->prepare($sqlVotos);
$stmt->execute($params);
$votos = $stmt->fetchAll();

if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_votos.csv"');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Eleitor', 'CPF', 'Telefone', 'Empresa', 'Setor', 'Candidato', 'Código sorteio', 'Data/Hora'], ';');
    foreach ($votos as $voto) {
        fputcsv($out, [
            $voto['id'],
            $voto['eleitor_nome'],
            $voto['eleitor_cpf'],
            $voto['eleitor_telefone'],
            $voto['eleitor_empresa'],
            $voto['eleitor_setor'],
            $voto['candidato_nome'],
            $voto['codigo_sorteio'],
            $voto['created_at'],
        ], ';');
    }
    fclose($out);
    exit;
}

$pageTitle = 'Admin - Relatório de Votos';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Ranking de votos por candidato</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Candidato</th>
                <th>Total de votos</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ranking as $item): ?>
                <tr>
                    <td><?= e($item['nome']) ?></td>
                    <td><?= (int) $item['total_votos'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Lista de votos</h2>

    <form method="get" class="filters">
        <label>Empresa
            <input type="text" name="empresa" value="<?= e($empresa) ?>">
        </label>
        <label>Setor
            <input type="text" name="setor" value="<?= e($setor) ?>">
        </label>
        <label>Data inicial
            <input type="date" name="data_inicio" value="<?= e($dataInicio) ?>">
        </label>
        <label>Data final
            <input type="date" name="data_fim" value="<?= e($dataFim) ?>">
        </label>
        <button class="btn btn-primary" type="submit">Filtrar</button>
        <a class="btn" href="<?= e(url('admin/votos.php')) ?>">Limpar</a>
        <a class="btn" href="<?= e(url('admin/votos.php')) ?>?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">Exportar CSV</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Eleitor</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Empresa</th>
                <th>Setor</th>
                <th>Candidato</th>
                <th>Código</th>
                <th>Data/Hora</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($votos as $voto): ?>
                <tr>
                    <td><?= (int) $voto['id'] ?></td>
                    <td><?= e($voto['eleitor_nome']) ?></td>
                    <td><?= e(formatCpf($voto['eleitor_cpf'])) ?></td>
                    <td><?= e($voto['eleitor_telefone']) ?></td>
                    <td><?= e($voto['eleitor_empresa']) ?></td>
                    <td><?= e($voto['eleitor_setor']) ?></td>
                    <td><?= e($voto['candidato_nome']) ?></td>
                    <td><?= e($voto['codigo_sorteio']) ?></td>
                    <td><?= e($voto['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
