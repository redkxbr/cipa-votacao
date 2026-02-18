<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pdo = pdo();

$winner = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sortear') {
  $stmt = $pdo->query('SELECT v.codigo_sorteio, v.eleitor_nome, v.eleitor_cpf, v.eleitor_empresa, v.eleitor_turno, v.eleitor_setor, c.nome candidato_nome
    FROM votos v
    INNER JOIN candidatos c ON c.id = v.candidato_id
    ORDER BY RAND() LIMIT 1');
  $winner = $stmt->fetch();
  if (!$winner) {
    flash('warning', 'Ainda não há votos para realizar sorteio.');
    redirect(url('admin/sorteio.php'));
  }
}

$pageTitle = 'Sorteio';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h3 mb-0 text-danger">Sorteio de prêmio</h1>
  <a class="btn btn-outline-secondary" href="<?= e(url('admin/dashboard.php')) ?>">Voltar</a>
</div>

<div class="card p-3 p-md-4 sorteio-stage">
  <div class="text-center mb-3">
    <h2 class="h4 mb-1">Momento do sorteio</h2>
    <p class="text-muted mb-0">Visual para projeção em tela grande, com animação dinâmica.</p>
  </div>

  <form method="post" class="text-center mb-4" id="drawForm">
    <input type="hidden" name="action" value="sortear">
    <button class="btn btn-friato btn-lg px-4" type="submit" id="drawBtn">Iniciar sorteio</button>
  </form>

  <div id="drawAnimation" class="draw-animation <?= $winner ? 'd-none' : '' ?>">
    <div class="countdown" id="countdown">3</div>
    <div class="fake-code" id="fakeCode">00000</div>
    <div class="small text-muted">Buscando código no banco de votos...</div>
  </div>

  <?php if ($winner): ?>
  <div id="winnerCard" class="winner-card text-center">
    <div class="winner-badge">SORTEADO</div>
    <div class="winner-code" id="winnerCode" data-code="<?= e($winner['codigo_sorteio']) ?>">00000</div>
    <h3 class="winner-name mb-2"><?= e($winner['eleitor_nome']) ?></h3>

    <div class="winner-meta">
      <p class="mb-1"><strong>Empresa:</strong> <?= e($winner['eleitor_empresa']) ?></p>
      <p class="mb-1"><strong>Turno:</strong> <?= e($winner['eleitor_turno']) ?></p>
      <p class="mb-2"><strong>Setor:</strong> <?= e($winner['eleitor_setor']) ?></p>
    </div>

    <div class="d-flex justify-content-center flex-wrap gap-2 mt-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleCpfBtn" data-hidden="1" data-cpf="<?= e(formatCpf($winner['eleitor_cpf'])) ?>">Mostrar CPF</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleVoteBtn" data-hidden="1" data-voto="<?= e($winner['candidato_nome']) ?>">Mostrar voto</button>
    </div>

    <div class="mt-3">
      <p id="cpfField" class="sensitive-field mb-2">CPF: •••.•••.•••-••</p>
      <p id="voteField" class="sensitive-field mb-0">Voto em: oculto</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
(function () {
  const form = document.getElementById('drawForm');
  const drawBtn = document.getElementById('drawBtn');
  const countdown = document.getElementById('countdown');
  const fakeCode = document.getElementById('fakeCode');

  if (form && countdown && fakeCode) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      drawBtn.disabled = true;
      drawBtn.textContent = 'Sorteando...';

      let c = 3;
      countdown.textContent = String(c);

      const codeTimer = setInterval(() => {
        fakeCode.textContent = String(Math.floor(Math.random() * 90000) + 10000).padStart(5, '0');
      }, 80);

      const countdownTimer = setInterval(() => {
        c -= 1;
        if (c <= 0) {
          countdown.textContent = 'GO!';
          clearInterval(countdownTimer);
          setTimeout(() => {
            clearInterval(codeTimer);
            form.submit();
          }, 700);
        } else {
          countdown.textContent = String(c);
        }
      }, 850);
    });
  }

  const winnerCode = document.getElementById('winnerCode');
  if (winnerCode) {
    const finalCode = winnerCode.dataset.code || '00000';
    let loops = 0;
    const reveal = setInterval(() => {
      winnerCode.textContent = String(Math.floor(Math.random() * 90000) + 10000).padStart(5, '0');
      loops++;
      if (loops > 16) {
        clearInterval(reveal);
        winnerCode.textContent = finalCode;
      }
    }, 90);
  }

  const cpfBtn = document.getElementById('toggleCpfBtn');
  const voteBtn = document.getElementById('toggleVoteBtn');
  const cpfField = document.getElementById('cpfField');
  const voteField = document.getElementById('voteField');

  if (cpfBtn && cpfField) {
    cpfBtn.addEventListener('click', function () {
      const hidden = cpfBtn.dataset.hidden === '1';
      if (hidden) {
        cpfField.textContent = 'CPF: ' + (cpfBtn.dataset.cpf || '');
        cpfBtn.textContent = 'Ocultar CPF';
        cpfBtn.dataset.hidden = '0';
      } else {
        cpfField.textContent = 'CPF: •••.•••.•••-••';
        cpfBtn.textContent = 'Mostrar CPF';
        cpfBtn.dataset.hidden = '1';
      }
    });
  }

  if (voteBtn && voteField) {
    voteBtn.addEventListener('click', function () {
      const hidden = voteBtn.dataset.hidden === '1';
      if (hidden) {
        voteField.textContent = 'Voto em: ' + (voteBtn.dataset.voto || '');
        voteBtn.textContent = 'Ocultar voto';
        voteBtn.dataset.hidden = '0';
      } else {
        voteField.textContent = 'Voto em: oculto';
        voteBtn.textContent = 'Mostrar voto';
        voteBtn.dataset.hidden = '1';
      }
    });
  }
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
