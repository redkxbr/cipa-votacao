<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getPDO();
$errors = [];
$data = [
    'nome' => '',
    'cpf' => '',
    'telefone' => '',
    'empresa' => '',
    'setor' => '',
    'candidato_id' => '',
];
$showConfirmation = false;

$candidates = $pdo->query('SELECT id, nome, cpf, foto_path FROM candidatos WHERE ativo = 1 ORDER BY nome ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['nome'] = trim($_POST['nome'] ?? '');
    $data['cpf'] = trim($_POST['cpf'] ?? '');
    $data['telefone'] = trim($_POST['telefone'] ?? '');
    $data['empresa'] = trim($_POST['empresa'] ?? '');
    $data['setor'] = trim($_POST['setor'] ?? '');
    $data['candidato_id'] = trim($_POST['candidato_id'] ?? '');

    if ($data['nome'] === '') {
        $errors[] = 'Nome é obrigatório.';
    }

    if (!validarCPF($data['cpf'])) {
        $errors[] = 'CPF inválido.';
    }

    if (!validarTelefone($data['telefone'])) {
        $errors[] = 'Telefone inválido. Informe 10 ou 11 dígitos.';
    }

    if ($data['empresa'] === '') {
        $errors[] = 'Empresa é obrigatória.';
    }

    if ($data['setor'] === '') {
        $errors[] = 'Setor é obrigatório.';
    }

    if ($data['candidato_id'] === '' || !ctype_digit($data['candidato_id'])) {
        $errors[] = 'Selecione um candidato.';
    }

    $cpfNumerico = onlyDigits($data['cpf']);

    if (empty($errors)) {
        $checkVoto = $pdo->prepare('SELECT id FROM votos WHERE eleitor_cpf = :cpf LIMIT 1');
        $checkVoto->execute(['cpf' => $cpfNumerico]);
        if ($checkVoto->fetch()) {
            $errors[] = 'Este CPF já votou.';
        }
    }

    if (empty($errors) && !isset($_POST['confirmar'])) {
        $showConfirmation = true;
    }

    if (empty($errors) && isset($_POST['confirmar'])) {
        try {
            $pdo->beginTransaction();

            $codigoSorteio = generateUniqueLotteryCode($pdo);
            $insert = $pdo->prepare('INSERT INTO votos (
                eleitor_nome,
                eleitor_cpf,
                eleitor_telefone,
                eleitor_empresa,
                eleitor_setor,
                candidato_id,
                codigo_sorteio
            ) VALUES (
                :eleitor_nome,
                :eleitor_cpf,
                :eleitor_telefone,
                :eleitor_empresa,
                :eleitor_setor,
                :candidato_id,
                :codigo_sorteio
            )');

            $insert->execute([
                'eleitor_nome' => $data['nome'],
                'eleitor_cpf' => $cpfNumerico,
                'eleitor_telefone' => onlyDigits($data['telefone']),
                'eleitor_empresa' => $data['empresa'],
                'eleitor_setor' => $data['setor'],
                'candidato_id' => (int) $data['candidato_id'],
                'codigo_sorteio' => $codigoSorteio,
            ]);

            $voteId = (int) $pdo->lastInsertId();
            $pdo->commit();

            $token = base64_encode(hash_hmac('sha256', (string) $voteId, APP_KEY, true));
            redirect(url('public/finalizar.php') . '?v=' . $voteId . '&t=' . urlencode($token));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((int) $e->getCode() === 23000) {
                $errors[] = 'Este CPF já votou.';
            } else {
                $errors[] = 'Erro ao registrar voto. Tente novamente.';
            }
        }
    }
}

$pageTitle = 'Votação CIPA';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Formulário de votação</h2>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($candidates)): ?>
        <p>Nenhum candidato ativo cadastrado no momento.</p>
    <?php else: ?>
        <?php if ($showConfirmation): ?>
            <div class="card card-confirm">
                <h3>Confirme seus dados</h3>
                <p><strong>Nome:</strong> <?= e($data['nome']) ?></p>
                <p><strong>CPF:</strong> <?= e(formatCpfMaskPublic($data['cpf'])) ?></p>
                <p><strong>Telefone:</strong> <?= e($data['telefone']) ?></p>
                <p><strong>Empresa:</strong> <?= e($data['empresa']) ?></p>
                <p><strong>Setor:</strong> <?= e($data['setor']) ?></p>
                <form method="post">
                    <?php foreach ($data as $key => $value): ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="confirmar" value="1">
                    <button type="submit" class="btn btn-primary">Confirmar voto</button>
                    <a class="btn" href="<?= e(url('public/votar.php')) ?>">Editar dados</a>
                </form>
            </div>
        <?php else: ?>
            <form method="post" class="vote-form">
                <label>Nome*
                    <input type="text" name="nome" value="<?= e($data['nome']) ?>" required>
                </label>
                <label>CPF*
                    <input type="text" name="cpf" value="<?= e($data['cpf']) ?>" required>
                </label>
                <label>Telefone*
                    <input type="text" name="telefone" value="<?= e($data['telefone']) ?>" required>
                </label>
                <label>Empresa*
                    <input type="text" name="empresa" value="<?= e($data['empresa']) ?>" required>
                </label>
                <label>Setor*
                    <input type="text" name="setor" value="<?= e($data['setor']) ?>" required>
                </label>

                <h3>Escolha um candidato</h3>
                <div class="candidate-grid">
                    <?php foreach ($candidates as $candidate): ?>
                        <label class="candidate-card">
                            <input
                                type="radio"
                                name="candidato_id"
                                value="<?= (int) $candidate['id'] ?>"
                                <?= $data['candidato_id'] === (string) $candidate['id'] ? 'checked' : '' ?>
                                required
                            >
                            <img src="<?= e(url('public/uploads/candidatos')) ?>/<?= e($candidate['foto_path']) ?>" alt="Foto de <?= e($candidate['nome']) ?>">
                            <strong><?= e($candidate['nome']) ?></strong>
                            <small>CPF: <?= e(formatCpfMaskPublic($candidate['cpf'])) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary">Continuar para confirmação</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
