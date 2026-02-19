<?php
require_once __DIR__ . '/../includes/functions.php';
$pdo = pdo();
$open = $pdo->query("SELECT nome, slug, descricao, periodo_inicio, periodo_fim FROM eleicoes WHERE NOW() BETWEEN periodo_inicio AND periodo_fim ORDER BY periodo_inicio DESC")->fetchAll();
$pageTitle = 'Início';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 align-items-stretch">
  <div class="col-lg-7">
    <div class="card hero-card p-4 p-lg-5 h-100">
      <div class="d-flex gap-3 align-items-center mb-4">
        <?= logoOrPlaceholder('logo-friato-red.png', 'Logo Friato', 'logo-hero') ?>
        <?= logoOrPlaceholder('logo-cipa.png', 'Logo CIPA', 'logo-hero') ?>
      </div>
      <span class="chip mb-3"><i class="bi bi-shield-check"></i> Eleição interna segura</span>
      <h1 class="hero-title display-5 mb-3">Votação CIPA</h1>
      <p class="hero-subtitle fs-5">Participe da eleição ativa da sua unidade usando o link compartilhado pela empresa.</p>
      <?php if ($open): ?>
      <div class="mt-3 d-grid gap-2">
        <?php foreach($open as $e): ?>
          <a class="btn btn-friato" href="<?= e(url('public/votar.php?eleicao=' . urlencode($e['slug']))) ?>"><i class="bi bi-check2-square"></i> Iniciar: <?= e($e['nome']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="alert alert-warning mt-3">Nenhuma eleição ativa no momento.</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card glass-card p-4 h-100">
      <h2 class="h4 mb-3 text-danger">Como funciona</h2>
      <ol class="mb-0 ps-3">
        <li class="mb-2">Informe nome, CPF e turno.</li>
        <li class="mb-2">Preencha telefone, empresa e setor.</li>
        <li class="mb-2">Escolha um único candidato.</li>
        <li class="mb-2">Confirme o voto e receba número para sorteio.</li>
      </ol>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
