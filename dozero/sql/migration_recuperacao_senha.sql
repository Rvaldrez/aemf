-- =============================================================================
-- AEMFPAR — Migration: email column + password_resets table
-- Compatível com MySQL 5.7 e MySQL 8.x
-- =============================================================================
-- Execute este script no banco de dados da aplicação para habilitar
-- a funcionalidade de recuperação de senha.
--
-- O código PHP já contém guardas de migração automáticas (SHOW COLUMNS /
-- CREATE TABLE IF NOT EXISTS), portanto essas alterações também são aplicadas
-- automaticamente na primeira execução após o deploy.
-- Use este script apenas se quiser aplicá-las manualmente com antecedência.
-- =============================================================================

-- =============================================================================
-- PASSO 1 — Adiciona o campo email à tabela usuarios
-- =============================================================================
-- Verifica se a coluna já existe antes de adicionar.
-- O bloco abaixo usa uma Stored Procedure temporária para ser compatível com
-- MySQL 5.7 (que não suporta "ADD COLUMN IF NOT EXISTS").

DROP PROCEDURE IF EXISTS sp_add_email_column;

DELIMITER $$
CREATE PROCEDURE sp_add_email_column()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM   INFORMATION_SCHEMA.COLUMNS
        WHERE  TABLE_SCHEMA = DATABASE()
          AND  TABLE_NAME   = 'usuarios'
          AND  COLUMN_NAME  = 'email'
    ) THEN
        ALTER TABLE usuarios
            ADD COLUMN email VARCHAR(180) DEFAULT NULL AFTER username;
    END IF;
END$$
DELIMITER ;

CALL sp_add_email_column();
DROP PROCEDURE IF EXISTS sp_add_email_column;

-- =============================================================================
-- PASSO 2 — Cria a tabela de tokens de redefinição de senha
-- =============================================================================

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT           AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT           NOT NULL,
    token      VARCHAR(64)   NOT NULL,
    expires_at DATETIME      NOT NULL,
    usado      TINYINT(1)    DEFAULT 0,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_pr_token   UNIQUE  (token),
    CONSTRAINT fk_pr_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_pr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- (Opcional) Preencha os e-mails dos usuários existentes após rodar o script:
-- =============================================================================
-- UPDATE usuarios SET email = 'seu@email.com'   WHERE username = 'admin';
-- UPDATE usuarios SET email = 'outro@email.com'  WHERE username = 'antonio';
-- =============================================================================
