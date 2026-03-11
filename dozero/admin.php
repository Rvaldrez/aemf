<?php
// dozero/admin.php — Painel Administrativo (somente admin)
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administração — AEMFPAR</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--primary:#1a3c5e;--accent:#2d7dd2;--success:#28a745;--danger:#dc3545;--warning:#ffc107;--border:#dee2e6;--shadow:0 2px 12px rgba(0,0,0,.08)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;min-height:100vh}
nav{background:var(--primary);color:#fff;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:60px;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.nav-brand{font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px}
.nav-links a{color:rgba(255,255,255,.85);text-decoration:none;margin-left:20px;font-size:14px;padding:6px 10px;border-radius:6px}
.nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,.15);color:#fff}
.nav-user a{color:rgba(255,255,255,.8);text-decoration:none;background:rgba(255,255,255,.15);padding:6px 14px;border-radius:6px;font-size:13px}
.main{max-width:1300px;margin:28px auto;padding:0 20px}
.page-title{color:var(--primary);font-size:22px;font-weight:700;margin-bottom:24px;display:flex;align-items:center;gap:10px}

/* Tabs */
.tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--border);overflow-x:auto}
.tab-btn{padding:12px 22px;background:none;border:none;cursor:pointer;font-size:14px;color:#666;border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:.2s;display:flex;align-items:center;gap:6px}
.tab-btn:hover{color:var(--accent)}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent);font-weight:600}
.tab-pane{display:none}
.tab-pane.active{display:block}

/* Panels */
.panel{background:#fff;border-radius:14px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px}
.panel-header{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.panel-header h3{font-size:15px;font-weight:600;color:#333;display:flex;align-items:center;gap:8px}
.panel-body{padding:20px 22px}

/* Buttons */
.btn{padding:8px 16px;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.2s;text-decoration:none}
.btn-sm{padding:5px 10px;font-size:12px}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:#1a68ba}
.btn-success{background:var(--success);color:#fff}
.btn-success:hover{background:#1e7e34}
.btn-danger{background:var(--danger);color:#fff}
.btn-danger:hover{background:#bd2130}
.btn-warning{background:var(--warning);color:#212529}
.btn-warning:hover{background:#e0a800}
.btn-outline{background:#fff;border:1.5px solid var(--border);color:#495057}
.btn-outline:hover{background:#f8f9fa}

/* Table */
table{width:100%;border-collapse:collapse;font-size:14px}
thead th{background:#f8f9fa;padding:11px 14px;text-align:left;font-weight:600;color:#495057;border-bottom:2px solid var(--border);white-space:nowrap}
tbody td{padding:10px 14px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
tbody tr:hover{background:#fafbfc}
tbody tr:last-child td{border-bottom:none}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap}
.badge-receita{background:#e8f5e9;color:#2e7d32}
.badge-aemf{background:#e3f2fd;color:#1565c0}
.badge-pf{background:#fff3e0;color:#e65100}
.dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px}
.text-muted{color:#adb5bd;font-style:italic}

/* Form */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
@media(max-width:600px){.form-row{grid-template-columns:1fr}}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group label{font-size:13px;font-weight:500;color:#495057}
.form-group input,.form-group select,.form-group textarea{padding:9px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:14px;color:#212529;outline:none;transition:.2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(45,125,210,.12)}
.form-actions{display:flex;gap:10px;margin-top:4px}

/* Alert */
.alert{padding:11px 16px;border-radius:8px;font-size:14px;margin-bottom:14px;display:none}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}

/* Classify */
.classify-row{display:grid;grid-template-columns:90px 1fr 1fr;gap:8px;padding:12px 0;border-bottom:1px solid #f0f0f0;align-items:start}
.classify-row:last-child{border-bottom:none}
.classify-row-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;grid-column:1/-1}
.classify-desc{flex:1;min-width:200px;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.classify-val{font-size:13px;font-weight:600;color:var(--danger);white-space:nowrap}
.classify-date{font-size:12px;color:#aaa;white-space:nowrap}
.classify-fields{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:8px}
@media(max-width:600px){.classify-fields{grid-template-columns:1fr}.classify-row{grid-template-columns:1fr}}
.classify-fields input,.classify-fields select{padding:5px 8px;border:1.5px solid var(--border);border-radius:6px;font-size:12px;width:100%}
.classify-fields input:focus,.classify-fields select:focus{border-color:var(--accent);outline:none}
.classify-actions{grid-column:1/-1;display:flex;gap:6px}

.spinner{display:inline-block;width:16px;height:16px;border:3px solid rgba(45,125,210,.2);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading-row td{text-align:center;padding:24px;color:#adb5bd}
</style>
</head>
<body>
<nav>
    <div class="nav-brand"><i class="fa-solid fa-building-columns"></i> AEMFPAR</div>
    <div class="nav-links">
        <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="upload.php"><i class="fa-solid fa-upload"></i> Importar</a>
        <a href="admin.php" class="active"><i class="fa-solid fa-sliders"></i> Administração</a>
    </div>
    <div class="nav-user"><a href="sair.php"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></div>
</nav>

<div class="main">
    <div class="page-title"><i class="fa-solid fa-sliders" style="color:var(--accent)"></i> Painel Administrativo</div>

    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('cat')"><i class="fa-solid fa-tags"></i> Categorias</button>
        <button class="tab-btn" onclick="showTab('ref')"><i class="fa-solid fa-link"></i> Referências</button>
        <button class="tab-btn" onclick="showTab('cls')"><i class="fa-solid fa-pen-to-square"></i> Classificar Transações</button>
        <button class="tab-btn" onclick="showTab('saldos')"><i class="fa-solid fa-wallet"></i> Saldos Mensais</button>
    </div>

    <!-- ═══════════════════════════════════════════════════════ CATEGORIAS -->
    <div id="tab-cat" class="tab-pane active">
        <div class="alert alert-success" id="catAlert"></div>
        <div class="alert alert-danger"  id="catErr"></div>

        <!-- Form -->
        <div class="panel">
            <div class="panel-header">
                <h3 id="catFormTitle"><i class="fa-solid fa-plus"></i> Nova Categoria</h3>
            </div>
            <div class="panel-body">
                <input type="hidden" id="catId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" id="catNome" placeholder="Nome da categoria">
                    </div>
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select id="catTipo">
                            <option value="receita">Receita</option>
                            <option value="despesa_aemf">Despesa AEMF</option>
                            <option value="despesa_pf">Despesa PF</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Grupo</label>
                        <input type="text" id="catGrupo" placeholder="Ex: Operacional">
                    </div>
                    <div class="form-group">
                        <label>Cor</label>
                        <input type="color" id="catCor" value="#17a2b8">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" onclick="salvarCategoria()"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                    <button class="btn btn-outline" onclick="resetCatForm()"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-list"></i> Categorias Cadastradas</h3>
            </div>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Cor</th><th>Nome</th><th>Tipo</th><th>Grupo</th><th>Ações</th></tr></thead>
                    <tbody id="catBody"><tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ REFERÊNCIAS -->
    <div id="tab-ref" class="tab-pane">
        <div class="alert alert-success" id="refAlert"></div>
        <div class="alert alert-danger"  id="refErr"></div>

        <div class="panel">
            <div class="panel-header"><h3 id="refFormTitle"><i class="fa-solid fa-plus"></i> Nova Referência</h3></div>
            <div class="panel-body">
                <input type="hidden" id="refId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Padrão (fragmento do texto) *</label>
                        <input type="text" id="refPadrao" placeholder="Ex: ELETROPAULO">
                    </div>
                    <div class="form-group">
                        <label>Descrição (substitui o descritivo no relatório)</label>
                        <input type="text" id="refDesc" placeholder="Ex: Conta de Luz CPFL">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Categoria *</label>
                        <select id="refCat"><option value="">— Selecione —</option></select>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Transação</label>
                        <select id="refTipo">
                            <option value="">— Todos —</option>
                            <option>PIX</option><option>TED</option><option>BOLETO</option>
                            <option>DEBITO</option><option>TARIFA</option><option>TRIBUTO</option>
                            <option>RENDIMENTO</option><option>CONCESSIONARIA</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Observações (aparece na coluna "Observação" do relatório)</label>
                        <input type="text" id="refObs" placeholder="Opcional">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" onclick="salvarRef()"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                    <button class="btn btn-outline" onclick="resetRefForm()"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-list"></i> Referências Cadastradas</h3>
            </div>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Padrão</th><th>Descrição</th><th>Categoria</th><th>Tipo</th><th>Observações</th><th>Ações</th></tr></thead>
                    <tbody id="refBody"><tr class="loading-row"><td colspan="6"><div class="spinner"></div></td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ CLASSIFICAR TX -->
    <div id="tab-cls" class="tab-pane">
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Aplicar Regras Automáticas</h3>
                <button class="btn btn-warning" onclick="aplicarRegras()"><i class="fa-solid fa-bolt"></i> Aplicar Regras</button>
            </div>
            <div class="panel-body" id="regrasResult" style="display:none;font-size:14px"></div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-question-circle"></i> Transações sem Categoria (<span id="clsCount">…</span>)</h3>
                <button class="btn btn-outline btn-sm" onclick="loadUncategorized()"><i class="fa-solid fa-rotate"></i> Recarregar</button>
            </div>
            <div class="panel-body" id="clsBody">
                <div style="text-align:center;padding:20px;color:#adb5bd"><div class="spinner"></div></div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ SALDOS MENSAIS -->
    <div id="tab-saldos" class="tab-pane">
        <div class="alert alert-success" id="saldosAlert"></div>
        <div class="alert alert-danger"  id="saldosErr"></div>

        <!-- Override saldo inicial -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Definir Saldo Inicial do Mês</h3>
            </div>
            <div class="panel-body">
                <p style="font-size:13px;color:#666;margin-bottom:16px">
                    Use este formulário para corrigir o saldo inicial de um mês quando o extrato OFX não contiver o saldo inicial (LEDGERBAL).
                    O sistema recalculará em cascata todos os meses seguintes.
                </p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mês de referência (YYYY-MM) *</label>
                        <input type="month" id="saldosMes" placeholder="Ex: 2024-01">
                    </div>
                    <div class="form-group">
                        <label>Saldo Inicial (R$) *</label>
                        <input type="number" id="saldosSI" step="0.01" placeholder="Ex: 50000.00">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" onclick="setSaldoInicial()"><i class="fa-solid fa-floppy-disk"></i> Salvar e Recalcular</button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-table"></i> Saldos por Mês</h3>
                <button class="btn btn-outline btn-sm" onclick="loadSaldos()"><i class="fa-solid fa-rotate"></i> Atualizar</button>
            </div>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr>
                        <th>Mês</th>
                        <th style="text-align:right">Saldo Inicial</th>
                        <th style="text-align:right">Entradas</th>
                        <th style="text-align:right">Saídas</th>
                        <th style="text-align:right">Saldo Final</th>
                    </tr></thead>
                    <tbody id="saldosBody">
                        <tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /main -->

<script>
const API = 'api/admin.php';
let allCats = [];

// ── Tabs ──────────────────────────────────────────────────────────────────
function showTab(name){
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.currentTarget.classList.add('active');
    if(name === 'cls') loadUncategorized();
    if(name === 'saldos') loadSaldos();
}

// ── Fetch helper ──────────────────────────────────────────────────────────
async function api(qs, body){
    const opts = body
        ? { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }
        : {};
    const r = await fetch(API + '?' + qs, opts);
    return r.json();
}

function esc(s){ return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }
function fmt(v){ return 'R$ ' + parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtDate(d){ return d ? d.split('-').reverse().join('/') : ''; }

function flash(id, msg, type){
    const el = document.getElementById(id);
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 4000);
}

// ════════════════════════════════════════════════════════ CATEGORIAS
async function loadCats(){
    const j = await api('action=getCategorias');
    allCats  = j.data || [];
    const tb = document.getElementById('catBody');
    if(!allCats.length){ tb.innerHTML='<tr><td colspan="5" style="text-align:center;padding:20px;color:#adb5bd">Nenhuma categoria cadastrada.</td></tr>'; return; }
    tb.innerHTML = allCats.map(c => {
        const tipoBadge = c.tipo === 'receita' ? 'badge-receita' : c.tipo === 'despesa_aemf' ? 'badge-aemf' : 'badge-pf';
        const tipoLabel = {receita:'Receita',despesa_aemf:'Despesa AEMF',despesa_pf:'Despesa PF'}[c.tipo] || c.tipo;
        return `<tr>
            <td><span class="dot" style="background:${esc(c.cor||'#ccc')}"></span></td>
            <td>${esc(c.nome)}</td>
            <td><span class="badge ${tipoBadge}">${tipoLabel}</span></td>
            <td>${esc(c.grupo||'')}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editCat(${c.id})"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-danger btn-sm" onclick="delCat(${c.id})"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
    // Update cat dropdowns elsewhere
    populateCatDropdowns();
}

function populateCatDropdowns(){
    const sels = [document.getElementById('refCat')];
    sels.forEach(sel => {
        if(!sel) return;
        const cur = sel.value;
        sel.innerHTML = '<option value="">— Selecione —</option>';
        allCats.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id; o.textContent = c.nome;
            sel.appendChild(o);
        });
        if(cur) sel.value = cur;
    });
}

async function salvarCategoria(){
    const id   = document.getElementById('catId').value;
    const nome = document.getElementById('catNome').value.trim();
    if(!nome){ flash('catErr','Nome é obrigatório.','danger'); return; }
    const payload = {
        nome, tipo: document.getElementById('catTipo').value,
        grupo: document.getElementById('catGrupo').value.trim(),
        cor:   document.getElementById('catCor').value,
    };
    let j;
    if(id){
        payload.id = id;
        j = await api('action=updateCategoria', payload);
    } else {
        j = await api('action=saveCategoria', payload);
    }
    if(j.success){ flash('catAlert','Categoria salva com sucesso!','success'); resetCatForm(); loadCats(); }
    else { flash('catErr', j.error || 'Erro ao salvar.', 'danger'); }
}

function editCat(id){
    const c = allCats.find(x => x.id == id);
    if(!c) return;
    document.getElementById('catId').value     = c.id;
    document.getElementById('catNome').value   = c.nome;
    document.getElementById('catTipo').value   = c.tipo;
    document.getElementById('catGrupo').value  = c.grupo || '';
    document.getElementById('catCor').value    = c.cor   || '#17a2b8';
    document.getElementById('catFormTitle').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Categoria';
    document.getElementById('catNome').focus();
    document.getElementById('tab-cat').scrollIntoView({ behavior:'smooth' });
}

async function delCat(id){
    if(!confirm('Excluir esta categoria?')) return;
    const j = await api('action=deleteCategoria&id=' + id);
    if(j.success){ flash('catAlert','Categoria excluída.','success'); loadCats(); }
    else { flash('catErr', j.error || 'Erro.', 'danger'); }
}

function resetCatForm(){
    document.getElementById('catId').value     = '';
    document.getElementById('catNome').value   = '';
    document.getElementById('catTipo').value   = 'despesa_aemf';
    document.getElementById('catGrupo').value  = '';
    document.getElementById('catCor').value    = '#17a2b8';
    document.getElementById('catFormTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Nova Categoria';
}

// ════════════════════════════════════════════════════════ REFERÊNCIAS
let allRefs = [];

async function loadRefs(){
    const j = await api('action=getReferencias');
    allRefs  = j.data || [];
    const tb = document.getElementById('refBody');
    if(!allRefs.length){ tb.innerHTML='<tr><td colspan="6" style="text-align:center;padding:20px;color:#adb5bd">Nenhuma referência cadastrada.</td></tr>'; return; }
    tb.innerHTML = allRefs.map(r => `<tr>
        <td style="font-weight:600">${esc(r.padrao)}</td>
        <td>${esc(r.descricao||'')}</td>
        <td>${esc(r.categoria_nome||'—')}</td>
        <td>${esc(r.tipo_transacao||'—')}</td>
        <td>${esc(r.observacoes||'')}</td>
        <td>
            <button class="btn btn-warning btn-sm" onclick="editRef(${r.id})"><i class="fa-solid fa-pen"></i></button>
            <button class="btn btn-danger btn-sm"  onclick="delRef(${r.id})"><i class="fa-solid fa-trash"></i></button>
        </td>
    </tr>`).join('');
}

async function salvarRef(){
    const id = document.getElementById('refId').value;
    const padrao = document.getElementById('refPadrao').value.trim();
    if(!padrao){ flash('refErr','Padrão é obrigatório.','danger'); return; }
    const payload = {
        padrao,
        descricao:      document.getElementById('refDesc').value.trim() || null,
        categoria_id:   document.getElementById('refCat').value   || null,
        tipo_transacao: document.getElementById('refTipo').value  || null,
        observacoes:    document.getElementById('refObs').value.trim() || null,
    };
    let j;
    if(id){ payload.id = id; j = await api('action=updateReferencia', payload); }
    else   { j = await api('action=saveReferencia', payload); }
    if(j.success){ flash('refAlert','Referência salva!','success'); resetRefForm(); loadRefs(); }
    else { flash('refErr', j.error || 'Erro.', 'danger'); }
}

function editRef(id){
    const r = allRefs.find(x => x.id == id);
    if(!r) return;
    document.getElementById('refId').value     = r.id;
    document.getElementById('refPadrao').value = r.padrao;
    document.getElementById('refDesc').value   = r.descricao || '';
    document.getElementById('refCat').value    = r.categoria_id || '';
    document.getElementById('refTipo').value   = r.tipo_transacao || '';
    document.getElementById('refObs').value    = r.observacoes || '';
    document.getElementById('refFormTitle').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Referência';
    document.getElementById('refPadrao').focus();
}

async function delRef(id){
    if(!confirm('Excluir esta referência?')) return;
    const j = await api('action=deleteReferencia&id=' + id);
    if(j.success){ flash('refAlert','Referência excluída.','success'); loadRefs(); }
    else { flash('refErr', j.error || 'Erro.', 'danger'); }
}

function resetRefForm(){
    ['refId','refPadrao','refDesc','refObs'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('refCat').value  = '';
    document.getElementById('refTipo').value = '';
    document.getElementById('refFormTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Nova Referência';
}

// ════════════════════════════════════════════════════════ CLASSIFICAR
async function loadUncategorized(){
    const body = document.getElementById('clsBody');
    body.innerHTML = '<div style="text-align:center;padding:20px;color:#adb5bd"><div class="spinner"></div></div>';
    const j = await api('action=getTransacoesSemCategoria');
    const rows = j.data || [];
    document.getElementById('clsCount').textContent = rows.length;

    if(!rows.length){
        body.innerHTML = '<p style="text-align:center;color:#28a745;padding:20px"><i class="fa-solid fa-circle-check"></i> Todas as transações estão classificadas!</p>';
        return;
    }

    const catOpts = allCats.map(c => `<option value="${c.id}">${esc(c.nome)}</option>`).join('');
    const tipoOpts = ['PIX','TED','BOLETO','DEBITO','TARIFA','TRIBUTO','RENDIMENTO','CONCESSIONARIA']
        .map(t => `<option value="${t}">${t}</option>`).join('');
    body.innerHTML = rows.map(t => `
        <div class="classify-row">
            <div class="classify-row-meta">
                <span class="classify-date">${fmtDate(t.data)}</span>
                <span class="classify-val">R$ ${parseFloat(t.valor).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
            </div>
            <div class="classify-fields">
                <div>
                    <label style="font-size:11px;color:#888">Descritivo (texto da transação)</label>
                    <input id="clsDesc_${t.id}" value="${esc(t.descricao)}" title="Edite para substituir o descritivo">
                </div>
                <div>
                    <label style="font-size:11px;color:#888">Descrição amigável (substitui no relatório)</label>
                    <input id="clsRefDesc_${t.id}" placeholder="Ex: Conta de Luz" value="">
                </div>
                <div>
                    <label style="font-size:11px;color:#888">Categoria</label>
                    <select id="clsCat_${t.id}">
                        <option value="">— Selecione —</option>${catOpts}
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;color:#888">Tipo de Transação</label>
                    <select id="clsTipo_${t.id}">
                        <option value="">— Todos —</option>${tipoOpts}
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;color:#888">Observações (aparece no relatório)</label>
                    <input id="clsObs_${t.id}" placeholder="Opcional">
                </div>
            </div>
            <div class="classify-actions">
                <button class="btn btn-success btn-sm" onclick="salvarClassif(${t.id})"><i class="fa-solid fa-check"></i> Salvar</button>
            </div>
        </div>`).join('');
}

async function salvarClassif(id){
    const catId   = document.getElementById('clsCat_' + id)?.value || null;
    const newDesc = document.getElementById('clsDesc_' + id)?.value.trim() || null;
    const refDesc = document.getElementById('clsRefDesc_' + id)?.value.trim() || null;
    const tipo    = document.getElementById('clsTipo_' + id)?.value || null;
    const obs     = document.getElementById('clsObs_' + id)?.value.trim() || null;
    const j = await api('action=classificarTransacao', {
        id,
        descricao:       newDesc,
        categoria_id:    catId,
        observacoes:     obs,
        ref_descricao:   refDesc,
        tipo_transacao:  tipo,
        ref_observacoes: obs,
    });
    if(j.success){
        const row = document.querySelector(`#clsCat_${id}`)?.closest('.classify-row');
        if(row){ row.style.background='#e8f5e9'; setTimeout(() => row.remove(), 600); }
        const cnt = document.getElementById('clsCount');
        cnt.textContent = Math.max(0, parseInt(cnt.textContent) - 1);
    }
}

async function aplicarRegras(){
    const btn  = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner"></div> Aplicando…';
    const j = await api('action=aplicarRegras');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Aplicar Regras';
    const res = document.getElementById('regrasResult');
    res.style.display = 'block';
    if(j.success){
        res.innerHTML = `<p style="color:#155724"><i class="fa-solid fa-circle-check"></i>
            <strong>${j.classificadas}</strong> transações classificadas.
            Ainda sem categoria: <strong>${j.sem_categoria}</strong>.</p>`;
        loadUncategorized();
    } else {
        res.innerHTML = `<p style="color:#721c24">Erro: ${esc(j.error)}</p>`;
    }
}

// ════════════════════════════════════════════════════════ SALDOS MENSAIS
async function loadSaldos(){
    const body = document.getElementById('saldosBody');
    body.innerHTML = '<tr class="loading-row"><td colspan="5"><div class="spinner"></div></td></tr>';
    const j = await api('action=getSaldosMensais');
    const rows = j.data || [];
    if(!rows.length){
        body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#adb5bd">Nenhum saldo cadastrado</td></tr>';
        return;
    }
    body.innerHTML = rows.map(r => {
        const si = parseFloat(r.saldo_inicial);
        const sf = parseFloat(r.saldo_final);
        const sfColor = sf >= 0 ? 'color:#28a745' : 'color:#dc3545';
        return `<tr>
            <td><strong>${esc(r.mes_referencia)}</strong></td>
            <td style="text-align:right">${fmt(r.saldo_inicial)}</td>
            <td style="text-align:right;color:#28a745">${fmt(r.total_creditos)}</td>
            <td style="text-align:right;color:#dc3545">${fmt(r.total_debitos)}</td>
            <td style="text-align:right;font-weight:700;${sfColor}">${fmt(r.saldo_final)}</td>
        </tr>`;
    }).join('');
}

async function setSaldoInicial(){
    const mes = document.getElementById('saldosMes').value.trim();
    const si  = document.getElementById('saldosSI').value.trim();
    if(!mes || si === ''){
        flash('saldosErr','Preencha o mês e o saldo inicial.','danger');
        return;
    }
    const j = await api('action=setSaldoInicial', { mes, saldo_inicial: parseFloat(si) });
    if(j.success){
        flash('saldosAlert', `Saldo inicial de ${mes} atualizado. Cascata recalculada.`, 'success');
        loadSaldos();
    } else {
        flash('saldosErr', j.error || 'Erro ao salvar.', 'danger');
    }
}

// ── Init ──────────────────────────────────────────────────────────────────
loadCats().then(() => loadRefs());
</script>
</body>
</html>
