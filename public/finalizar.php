<?php
require_once __DIR__ . '/../includes/functions.php';

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    flash('warning', 'Token de acesso inválido.');
    redirect(url('public/votar.php'));
}

$stmt = pdo()->prepare('SELECT codigo_sorteio, created_at FROM votos WHERE token = :t LIMIT 1');
$stmt->execute(['t' => $token]);
$vote = $stmt->fetch();
if (!$vote) {
    flash('warning', 'Registro não encontrado.');
    redirect(url('public/votar.php'));
}

$pageTitle = 'Número do Sorteio';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card hero-card p-4 p-lg-5 text-center">
  <h2 class="h3">Seu número para o sorteio</h2>
  <div class="number-huge my-3" id="luckyNumber"><?= e($vote['codigo_sorteio']) ?></div>
  <p class="text-secondary mb-4"><?= e(FINAL_MESSAGE) ?></p>
  <div class="d-flex gap-2 justify-content-center flex-wrap">
    <button class="btn btn-friato" type="button" onclick="saveNumberImage('<?= e($vote['codigo_sorteio']) ?>')"><i class="bi bi-download"></i> Salvar imagem do número (PNG)</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('public/index.php')) ?>">Voltar ao início</a>
  </div>
  <canvas id="drawNumberCanvas" width="1000" height="560" class="d-none"></canvas>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
