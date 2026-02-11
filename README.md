# Sistema de Votação CIPA - Friato (PHP + MySQL)

Projeto simples para votação da CIPA com área pública e área administrativa.

## Estrutura de pastas

```bash
/
  config/
    config.php
    auth.php
  includes/
    header.php
    footer.php
    functions.php
  public/
    index.php
    votar.php
    finalizar.php
    assets/
      style.css
    uploads/
      candidatos/
        .htaccess
  admin/
    login.php
    logout.php
    dashboard.php
    candidatos.php
    votos.php
  database.sql
  README.md
```

## Requisitos

- PHP 8+
- MySQL 5.7+ ou MariaDB compatível
- Apache (XAMPP/WAMP/LAMP)

## Como configurar

1. Copie o projeto para a pasta web do servidor (ex: `htdocs/cipa-votacao`).
2. Crie um banco no MySQL (ou apenas importe o script, ele já cria o banco `cipa_votacao`).
3. Importe o arquivo `database.sql` no phpMyAdmin.
4. Edite `config/config.php` com host, banco, usuário e senha do MySQL.
5. Ajuste a constante `APP_KEY` para um valor secreto em produção.
6. Garanta permissão de escrita em `public/uploads/candidatos`.

## Rodando no XAMPP/WAMP

- Inicie Apache e MySQL.
- Acesse: `http://localhost/cipa-votacao/public/index.php`
- Admin: `http://localhost/cipa-votacao/admin/login.php`

## Login inicial do admin

- **Usuário:** `admin`
- **Senha:** `admin123`

> Altere imediatamente a senha após o primeiro acesso (via SQL, gerando novo hash com `password_hash`).

Exemplo para gerar hash:

```php
<?php echo password_hash('NovaSenhaForte', PASSWORD_DEFAULT); ?>
```

Atualize no banco:

```sql
UPDATE admins SET password_hash = 'COLE_O_HASH_AQUI' WHERE username = 'admin';
```

## Funcionalidades implementadas

- Página pública de instruções com passo a passo.
- Votação pública com validações server-side:
  - CPF válido (dígitos verificadores)
  - Telefone com 10/11 dígitos
  - Campos obrigatórios
- Regra de voto único por CPF (UNIQUE no banco + bloqueio na aplicação).
- Seleção de 1 candidato ativo (radio button).
- Geração de código de sorteio único de 5 dígitos.
- Página final com número do sorteio e botão para baixar imagem PNG (canvas).
- Admin com login por sessão.
- CRUD de candidatos (nome, CPF, foto, status ativo/inativo).
- Relatório de votos com filtros (empresa, setor, data) + exportação CSV.

## Observações de segurança

- Prepared statements com PDO.
- Sanitização de saída com `htmlspecialchars` (helper `e()`).
- Upload de foto com validação MIME e tamanho máximo (2MB).
- Nome de arquivo único para upload.
- Bloqueio de execução de script em diretório de upload via `.htaccess`.
- Token simples com HMAC para acesso à página final.

## Importante

- Este projeto é educacional e simples; para produção, recomenda-se:
  - CSRF token em todos os formulários.
  - Rate limiting e logs de auditoria.
  - HTTPS obrigatório.
  - Política de senha forte e troca periódica.
