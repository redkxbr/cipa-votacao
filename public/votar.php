<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = pdo();
$candidates = $pdo->query('SELECT id, nome, cpf, foto_path, turno, setor FROM candidatos WHERE ativo = 1 ORDER BY nome')->fetchAll();

$turnos = ['1° Turno', '2° Turno', 'Comercial', 'Outro'];
$data = [
  'nome' => '', 'cpf' => '', 'turno' => '1° Turno', 'telefone' => '', 'empresa' => '', 'setor' => '', 'candidato_id' => '', 'current_step' => '1'
];
$serverError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($data as $k => $v) {
    $data[$k] = trim((string)($_POST[$k] ?? ''));
  }

  $cpf = digits($data['cpf']);
  $phone = digits($data['telefone']);

  if ($data['nome'] === '' || $cpf === '' || $phone === '' || $data['empresa'] === '' || $data['setor'] === '' || $data['candidato_id'] === '') {
    $serverError = 'Preencha todos os campos obrigatórios para concluir o voto.';
    $data['current_step'] = '4';
  } elseif (!in_array($data['turno'], $turnos, true)) {
    $serverError = 'Selecione um turno válido.';
    $data['current_step'] = '2';
  } elseif (!validateCPF($cpf)) {
    $serverError = 'CPF inválido. Verifique e tente novamente.';
    $data['current_step'] = '2';
  } elseif (!validatePhone($phone)) {
    $serverError = 'Telefone inválido. Informe 10 ou 11 dígitos.';
    $data['current_step'] = '3';
  } else {
    $authorized = $pdo->prepare('SELECT nome, empresa FROM eleitores_autorizados WHERE cpf = :cpf LIMIT 1');
    $authorized->execute(['cpf' => $cpf]);
    $allowed = $authorized->fetch();
    if (!$allowed) {
      $serverError = 'Este CPF não está autorizado para votar. Procure o RH/SESMT.';
      $data['current_step'] = '2';
    } else {
      $check = $pdo->prepare('SELECT 1 FROM votos WHERE eleitor_cpf = :cpf LIMIT 1');
      $check->execute(['cpf' => $cpf]);
      if ($check->fetch()) {
        $serverError = 'Este CPF já votou.';
        $data['current_step'] = '2';
      } else {
        try {
          $pdo->beginTransaction();
          $code = uniqueLotteryCode($pdo);
          $token = bin2hex(random_bytes(16));

          $stmt = $pdo->prepare('INSERT INTO votos (eleitor_nome, eleitor_cpf, eleitor_turno, eleitor_telefone, eleitor_empresa, eleitor_setor, candidato_id, codigo_sorteio, token)
                                 VALUES (:n,:cpf,:turno,:t,:e,:s,:c,:code,:token)');
          $stmt->execute([
            'n' => $data['nome'],
            'cpf' => $cpf,
            'turno' => $data['turno'],
            't' => $phone,
            'e' => $data['empresa'],
            's' => $data['setor'],
            'c' => (int)$data['candidato_id'],
            'code' => $code,
            'token' => $token,
          ]);
          $pdo->commit();
          flash('success', 'Voto confirmado com sucesso!');
          redirect(url('public/finalizar.php?token=' . urlencode($token)));
        } catch (Throwable $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          $serverError = ((int)$e->getCode() === 23000) ? 'Este CPF já votou.' : 'Não foi possível concluir seu voto agora.';
        }
      }
    }
  }
}

$pageTitle = 'Votação CIPA (Wizard)';
require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($serverError): ?>
<script>
document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'error',text:'<?= e($serverError) ?>',confirmButtonColor:'#DA291C'}));
</script>
<?php endif; ?>

<div class="card step-card p-4" data-wizard>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="h4 mb-0 text-danger">Wizard de Votação</h2>
    <span class="badge text-bg-dark">4 etapas</span>
  </div>
  <div class="stepper">
    <div class="step"></div><div class="step"></div><div class="step"></div><div class="step"></div>
  </div>

  <form method="post">
    <input type="hidden" name="current_step" value="<?= e($data['current_step']) ?>">

    <section class="wizard-pane">
      <h3 class="h5">1) Instruções</h3>
      <p class="text-secondary">Você informará seus dados, escolherá 1 candidato e receberá seu número único para sorteio.</p>
      <div class="mt-3 d-flex gap-2 wizard-actions"><button type="button" class="btn btn-friato w-100" data-next>Continuar</button></div>
    </section>

    <section class="wizard-pane">
      <h3 class="h5">2) Identificação</h3>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome*</label><input class="form-control" type="text" name="nome" value="<?= e($data['nome']) ?>"></div>
        <div class="col-md-3"><label class="form-label">CPF*</label><input class="form-control" type="text" name="cpf" value="<?= e($data['cpf']) ?>" placeholder="000.000.000-00"></div>
        <div class="col-md-3"><label class="form-label">Turno*</label>
          <select class="form-select" name="turno">
            <?php foreach($turnos as $turno): ?>
            <option value="<?= e($turno) ?>" <?= $data['turno'] === $turno ? 'selected' : '' ?>><?= e($turno) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2 wizard-actions"><button type="button" class="btn btn-outline-secondary" data-prev>Voltar</button><button type="button" class="btn btn-friato" data-next>Continuar</button></div>
    </section>

    <section class="wizard-pane">
      <h3 class="h5">3) Contato e Lotação</h3>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Telefone*</label><input class="form-control" type="text" name="telefone" value="<?= e($data['telefone']) ?>" placeholder="(00) 00000-0000"></div>
        <div class="col-md-4"><label class="form-label">Empresa*</label><input class="form-control" type="text" name="empresa" value="<?= e($data['empresa']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Setor*</label><input class="form-control" type="text" name="setor" value="<?= e($data['setor']) ?>"></div>
      </div>
      <div class="mt-3 d-flex gap-2 wizard-actions"><button type="button" class="btn btn-outline-secondary" data-prev>Voltar</button><button type="button" class="btn btn-friato" data-next>Continuar</button></div>
    </section>

    <section class="wizard-pane">
      <h3 class="h5">4) Escolha seu candidato</h3>
      <div class="row g-3 mt-1">
        <?php foreach ($candidates as $c): ?>
          <div class="col-md-4">
            <label class="candidate-card w-100 <?= $data['candidato_id'] === (string)$c['id'] ? 'selected' : '' ?>">
              <?php if (!empty($c['foto_path'])): ?>
                <img class="candidate-photo" src="<?= e(url('public/uploads/candidatos/' . $c['foto_path'])) ?>" alt="<?= e($c['nome']) ?>">
              <?php else: ?>
                <div class="candidate-photo d-flex align-items-center justify-content-center">Sem foto</div>
              <?php endif; ?>
              <div class="fw-semibold"><?= e($c['nome']) ?></div>
              <small class="text-muted">CPF: <?= e(maskCpfPublic($c['cpf'])) ?></small>
              <small class="text-muted d-block">Turno: <?= e($c['turno']) ?> | Setor: <?= e($c['setor']) ?></small>
              <div class="form-check mt-2 d-flex justify-content-center">
                <input class="form-check-input" type="radio" name="candidato_id" value="<?= (int)$c['id'] ?>" <?= $data['candidato_id'] === (string)$c['id'] ? 'checked' : '' ?>>
              </div>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3 d-flex gap-2 wizard-actions"><button type="button" class="btn btn-outline-secondary" data-prev>Voltar</button><button type="submit" class="btn btn-friato">Confirmar voto</button></div>
    </section>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
