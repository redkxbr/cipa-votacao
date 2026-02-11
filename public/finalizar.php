<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$voteId = isset($_GET['v']) && ctype_digit($_GET['v']) ? (int) $_GET['v'] : 0;
$token = $_GET['t'] ?? '';

if ($voteId <= 0 || $token === '') {
    setFlash('error', 'Acesso inválido à página final.');
    redirect(url('public/votar.php'));
}

$expected = base64_encode(hash_hmac('sha256', (string) $voteId, APP_KEY, true));
if (!hash_equals($expected, $token)) {
    setFlash('error', 'Token inválido.');
    redirect(url('public/votar.php'));
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT codigo_sorteio FROM votos WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $voteId]);
$vote = $stmt->fetch();

if (!$vote) {
    setFlash('error', 'Voto não encontrado.');
    redirect(url('public/votar.php'));
}

$pageTitle = 'Número do Sorteio';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card text-center">
    <h2>Seu número para o sorteio:</h2>
    <p class="lottery-number" id="lotteryNumber"><?= e($vote['codigo_sorteio']) ?></p>
    <p><?= e(MENSAGEM_FINAL) ?></p>

    <button class="btn btn-primary" id="saveImageBtn" type="button">Salvar imagem do número</button>
    <a class="btn" href="<?= e(url('public/index.php')) ?>">Voltar ao início</a>

    <canvas id="lotteryCanvas" width="1000" height="600" class="hidden"></canvas>
</section>

<script>
const btn = document.getElementById('saveImageBtn');
const canvas = document.getElementById('lotteryCanvas');
const number = document.getElementById('lotteryNumber').textContent.trim();

btn.addEventListener('click', function () {
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    ctx.fillStyle = '#1a2a44';
    ctx.font = 'bold 64px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('CIPA Friato', canvas.width / 2, 140);

    ctx.fillStyle = '#0d7a4f';
    ctx.font = 'bold 140px Arial';
    ctx.fillText(number, canvas.width / 2, 340);

    ctx.fillStyle = '#333';
    ctx.font = '32px Arial';
    ctx.fillText('Guarde este número para o sorteio', canvas.width / 2, 450);

    const link = document.createElement('a');
    link.download = 'numero_sorteio_' + number + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
