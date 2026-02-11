CREATE DATABASE IF NOT EXISTS cipa_votacao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cipa_votacao;

CREATE TABLE IF NOT EXISTS candidatos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    foto_path VARCHAR(255) NOT NULL,
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

CREATE TABLE IF NOT EXISTS votos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    eleitor_nome VARCHAR(150) NOT NULL,
    eleitor_cpf VARCHAR(11) NOT NULL UNIQUE,
    eleitor_telefone VARCHAR(11) NOT NULL,
    eleitor_empresa VARCHAR(150) NOT NULL,
    eleitor_setor VARCHAR(150) NOT NULL,
    candidato_id INT UNSIGNED NOT NULL,
    codigo_sorteio VARCHAR(5) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_votos_candidato (candidato_id),
    INDEX idx_votos_empresa (eleitor_empresa),
    INDEX idx_votos_setor (eleitor_setor),
    INDEX idx_votos_created_at (created_at),
    CONSTRAINT fk_votos_candidato
        FOREIGN KEY (candidato_id)
        REFERENCES candidatos(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$0MO5tgUQz3uxYSmD2kbeVOIgsWMvDWrvY1Syt4F.uKJ88ec74hTKG')
ON DUPLICATE KEY UPDATE username = VALUES(username);
