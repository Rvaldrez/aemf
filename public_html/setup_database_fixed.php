<?php
// setup_database_fixed.php
// Versão corrigida - sem a coluna icone

require_once 'includes/database.php';

$db = Database::getInstance()->getConnection();

try {
    // Limpar tabelas existentes se houver problemas
    $db->exec("DROP TABLE IF EXISTS referencias");
    $db->exec("DROP TABLE IF EXISTS transacoes");
    $db->exec("DROP TABLE IF EXISTS categorias");
    
    // Criar tabela de categorias (SEM a coluna icone)
    $sql = "
    CREATE TABLE categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        tipo ENUM('receita', 'despesa_aemf', 'despesa_pf') NOT NULL,
        grupo VARCHAR(50),
        cor VARCHAR(7) DEFAULT '#17a2b8',
        ativo BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✓ Tabela categorias criada<br>";
    
    // Criar tabela de transações
    $sql = "
    CREATE TABLE transacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data DATE NOT NULL,
        descricao VARCHAR(500) NOT NULL,
        valor DECIMAL(15,2) NOT NULL,
        tipo ENUM('credito', 'debito') NOT NULL,
        categoria_id INT,
        classificacao ENUM('aemf', 'pf', 'receita') DEFAULT NULL,
        mes_referencia VARCHAR(7),
        documento_origem VARCHAR(255),
        hash_unico VARCHAR(64) UNIQUE,
        beneficiario VARCHAR(255) DEFAULT NULL,
        conciliado TINYINT(1) DEFAULT 0,
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
        INDEX idx_data (data),
        INDEX idx_mes (mes_referencia),
        INDEX idx_hash (hash_unico)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✓ Tabela transacoes criada<br>";
    
    // Criar tabela de referências
    $sql = "
    CREATE TABLE referencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        padrao VARCHAR(255) NOT NULL,
        categoria_id INT,
        tipo_transacao VARCHAR(50),
        observacoes TEXT,
        confianca DECIMAL(3,2) DEFAULT 1.00,
        uso_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
        INDEX idx_padrao (padrao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✓ Tabela referencias criada<br>";
    
    // Inserir categorias padrão (SEM a coluna icone)
    $sql = "
    INSERT INTO categorias (nome, tipo, grupo, cor) VALUES
    -- Receitas
    ('Aportes de Capital', 'receita', 'Receitas', '#28a745'),
    ('Rendimentos', 'receita', 'Receitas', '#28a745'),
    
    -- Despesas AEMF
    ('Despesas com Escritório', 'despesa_aemf', 'Operacional', '#17a2b8'),
    ('Despesas com Pessoal', 'despesa_aemf', 'Operacional', '#17a2b8'),
    ('Contabilidade', 'despesa_aemf', 'Operacional', '#17a2b8'),
    ('Assessoria Jurídica', 'despesa_aemf', 'Operacional', '#dc3545'),
    ('Taxas e Despesas Financeiras', 'despesa_aemf', 'Operacional', '#6c757d'),
    ('Impostos', 'despesa_aemf', 'Operacional', '#dc3545'),
    ('Energia Elétrica', 'despesa_aemf', 'Operacional', '#ffc107'),
    ('Telefonia/Internet', 'despesa_aemf', 'Operacional', '#17a2b8'),
    ('Aluguel/Armazenamento', 'despesa_aemf', 'Operacional', '#6c757d'),
    ('Administração', 'despesa_aemf', 'Operacional', '#17a2b8'),
    ('Serviços', 'despesa_aemf', 'Operacional', '#6c757d'),
    ('Estacionamento', 'despesa_aemf', 'Operacional', '#6c757d'),
    
    -- Despesas PF
    ('Gastos com Veículos', 'despesa_pf', 'Pessoa Física', '#ffc107'),
    ('Cartão Corporativo Wagner SP', 'despesa_pf', 'Pessoa Física', '#fd7e14'),
    ('Cartão Corporativo Wagner MG', 'despesa_pf', 'Pessoa Física', '#dc3545'),
    ('Outras Despesas PF', 'despesa_pf', 'Pessoa Física', '#6c757d');";
    
    $db->exec($sql);
    echo "✓ Categorias inseridas<br>";
    
    // Inserir referências
    $sql = "
    INSERT INTO referencias (padrao, categoria_id, tipo_transacao) VALUES
    ('SYLVIA MARIA MARTORELLI', 4, 'PIX'),
    ('ACG ADMINISTRADORA', 12, 'PIX'),
    ('MAFRA SOCIEDADE', 6, 'PIX'),
    ('MAFRA ADVOGADOS', 6, 'PIX'),
    ('PERSONAL', 13, 'BOLETO'),
    ('GOODSTORAGE', 11, 'BOLETO'),
    ('ELETROPAULO', 9, 'BOLETO'),
    ('PAVANELLO CONTABILIDADE', 5, 'BOLETO'),
    ('BRASIL ADMINISTRACAO', 12, 'BOLETO'),
    ('INFORM IMOVEIS', 11, 'BOLETO'),
    ('CERSAN PARK', 14, 'PIX'),
    ('NELSON GONSALVES', 13, 'PIX'),
    ('EMBRATEL', 10, 'CONCESSIONARIA'),
    ('SAMM TECNOLOGIA', 13, 'BOLETO'),
    ('ROBERTO VALDREZ', 13, 'PIX'),
    ('TED 237.1233.ANTONIO E D', 1, 'TED'),
    ('ANTONIO E D', 1, 'TED'),
    ('DARF', 8, 'TRIBUTO'),
    ('TAR CONTA CERTA', 7, 'TARIFA'),
    ('TAR CARTAO SERVICO', 7, 'TARIFA'),
    ('DEB AUTOR SEM PARAR', 14, 'DEBITO'),
    ('SEM PARAR', 14, 'DEBITO'),
    ('VIVO FIXO', 10, 'DEBITO'),
    ('SISPAG PIX QR-CODE', 13, 'PIX'),
    ('REND PAGO APLIC', 2, 'RENDIMENTO'),
    ('VB-SERVICOS', 13, 'PIX'),
    ('VB SERVICOS', 13, 'PIX'),
    ('CEF MATRIZ', 7, 'PIX'),
    ('SECRETARIA FAZENDA', 8, 'PIX');";
    
    $db->exec($sql);
    echo "✓ Referências inseridas<br>";
    
    echo "<br><strong>✅ Setup concluído com sucesso!</strong>";
    echo "<br><br>⚠️ <strong>IMPORTANTE:</strong> Delete ou renomeie este arquivo por segurança!";
    
} catch(PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
    echo "<br>Código do erro: " . $e->getCode();
}
?>