<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
requireAdminLogin();

$pdo = getPDO();
$uploadDir = __DIR__ . '/../public/uploads/candidatos';
$errors = [];
$editId = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = isset($_POST['id']) && ctype_digit($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT foto_path FROM candidatos WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $candidate = $stmt->fetch();

            $delete = $pdo->prepare('DELETE FROM candidatos WHERE id = :id');
            $delete->execute(['id' => $id]);

            if ($candidate && !empty($candidate['foto_path'])) {
                $filePath = $uploadDir . '/' . $candidate['foto_path'];
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
            setFlash('success', 'Candidato excluído com sucesso.');
            redirect(url('admin/candidatos.php'));
        }
    }

    if ($action === 'toggle') {
        $id = isset($_POST['id']) && ctype_digit($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id > 0) {
            $pdo->prepare('UPDATE candidatos SET ativo = IF(ativo = 1, 0, 1) WHERE id = :id')->execute(['id' => $id]);
            setFlash('success', 'Status do candidato atualizado.');
            redirect(url('admin/candidatos.php'));
        }
    }

    if ($action === 'save') {
        $id = isset($_POST['id']) && ctype_digit($_POST['id']) ? (int) $_POST['id'] : 0;
        $nome = trim($_POST['nome'] ?? '');
        $cpf = onlyDigits($_POST['cpf'] ?? '');

        if ($nome === '') {
            $errors[] = 'Nome é obrigatório.';
        }
        if (!validarCPF($cpf)) {
            $errors[] = 'CPF inválido.';
        }

        if (empty($errors)) {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare('SELECT foto_path FROM candidatos WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                    $existing = $stmt->fetch();

                    $fotoPath = $existing['foto_path'] ?? '';
                    if (!empty($_FILES['foto']['name'])) {
                        $newPhoto = saveCandidatePhoto($_FILES['foto'], $uploadDir, 'candidato_' . $id);
                        if ($fotoPath && is_file($uploadDir . '/' . $fotoPath)) {
                            unlink($uploadDir . '/' . $fotoPath);
                        }
                        $fotoPath = $newPhoto;
                    }

                    $update = $pdo->prepare('UPDATE candidatos SET nome = :nome, cpf = :cpf, foto_path = :foto WHERE id = :id');
                    $update->execute([
                        'nome' => $nome,
                        'cpf' => $cpf,
                        'foto' => $fotoPath,
                        'id' => $id,
                    ]);
                    setFlash('success', 'Candidato atualizado com sucesso.');
                } else {
                    if (empty($_FILES['foto']['name'])) {
                        throw new RuntimeException('A foto é obrigatória para novo candidato.');
                    }
                    $fotoPath = saveCandidatePhoto($_FILES['foto'], $uploadDir, 'candidato');

                    $insert = $pdo->prepare('INSERT INTO candidatos (nome, cpf, foto_path, ativo) VALUES (:nome, :cpf, :foto, 1)');
                    $insert->execute([
                        'nome' => $nome,
                        'cpf' => $cpf,
                        'foto' => $fotoPath,
                    ]);
                    setFlash('success', 'Candidato cadastrado com sucesso.');
                }
                redirect(url('admin/candidatos.php'));
            } catch (Throwable $e) {
                if ((int) $e->getCode() === 23000) {
                    $errors[] = 'CPF já cadastrado para outro candidato.';
                } else {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
}

$editing = ['id' => 0, 'nome' => '', 'cpf' => '', 'foto_path' => ''];
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM candidatos WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $row = $stmt->fetch();
    if ($row) {
        $editing = $row;
    }
}

$candidates = $pdo->query('SELECT * FROM candidatos ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Admin - Candidatos';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2><?= $editing['id'] ? 'Editar candidato' : 'Cadastrar candidato' ?></h2>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="vote-form">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">

        <label>Nome
            <input type="text" name="nome" value="<?= e($editing['nome']) ?>" required>
        </label>
        <label>CPF
            <input type="text" name="cpf" value="<?= e($editing['cpf']) ?>" required>
        </label>
        <label>Foto (JPG/PNG até 2MB)
            <input type="file" name="foto" accept="image/jpeg,image/png" <?= $editing['id'] ? '' : 'required' ?>>
        </label>

        <?php if (!empty($editing['foto_path'])): ?>
            <img class="thumb" src="<?= e(url('public/uploads/candidatos')) ?>/<?= e($editing['foto_path']) ?>" alt="Foto atual">
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Salvar candidato</button>
        <?php if ($editing['id']): ?>
            <a href="<?= e(url('admin/candidatos.php')) ?>" class="btn">Cancelar edição</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Lista de candidatos</h2>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Foto</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($candidates as $candidate): ?>
                <tr>
                    <td><?= (int) $candidate['id'] ?></td>
                    <td><img class="thumb" src="<?= e(url('public/uploads/candidatos')) ?>/<?= e($candidate['foto_path']) ?>" alt="Foto"></td>
                    <td><?= e($candidate['nome']) ?></td>
                    <td><?= e(formatCpf($candidate['cpf'])) ?></td>
                    <td><?= (int) $candidate['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></td>
                    <td>
                        <a class="btn btn-sm" href="<?= e(url('admin/candidatos.php')) ?>?edit=<?= (int) $candidate['id'] ?>">Editar</a>
                        <form method="post" class="inline-form" onsubmit="return confirm('Confirma exclusão?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $candidate['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $candidate['id'] ?>">
                            <button type="submit" class="btn btn-sm">
                                <?= (int) $candidate['ativo'] === 1 ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
