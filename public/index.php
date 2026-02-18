<?php
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 align-items-stretch">
  <div class="col-lg-7">
    <div class="card hero-card p-4 p-lg-5 h-100">
      <div class="d-flex gap-3 align-items-center mb-4">
        <?= logoOrPlaceholder('logo-friato.png', 'Logo Friato', 'logo-hero') ?>
        <?= logoOrPlaceholder('logo-cipa.png', 'Logo CIPA', 'logo-hero') ?>
      </div>
      <h1 class="hero-title display-5 mb-3">Votação CIPA – Friato</h1>
      <p class="text-secondary fs-5">Participe do processo eleitoral interno com um fluxo rápido, seguro e transparente.</p>
      <div class="mt-3">
        <a class="btn btn-friato btn-lg" href="<?= e(url('public/votar.php')) ?>"><i class="bi bi-check2-square"></i> Iniciar votação</a>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card glass-card p-4 h-100">
      <h2 class="h4 mb-3">Como funciona</h2>
      <ol class="mb-0 ps-3">
        <li class="mb-2">Informe seu nome e CPF.</li>
        <li class="mb-2">Preencha telefone, empresa e setor.</li>
        <li class="mb-2">Escolha um único candidato.</li>
        <li class="mb-2">Confirme seu voto.</li>
        <li>Receba um número único para sorteio.</li>
      </ol>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
