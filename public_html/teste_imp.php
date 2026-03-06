<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste de Importação - AEMFPAR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background: #f0f4ff;
            border-color: #5568d3;
        }
        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        input[type="file"] {
            display: none;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .result-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        .status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 5px;
            text-transform: uppercase;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        .metric {
            display: inline-block;
            margin: 10px 20px;
        }
        .metric-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste de Importação Itaú</h1>
        <p class="subtitle">Sistema AEMFPAR - Parser Corrigido v3.0</p>
        
        <div class="section">
            <h2>📤 1. Upload do Extrato</h2>
            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                <div class="upload-icon">📄</div>
                <p><strong>Clique para selecionar o PDF do extrato</strong></p>
                <p style="color: #666; font-size: 14px; margin-top: 10px;">
                    Arquivo suportado: Extrato Itaú em PDF
                </p>
                <p id="fileName" style="color: #667eea; margin-top: 10px; font-weight: 600;"></p>
            </div>
            <input type="file" id="fileInput" accept=".pdf" onchange="handleFileSelect(event)">
            
            <div style="margin-top: 20px; text-align: center;">
                <button id="btnImport" onclick="importarExtrato()" disabled>
                    🚀 Importar Transações
                </button>
                <button onclick="aplicarRegras()" id="btnRegras" disabled>
                    🤖 Aplicar Regras Automáticas
                </button>
                <button onclick="location.href='../admin_categorias.php'">
                    📊 Abrir Admin
                </button>
            </div>
        </div>
        
        <div id="results" style="display: none;">
            <div class="section">
                <h2>✅ 2. Resultado da Importação</h2>
                <div id="resultContent"></div>
            </div>
        </div>
        
        <div id="regrasResult" style="display: none;">
            <div class="section">
                <h2>🤖 3. Aplicação de Regras</h2>
                <div id="regrasContent"></div>
            </div>
        </div>
        
        <div class="section">
            <h2>ℹ️ Informações do Sistema</h2>
            <div id="systemInfo">Carregando...</div>
        </div>
    </div>

    <script>
        let selectedFile = null;
        
        // Carregar info do sistema
        window.addEventListener('DOMContentLoaded', function() {
            fetch('import_itau_parser.php')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('systemInfo').innerHTML = `
                        <div class="result-box">
                            <strong>Status:</strong> ${data.status}<br>
                            <strong>Banco de Dados:</strong> ${data.database ? '✅ Conectado' : '❌ Desconectado'}<br>
                            <strong>Recursos:</strong>
                            <ul style="margin: 10px 0 0 20px;">
                                ${data.features.map(f => `<li>${f}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                })
                .catch(e => {
                    document.getElementById('systemInfo').innerHTML = 
                        '<span class="status error">Erro ao conectar com parser</span>';
                });
        });
        
        function handleFileSelect(event) {
            selectedFile = event.target.files[0];
            if (selectedFile) {
                document.getElementById('fileName').textContent = selectedFile.name;
                document.getElementById('btnImport').disabled = false;
            }
        }
        
        function importarExtrato() {
            if (!selectedFile) {
                alert('Selecione um arquivo PDF primeiro!');
                return;
            }
            
            const formData = new FormData();
            formData.append('pdf', selectedFile);
            
            document.getElementById('btnImport').disabled = true;
            document.getElementById('btnImport').textContent = '⏳ Importando...';
            
            fetch('import_itau_parser.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                mostrarResultado(data);
                document.getElementById('btnImport').textContent = '🚀 Importar Transações';
                document.getElementById('btnImport').disabled = false;
                
                if (data.success && data.imported > 0) {
                    document.getElementById('btnRegras').disabled = false;
                }
            })
            .catch(error => {
                alert('Erro na importação: ' + error);
                document.getElementById('btnImport').textContent = '🚀 Importar Transações';
                document.getElementById('btnImport').disabled = false;
            });
        }
        
        function mostrarResultado(data) {
            const resultsDiv = document.getElementById('results');
            const contentDiv = document.getElementById('resultContent');
            
            resultsDiv.style.display = 'block';
            
            if (data.success) {
                const porcentagem = data.total > 0 
                    ? Math.round((data.imported / data.total) * 100) 
                    : 0;
                
                contentDiv.innerHTML = `
                    <div class="result-box">
                        <div style="margin-bottom: 20px;">
                            <span class="status success">✓ IMPORTAÇÃO BEM-SUCEDIDA</span>
                        </div>
                        
                        <div style="display: flex; flex-wrap: wrap;">
                            <div class="metric">
                                <div class="metric-label">Encontradas</div>
                                <div class="metric-value">${data.total}</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Importadas</div>
                                <div class="metric-value">${data.imported}</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Duplicadas</div>
                                <div class="metric-value">${data.duplicates}</div>
                            </div>
                        </div>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${porcentagem}%">
                                ${porcentagem}% importado
                            </div>
                        </div>
                        
                        ${data.errors && data.errors.length > 0 ? `
                            <div style="margin-top: 20px;">
                                <span class="status warning">⚠ ${data.errors.length} erros</span>
                                <ul style="margin: 10px 0 0 20px; color: #666;">
                                    ${data.errors.slice(0, 5).map(e => `<li>${e}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                        
                        ${data.debug ? `
                            <details style="margin-top: 20px;">
                                <summary style="cursor: pointer; font-weight: 600; color: #667eea;">
                                    🔍 Ver Debug Info
                                </summary>
                                <pre>${JSON.stringify(data.debug, null, 2)}</pre>
                            </details>
                        ` : ''}
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `
                    <div class="result-box">
                        <span class="status error">✗ ERRO NA IMPORTAÇÃO</span>
                        <p style="margin: 15px 0; color: #721c24;">
                            <strong>Erro:</strong> ${data.error}
                        </p>
                        ${data.debug ? `
                            <details>
                                <summary style="cursor: pointer; font-weight: 600;">
                                    🔍 Ver Detalhes
                                </summary>
                                <pre>${JSON.stringify(data.debug, null, 2)}</pre>
                            </details>
                        ` : ''}
                    </div>
                `;
            }
        }
        
        function aplicarRegras() {
            document.getElementById('btnRegras').disabled = true;
            document.getElementById('btnRegras').textContent = '⏳ Aplicando...';
            
            fetch('../api/admin_api.php?action=aplicarRegrasAutomaticas', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                mostrarResultadoRegras(data);
                document.getElementById('btnRegras').textContent = '🤖 Aplicar Regras Automáticas';
            })
            .catch(error => {
                alert('Erro ao aplicar regras: ' + error);
                document.getElementById('btnRegras').textContent = '🤖 Aplicar Regras Automáticas';
                document.getElementById('btnRegras').disabled = false;
            });
        }
        
        function mostrarResultadoRegras(data) {
            const div = document.getElementById('regrasResult');
            const content = document.getElementById('regrasContent');
            
            div.style.display = 'block';
            
            if (data.success) {
                const stats = data.stats;
                const porcentagem = stats.transacoes_analisadas > 0
                    ? Math.round((stats.transacoes_classificadas / stats.transacoes_analisadas) * 100)
                    : 0;
                
                content.innerHTML = `
                    <div class="result-box">
                        <span class="status success">✓ REGRAS APLICADAS</span>
                        
                        <div style="display: flex; flex-wrap: wrap; margin: 20px 0;">
                            <div class="metric">
                                <div class="metric-label">Analisadas</div>
                                <div class="metric-value">${stats.transacoes_analisadas}</div>
                            </div>
                            <div class="metric">
                                <div class="metric-label">Categorizadas</div>
                                <div class="metric-value">${stats.transacoes_classificadas}</div>
                            </div>
                        </div>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${porcentagem}%">
                                ${porcentagem}% categorizadas
                            </div>
                        </div>
                        
                        ${Object.keys(stats.regras_aplicadas).length > 0 ? `
                            <table style="margin-top: 20px;">
                                <thead>
                                    <tr>
                                        <th>Regra</th>
                                        <th>Aplicações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.entries(stats.regras_aplicadas).map(([regra, count]) => `
                                        <tr>
                                            <td><strong>${regra}</strong></td>
                                            <td>${count}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : ''}
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="result-box">
                        <span class="status error">✗ ERRO</span>
                        <p style="margin-top: 15px;">${data.error || 'Erro desconhecido'}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>