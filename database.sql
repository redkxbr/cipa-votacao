CREATE DATABASE IF NOT EXISTS cipa_votacao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cipa_votacao;

CREATE TABLE IF NOT EXISTS candidatos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cpf VARCHAR(11) NOT NULL UNIQUE,
  turno VARCHAR(30) NOT NULL DEFAULT 'Outro',
  setor VARCHAR(150) NOT NULL DEFAULT '',
  foto_path VARCHAR(255) DEFAULT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_candidatos_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eleitores_autorizados (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  cpf VARCHAR(11) NOT NULL UNIQUE,
  empresa VARCHAR(150) NOT NULL,
  gerencia VARCHAR(150) NOT NULL DEFAULT '',
  supervisao VARCHAR(150) NOT NULL DEFAULT '',
  nome_cc VARCHAR(150) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_eleitores_empresa (empresa),
  INDEX idx_eleitores_gerencia (gerencia),
  INDEX idx_eleitores_supervisao (supervisao),
  INDEX idx_eleitores_nome_cc (nome_cc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eleicoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(200) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  descricao TEXT NULL,
  justificativa_negacao TEXT NULL,
  periodo_inicio DATETIME NOT NULL,
  periodo_fim DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_eleicoes_periodo (periodo_inicio, periodo_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eleicao_permissoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  eleicao_id INT UNSIGNED NOT NULL,
  tipo ENUM('gerencia','supervisao','nome_cc') NOT NULL,
  valor VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_eleicao_permissao (eleicao_id, tipo, valor),
  INDEX idx_eleicao_perm_tipo_valor (tipo, valor),
  CONSTRAINT fk_eleicao_perm_eleicao FOREIGN KEY (eleicao_id)
    REFERENCES eleicoes(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS votos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  eleicao_id INT UNSIGNED NOT NULL,
  eleitor_nome VARCHAR(150) NOT NULL,
  eleitor_cpf VARCHAR(11) NOT NULL,
  eleitor_turno VARCHAR(30) NOT NULL,
  eleitor_telefone VARCHAR(11) NOT NULL,
  eleitor_empresa VARCHAR(150) NOT NULL,
  eleitor_setor VARCHAR(150) NOT NULL,
  candidato_id INT UNSIGNED NOT NULL,
  codigo_sorteio VARCHAR(5) NOT NULL UNIQUE,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voto_eleicao_cpf (eleicao_id, eleitor_cpf),
  INDEX idx_votos_eleicao (eleicao_id),
  INDEX idx_votos_candidato (candidato_id),
  INDEX idx_votos_codigo (codigo_sorteio),
  CONSTRAINT fk_votos_eleicao FOREIGN KEY (eleicao_id)
    REFERENCES eleicoes(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_votos_candidato FOREIGN KEY (candidato_id)
    REFERENCES candidatos(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$0MO5tgUQz3uxYSmD2kbeVOIgsWMvDWrvY1Syt4F.uKJ88ec74hTKG')
ON DUPLICATE KEY UPDATE username = VALUES(username);
