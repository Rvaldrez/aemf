-- =============================================================================
-- AEMFPAR — Migration: email column + password_resets table
-- =============================================================================
-- Execute este script no banco de dados da aplicação para habilitar
-- a funcionalidade de recuperação de senha.
--
-- O código PHP já contém guardas de migração (SHOW COLUMNS / CREATE TABLE IF NOT EXISTS),
-- portanto essas alterações também são aplicadas automaticamente na primeira
-- execução após o deploy. Use este script para aplicá-las manualmente com
-- antecedência, se preferir.
-- =============================================================================

-- 1. Adiciona o campo email à tabela usuarios (sem sobrescrever se já existir)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS email VARCHAR(180) DEFAULT NULL AFTER username;

-- (Para servidores MySQL < 8.0 que não suportam IF NOT EXISTS em ALTER TABLE:
--  execute apenas se a coluna ainda não existir — verifique com SHOW COLUMNS FROM usuarios.)

-- 2. Cria a tabela de tokens de redefinição de senha
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT          NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    usado      TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pr_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- (Opcional) Preencha os e-mails dos usuários existentes:
-- =============================================================================
-- UPDATE usuarios SET email = 'seu@email.com'  WHERE username = 'admin';
-- UPDATE usuarios SET email = 'outro@email.com' WHERE username = 'antonio';
-- =============================================================================
