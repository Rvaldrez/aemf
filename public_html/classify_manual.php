<?php
// classify_manual.php - Interface para classificação manual
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance()->getConnection();

// Busca transações não classificadas
$sql = "
    SELECT t.*, 
           COUNT(*) OVER() as total_pendentes
    FROM transacoes t
    WHERE t.categoria_id IS NULL 
       OR t.categoria_id = 0
       OR t.classificacao IS NULL
    ORDER BY t.data DESC
    LIMIT 1
";

$stmt = $db->query($sql);
$transacao = $stmt->fetch();

// Busca categorias disponíveis
$sqlCat = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY tipo, nome";
$stmtCat = $db->query($sqlCat);
$categorias = $stmtCat->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classificação Manual - AEMFPAR</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .progress-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .progress-bar {
            width: 200px;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: white;
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        .content {
            padding: 40px;
        }
        
        .transaction-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #17a2b8;
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }
        
        .transaction-date {
            font-size: 14px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .transaction-value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .transaction-value.credit {
            color: #28a745;
        }
        
        .transaction-value.debit {
            color: #dc3545;
        }
        
        .transaction-description {
            font-size: 18px;
            color: #2c3e50;
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .classification-section {
            margin-top: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-groups {
            display: grid;
            gap: 20px;
        }
        
        .category-group {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
        }
        
        .group-title {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .category-btn {
            padding: 12px 16px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-btn:hover {
            border-color: #17a2b8;
            background: #f0f8fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .category-btn.selected {
            border-color: #17a2b8;
            background: #17a2b8;
            color: white;
        }
        
        .category-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .category-receita .category-icon {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .category-despesa_aemf .category-icon {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        .category-despesa_pf .category-icon {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .save-pattern-section {
            margin-top: 25px;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 10px;
            display: none;
        }
        
        .save-pattern-section.show {
            display: block;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .pattern-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: #17a2b8;
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            background: #138496;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-skip {
            background: #ffc107;
            color: #212529;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
        }
        
        .empty-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .suggestions {
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border: 1px solid #ffeaa7;
            display: none;
        }
        
        .suggestions.show {
            display: block;
        }
        
        .suggestion-title {
            font-size: 14px;
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
        }
        
        .suggestion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .suggestion-chip {
            padding: 6px 12px;
            background: white;
            border: 1px solid #ffc107;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .suggestion-chip:hover {
            background: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($transacao): ?>
        <div class="header">
            <h1><i class="fas fa-tags"></i> Classificação Manual de Transações</h1>
            <div class="progress-info">
                <span>Pendentes: <?php echo $transacao['total_pendentes']; ?></span>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo ((100 - $transacao['total_pendentes']) / 100) * 100; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="transaction-card">
                <div class="transaction-header">
                    <div>
                        <div class="transaction-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d/m/Y', strtotime($transacao['data'])); ?>
                        </div>
                    </div>
                    <div class="transaction-value <?php echo $transacao['tipo'] == 'credito' ? 'credit' : 'debit'; ?>">
                        <?php echo $transacao['tipo'] == 'credito' ? '+' : '-'; ?>
                        R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                    </div>
                </div>
                
                <div class="transaction-description">
                    <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($transacao['descricao']); ?>
                </div>
                
                <div id="suggestions" class="suggestions">
                    <div class="suggestion-title">
                        <i class="fas fa-lightbulb"></i> Sugestões baseadas em transações similares:
                    </div>
                    <div class="suggestion-list" id="suggestionList"></div>
                </div>
            </div>
            
            <div class="classification-section">
                <div class="section-title">
                    <i class="fas fa-folder-tree"></i>
                    Selecione a categoria apropriada:
                </div>
                
                <div class="category-groups">
                    <?php
                    $grupos = [];
                    foreach ($categorias as $cat) {
                        $tipo = $cat['tipo'];
                        if (!isset($grupos[$tipo])) {
                            $grupos[$tipo] = [];
                        }
                        $grupos[$tipo][] = $cat;
                    }
                    
                    foreach ($grupos as $tipo => $cats):
                        $tipoLabel = [
                            'receita' => 'Receitas',
                            'despesa_aemf' => 'Despesas AEMF I (Operacionais)',
                            'despesa_pf' => 'Despesas Pessoa Física'
                        ][$tipo] ?? $tipo;
                    ?>
                    <div class="category-group">
                        <div class="group-title"><?php echo $tipoLabel; ?></div>
                        <div class="category-buttons">
                            <?php foreach ($cats as $cat): ?>
                            <button class="category-btn category-<?php echo $tipo; ?>" 
                                    data-category-id="<?php echo $cat['id']; ?>"
                                    data-category-type="<?php echo $tipo; ?>"
                                    onclick="selectCategory(this)">
                                <div class="category-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 500;"><?php echo $cat['nome']; ?></div>
                                    <?php if ($cat['grupo']): ?>
                                    <div style="font-size: 11px; color: #6c757d;"><?php echo $cat['grupo']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="save-pattern-section" id="savePatternSection">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="savePattern" checked>
                        <label for="savePattern">
                            <strong>Salvar padrão para classificação automática futura</strong>
                        </label>
                    </div>
                    <div style="margin-left: 30px;">
                        <label style="font-size: 14px; color: #6c757d;">
                            Palavras-chave para identificação (opcional - sistema aprenderá automaticamente):
                        </label>
                        <input type="text" 
                               class="pattern-input" 
                               id="patternKeywords"
                               placeholder="Ex: MAFRA, ADVOGADOS, JURÍDICA">
                        <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                            O sistema salvará automaticamente o padrão da descrição completa
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-skip" onclick="skipTransaction()">
                    <i class="fas fa-forward"></i> Pular
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-primary" id="saveBtn" onclick="saveClassification()" disabled>
                    <i class="fas fa-save"></i> Salvar e Próxima
                </button>
            </div>
        </div>
        
        <?php else: ?>
        
        <div class="content">
            <div class="empty-state">
                <i class="fas fa-check-circle empty-icon"></i>
                <h2>Parabéns!</h2>
                <p style="color: #6c757d; margin-top: 10px;">
                    Todas as transações foram classificadas.
                </p>
                <button class="btn btn-primary" style="margin-top: 20px;" onclick="window.location.href='index.php'">
                    <i class="fas fa-chart-line"></i> Ir para Dashboard
                </button>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <script>
        let selectedCategory = null;
        let transactionId = <?php echo $transacao ? $transacao['id'] : 'null'; ?>;
        
        <?php if ($transacao): ?>
        
        // Busca sugestões baseadas em transações similares
        window.addEventListener('DOMContentLoaded', function() {
            searchSuggestions();
        });
        
        function searchSuggestions() {
            const description = <?php echo json_encode($transacao['descricao']); ?>;
            
            fetch('api/classification_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'get_suggestions',
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.suggestions && data.suggestions.length > 0) {
                    const suggestionList = document.getElementById('suggestionList');
                    const suggestionsDiv = document.getElementById('suggestions');
                    
                    suggestionList.innerHTML = data.suggestions.map(s => 
                        `<span class="suggestion-chip" onclick="applySuggestion(${s.categoria_id})">
                            ${s.categoria_nome} (${s.confidence}% similar)
                        </span>`
                    ).join('');
                    
                    suggestionsDiv.classList.add('show');
                }
            });
        }
        
        function applySuggestion(categoryId) {
            const btn = document.querySelector(`[data-category-id="${categoryId}"]`);
            if (btn) {
                selectCategory(btn);
                btn.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        }
        
        function selectCategory(btn) {
            // Remove seleção anterior
            document.querySelectorAll('.category-btn').forEach(b => 
                b.classList.remove('selected')
            );
            
            // Adiciona seleção
            btn.classList.add('selected');
            selectedCategory = {
                id: btn.dataset.categoryId,
                type: btn.dataset.categoryType
            };
            
            // Mostra seção de salvar padrão
            document.getElementById('savePatternSection').classList.add('show');
            
            // Habilita botão salvar
            document.getElementById('saveBtn').disabled = false;
            
            // Auto-preenche palavras-chave baseado na descrição
            const description = <?php echo json_encode($transacao['descricao']); ?>;
            const keywords = extractKeywords(description);
            document.getElementById('patternKeywords').value = keywords;
        }
        
        function extractKeywords(description) {
            // Extrai palavras principais (substantivos próprios, empresas, etc)
            const words = description.split(/\s+/);
            const keywords = [];
            
            words.forEach(word => {
                // Pega palavras em maiúsculas ou com mais de 4 caracteres
                if ((word === word.toUpperCase() && word.length > 2) || 
                    word.length > 4) {
                    const clean = word.replace(/[^A-Za-z0-9]/g, '');
                    if (clean && !['PARA', 'ENVIADO', 'RECEBIDO', 'PAGO'].includes(clean)) {
                        keywords.push(clean);
                    }
                }
            });
            
            return keywords.slice(0, 3).join(', ');
        }
        
        function saveClassification() {
            if (!selectedCategory) return;
            
            const savePattern = document.getElementById('savePattern').checked;
            const keywords = document.getElementById('patternKeywords').value;
            
            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            
            fetch('api/classification_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'classify',
                    transaction_id: transactionId,
                    category_id: selectedCategory.id,
                    classification: getClassificationType(selectedCategory.type),
                    save_pattern: savePattern,
                    keywords: keywords,
                    description: <?php echo json_encode($transacao['descricao']); ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recarrega para próxima transação
                    window.location.reload();
                } else {
                    alert('Erro ao salvar: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Salvar e Próxima';
                }
            });
        }
        
        function getClassificationType(categoryType) {
            if (categoryType === 'receita') return 'receita';
            if (categoryType === 'despesa_pf') return 'pf';
            return 'aemf';
        }
        
        function skipTransaction() {
            if (confirm('Deseja pular esta transação? Ela permanecerá não classificada.')) {
                fetch('api/classification_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'skip',
                        transaction_id: transactionId
                    })
                })
                .then(() => window.location.reload());
            }
        }
        
        <?php endif; ?>
    </script>
</body>
</html>