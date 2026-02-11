<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();
$errors = [];
$editId = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'toggle' && ctype_digit($_POST['id'] ?? '')) {
    $pdo->prepare('UPDATE candidatos SET ativo = IF(ativo=1,0,1) WHERE id=:id')->execute(['id' => (int)$_POST['id']]);
    flash('success', 'Status do candidato atualizado.');
    redirect(url('admin/candidatos.php'));
  }

  if ($action === 'save') {
    $id = ctype_digit($_POST['id'] ?? '') ? (int)$_POST['id'] : 0;
    $nome = trim((string)($_POST['nome'] ?? ''));
    $cpf = digits((string)($_POST['cpf'] ?? ''));

    if ($nome === '') $errors[] = 'Nome é obrigatório.';
    if (!validateCPF($cpf)) $errors[] = 'CPF inválido.';

    if (!$errors) {
      try {
        if ($id > 0) {
          $old = $pdo->prepare('SELECT foto_path FROM candidatos WHERE id=:id');
          $old->execute(['id'=>$id]);
          $cand = $old->fetch();
          $foto = $cand['foto_path'] ?? null;
          if (!empty($_FILES['foto']['name'])) {
            $foto = uploadCandidatePhoto($_FILES['foto']);
          }
          $pdo->prepare('UPDATE candidatos SET nome=:n, cpf=:c, foto_path=:f WHERE id=:id')->execute(['n'=>$nome,'c'=>$cpf,'f'=>$foto,'id'=>$id]);
          flash('success', 'Candidato atualizado.');
        } else {
          $foto = !empty($_FILES['foto']['name']) ? uploadCandidatePhoto($_FILES['foto']) : null;
          $pdo->prepare('INSERT INTO candidatos (nome, cpf, foto_path, ativo) VALUES (:n,:c,:f,1)')->execute(['n'=>$nome,'c'=>$cpf,'f'=>$foto]);
          flash('success', 'Candidato cadastrado.');
        }
        redirect(url('admin/candidatos.php'));
      } catch (Throwable $e) {
        $errors[] = ((int)$e->getCode() === 23000) ? 'CPF já cadastrado.' : $e->getMessage();
      }
    }
  }
}

$editing = ['id'=>0,'nome'=>'','cpf'=>'','foto_path'=>''];
if ($editId > 0) {
  $s = $pdo->prepare('SELECT * FROM candidatos WHERE id=:id');
  $s->execute(['id'=>$editId]);
  $editing = $s->fetch() ?: $editing;
}
$list = $pdo->query('SELECT * FROM candidatos ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Admin Candidatos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Candidatos</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>">Voltar</a>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h2 class="h5"><?= $editing['id'] ? 'Editar candidato' : 'Novo candidato' ?></h2>
      <?php if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="vstack gap-2">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
        <input class="form-control" name="nome" placeholder="Nome" value="<?= e($editing['nome']) ?>" required>
        <input class="form-control" name="cpf" placeholder="CPF" value="<?= e($editing['cpf']) ?>" required>
        <input class="form-control" type="file" name="foto" accept="image/jpeg,image/png">
        <button class="btn btn-friato" type="submit">Salvar</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-3 table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Foto</th><th>Nome</th><th>CPF</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($list as $c): ?>
          <tr>
            <td><?php if ($c['foto_path']): ?><img src="<?= e(url('public/uploads/candidatos/' . $c['foto_path'])) ?>" class="candidate-photo" alt="foto"><?php endif; ?></td>
            <td><?= e($c['nome']) ?></td>
            <td><?= e(formatCpf($c['cpf'])) ?></td>
            <td><span class="badge <?= (int)$c['ativo']===1 ? 'text-bg-success':'text-bg-secondary' ?>"><?= (int)$c['ativo']===1 ? 'Ativo':'Inativo' ?></span></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/candidatos.php?edit=' . (int)$c['id'])) ?>">Editar</a>
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-sm btn-outline-dark" type="submit"><?= (int)$c['ativo']===1 ? 'Desativar':'Ativar' ?></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
