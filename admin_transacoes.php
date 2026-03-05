<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - AEMFPAR</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 15px 30px;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: #17a2b8;
            border-bottom-color: #17a2b8;
        }
        
        .content-area {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-primary {
            background: #17a2b8;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .categoria-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .color-box {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        
        .upload-area {
            border: 2px dashed #17a2b8;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            margin-bottom: 30px;
        }
        
        .upload-area.dragover {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #17a2b8;
            margin-bottom: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin: 10px;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: #17a2b8;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .file-input-label:hover {
            background: #138496;
        }
        
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .step-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .step-card:hover {
            transform: translateY(-5px);
        }
        
        .step-card.complete {
            border-left: 4px solid #28a745;
        }
        
        .step-card.processing {
            border-left: 4px solid #ffc107;
        }
        
        .step-card.error {
            border-left: 4px solid #dc3545;
        }
        
        .results-container {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #17a2b8, #138496);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        /* Estilos adicionais para a aba de categorização */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #212529;
        }
        
        .stat-detail {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .table-container {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }
        
        .categoria-select {
            width: 150px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .valor-positivo {
            color: #28a745;
            font-weight: 600;
        }
        
        .valor-negativo {
            color: #dc3545;
            font-weight: 600;
        }
        
        .status-conciliado {
            background: #d4edda;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #155724;
        }
        
        .status-pendente {
            background: #fff3cd;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #856404;
        }
        
        .checkbox-cell {
            width: 30px;
        }
        
        .actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .selected-count {
            color: #17a2b8;
            font-weight: 600;
        }
        
        .highlight-new {
            animation: fadeIn 0.5s;
            background-color: #fff3cd !important;
        }
        
        @keyframes fadeIn {
            from { background-color: #ffc107; }
            to { background-color: #fff3cd; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> Painel Administrativo</h1>
            <p>Sistema de Gestão Financeira AEMFPAR</p>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('importacao')">
                <i class="fas fa-file-upload"></i> Importação
            </button>
            <button class="tab" onclick="showTab('referencias')">
                <i class="fas fa-robot"></i> Padrões
            </button>
            <button class="tab" onclick="showTab('reclassificar')">
                <i class="fas fa-sync"></i> Reclassificar
            </button>
            <button class="tab" onclick="showTab('categorias')">
                <i class="fas fa-tags"></i> Categorias
            </button>
            <button class="tab" onclick="showTab('saldo')">
                <i class="fas fa-balance-scale"></i> Saldo Inicial
            </button>
            <button class="tab" onclick="window.location.href='index.php'">
                <i class="fas fa-chart-line"></i> Dashboard
            </button>
        </div>
        
        <!-- Tab Importação -->
        <div id="tab-importacao" class="content-area">
            <h2><i class="fas fa-file-import"></i> Importação de Documentos</h2>
            <p>Use a interface de importação para fazer upload de extratos e comprovantes.</p>

            <div style="margin-top: 30px; text-align: center;">
                <div style="background: #f8f9fa; border: 2px dashed #17a2b8; border-radius: 10px; padding: 50px 30px; display: inline-block; max-width: 500px; width: 100%;">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 64px; color: #17a2b8; display: block; margin-bottom: 20px;"></i>
                    <h3 style="color: #333; margin-bottom: 10px;">Importar Extratos e Comprovantes</h3>
                    <p style="color: #666; margin-bottom: 25px;">
                        Clique no botão abaixo para abrir a interface de importação de documentos financeiros.
                        Após a importação, o sistema classifica automaticamente as transações usando os Padrões cadastrados.
                    </p>
                    <a href="upload_interface.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 40px; border-radius: 30px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-file-upload"></i> Abrir Importador de Documentos
                    </a>
                    <p style="color: #999; font-size: 13px; margin-top: 20px;">
                        Transações não classificadas automaticamente ficam disponíveis na aba <strong>Reclassificar</strong>.
                    </p>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <hr style="margin-bottom: 25px;">
                <h3 style="margin-bottom: 10px;">Classificação Automática</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    Aplica as regras da tabela <strong>Padrões</strong> a todas as transações ainda sem classificação.
                </p>
                <button class="btn btn-primary" onclick="aplicarRegrasAutomaticas()" id="btnAutoClassify">
                    <i class="fas fa-magic"></i> Aplicar Regras Automáticas
                </button>
                <div id="autoClassifyResult" style="margin-top:15px;"></div>
            </div>
        </div>
        
        <!-- Tab Categorias -->
        <div id="tab-categorias" class="content-area" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Categorias de Transações</h2>
                <button class="btn btn-primary" onclick="openModalCategoria()">
                    <i class="fas fa-plus"></i> Nova Categoria
                </button>
            </div>
            
            <table id="categoriasTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Grupo</th>
                        <th>Cor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="categoriasBody">
                    <!-- Será preenchido via JS -->
                </tbody>
            </table>
        </div>
        
        <!-- Tab Referências -->
        <div id="tab-referencias" class="content-area" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Padrões de Classificação Automática</h2>
                <button class="btn btn-primary" onclick="openModalReferencia()">
                    <i class="fas fa-plus"></i> Novo Padrão
                </button>
            </div>
            
            <table id="referenciasTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Padrão de Texto</th>
                        <th>Categoria</th>
                        <th>Tipo Transação</th>
                        <th>Confiança</th>
                        <th>Usos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="referenciasBody">
                    <!-- Será preenchido via JS -->
                </tbody>
            </table>
        </div>
        
        <!-- Tab Reclassificar -->
        <div id="tab-reclassificar" class="content-area" style="display: none;">
            <h2>Reclassificar Transações</h2>
            <p>Selecione as transações para reclassificar em lote</p>
            
            <div style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
                <label>Filtrar por período:</label>
                <input type="month" id="filterMonth" value="<?= date('Y-m') ?>">
                <button class="btn btn-primary" onclick="loadTransacoes()">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
            
            <table id="transacoesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllReclass" onchange="toggleSelectAllReclass()"></th>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Categoria Atual</th>
                        <th>Nova Categoria</th>
                    </tr>
                </thead>
                <tbody id="transacoesBody">
                    <!-- Será preenchido via JS -->
                </tbody>
            </table>
            
            <div style="margin-top: 20px;">
                <button class="btn btn-success" onclick="aplicarReclassificacao()">
                    <i class="fas fa-check"></i> Aplicar Reclassificação
                </button>
            </div>
        </div>

        <!-- Tab Saldo Inicial -->
        <div id="tab-saldo" class="content-area" style="display: none;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2><i class="fas fa-balance-scale" style="color:#17a2b8;margin-right:8px;"></i>Saldo Inicial por Período</h2>
                <button class="btn btn-primary" onclick="openModalSaldo()">
                    <i class="fas fa-plus"></i> Novo Saldo Inicial
                </button>
            </div>
            <p style="color:#666;margin-bottom:20px;">
                Informe o saldo bancário no <strong>último dia do mês anterior</strong> para que o Dashboard calcule corretamente o Fluxo de Caixa.
            </p>

            <table id="saldoInicialTable">
                <thead>
                    <tr>
                        <th>Mês de Referência</th>
                        <th>Data de Referência</th>
                        <th>Saldo (R$)</th>
                        <th>Tipo</th>
                        <th>Observações</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="saldoInicialBody">
                    <!-- preenchido via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Saldo Inicial -->
    <div id="modalSaldo" class="modal">
        <div class="modal-content">
            <h3 id="modalSaldoTitle">Novo Saldo Inicial</h3>
            <form id="formSaldo" onsubmit="submitSaldo(event)">
                <input type="hidden" id="saldoId">
                <div class="form-group">
                    <label>Mês de Referência (YYYY-MM):</label>
                    <input type="month" id="saldoMes" required
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                    <small style="color:#666;">Exemplo: 2026-01 para indicar o saldo de abertura de Janeiro/2026</small>
                </div>
                <div class="form-group">
                    <label>Data de Referência (data exata do saldo):</label>
                    <input type="date" id="saldoDataRef"
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                    <small style="color:#666;">Geralmente o último dia do mês anterior (ex.: 31/12/2025 para Janeiro/2026)</small>
                </div>
                <div class="form-group">
                    <label>Saldo (R$):</label>
                    <input type="number" id="saldoValor" step="0.01" required placeholder="Ex: 12345.67"
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                </div>
                <div class="form-group">
                    <label>Observações (opcional):</label>
                    <input type="text" id="saldoObs" placeholder="Ex: Extrato Itaú Dez/2025"
                           style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('Saldo')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modais -->
    <div id="modalCategoria" class="modal">
        <div class="modal-content">
            <h3 id="modalCategoriaTitle">Nova Categoria</h3>
            <form id="formCategoria">
                <input type="hidden" name="id" id="categoriaId">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" id="categoriaNome" required>
                </div>
                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="tipo" id="categoriaTipo" required>
                        <option value="receita">Receita</option>
                        <option value="despesa_aemf">Despesa AEMF</option>
                        <option value="despesa_pf">Despesa PF</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grupo:</label>
                    <input type="text" name="grupo" id="categoriaGrupo" placeholder="Ex: Operacional, Administrativo">
                </div>
                <div class="form-group">
                    <label>Cor:</label>
                    <input type="color" name="cor" id="categoriaCor" value="#17a2b8">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" id="categoriaAtivo" value="1" checked>
                        Categoria Ativa
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('categoria')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="modalReferencia" class="modal">
        <div class="modal-content">
            <h3 id="modalReferenciaTitle">Novo Padrão de Referência</h3>
            <form id="formReferencia">
                <input type="hidden" name="id" id="referenciaId">
                <div class="form-group">
                    <label>Padrão de Texto:</label>
                    <input type="text" name="padrao" id="referenciaPadrao" required placeholder="Ex: SYLVIA MARIA">
                </div>
                <div class="form-group">
                    <label>Categoria:</label>
                    <select name="categoria_id" id="selectCategoria" required>
                        <!-- Será preenchido via JS -->
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Transação:</label>
                    <select name="tipo_transacao" id="referenciaTipoTransacao">
                        <option value="">Qualquer</option>
                        <option value="PIX">PIX</option>
                        <option value="TED">TED</option>
                        <option value="BOLETO">Boleto</option>
                        <option value="CARTAO">Cartão</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Observações:</label>
                    <textarea name="observacoes" id="referenciaObservacoes" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('referencia')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Categorização em Lote -->
    <div id="modalCategorizarLote" class="modal">
        <div class="modal-content">
            <h3>Categorizar Transações Selecionadas</h3>
            <form id="formCategorizarLote">
                <div class="form-group">
                    <label>Categoria para todas as transações selecionadas:</label>
                    <select id="categoriaLote" required>
                        <!-- Será preenchido via JS -->
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="criarRegra">
                        Criar regra automática para transações similares
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Aplicar</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('categorizarLote')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let categorias = [];
        let transacoesSemCategoria = [];
        
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.content-area').forEach(c => c.style.display = 'none');
            
            event.target.classList.add('active');
            document.getElementById(`tab-${tab}`).style.display = 'block';
            
            if (tab === 'categorias')  loadCategorias();
            if (tab === 'referencias') loadReferencias();
            if (tab === 'saldo')       loadSaldoInicial();
        }
        
        function showResults(result) {
            const resultsDiv = document.getElementById('results');
            const content = document.getElementById('resultsContent');
            if (!resultsDiv || !content) return;
            
            let html = '<div class="alert alert-success">';
            html += '<h4>Processamento Concluído!</h4>';
            html += `<p>Transações importadas: ${result.extrato?.imported || 0}</p>`;
            html += `<p>Comprovantes processados: ${result.comprovantes?.processed || 0}</p>`;
            html += `<p>Conciliações realizadas: ${result.comprovantes?.matched || 0}</p>`;
            
            if (result.stats) {
                html += '<hr>';
                html += '<h5>Estatísticas:</h5>';
                html += `<p>Total de transações: ${result.stats.total_transacoes || 0}</p>`;
                html += `<p>Sem categoria: ${result.stats.sem_categoria || 0}</p>`;
            }
            
            html += '</div>';
            
            content.innerHTML = html;
            resultsDiv.style.display = 'block';
        }
        
        // Funções de categorização
        function loadTransacoesSemCategoria() {
            fetch('api/admin_api.php?action=getTransacoesSemCategoria')
                .then(response => response.json())
                .then(data => {
                    transacoesSemCategoria = data;
                    const tbody = document.getElementById('transacoesSemCategoria');
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Nenhuma transação sem categoria</td></tr>';
                        updateCategorizacaoStats();
                        return;
                    }
                    
                    tbody.innerHTML = data.map(trans => `
                        <tr data-id="${trans.id}">
                            <td><input type="checkbox" class="select-transaction" data-id="${trans.id}"></td>
                            <td>${formatDate(trans.data)}</td>
                            <td>${trans.descricao}</td>
                            <td>${trans.beneficiario || '-'}</td>
                            <td class="${trans.valor >= 0 ? 'valor-positivo' : 'valor-negativo'}">
                                ${formatCurrency(trans.valor)}
                            </td>
                            <td>
                                <span class="${trans.conciliado ? 'status-conciliado' : 'status-pendente'}">
                                    ${trans.conciliado ? 'Conciliado' : 'Pendente'}
                                </span>
                            </td>
                            <td>
                                <select class="categoria-select" id="cat-${trans.id}">
                                    <option value="">Selecione...</option>
                                    ${getCategoriaOptions()}
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-success btn-sm" onclick="salvarCategoria(${trans.id})">
                                    Salvar
                                </button>
                            </td>
                        </tr>
                    `).join('');
                    
                    // Adicionar event listeners
                    document.querySelectorAll('.select-transaction').forEach(cb => {
                        cb.addEventListener('change', updateSelectedCount);
                    });
                    
                    updateCategorizacaoStats();
                })
                .catch(error => {
                    console.error('Erro ao carregar transações:', error);
                    document.getElementById('transacoesSemCategoria').innerHTML = 
                        '<tr><td colspan="8">Erro ao carregar transações</td></tr>';
                });
        }
        
        function getCategoriaOptions(selectedId = null) {
            if (categorias.length === 0) {
                loadCategorias(false);
            }

            if (categorias.length > 0) {
                return categorias.map(cat =>
                    `<option value="${cat.id}" ${cat.id == selectedId ? 'selected' : ''}>${cat.nome} (${cat.tipo})</option>`
                ).join('');
            }

            // Fallback enquanto as categorias carregam
            const options = [
                { value: 'folha_pagamento', label: 'Folha de Pagamento' },
                { value: 'servicos_juridicos', label: 'Serviços Jurídicos' },
                { value: 'servicos_contabeis', label: 'Serviços Contábeis' },
                { value: 'armazenagem', label: 'Armazenagem' },
                { value: 'impostos', label: 'Impostos' },
                { value: 'despesa_aemf', label: 'Despesa AEMF' },
                { value: 'despesa_pf', label: 'Despesa PF' },
                { value: 'investimento', label: 'Investimento' },
                { value: 'aporte_capital', label: 'Aporte de Capital' },
                { value: 'receita_servicos', label: 'Receita de Serviços' }
            ];

            return options.map(opt =>
                `<option value="${opt.value}">${opt.label}</option>`
            ).join('');
        }
        
        function salvarCategoria(id) {
            const select = document.getElementById(`cat-${id}`);
            const categoria = select.value;
            
            if (!categoria) {
                alert('Por favor, selecione uma categoria');
                return;
            }
            
            const formData = new FormData();
            formData.append('transacao_id', id);
            formData.append('categoria', categoria);
            
            fetch('api/admin_api.php?action=categorizarTransacao', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = select.closest('tr');
                    row.classList.add('highlight-new');
                    setTimeout(() => {
                        row.remove();
                        updateCategorizacaoStats();
                    }, 1000);
                    
                    alert('Categoria salva com sucesso!');
                } else {
                    alert('Erro ao salvar categoria');
                }
            });
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.select-transaction');
            
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checked = document.querySelectorAll('.select-transaction:checked').length;
            document.getElementById('selectedCount').textContent = `${checked} selecionadas`;
        }
        
        function categorizarSelecionadas() {
            const checked = document.querySelectorAll('.select-transaction:checked');
            
            if (checked.length === 0) {
                alert('Selecione pelo menos uma transação');
                return;
            }
            
            // Carregar categorias e abrir modal
            loadCategorias(false);
            document.getElementById('modalCategorizarLote').classList.add('active');
        }
        
        function updateCategorizacaoStats() {
            const total = document.querySelectorAll('#transacoesSemCategoria tr').length;
            const conciliadas = document.querySelectorAll('.status-conciliado').length;
            
            document.getElementById('statSemCategoria').textContent = total;
            document.getElementById('statConciliadas').textContent = conciliadas;
            
            // Calcular valor total (exemplo)
            let valorTotal = 0;
            transacoesSemCategoria.forEach(t => {
                valorTotal += Math.abs(t.valor || 0);
            });
            document.getElementById('statValorTotal').textContent = formatCurrency(valorTotal);
            
            // Taxa de conclusão
            const totalTransacoes = 100; // Exemplo
            const categorizadas = totalTransacoes - total;
            const taxa = totalTransacoes > 0 ? Math.round((categorizadas / totalTransacoes) * 100) : 0;
            document.getElementById('statTaxa').textContent = taxa + '%';
        }
        
        // Funções auxiliares
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }
        
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text ?? ''));
            return div.innerHTML;
        }
        
        // Função para carregar categorias
        function loadCategorias(updateTable = true) {
            fetch('api/admin_api.php?action=getCategorias')
                .then(response => response.json())
                .then(data => {
                    categorias = data;
                    
                    if (updateTable) {
                        const tbody = document.getElementById('categoriasBody');
                        tbody.innerHTML = data.map(cat => `
                            <tr>
                                <td>${cat.id}</td>
                                <td><strong>${cat.nome}</strong></td>
                                <td><span class="badge badge-${cat.tipo.includes('receita') ? 'success' : cat.tipo.includes('pf') ? 'warning' : 'danger'}">${cat.tipo}</span></td>
                                <td>${cat.grupo || '-'}</td>
                                <td>
                                    <div class="categoria-display">
                                        <span class="color-box" style="background: ${cat.cor || '#17a2b8'};"></span>
                                        <small>${cat.cor || '#17a2b8'}</small>
                                    </div>
                                </td>
                                <td>${cat.ativo == 1 ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>'}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="openModalCategoria(${cat.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deletarCategoria(${cat.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }
                    
                    // Atualizar selects
                    const select = document.getElementById('selectCategoria');
                    select.innerHTML = '<option value="">Selecione...</option>' + 
                        data.map(cat => `<option value="${cat.id}">${cat.nome} (${cat.tipo})</option>`).join('');
                    
                    const selectLote = document.getElementById('categoriaLote');
                    if (selectLote) {
                        selectLote.innerHTML = '<option value="">Selecione...</option>' + 
                            data.map(cat => `<option value="${cat.id}">${cat.nome} (${cat.tipo})</option>`).join('');
                    }
                });
        }
        
        // FUNÇÃO QUE ESTAVA NO ARQUIVO ORIGINAL
        function loadReferencias() {
            fetch('api/admin_api.php?action=getReferencias')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('referenciasBody');
                    tbody.innerHTML = data.map(ref => `
                        <tr>
                            <td>${ref.id}</td>
                            <td><strong>${ref.padrao}</strong></td>
                            <td>${ref.categoria_nome || '-'}</td>
                            <td>${ref.tipo_transacao || 'Qualquer'}</td>
                            <td>${ref.confianca ? (ref.confianca * 100).toFixed(0) + '%' : '100%'}</td>
                            <td>${ref.usos || 0}</td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="openModalReferencia(${ref.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deletarReferencia(${ref.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => {
                    console.error('Erro ao carregar referências:', error);
                    document.getElementById('referenciasBody').innerHTML = '<tr><td colspan="7">Erro ao carregar dados</td></tr>';
                });
        }
        
        // Funções para modais
        function openModalCategoria(id = null) {
            const modal = document.getElementById('modalCategoria');
            const title = document.getElementById('modalCategoriaTitle');
            
            if (id) {
                title.textContent = 'Editar Categoria';
                fetch(`api/admin_api.php?action=getCategoria&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('categoriaId').value = data.id;
                        document.getElementById('categoriaNome').value = data.nome;
                        document.getElementById('categoriaTipo').value = data.tipo;
                        document.getElementById('categoriaGrupo').value = data.grupo || '';
                        document.getElementById('categoriaCor').value = data.cor || '#17a2b8';
                        document.getElementById('categoriaAtivo').checked = data.ativo == 1;
                    });
            } else {
                title.textContent = 'Nova Categoria';
                document.getElementById('formCategoria').reset();
                document.getElementById('categoriaId').value = '';
            }
            
            modal.classList.add('active');
        }
        
        function openModalReferencia(id = null) {
            const modal = document.getElementById('modalReferencia');
            const title = document.getElementById('modalReferenciaTitle');
            
            // Carregar categorias no select
            loadCategorias(false);
            
            if (id) {
                title.textContent = 'Editar Padrão';
                fetch(`api/admin_api.php?action=getReferencia&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('referenciaId').value = data.id;
                        document.getElementById('referenciaPadrao').value = data.padrao;
                        document.getElementById('selectCategoria').value = data.categoria_id;
                        document.getElementById('referenciaTipoTransacao').value = data.tipo_transacao || '';
                        document.getElementById('referenciaObservacoes').value = data.observacoes || '';
                    });
            } else {
                title.textContent = 'Novo Padrão';
                document.getElementById('formReferencia').reset();
                document.getElementById('referenciaId').value = '';
            }
            
            modal.classList.add('active');
        }
        
        function closeModal(type) {
            document.getElementById(`modal${type.charAt(0).toUpperCase() + type.slice(1)}`).classList.remove('active');
        }
        
        function deletarCategoria(id) {
            if (confirm('Tem certeza que deseja excluir esta categoria?')) {
                fetch(`api/admin_api.php?action=deleteCategoria&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Categoria excluída com sucesso!');
                        loadCategorias();
                    } else {
                        alert(data.error || 'Erro ao excluir categoria');
                    }
                });
            }
        }
        
        function deletarReferencia(id) {
            if (confirm('Tem certeza que deseja excluir este padrão?')) {
                fetch(`api/admin_api.php?action=deleteReferencia&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Padrão excluído com sucesso!');
                        loadReferencias();
                    } else {
                        alert('Erro ao excluir padrão');
                    }
                });
            }
        }
        
        // Form submissions
        document.getElementById('formCategoria').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const id = formData.get('id');
            
            fetch(`api/admin_api.php?action=${id ? 'updateCategoria' : 'saveCategoria'}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('categoria');
                    loadCategorias();
                    alert('Categoria salva com sucesso!');
                }
            });
        });
        
        document.getElementById('formReferencia').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const id = formData.get('id');
            
            fetch(`api/admin_api.php?action=${id ? 'updateReferencia' : 'saveReferencia'}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('referencia');
                    loadReferencias();
                    alert('Padrão salvo com sucesso!');
                }
            });
        });
        
        document.getElementById('formCategorizarLote').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const categoria = document.getElementById('categoriaLote').value;
            const criarRegra = document.getElementById('criarRegra').checked;
            const checkboxes = document.querySelectorAll('.select-transaction:checked');
            
            const transacoes = Array.from(checkboxes).map(cb => cb.dataset.id);
            
            const formData = new FormData();
            formData.append('categoria', categoria);
            formData.append('transacoes', JSON.stringify(transacoes));
            formData.append('criar_regra', criarRegra ? '1' : '0');
            
            fetch('api/admin_api.php?action=categorizarLote', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('categorizarLote');
                    loadTransacoesSemCategoria();
                    alert(`${transacoes.length} transações categorizadas com sucesso!`);
                }
            });
        });
        
        // Funções da aba Reclassificar
        function loadTransacoes() {
            const mes = document.getElementById('filterMonth').value;
            fetch(`api/admin_api.php?action=getTransacoes&mes=${mes}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('transacoesBody');
                    if (!Array.isArray(data) || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Nenhuma transação encontrada para o período</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(t => `
                        <tr data-id="${escapeHtml(String(t.id))}">
                            <td><input type="checkbox" class="select-reclass" data-id="${escapeHtml(String(t.id))}"></td>
                            <td>${formatDate(t.data)}</td>
                            <td>${escapeHtml(t.descricao)}</td>
                            <td class="${t.tipo === 'credito' ? 'valor-positivo' : 'valor-negativo'}">
                                ${formatCurrency(t.valor)}
                            </td>
                            <td>${t.categoria_nome ? escapeHtml(t.categoria_nome) : '<em>Sem categoria</em>'}</td>
                            <td>
                                <select class="categoria-select" id="reclass-${escapeHtml(String(t.id))}">
                                    <option value="">Manter atual</option>
                                    ${getCategoriaOptions(t.categoria_id)}
                                </select>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => {
                    console.error('Erro ao carregar transações:', error);
                    document.getElementById('transacoesBody').innerHTML =
                        '<tr><td colspan="6">Erro ao carregar transações</td></tr>';
                });
        }

        function toggleSelectAllReclass() {
            const selectAll = document.getElementById('selectAllReclass');
            document.querySelectorAll('.select-reclass').forEach(cb => {
                cb.checked = selectAll.checked;
            });
        }

        function aplicarReclassificacao() {
            const checked = document.querySelectorAll('.select-reclass:checked');
            if (checked.length === 0) {
                alert('Selecione pelo menos uma transação');
                return;
            }

            const promises = [];
            checked.forEach(cb => {
                const id = cb.dataset.id;
                const select = document.getElementById(`reclass-${id}`);
                if (select && select.value) {
                    const formData = new FormData();
                    formData.append('transacao_id', id);
                    formData.append('categoria', select.value);
                    promises.push(
                        fetch('api/admin_api.php?action=categorizarTransacao', { method: 'POST', body: formData })
                    );
                }
            });

            if (promises.length === 0) {
                alert('Nenhuma nova categoria selecionada');
                return;
            }

            Promise.all(promises)
                .then(() => {
                    alert(`${promises.length} transações reclassificadas com sucesso!`);
                    loadTransacoes();
                })
                .catch(error => {
                    alert('Erro ao reclassificar: ' + error.message);
                });
        }

        // ── Aplicar Regras Automáticas ───────────────────────────────────────
        function aplicarRegrasAutomaticas() {
            const btn = document.getElementById('btnAutoClassify');
            const resultEl = document.getElementById('autoClassifyResult');
            if (btn) btn.disabled = true;
            if (resultEl) resultEl.innerHTML = '<em>Processando...</em>';

            fetch('api/admin_api.php?action=aplicarRegrasAutomaticas')
                .then(r => r.json())
                .then(data => {
                    if (btn) btn.disabled = false;
                    if (data.success && resultEl) {
                        const s = data.stats || {};
                        resultEl.innerHTML = `<div style="background:#d4edda;color:#155724;padding:12px;border-radius:6px;margin-top:10px;">
                            <strong>✔ Concluído!</strong><br>
                            Transações analisadas: ${s.transacoes_analisadas || 0}<br>
                            Classificadas automaticamente: ${s.transacoes_classificadas || 0}
                        </div>`;
                    } else if (resultEl) {
                        resultEl.innerHTML = `<div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:6px;margin-top:10px;">
                            Erro: ${escapeHtml(data.error || 'Falha desconhecida')}
                        </div>`;
                    }
                })
                .catch(err => {
                    if (btn) btn.disabled = false;
                    if (resultEl) resultEl.innerHTML = `<div style="color:red;">Erro de comunicação: ${err.message}</div>`;
                });
        }

        // ── Saldo Inicial ─────────────────────────────────────────────────────
        function loadSaldoInicial() {
            fetch('api/admin_api.php?action=getSaldoInicial')
                .then(r => r.json())
                .then(res => {
                    const tbody = document.getElementById('saldoInicialBody');
                    if (!res.success || !res.data.length) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#6c757d;padding:20px;">Nenhum saldo inicial cadastrado.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = res.data.map(s => {
                        const editBtn = s.tipo === 'manual'
                            ? `<button class="btn btn-warning btn-sm" data-action="edit-saldo"
                                    data-id="${s.id}"
                                    data-mes="${escapeHtml(s.mes_referencia)}"
                                    data-dtref="${s.data_referencia || ''}"
                                    data-saldo="${s.saldo}"
                                    data-obs="${escapeHtml(s.observacoes || '')}">
                                   <i class="fas fa-edit"></i>
                               </button>
                               <button class="btn btn-danger btn-sm" data-action="delete-saldo" data-id="${s.id}">
                                   <i class="fas fa-trash"></i>
                               </button>`
                            : '—';
                        return `<tr>
                            <td><strong>${escapeHtml(s.mes_referencia)}</strong></td>
                            <td>${s.data_referencia ? formatDate(s.data_referencia) : '—'}</td>
                            <td>${formatCurrency(s.saldo)}</td>
                            <td><span class="badge badge-${s.tipo === 'manual' ? 'success' : 'secondary'}">${s.tipo}</span></td>
                            <td>${escapeHtml(s.observacoes || '—')}</td>
                            <td>${editBtn}</td>
                        </tr>`;
                    }).join('');

                    // Attach click handlers after rendering
                    tbody.querySelectorAll('[data-action="edit-saldo"]').forEach(btn => {
                        btn.addEventListener('click', () => {
                            openModalSaldo(
                                btn.dataset.id,
                                btn.dataset.mes,
                                btn.dataset.dtref,
                                btn.dataset.saldo,
                                btn.dataset.obs
                            );
                        });
                    });
                    tbody.querySelectorAll('[data-action="delete-saldo"]').forEach(btn => {
                        btn.addEventListener('click', () => deleteSaldo(btn.dataset.id));
                    });
                })
                .catch(() => {
                    document.getElementById('saldoInicialBody').innerHTML =
                        '<tr><td colspan="6" style="color:red;text-align:center;padding:20px;">Erro ao carregar dados.</td></tr>';
                });
        }

        function openModalSaldo(id = null, mes = '', dtRef = '', saldo = '', obs = '') {
            document.getElementById('saldoId').value = id || '';
            document.getElementById('saldoMes').value = mes;
            document.getElementById('saldoDataRef').value = dtRef;
            document.getElementById('saldoValor').value = saldo;
            document.getElementById('saldoObs').value = obs;
            document.getElementById('modalSaldoTitle').textContent = id ? 'Editar Saldo Inicial' : 'Novo Saldo Inicial';
            document.getElementById('modalSaldo').classList.add('active');
        }

        function submitSaldo(e) {
            e.preventDefault();
            const body = new URLSearchParams({
                mes_referencia:  document.getElementById('saldoMes').value,
                data_referencia: document.getElementById('saldoDataRef').value,
                saldo:           document.getElementById('saldoValor').value,
                observacoes:     document.getElementById('saldoObs').value,
            });
            fetch('api/admin_api.php?action=saveSaldoInicial', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal('Saldo');
                    loadSaldoInicial();
                    alert('Saldo inicial salvo com sucesso!');
                } else {
                    alert('Erro: ' + (data.error || 'Falha ao salvar.'));
                }
            })
            .catch(() => alert('Erro de comunicação com o servidor.'));
        }

        function deleteSaldo(id) {
            if (!confirm('Excluir este saldo inicial?')) return;
            fetch(`api/admin_api.php?action=deleteSaldoInicial&id=${id}`, { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadSaldoInicial();
                        alert('Saldo excluído.');
                    } else {
                        alert('Erro ao excluir.');
                    }
                });
        }

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar categorias para os selects
            loadCategorias(false);

            // Abrir aba solicitada via query string ?tab=saldo
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const tabBtn = document.querySelector(`.tab[onclick*="'${tab}'"]`);
                if (tabBtn) tabBtn.click();
            }
        });
    </script>
</body>
</html>