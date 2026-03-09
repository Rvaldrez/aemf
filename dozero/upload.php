<?php
// dozero/upload.php — Importação de extratos e comprovantes (somente admin)
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Importar Documentos — AEMFPAR</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--primary:#1a3c5e;--accent:#2d7dd2;--success:#28a745;--danger:#dc3545;--border:#dee2e6;--shadow:0 2px 12px rgba(0,0,0,.08)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;min-height:100vh}
nav{background:var(--primary);color:#fff;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:60px;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.nav-brand{font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px}
.nav-links a{color:rgba(255,255,255,.85);text-decoration:none;margin-left:20px;font-size:14px;padding:6px 10px;border-radius:6px}
.nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,.15);color:#fff}
.nav-user a{color:rgba(255,255,255,.8);text-decoration:none;background:rgba(255,255,255,.15);padding:6px 14px;border-radius:6px;font-size:13px}
.main{max-width:900px;margin:32px auto;padding:0 20px}
h2{color:var(--primary);font-size:22px;margin-bottom:24px;display:flex;align-items:center;gap:10px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
@media(max-width:700px){.grid{grid-template-columns:1fr}}
.panel{background:#fff;border-radius:14px;padding:28px;box-shadow:var(--shadow)}
.panel h3{font-size:16px;font-weight:600;margin-bottom:16px;color:#333;display:flex;align-items:center;gap:8px}
.drop-zone{border:2.5px dashed #ccc;border-radius:10px;padding:36px 20px;text-align:center;cursor:pointer;transition:.25s;min-height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px}
.drop-zone.ofx{border-color:#28a745}
.drop-zone.pdf{border-color:#ffc107}
.drop-zone.dragover{background:#f0f8ff;border-color:var(--accent)}
.drop-zone i{font-size:42px;color:#ccc}
.drop-zone.ofx i{color:#28a745}
.drop-zone.pdf i{color:#ffc107}
.drop-zone p{color:#666;font-size:14px}
.drop-zone .hint{font-size:12px;color:#aaa}
.file-list{margin-top:12px;max-height:120px;overflow-y:auto}
.file-item{display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#f8f9fa;border-radius:6px;margin-bottom:4px;font-size:13px}
.file-item .rm{color:var(--danger);cursor:pointer;background:none;border:none;font-size:14px;padding:0 4px}
.btn{padding:10px 22px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:.2s}
.btn-primary{background:var(--accent);color:#fff;width:100%}
.btn-primary:hover{background:#1a68ba}
.btn-primary:disabled{opacity:.5;cursor:not-allowed}
.progress-wrap{display:none;margin-top:16px}
.progress-bar-bg{background:#e9ecef;border-radius:20px;height:8px;overflow:hidden}
.progress-bar{background:var(--accent);height:100%;width:0;transition:width .4s;border-radius:20px}
.result-panel{background:#fff;border-radius:14px;padding:24px;box-shadow:var(--shadow);display:none}
.result-panel h3{font-size:16px;font-weight:600;margin-bottom:16px;color:#333}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-bottom:16px}
.stat-box{background:#f8f9fa;border-radius:10px;padding:16px;text-align:center}
.stat-box .n{font-size:28px;font-weight:700;color:var(--primary)}
.stat-box .l{font-size:12px;color:#666;margin-top:4px}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:14px}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
.spinner{display:inline-block;width:16px;height:16px;border:3px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<nav>
    <div class="nav-brand"><i class="fa-solid fa-building-columns"></i> AEMFPAR</div>
    <div class="nav-links">
        <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="upload.php" class="active"><i class="fa-solid fa-upload"></i> Importar</a>
        <a href="admin.php"><i class="fa-solid fa-sliders"></i> Administração</a>
    </div>
    <div class="nav-user"><a href="sair.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></div>
</nav>

<div class="main">
    <h2><i class="fa-solid fa-file-import" style="color:var(--accent)"></i> Importar Documentos</h2>

    <div class="grid">
        <!-- OFX -->
        <div class="panel">
            <h3><i class="fa-solid fa-file-lines" style="color:#28a745"></i> Extrato OFX</h3>
            <div class="drop-zone ofx" id="dropOfx" onclick="document.getElementById('fileOfx').click()">
                <i class="fa-solid fa-file-waveform"></i>
                <p>Clique ou arraste o arquivo .OFX</p>
                <span class="hint">Formato: Open Financial Exchange</span>
            </div>
            <input type="file" id="fileOfx" accept=".ofx" style="display:none">
            <div class="file-list" id="listOfx"></div>
        </div>

        <!-- PDFs -->
        <div class="panel">
            <h3><i class="fa-solid fa-file-pdf" style="color:#ffc107"></i> Comprovantes PDF</h3>
            <div class="drop-zone pdf" id="dropPdf" onclick="document.getElementById('filePdf').click()">
                <i class="fa-solid fa-file-pdf"></i>
                <p>Clique ou arraste os PDFs</p>
                <span class="hint">Múltiplos arquivos aceitos</span>
            </div>
            <input type="file" id="filePdf" accept=".pdf" multiple style="display:none">
            <div class="file-list" id="listPdf"></div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:24px">
        <button class="btn btn-primary" id="btnImport" onclick="importar()">
            <i class="fa-solid fa-cloud-arrow-up"></i> Importar Documentos
        </button>
        <div class="progress-wrap" id="progressWrap">
            <div style="font-size:13px;color:#666;margin-bottom:6px" id="progressLabel">Processando…</div>
            <div class="progress-bar-bg"><div class="progress-bar" id="progressBar"></div></div>
        </div>
    </div>

    <div class="result-panel" id="resultPanel">
        <h3><i class="fa-solid fa-circle-check" style="color:#28a745"></i> Resultado da Importação</h3>
        <div id="resultContent"></div>
    </div>
</div>

<script>
const ofxInput = document.getElementById('fileOfx');
const pdfInput = document.getElementById('filePdf');

function renderList(listEl, files){
    listEl.innerHTML = '';
    Array.from(files).forEach((f,i) => {
        const d = document.createElement('div');
        d.className = 'file-item';
        d.innerHTML = `<span>${f.name}</span><span style="color:#aaa;font-size:12px">${(f.size/1024).toFixed(1)}KB</span>`;
        listEl.appendChild(d);
    });
}

ofxInput.addEventListener('change', () => renderList(document.getElementById('listOfx'), ofxInput.files));
pdfInput.addEventListener('change', () => renderList(document.getElementById('listPdf'), pdfInput.files));

// Drag & drop
['dropOfx','dropPdf'].forEach((id,idx) => {
    const el   = document.getElementById(id);
    const input = idx === 0 ? ofxInput : pdfInput;
    el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('dragover'); });
    el.addEventListener('dragleave', () => el.classList.remove('dragover'));
    el.addEventListener('drop', e => {
        e.preventDefault(); el.classList.remove('dragover');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    });
});

async function importar(){
    const btn = document.getElementById('btnImport');
    if(!ofxInput.files.length && !pdfInput.files.length){
        alert('Selecione pelo menos um arquivo para importar.');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div> Processando…';

    const pw = document.getElementById('progressWrap');
    const pb = document.getElementById('progressBar');
    const pl = document.getElementById('progressLabel');
    pw.style.display = 'block';
    pb.style.width   = '20%';
    pl.textContent   = 'Enviando arquivos…';

    const fd = new FormData();
    if(ofxInput.files.length)  fd.append('extrato', ofxInput.files[0]);
    if(pdfInput.files.length){
        Array.from(pdfInput.files).forEach(f => fd.append('comprovantes[]', f));
    }

    try {
        pb.style.width = '50%';
        pl.textContent = 'Importando transações…';
        const r = await fetch('api/upload.php', { method:'POST', body:fd });
        pb.style.width = '90%';
        pl.textContent = 'Finalizando…';
        const j = await r.json();
        pb.style.width = '100%';
        showResult(j);
    } catch(e){
        showResult({ success:false, error: e.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Importar Documentos';
        setTimeout(() => { pw.style.display='none'; pb.style.width='0'; }, 1500);
    }
}

function showResult(j){
    const rp = document.getElementById('resultPanel');
    const rc = document.getElementById('resultContent');
    rp.style.display = 'block';
    rp.scrollIntoView({ behavior:'smooth' });

    if(!j.success){
        rc.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Erro: ${j.error||'desconhecido'}</div>`;
        return;
    }

    const e = j.extrato  || {};
    const c = j.comprovantes || {};
    const s = j.stats    || {};

    rc.innerHTML = `
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Importação concluída com sucesso!</div>
        <div class="stat-grid">
            <div class="stat-box"><div class="n">${e.imported||0}</div><div class="l">Transações importadas</div></div>
            <div class="stat-box"><div class="n">${e.duplicates||0}</div><div class="l">Duplicatas ignoradas</div></div>
            <div class="stat-box"><div class="n">${c.processed||0}</div><div class="l">PDFs processados</div></div>
            <div class="stat-box"><div class="n">${c.matched||0}</div><div class="l">Conciliados</div></div>
            <div class="stat-box"><div class="n">${s.total||0}</div><div class="l">Total no banco</div></div>
            <div class="stat-box"><div class="n">${s.sem_categoria||0}</div><div class="l">Sem categoria</div></div>
        </div>
        <p style="font-size:13px;color:#666;margin-top:8px">
            <i class="fa-solid fa-circle-info" style="color:var(--accent)"></i>
            As regras automáticas foram aplicadas. Acesse <a href="admin.php">Administração</a> para classificar o restante.
        </p>`;
}
</script>
</body>
</html>
