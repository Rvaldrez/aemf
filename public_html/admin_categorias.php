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
            max-width: 1400px;
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
            <button class="tab" onclick="showTab('categorias')">
                <i class="fas fa-tags"></i> Categorias
            </button>
            <button class="tab" onclick="showTab('referencias')">
                <i class="fas fa-robot"></i> Padrões
            </button>
            <button class="tab" onclick="showTab('reclassificar')">
                <i class="fas fa-sync"></i> Reclassificar
            </button>
            <button class="tab" onclick="window.location.href='index.php'">
                <i class="fas fa-chart-line"></i> Dashboard
            </button>
        </div>
        
        <!-- Tab Importação -->
        <div id="tab-importacao" class="content-area">
            <h2><i class="fas fa-file-import"></i> Importação de Documentos</h2>
            <p>Faça o upload de extratos e comprovantes para processamento automático</p>
            
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3>Arraste os arquivos aqui</h3>
                <p>ou clique para selecionar</p>
                
                <div style="margin-top: 20px;">
                    <div class="file-input-wrapper">
                        <input type="file" id="extratoFile" class="file-input" accept=".ofx,.pdf" onchange="handleFileSelect(event, 'extrato')">
                        <label for="extratoFile" class="file-input-label">
                            <i class="fas fa-file-invoice"></i> Selecionar Extrato (OFX)
                        </label>
                    </div>
                    
                    <div class="file-input-wrapper">
                        <input type="file" id="comprovantesFile" class="file-input" accept=".pdf" multiple onchange="handleFileSelect(event, 'comprovantes')">
                        <label for="comprovantesFile" class="file-input-label">
                            <i class="fas fa-file-pdf"></i> Selecionar Comprovantes
                        </label>
                    </div>
                </div>
            </div>
            
            <div id="selectedFiles" style="margin: 20px 0;"></div>
            
            <button class="btn btn-success" onclick="processDocuments()" style="display: none;" id="processButton">
                <i class="fas fa-cog"></i> Processar Documentos
            </button>
            
            <div class="progress-bar" style="display: none;" id="progressBar">
                <div class="progress-fill" id="progressFill" style="width: 0%">0%</div>
            </div>
            
            <div class="process-steps" id="processSteps" style="display: none;">
                <div class="step-card" id="step1">
                    <h4><i class="fas fa-file-alt"></i> Leitura de PDFs</h4>
                    <p>Extraindo dados dos documentos...</p>
                </div>
                <div class="step-card" id="step2">
                    <h4><i class="fas fa-check-double"></i> Conciliação</h4>
                    <p>Comparando extrato com comprovantes...</p>
                </div>
                <div class="step-card" id="step3">
                    <h4><i class="fas fa-tags"></i> Classificação</h4>
                    <p>Categorizando transações...</p>
                </div>
                <div class="step-card" id="step4">
                    <h4><i class="fas fa-database"></i> Salvando</h4>
                    <p>Armazenando no banco de dados...</p>
                </div>
            </div>
            
            <div class="results-container" id="results" style="display: none;">
                <h3>Resultados do Processamento</h3>
                <div id="resultsContent"></div>
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
                <input type="month" id="filterMonth" value="2025-09">
                <button class="btn btn-primary" onclick="loadTransacoes()">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
            
            <table id="transacoesTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
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
    
    <script>
        let categorias = [];
        let selectedFiles = {
            extrato: null,
            comprovantes: []
        };
        
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.content-area').forEach(c => c.style.display = 'none');
            
            event.target.classList.add('active');
            document.getElementById(`tab-${tab}`).style.display = 'block';
            
            if (tab === 'categorias') loadCategorias();
            if (tab === 'referencias') loadReferencias();
        }
        
        // Funções de importação
        function handleFileSelect(event, type) {
            const files = event.target.files;
            
            if (type === 'extrato') {
                selectedFiles.extrato = files[0];
            } else {
                selectedFiles.comprovantes = Array.from(files);
            }
            
            updateFileDisplay();
        }
        
        function updateFileDisplay() {
            const display = document.getElementById('selectedFiles');
            let html = '';
            
            if (selectedFiles.extrato) {
                html += `<div class="badge badge-success" style="margin: 5px;">
                    <i class="fas fa-file-pdf"></i> Extrato: ${selectedFiles.extrato.name}
                </div>`;
            }
            
            selectedFiles.comprovantes.forEach(file => {
                html += `<div class="badge badge-warning" style="margin: 5px;">
                    <i class="fas fa-file-pdf"></i> ${file.name}
                </div>`;
            });
            
            display.innerHTML = html;
            
            const processButton = document.getElementById('processButton');
            if (selectedFiles.extrato || selectedFiles.comprovantes.length > 0) {
                processButton.style.display = 'inline-block';
            } else {
                processButton.style.display = 'none';
            }
        }
        
        // Drag and drop
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            // Processar arquivos aqui
        });
        
async function processDocuments() {
    // Resetar e mostrar elementos de progresso
    document.getElementById('progressBar').style.display = 'block';
    document.getElementById('processSteps').style.display = 'grid';
    document.getElementById('results').style.display = 'none';
    
    let results = {
        extrato: null,
        comprovantes: null
    };
    
    try {
        // Iniciar progresso
        updateProgress(25, 'step1', 'processing');
        
        // Processar EXTRATO com o import_handler original
        if (selectedFiles.extrato) {
            const extratoForm = new FormData();
            extratoForm.append('pdf', selectedFiles.extrato);
            
            updateProgress(50, 'step1', 'complete');
            updateProgress(50, 'step2', 'processing');
            
            const extratoResponse = await fetch('api/test_extrato.php', {
                method: 'POST',
                body: extratoForm
            });
            results.extrato = await extratoResponse.json();
            
            updateProgress(75, 'step2', 'complete');
        }
        
        // Processar COMPROVANTES com o novo processador
        if (selectedFiles.comprovantes.length > 0) {
            updateProgress(75, 'step3', 'processing');
            
            const compForm = new FormData();
            selectedFiles.comprovantes.forEach(file => {
                compForm.append('comprovantes[]', file);
            });
            
            const compResponse = await fetch('api/process_comprovantes.php', {
                method: 'POST',
                body: compForm
            });
            results.comprovantes = await compResponse.json();
            
            updateProgress(100, 'step3', 'complete');
        }
        
        // Finalizar progresso
        updateProgress(100, 'step4', 'complete');
        
        // Esconder barra de progresso após 1 segundo
        setTimeout(() => {
            document.getElementById('progressBar').style.display = 'none';
            document.getElementById('processSteps').style.display = 'none';
        }, 1000);
        
        // Mostrar resultados combinados
        showCombinedResults(results);
        
    } catch (error) {
        console.error('Erro no processamento:', error);
        
        // Marcar erro nos steps
        updateProgress(0, 'step1', 'error');
        
        // Mostrar mensagem de erro
        document.getElementById('resultsContent').innerHTML = `
            <div class="alert alert-danger">
                <h4>Erro no Processamento!</h4>
                <p>${error.message || 'Erro desconhecido. Verifique o console.'}</p>
            </div>
        `;
        document.getElementById('results').style.display = 'block';
    }
}

function showCombinedResults(results) {
    let html = '';
    
    // Verificar se houve sucesso
    const hasSuccess = (results.extrato && results.extrato.success) || 
                       (results.comprovantes && results.comprovantes.success);
    
    if (hasSuccess) {
        html += '<div class="alert alert-success">';
        html += '<h4>Processamento Concluído!</h4>';
        
        if (results.extrato) {
            html += '<p><strong>📊 Extrato:</strong><br>';
            html += `✅ Transações importadas: ${results.extrato.imported || 0}<br>`;
            html += `⚠️ Duplicatas ignoradas: ${results.extrato.duplicates || 0}<br>`;
            if (results.extrato.total) {
                html += `📝 Total processado: ${results.extrato.total}</p>`;
            }
        }
        
        if (results.comprovantes) {
            html += '<p><strong>📄 Comprovantes:</strong><br>';
            html += `📤 Processados: ${results.comprovantes.processed || 0}<br>`;
            html += `🔗 Conciliados: ${results.comprovantes.matched || 0}</p>`;
        }
        
        html += '</div>';
    } else {
        html += '<div class="alert alert-warning">';
        html += '<h4>Nenhum documento processado</h4>';
        html += '<p>Verifique se os arquivos estão no formato correto.</p>';
        html += '</div>';
    }
    
    // Adicionar erros se houver
    if (results.extrato && results.extrato.errors && results.extrato.errors.length > 0) {
        html += '<div class="alert alert-danger">';
        html += '<strong>Erros no Extrato:</strong><ul>';
        results.extrato.errors.forEach(err => {
            html += `<li>${err}</li>`;
        });
        html += '</ul></div>';
    }
    
    if (results.comprovantes && results.comprovantes.errors && results.comprovantes.errors.length > 0) {
        html += '<div class="alert alert-danger">';
        html += '<strong>Erros nos Comprovantes:</strong><ul>';
        results.comprovantes.errors.forEach(err => {
            html += `<li>${err}</li>`;
        });
        html += '</ul></div>';
    }
    
    document.getElementById('resultsContent').innerHTML = html;
    document.getElementById('results').style.display = 'block';
}

function updateProgress(percent, stepId = null, status = null) {
    const progressFill = document.getElementById('progressFill');
    progressFill.style.width = percent + '%';
    progressFill.textContent = percent + '%';
    
    if (stepId && status) {
        const step = document.getElementById(stepId);
        step.className = 'step-card ' + status;
    }
}
        
        // Função para carregar categorias
        function loadCategorias() {
            fetch('api/admin_api.php?action=getCategorias')
                .then(response => response.json())
                .then(data => {
                    categorias = data;
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
                    
                    const select = document.getElementById('selectCategoria');
                    select.innerHTML = '<option value="">Selecione...</option>' + 
                        data.map(cat => `<option value="${cat.id}">${cat.nome} (${cat.tipo})</option>`).join('');
                });
        }
        
        // FUNÇÃO QUE ESTAVA FALTANDO - loadReferencias
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
            loadCategorias();
            
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
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            // Não carregar categorias automaticamente na aba de importação
        });
        
        
    </script>
    
    
    
    
</body>
</html>
