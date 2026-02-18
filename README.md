# Votação CIPA – Friato (PHP 8 + MySQL)

Sistema de votação com UI moderna, wizard/stepper em 4 etapas, área administrativa e segurança básica para ambiente corporativo.

## Stack
- PHP 8+
- MySQL (phpMyAdmin)
- HTML/CSS + Bootstrap 5 (CDN)
- JS vanilla + SweetAlert2 + Chart.js (CDN)

## Estrutura

```bash
/public
  index.php
  votar.php
  finalizar.php
  assets/
    css/style.css
    js/app.js
    img/logo-friato.png
    img/logo-cipa.png
  uploads/candidatos/
/admin
  login.php
  logout.php
  dashboard.php
  candidatos.php
  eleitores.php
  eleicoes.php
  votos.php
  sorteio.php
/includes
  header.php
  footer.php
  functions.php
  db.php
database.sql
README.md
```

## Instalação (XAMPP/WAMP)
1. Copie o projeto para `htdocs/cipa-votacao`.
2. No phpMyAdmin, importe `database.sql`.
3. Ajuste credenciais no arquivo `includes/db.php`:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Acesse:
   - Público: `http://localhost/cipa-votacao/public/index.php`
   - Admin: `http://localhost/cipa-votacao/admin/login.php`

## Login admin inicial
- Usuário: `admin`
- Senha: `admin123`

> Troque a senha após o primeiro acesso gerando hash com `password_hash`.

## Logos Friato/CIPA
- Coloque os arquivos reais em:
  - `/public/assets/img/logo-friato.png`
  - `/public/assets/img/logo-cipa.png`
- Se não houver arquivos válidos, o sistema mostra placeholder visual automaticamente.
- Este repositório não inclui imagens binárias de logo; os arquivos em `/public/assets/img/` são placeholders textuais para compatibilidade de ambiente.

## Fluxo de votação (wizard)
1. Instruções
2. Identificação (nome + CPF + turno)
3. Contato e lotação (telefone, empresa, setor)
4. Escolha de 1 candidato + confirmação

Após voto:
- Gera código único de sorteio (10000–99999)
- Salva token único (sem expor ID incremental)
- Redireciona para `finalizar.php?token=...`

## Segurança implementada
- PDO com prepared statements
- `utf8mb4`
- `htmlspecialchars` na saída
- CPF válido (dígitos verificadores)
- CPF do eleitor `UNIQUE` (1 voto por CPF)
- Código de sorteio `UNIQUE`
- Token de finalização `UNIQUE`
- Upload de foto com:
  - validação MIME (JPG/PNG)
  - limite de 2MB
  - nome de arquivo único
- `public/uploads/candidatos/.htaccess` para bloquear execução de scripts

## Personalização rápida
- Texto da página final: constante `FINAL_MESSAGE` em `includes/db.php`.
- Cores da marca: variáveis CSS em `public/assets/css/style.css`.

## Regras adicionais implementadas
- Apenas eleitores importados em `eleitores_autorizados` podem votar.
- Importação de eleitores via CSV no admin (`nome,cpf,empresa,gerencia,supervisao,nome_cc`).
- Cadastro de candidatos com campos extras: `turno` e `setor`.
- Página de sorteio no admin para selecionar aleatoriamente um votante pelo código.
- Sorteio admin com animação de contagem/revelação, modo tela cheia e exibição protegida de CPF/voto (revelação sob clique).
- Relatório de votos com filtro por código de sorteio e exportações CSV separadas:
  - votos
  - candidatos + total de votos


## Importação de eleitores (CSV)
- Tela: `admin/eleitores.php`
- Clique em **Baixar modelo CSV** para usar o formato correto.
- Colunas esperadas (ordem): `nome,cpf,empresa,gerencia,supervisao,nome_cc`.
- Opções na tela:
  - marcar/desmarcar se a 1ª linha é cabeçalho;
  - escolher separador `,` ou `;`.
- Arquivos XLS/XLSX: exporte para CSV antes da importação.

Exemplo de cabeçalho:
```csv
nome,cpf,empresa,gerencia,supervisao,nome_cc
```


## Gestão de Eleições (novo)
- Crie eleições em `admin/eleicoes.php` com:
  - nome (ex.: Eleição CIPA Friato 2026)
  - período início/fim
  - descrição pública
  - justificativa para não elegíveis
- Ao criar, o sistema gera um **link de compartilhamento** com slug, ex:
  - `public/votar.php?eleicao=eleicao-cipa-friato-2026`
- Permissões da eleição podem ser definidas por grupos de eleitores:
  - `gerencia`
  - `supervisao`
  - `nome_cc`
- Somente eleitores dessas combinações poderão votar.

