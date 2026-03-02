<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Documentos - AEMF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .upload-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .upload-area {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .upload-area:hover {
            transform: translateY(-5px);
        }
        
        .upload-zone {
            border: 3px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            min-height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .upload-zone.dragover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .upload-zone.extrato {
            border-color: #28a745;
        }
        
        .upload-zone.extrato.dragover {
            background: #e8f5e9;
        }
        
        .upload-zone.comprovante {
            border-color: #ffc107;
        }
        
        .upload-zone.comprovante.dragover {
            background: #fff8e1;
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .extrato .upload-icon {
            color: #28a745;
        }
        
        .comprovante .upload-icon {
            color: #ffc107;
        }
        
        .upload-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .upload-desc {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn-select {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s;
        }
        
        .btn-select:hover {
            transform: scale(1.05);
        }
        
        .file-list {
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            background: #f5f5f5;
            margin: 5px 0;
            border-radius: 5px;
        }
        
        .file-item .name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .file-item .size {
            color: #666;
            font-size: 12px;
            margin: 0 10px;
        }
        
        .file-item .remove {
            color: #dc3545;
            cursor: pointer;
        }
        
        .btn-process {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 30px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-process:hover:not(:disabled) {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3);
        }
        
        .btn-process:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .progress-area {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .progress-bar {
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .status-message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 16px;
            transition: transform 0.3s;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Voltar ao Dashboard
        </a>
        
        <div class="header">
            <h1>
                <i class="fas fa-file-upload"></i>
                Importador de Documentos Financeiros
            </h1>
        </div>
        
        <div class="upload-grid">
            <!-- Área de Upload de Extratos -->
            <div class="upload-area">
                <h2 style="color: #28a745; margin-bottom: 20px;">
                    <i class="fas fa-file-invoice"></i> Extratos Bancários
                </h2>
                <div class="upload-zone extrato" id="extratoZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-title">Arraste o extrato aqui</div>
                    <div class="upload-desc">ou clique para selecionar</div>
                    <button class="btn-select">Selecionar Extrato</button>
                    <input type="file" id="extratoInput" accept=".pdf" style="display: none;">
                </div>
                <div class="file-list" id="extratoList"></div>
            </div>
            
            <!-- Área de Upload de Comprovantes -->
            <div class="upload-area">
                <h2 style="color: #ffc107; margin-bottom: 20px;">
                    <i class="fas fa-receipt"></i> Comprovantes
                </h2>
                <div class="upload-zone comprovante" id="comprovanteZone">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-title">Arraste comprovantes aqui</div>
                    <div class="upload-desc">Múltiplos arquivos permitidos</div>
                    <button class="btn-select">Selecionar Comprovantes</button>
                    <input type="file" id="comprovanteInput" accept=".pdf" multiple style="display: none;">
                </div>
                <div class="file-list" id="comprovanteList"></div>
            </div>
        </div>
        
        <button class="btn-process" id="btnProcess" disabled>
            <i class="fas fa-cog"></i> Processar Documentos
        </button>
        
        <div class="progress-area" id="progressArea">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
            <div class="status-message" id="statusMessage"></div>
        </div>
    </div>
    
    <script>
        let extratoFile = null;
        let comprovanteFiles = [];
        
        // Setup das zonas de upload
        setupDropZone('extratoZone', 'extratoInput', false);
        setupDropZone('comprovanteZone', 'comprovanteInput', true);
        
        function setupDropZone(zoneId, inputId, multiple) {
            const zone = document.getElementById(zoneId);
            const input = document.getElementById(inputId);
            const btn = zone.querySelector('.btn-select');
            
            // Click no botão
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                input.click();
            });
            
            // Click na zona
            zone.addEventListener('click', () => {
                input.click();
            });
            
            // Drag and drop
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('dragover');
            });
            
            zone.addEventListener('dragleave', () => {
                zone.classList.remove('dragover');
            });
            
            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('dragover');
                handleFiles(e.dataTransfer.files, zoneId.includes('extrato'));
            });
            
            // Input change
            input.addEventListener('change', (e) => {
                handleFiles(e.target.files, zoneId.includes('extrato'));
            });
        }
        
        function handleFiles(files, isExtrato) {
            if (isExtrato) {
                if (files.length > 0) {
                    extratoFile = files[0];
                    updateFileList('extratoList', [extratoFile]);
                }
            } else {
                comprovanteFiles = Array.from(files);
                updateFileList('comprovanteList', comprovanteFiles);
            }
            
            checkCanProcess();
        }
        
        function updateFileList(listId, files) {
            const list = document.getElementById(listId);
            list.innerHTML = '';
            
            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'file-item';
                item.innerHTML = `
                    <span class="name">
                        <i class="fas fa-file-pdf" style="color: #dc3545; margin-right: 10px;"></i>
                        ${file.name}
                    </span>
                    <span class="size">${formatFileSize(file.size)}</span>
                    <i class="fas fa-times remove" onclick="removeFile('${listId}', ${index})"></i>
                `;
                list.appendChild(item);
            });
        }
        
        function removeFile(listId, index) {
            if (listId === 'extratoList') {
                extratoFile = null;
                document.getElementById('extratoInput').value = '';
                updateFileList('extratoList', []);
            } else {
                comprovanteFiles.splice(index, 1);
                updateFileList('comprovanteList', comprovanteFiles);
            }
            checkCanProcess();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        function checkCanProcess() {
            const btn = document.getElementById('btnProcess');
            btn.disabled = !extratoFile && comprovanteFiles.length === 0;
        }
        
        // Processar arquivos
        document.getElementById('btnProcess').addEventListener('click', async () => {
            const progressArea = document.getElementById('progressArea');
            const progressFill = document.getElementById('progressFill');
            const statusMessage = document.getElementById('statusMessage');
            const btnProcess = document.getElementById('btnProcess');
            
            progressArea.style.display = 'block';
            btnProcess.disabled = true;
            statusMessage.style.display = 'none';
            
            const formData = new FormData();
            
            // Adicionar extrato se existir
            if (extratoFile) {
                formData.append('extrato', extratoFile);
            }
            
            // Adicionar comprovantes
            comprovanteFiles.forEach(file => {
                formData.append('comprovantes[]', file);
            });
            
            try {
                // Atualizar progresso
                progressFill.style.width = '30%';
                progressFill.textContent = 'Enviando arquivos...';
                
                const response = await fetch('api/process_documents.php', {
                    method: 'POST',
                    body: formData
                });
                
                progressFill.style.width = '70%';
                progressFill.textContent = 'Processando...';
                
                const result = await response.json();
                
                progressFill.style.width = '100%';
                progressFill.textContent = 'Concluído!';
                
                if (result.success) {
                    statusMessage.className = 'status-message success';
                    statusMessage.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        <strong>Processamento concluído!</strong><br>
                        ${result.extrato ? `Extrato: ${result.extrato.imported} transações importadas<br>` : ''}
                        ${result.comprovantes ? `Comprovantes: ${result.comprovantes.matched} de ${result.comprovantes.processed} conciliados<br>` : ''}
                        <br>
                        <a href="index.php" style="color: #155724;">Ver Dashboard</a>
                    `;
                } else {
                    throw new Error(result.error || 'Erro no processamento');
                }
                
            } catch (error) {
                progressFill.style.width = '100%';
                progressFill.textContent = 'Erro!';
                progressFill.style.background = '#dc3545';
                
                statusMessage.className = 'status-message error';
                statusMessage.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Erro no processamento:</strong><br>
                    ${error.message}
                `;
            } finally {
                statusMessage.style.display = 'block';
                btnProcess.disabled = false;
                checkCanProcess();
            }
        });
    </script>
</body>
</html>
