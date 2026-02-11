<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Início - Votação CIPA Friato';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Como funciona a votação da CIPA</h2>
    <ol class="steps">
        <li>Informe nome e CPF.</li>
        <li>Se CPF válido, informe telefone, empresa e setor.</li>
        <li>Escolha <strong>1 candidato</strong>.</li>
        <li>Confirme os dados e o voto.</li>
        <li>Receba um número aleatório único de 5 dígitos para sorteio.</li>
    </ol>

    <a class="btn btn-primary" href="<?= e(url('public/votar.php')) ?>">Iniciar votação</a>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
