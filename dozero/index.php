<?php
// dozero/index.php — Dashboard principal AEMFPAR
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — AEMFPAR</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<style>
:root{
    --primary:#1a3c5e;--accent:#2d7dd2;--success:#28a745;--warning:#ffc107;
    --danger:#dc3545;--info:#17a2b8;--muted:#6c757d;--light:#f8f9fa;
    --white:#fff;--border:#dee2e6;--shadow:0 2px 12px rgba(0,0,0,.08);
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f0f4f8;color:#333;min-height:100vh}

/* ── NAV ── */
nav{background:var(--primary);color:#fff;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:60px;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.nav-brand{font-size:20px;font-weight:700;display:flex;align-items:center;gap:8px}
.nav-brand-logo{height:26px;width:26px;min-width:26px;min-height:26px;object-fit:contain;display:block;flex-shrink:0}
.nav-links a{color:rgba(255,255,255,.85);text-decoration:none;margin-left:20px;font-size:14px;padding:6px 10px;border-radius:6px;transition:.2s}
.nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,.15);color:#fff}
.nav-user{font-size:13px;color:rgba(255,255,255,.7);display:flex;align-items:center;gap:12px}
.btn-logout{background:rgba(255,255,255,.15);border:none;color:#fff;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:13px;text-decoration:none}
.btn-logout:hover{background:rgba(255,255,255,.25)}

/* ── MAIN ── */
.main{padding:28px 32px;max-width:1400px;margin:0 auto}

/* ── TOOLBAR ── */
.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.toolbar h2{font-size:22px;color:var(--primary);font-weight:700}
.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
select,input[type=month]{padding:8px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;color:#495057;outline:none;background:#fff;cursor:pointer;min-width:200px}
select:focus,input[type=month]:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(45,125,210,.12)}
.btn{padding:8px 18px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.2s;text-decoration:none}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:#1a68ba}
.btn-success{background:var(--success);color:#fff}
.btn-success:hover{background:#1e7e34}

/* ── Period Toggle ── */
.period-toggle{display:flex;border:1.5px solid var(--border);border-radius:8px;overflow:hidden;background:#fff}
.period-toggle button{padding:7px 16px;border:none;background:transparent;cursor:pointer;font-size:14px;color:#495057;transition:.15s;white-space:nowrap}
.period-toggle button.active{background:var(--accent);color:#fff}

/* ── CARDS ── */
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:28px}
.card{background:#fff;border-radius:14px;padding:24px 22px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:18px;position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
.card.green::before{background:var(--success)}
.card.blue::before{background:var(--info)}
.card.red::before{background:var(--danger)}
.card.teal::before{background:var(--accent)}
.card.grey::before{background:var(--muted)}
.card-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.card.green .card-icon{background:#e8f5e9;color:var(--success)}
.card.blue .card-icon{background:#e3f2fd;color:var(--info)}
.card.red .card-icon{background:#fdecea;color:var(--danger)}
.card.teal .card-icon{background:#e8f4f8;color:var(--accent)}
.card.grey .card-icon{background:#f3f3f3;color:var(--muted)}
.card-body .label{font-size:13px;color:var(--muted);margin-bottom:6px;font-weight:500;text-transform:uppercase;letter-spacing:.5px}
.card-body .value{font-size:26px;font-weight:700;color:#212529;line-height:1}
.card-body .value.loading{font-size:18px;color:#adb5bd}
.card-body .sub{font-size:12px;color:var(--muted);margin-top:6px}

/* ── GRID ── */
.grid-2{display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:28px}
@media(max-width:900px){.grid-2{grid-template-columns:1fr}}

/* ── PANEL ── */
.panel{background:#fff;border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
.panel-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.panel-header h3{font-size:16px;font-weight:600;color:#333}
.panel-body{padding:20px 22px}

/* ── CHART ── */
.chart-wrap{position:relative;height:300px}
.pie-wrap{position:relative;height:280px;display:flex;align-items:center;justify-content:center}

/* ── TABLE ── */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.search-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.search-bar input{padding:8px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;outline:none;width:240px}
.search-bar input:focus{border-color:var(--accent)}
.filter-tipo{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:14px;background:#fff}

table{width:100%;border-collapse:collapse;font-size:14px}
thead th{background:#f8f9fa;padding:12px 14px;text-align:left;font-weight:600;color:#495057;white-space:nowrap;border-bottom:2px solid var(--border)}
tbody td{padding:11px 14px;border-bottom:1px solid #f0f0f0;vertical-align:middle}
tbody tr:hover{background:#fafbfc}
tbody tr:last-child td{border-bottom:none}
.tx-table td:nth-child(2){max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600}
.badge-aemf{background:#e3f2fd;color:#1565c0}
.badge-pf{background:#fff3e0;color:#e65100}
.badge-receita{background:#e8f5e9;color:#2e7d32}
.badge-sem{background:#f3f3f3;color:#9e9e9e}
.badge-ok{background:#e8f5e9;color:#2e7d32}
.text-muted{color:#adb5bd;font-style:italic}
.valor-credito{color:#28a745;font-weight:600}
.valor-debito{color:#dc3545;font-weight:600}
.desc-comp{color:var(--primary);font-weight:500}
.desc-tag{display:inline-block;font-size:10px;background:#e3f2fd;color:#1565c0;padding:1px 6px;border-radius:10px;margin-left:4px;vertical-align:middle}

/* ── PAGINATION ── */
.pagination{display:flex;align-items:center;justify-content:space-between;margin-top:16px;flex-wrap:wrap;gap:10px}
.pagination .info{font-size:13px;color:var(--muted)}
.pag-btns{display:flex;gap:4px}
.pag-btn{padding:6px 12px;border:1.5px solid var(--border);background:#fff;border-radius:6px;cursor:pointer;font-size:13px;color:#495057;transition:.15s}
.pag-btn:hover{border-color:var(--accent);color:var(--accent)}
.pag-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.pag-btn:disabled{opacity:.4;cursor:not-allowed}

/* ── LOADING SPINNER ── */
.spinner{display:inline-block;width:18px;height:18px;border:3px solid rgba(45,125,210,.2);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading-row td{text-align:center;padding:30px;color:var(--muted)}

/* ── ALERTS ── */
.alert{padding:12px 18px;border-radius:8px;margin-bottom:16px;font-size:14px;display:none}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}

/* ── Print / PDF ── */
@media print {
    nav,.toolbar-right,.period-toggle,#periodoMensal,#periodoAnual,.pagination,.search-bar,.filter-tipo,#alertBox,.btn-logout,a[href="upload.php"],a[href="admin.php"]{display:none!important}
    body{background:#fff}
    .main{padding:8px 12px;max-width:100%}
    .panel,.card{box-shadow:none;border:1px solid #dee2e6}
    .toolbar h2{font-size:18px}
    .grid-2{grid-template-columns:1fr 1fr}
}

@media(max-width:600px){
    .main{padding:12px}
    .toolbar{flex-direction:column;align-items:flex-start}
    .toolbar-right{width:100%;flex-wrap:wrap}
    .period-toggle{width:100%}
    .period-toggle button{flex:1;text-align:center}
    #periodoMensal,#periodoAnual{width:100%}
    #periodoMensal input,#periodoAnual select{width:100%;min-width:0;box-sizing:border-box}
    .cards{grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
    .card{padding:12px 14px;gap:10px}
    .card-icon{width:38px;height:38px;font-size:16px;border-radius:8px}
    .card-body{min-width:0}
    .card-body .label{font-size:11px}
    .card-body .value{font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .card-body .sub{font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    nav .nav-links{display:none}

    /* Sticky first 3 columns (Data, Descrição, Valor) in Movimentos Financeiros */
    .tx-table{table-layout:fixed;font-size:12px;--col1:90px;--col2:140px;--col3:96px}
    .tx-table thead th,.tx-table tbody td{padding:8px 8px}
    .tx-table th:nth-child(1),.tx-table td:nth-child(1){position:sticky;left:0;z-index:2;width:var(--col1)}
    .tx-table th:nth-child(2),.tx-table td:nth-child(2){position:sticky;left:var(--col1);z-index:2;width:var(--col2)}
    .tx-table th:nth-child(3),.tx-table td:nth-child(3){position:sticky;left:calc(var(--col1) + var(--col2));z-index:2;width:var(--col3);text-align:right}
    .tx-table th:nth-child(4),.tx-table td:nth-child(4){width:130px}
    .tx-table th:nth-child(5),.tx-table td:nth-child(5){width:110px}
    .tx-table thead th:nth-child(1),.tx-table thead th:nth-child(2),.tx-table thead th:nth-child(3){background:#f8f9fa}
    .tx-table tbody tr td:nth-child(1),.tx-table tbody tr td:nth-child(2),.tx-table tbody tr td:nth-child(3){background:#fff}
    .tx-table tbody tr:hover td:nth-child(1),.tx-table tbody tr:hover td:nth-child(2),.tx-table tbody tr:hover td:nth-child(3){background:#fafbfc}
}

/* ── Landscape phones (keyboard-visible or short viewport) ── */
@media(orientation:landscape) and (max-height:500px){
    .toolbar{flex-wrap:wrap}
    .toolbar-right{flex-wrap:wrap}
    #periodoMensal input,#periodoAnual select{min-width:200px;width:auto}
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
    <div class="nav-brand"><img src="images/branco_aemf.svg" alt="AEMFPAR" class="nav-brand-logo"> AEMFPAR</div>
    <div class="nav-links">
        <a href="index.php" class="active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <?php if ($isAdmin): ?>
        <a href="upload.php"><i class="fa-solid fa-upload"></i> Importar</a>
        <a href="admin.php"><i class="fa-solid fa-sliders"></i> Administração</a>
        <?php endif; ?>
    </div>
    <div class="nav-user">
        <span><i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars($_SESSION['usuario'] ?? '') ?></span>
        <a href="sair.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>
</nav>

<!-- MAIN -->
<div class="main">

    <!-- TOOLBAR -->
    <div class="toolbar">
        <h2><i class="fa-solid fa-chart-pie" style="color:var(--accent)"></i> Painel Financeiro</h2>
        <div class="toolbar-right">
            <div class="period-toggle">
                <button id="btnMensal" class="active" onclick="setPeriod('mensal')"><i class="fa-solid fa-calendar-day"></i> Mensal</button>
                <button id="btnAnual" onclick="setPeriod('anual')"><i class="fa-solid fa-calendar"></i> Anual</button>
            </div>
            <div id="periodoMensal" style="display:flex;align-items:center;gap:8px">
                <label style="font-size:13px;color:#666">Mês:</label>
                <input type="month" id="monthPicker" value="">
            </div>
            <div id="periodoAnual" style="display:none;align-items:center;gap:8px">
                <label style="font-size:13px;color:#666">Ano:</label>
                <select id="yearPicker" title="Ano"></select>
            </div>
            <?php if ($isAdmin): ?>
            <a href="upload.php" class="btn btn-success"><i class="fa-solid fa-upload"></i> Importar</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ALERT -->
    <div class="alert alert-danger" id="alertBox"></div>

    <!-- SUMMARY CARDS -->
    <div class="cards" id="cardsArea">
        <div class="card green">
            <div class="card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="card-body">
                <div class="label">Entradas</div>
                <div class="value loading" id="val-aportes"><div class="spinner"></div></div>
                <div class="sub" id="sub-aportes">&nbsp;</div>
            </div>
        </div>
        <div class="card red">
            <div class="card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            <div class="card-body">
                <div class="label">Saídas</div>
                <div class="value loading" id="val-saidas"><div class="spinner"></div></div>
                <div class="sub" id="sub-saidas">&nbsp;</div>
            </div>
        </div>
        <div class="card grey">
            <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
            <div class="card-body">
                <div class="label">Saldo Inicial</div>
                <div class="value loading" id="val-sinicial"><div class="spinner"></div></div>
                <div class="sub" id="sub-sinicial">&nbsp;</div>
            </div>
        </div>
        <div class="card teal">
            <div class="card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="card-body">
                <div class="label">Saldo Final</div>
                <div class="value loading" id="val-sfinal"><div class="spinner"></div></div>
                <div class="sub" id="sub-sfinal">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- CHART + CATEGORY -->
    <div class="grid-2">
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-chart-bar" style="color:var(--accent)"></i> Movimentação</h3>
                <span id="chartLabel" style="font-size:13px;color:var(--muted)"></span>
            </div>
            <div class="panel-body">
                <div class="chart-wrap"><canvas id="mainChart"></canvas></div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fa-solid fa-chart-pie" style="color:var(--accent)"></i> Gastos por Categoria</h3>
                <span id="catPeriodo" style="font-size:13px;color:var(--muted)"></span>
            </div>
            <div class="panel-body" style="padding:10px 14px">
                <div class="pie-wrap"><canvas id="pieChart"></canvas></div>
                <div id="pieLegend" style="font-size:12px;margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;justify-content:center"></div>
            </div>
        </div>
    </div>

    <!-- TRANSACTIONS -->
    <div class="panel">
        <div class="panel-header">
            <h3><i class="fa-solid fa-list-ul" style="color:var(--accent)"></i> Movimentos Financeiros</h3>
            <span id="txCount" style="font-size:13px;color:var(--muted)"></span>
        </div>
        <div class="panel-body">
            <!-- Balance summary row above transactions -->
            <div id="saldoRow" style="display:none;background:#f8f9fa;border-radius:8px;padding:10px 16px;margin-bottom:14px;font-size:13px;align-items:center;flex-wrap:wrap;gap:16px">
                <span><strong style="color:var(--muted)">Saldo Inicial:</strong>
                    <strong id="saldoRowSI" style="color:var(--primary);margin-left:4px">R$ —</strong></span>
                <span style="color:#ccc">|</span>
                <span><strong style="color:var(--muted)">Saldo Final:</strong>
                    <strong id="saldoRowSF" style="margin-left:4px">R$ —</strong></span>
            </div>

            <div class="section-header">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Buscar por descrição…">
                    <select class="filter-tipo" id="tipoFilter">
                        <option value="">Todos os tipos</option>
                        <option value="credito">Créditos</option>
                        <option value="debito">Débitos</option>
                    </select>
                </div>
                <div style="font-size:13px;color:var(--muted)" id="txInfo"></div>
            </div>

            <div style="overflow-x:auto">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th style="text-align:right">Valor (R$)</th>
                            <th>Observação</th>
                            <th>Categoria</th>
                        </tr>
                    </thead>
                    <tbody id="txBody">
                        <tr class="loading-row"><td colspan="5"><div class="spinner"></div> Carregando…</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <div class="info" id="pagInfo"></div>
                <div class="pag-btns" id="pagBtns"></div>
            </div>
        </div>
    </div>

<!-- Transaction Detail Modal -->
<div id="txModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999;align-items:center;justify-content:center;padding:16px" onclick="closeTxModal()">
    <div style="background:#fff;border-radius:14px;padding:24px 20px;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.18)" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;border-bottom:1px solid #dee2e6;padding-bottom:12px">
            <h3 style="font-size:16px;color:var(--primary);font-weight:700"><i class="fa-solid fa-receipt" style="color:var(--accent)"></i> Detalhes do Lançamento</h3>
            <button onclick="closeTxModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#adb5bd;line-height:1">&times;</button>
        </div>
        <dl style="display:grid;grid-template-columns:90px 1fr;gap:8px 12px;font-size:14px" id="txModalContent"></dl>
    </div>
</div>

</div><!-- /main -->

<!-- Movimentos Financeiros — hint popup (shown once on scroll-in or after 1.5 s) -->
<div id="txHintModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:998;align-items:center;justify-content:center;padding:16px" onclick="closeTxHintModal()">
    <div style="background:#fff;border-radius:14px;padding:26px 22px;max-width:360px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.2)" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
            <span style="width:38px;height:38px;min-width:38px;border-radius:50%;background:#eef4ff;display:flex;align-items:center;justify-content:center">
                <i class="fa-regular fa-hand-pointer" style="color:#2d7dd2;font-size:17px"></i>
            </span>
            <strong style="color:#1a3c5e;font-size:16px">Dica</strong>
        </div>
        <p style="font-size:14px;color:#495057;line-height:1.6;margin-bottom:18px">
            Toque em qualquer linha da tabela <strong>Movimentos Financeiros</strong> para visualizar todos os detalhes do lançamento.
        </p>
        <label style="display:flex;align-items:center;gap:9px;font-size:13px;color:#6c757d;margin-bottom:18px;cursor:pointer;user-select:none">
            <input type="checkbox" id="txHintNoShow" style="width:16px;height:16px;cursor:pointer;accent-color:#2d7dd2">
            Não exibir esta mensagem novamente
        </label>
        <button onclick="closeTxHintModal()" style="width:100%;padding:12px;background:linear-gradient(135deg,#1a3c5e,#2d7dd2);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer">OK</button>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════════════════
// Utilities
// ═══════════════════════════════════════════════════════════════════════════
const fmt = v => 'R$ ' + parseFloat(v || 0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
const fmtDate = d => d ? d.split('-').reverse().join('/') : '';
const monthLabel = m => {
    if (!m) return '';
    const [y, mo] = m.split('-');
    return new Date(+y, +mo - 1, 1).toLocaleString('pt-BR', {month: 'long', year: 'numeric'});
};
// Abbreviated number for pie center: 278789 → "279K", 2100000 → "2,1M"
const fmtAbbrev = v => {
    v = Math.abs(parseFloat(v) || 0);
    if (v >= 1e6) return (v/1e6).toLocaleString('pt-BR',{minimumFractionDigits:1,maximumFractionDigits:1}) + 'M';
    if (v >= 1e3) return Math.round(v/1e3).toLocaleString('pt-BR') + 'K';
    return v.toLocaleString('pt-BR',{maximumFractionDigits:0});
};

// ── Custom Chart.js plugins ───────────────────────────────────────────────
// Center label plugin for doughnut charts
Chart.register({
    id: 'doughnutCenter',
    beforeDraw(chart) {
        const text = chart.options._centerText;
        if (!text || chart.config.type !== 'doughnut') return;
        const {ctx, chartArea} = chart;
        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top  + chartArea.bottom) / 2;
        ctx.save();
        ctx.font = 'bold 26px "Segoe UI",sans-serif';
        ctx.fillStyle = '#212529';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, cx, cy);
        ctx.restore();
    }
});
        // Percentage labels on pie slices ≥ 5 %
Chart.register({
    id: 'pieSliceLabels',
    afterDatasetsDraw(chart) {
        if (chart.config.type !== 'doughnut') return;
        const MIN_PCT = 5; // only label slices at or above this percentage
        const {ctx} = chart;
        const meta = chart.getDatasetMeta(0);
        const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
        if (!total) return;
        meta.data.forEach((arc, i) => {
            const val = chart.data.datasets[0].data[i];
            const pct = Math.round(val / total * 100);
            if (pct < MIN_PCT) return;
            const mid = arc.startAngle + (arc.endAngle - arc.startAngle) / 2;
            const r   = (arc.innerRadius + arc.outerRadius) / 2;
            const x   = arc.x + Math.cos(mid) * r;
            const y   = arc.y + Math.sin(mid) * r;
            ctx.save();
            ctx.font = 'bold 11px "Segoe UI",sans-serif';
            ctx.fillStyle = '#fff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(pct + '%', x, y);
            ctx.restore();
        });
    }
});
const API_DASH  = 'api/dashboard.php';
const API_ADMIN = 'api/admin.php';

let chartInstance = null;
let pieInstance   = null;
let txPage        = 1;
let txTotalPages  = 1;
let searchTimer   = null;
let periodMode    = 'mensal'; // 'mensal' | 'anual'
let txDataMap     = {}; // populated each time rows are rendered

// ═══════════════════════════════════════════════════════════════════════════
// Period control
// ═══════════════════════════════════════════════════════════════════════════
function setPeriod(mode) {
    periodMode = mode;
    document.getElementById('btnMensal').classList.toggle('active', mode === 'mensal');
    document.getElementById('btnAnual').classList.toggle('active', mode === 'anual');
    document.getElementById('periodoMensal').style.display = mode === 'mensal' ? 'flex' : 'none';
    document.getElementById('periodoAnual').style.display  = mode === 'anual'  ? 'flex' : 'none';
    txPage = 1;
    loadAll();
}

function getMonth() { return document.getElementById('monthPicker').value; }
function getYear()  { return document.getElementById('yearPicker').value; }

// Build year picker
(function initYearPicker() {
    const sel = document.getElementById('yearPicker');
    const cur = new Date().getFullYear();
    for (let y = cur; y >= cur - 5; y--) {
        const o = document.createElement('option');
        o.value = y; o.textContent = y;
        sel.appendChild(o);
    }
    sel.value = cur;
})();

// Load most recent month, then kick off dashboard
async function init() {
    try {
        const j = await fetchJSON(`${API_DASH}?action=latestMonth`);
        if (j.mes) {
            document.getElementById('monthPicker').value = j.mes;
        } else {
            document.getElementById('monthPicker').value = new Date().toISOString().slice(0, 7);
        }
    } catch (e) {
        document.getElementById('monthPicker').value = new Date().toISOString().slice(0, 7);
    }
    loadAll();
}

document.getElementById('monthPicker').addEventListener('change', () => { txPage = 1; loadAll(); });
document.getElementById('yearPicker').addEventListener('change', () => { txPage = 1; loadAll(); });
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { txPage = 1; loadTransactions(); }, 400);
});
document.getElementById('tipoFilter').addEventListener('change', () => { txPage = 1; loadTransactions(); });

// ═══════════════════════════════════════════════════════════════════════════
// Load everything
// ═══════════════════════════════════════════════════════════════════════════
async function fetchJSON(url) {
    const r = await fetch(url);
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const j = await r.json();
    if (j.error) throw new Error(j.error);
    return j;
}

function showError(msg) {
    const a = document.getElementById('alertBox');
    a.textContent = '⚠ ' + msg;
    a.style.display = 'block';
    setTimeout(() => a.style.display = 'none', 6000);
}

function loadAll() {
    loadSummary();
    loadTransactions();
    loadCategories();
    loadChart();
}

// ── Summary cards ─────────────────────────────────────────────────────────
async function loadSummary() {
    ['val-aportes','val-saidas','val-sinicial','val-sfinal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<div class="spinner"></div>';
    });

    try {
        let j;
        if (periodMode === 'mensal') {
            j = await fetchJSON(`${API_DASH}?action=summary&month=${getMonth()}`);
        } else {
            j = await fetchJSON(`${API_DASH}?action=annualSummary&year=${getYear()}`);
        }
        const d = j.data || {};

        const si = parseFloat(d.saldo_inicial  || 0);
        const c  = parseFloat(d.total_creditos || 0);
        const de = parseFloat(d.total_debitos  || 0);
        const sf = parseFloat(d.saldo_final    || si + c - de);

        // Date labels
        const dateLabel = periodMode === 'mensal' ? monthLabel(getMonth()) : 'Ano ' + getYear();

        // Cards
        document.getElementById('val-aportes').className  = 'value';
        document.getElementById('val-aportes').textContent = fmt(c);
        document.getElementById('sub-aportes').textContent = (d.total_transacoes || 0) + ' lançamentos';

        document.getElementById('val-saidas').className   = 'value';
        document.getElementById('val-saidas').textContent = fmt(de);
        document.getElementById('sub-saidas').textContent = '';

        document.getElementById('val-sinicial').className   = 'value';
        document.getElementById('val-sinicial').textContent = fmt(si);
        document.getElementById('sub-sinicial').textContent = dateLabel;

        const elSF = document.getElementById('val-sfinal');
        elSF.className = 'value';
        elSF.textContent = fmt(sf);
        elSF.style.color = sf >= 0 ? '#28a745' : '#dc3545';
        document.getElementById('sub-sfinal').textContent = dateLabel + (sf >= 0 ? ' ▲' : ' ▼');

        // Saldo row inside Movimentos Financeiros
        const saldoRow = document.getElementById('saldoRow');
        const siEl = document.getElementById('saldoRowSI');
        const sfEl = document.getElementById('saldoRowSF');
        if (saldoRow) {
            saldoRow.style.display = 'flex';
            siEl.textContent = fmt(si);
            sfEl.textContent = fmt(sf);
            sfEl.style.color = sf >= 0 ? '#28a745' : '#dc3545';
        }

    } catch (e) {
        showError('Erro ao carregar resumo: ' + e.message);
    }
}

// ── Transactions table ────────────────────────────────────────────────────
async function loadTransactions() {
    const body = document.getElementById('txBody');
    body.innerHTML = '<tr class="loading-row"><td colspan="5"><div class="spinner"></div> Carregando…</td></tr>';
    document.getElementById('txCount').textContent = '';

    const search = encodeURIComponent(document.getElementById('searchInput').value.trim());
    const tipo   = document.getElementById('tipoFilter').value;

    let url;
    if (periodMode === 'mensal') {
        url = `${API_DASH}?action=transactions&month=${getMonth()}&page=${txPage}&limit=10` +
              (search ? `&search=${search}` : '') + (tipo ? `&tipo=${tipo}` : '');
    } else {
        url = `${API_DASH}?action=annualTransactions&year=${getYear()}&page=${txPage}&limit=10` +
              (search ? `&search=${search}` : '') + (tipo ? `&tipo=${tipo}` : '');
    }

    try {
        const j = await fetchJSON(url);
        const rows = j.data || [];
        txTotalPages = j.pages || 1;

        document.getElementById('txCount').textContent = j.total + ' transações';
        document.getElementById('txInfo').textContent  =
            `Exibindo ${rows.length} de ${j.total} — Página ${j.page} de ${Math.max(1, j.pages)}`;

        if (rows.length === 0) {
            body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#adb5bd"><i class="fa-solid fa-inbox"></i> Nenhuma transação encontrada.</td></tr>';
        } else {
            txDataMap = {};
            body.innerHTML = rows.map((t, i) => {
                txDataMap[i] = t;
                const v   = parseFloat(t.valor);
                const cls = t.tipo === 'credito' ? 'valor-credito' : 'valor-debito';
                const sig = t.tipo === 'credito' ? '+' : '-';

                const catDot = t.categoria_cor
                    ? `<span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:${esc(t.categoria_cor)};margin-right:5px"></span>` : '';
                const cat = t.categoria
                    ? `${catDot}<span style="font-size:13px">${esc(t.categoria)}</span>`
                    : '<span class="text-muted">—</span>';

                const descText   = esc(t.descricao);
                const rawExtrato = esc(t.descricao_extrato || '');
                const descEl = rawExtrato && rawExtrato !== descText
                    ? `<span title="Extrato: ${rawExtrato}">${descText}</span>`
                    : `<span>${descText}</span>`;

                const obs = t.ref_observacoes
                    ? `<span style="font-size:12px;color:#555">${esc(t.ref_observacoes)}</span>`
                    : '<span class="text-muted">—</span>';

                const mes = t.mes_referencia ? `<br><span style="font-size:11px;color:var(--muted)">${t.mes_referencia}</span>` : '';
                return `<tr onclick="openTxModal(${i})" onkeydown="if(event.key==='Enter'||event.key===' ')openTxModal(${i})" tabindex="0" style="cursor:pointer">
                    <td style="white-space:nowrap">${fmtDate(t.data)}${periodMode==='anual'?mes:''}</td>
                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${descEl}</td>
                    <td style="text-align:right" class="${cls}">${sig} ${fmt(v)}</td>
                    <td>${obs}</td>
                    <td>${cat}</td>
                </tr>`;
            }).join('');
        }

        renderPagination(j.page, j.pages, j.total);
    } catch (e) {
        body.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;color:#dc3545">Erro: ${e.message}</td></tr>`;
        showError('Erro ao carregar transações: ' + e.message);
    }
}

function classBadge(c) {
    const map   = {aemf:'badge-aemf', pf:'badge-pf', receita:'badge-receita'};
    const label = {aemf:'AEMF', pf:'Pessoa Física', receita:'Receita'};
    if (!c) return '<span class="badge badge-sem">—</span>';
    return `<span class="badge ${map[c]||'badge-sem'}">${label[c]||c}</span>`;
}

function esc(s) {
    return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : '';
}

function renderPagination(page, pages, total) {
    const info = document.getElementById('pagInfo');
    const btns = document.getElementById('pagBtns');
    pages = Math.max(1, pages);
    info.textContent = `Total: ${total} registros`;

    const make = (label, p, disabled, active) => {
        const b = document.createElement('button');
        b.className = 'pag-btn' + (active ? ' active' : '');
        b.innerHTML = label;
        b.disabled  = disabled;
        if (!disabled && !active) b.addEventListener('click', () => { txPage = p; loadTransactions(); });
        return b;
    };

    btns.innerHTML = '';
    btns.appendChild(make('<i class="fa-solid fa-angles-left"></i>',  1,      page <= 1, false));
    btns.appendChild(make('<i class="fa-solid fa-angle-left"></i>',   page-1, page <= 1, false));

    let start = Math.max(1, page-2), end = Math.min(pages, page+2);
    if (start > 1) btns.appendChild(make('…', start-1, false, false));
    for (let p = start; p <= end; p++) btns.appendChild(make(p, p, false, p === page));
    if (end < pages) btns.appendChild(make('…', end+1, false, false));

    btns.appendChild(make('<i class="fa-solid fa-angle-right"></i>',  page+1, page >= pages, false));
    btns.appendChild(make('<i class="fa-solid fa-angles-right"></i>', pages,  page >= pages, false));
}

// ── Pie chart for categories ──────────────────────────────────────────────
async function loadCategories() {
    const legend = document.getElementById('pieLegend');
    legend.innerHTML = '';
    document.getElementById('catPeriodo').textContent =
        periodMode === 'mensal' ? monthLabel(getMonth()) : 'Ano ' + getYear();

    try {
        let url;
        if (periodMode === 'mensal') {
            url = `${API_DASH}?action=byCategory&month=${getMonth()}`;
        } else {
            url = `${API_DASH}?action=byCategory&year=${getYear()}&month=`;
        }
        const j = await fetchJSON(url);
        const rows = j.data || [];

        if (rows.length === 0) {
            if (pieInstance) { pieInstance.destroy(); pieInstance = null; }
            legend.innerHTML = '<span style="color:#adb5bd"><i class="fa-solid fa-inbox"></i> Sem dados</span>';
            return;
        }

        const labels = rows.map(r => r.nome);
        const values = rows.map(r => parseFloat(r.total));
        const colors = rows.map(r => r.cor || '#ccc');
        const totalGastos = values.reduce((a, b) => a + b, 0);

        const ctx = document.getElementById('pieChart').getContext('2d');
        if (pieInstance) pieInstance.destroy();
        pieInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data:            values,
                    backgroundColor: colors.map(c => c + 'cc'),
                    borderColor:     colors,
                    borderWidth:     1.5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                _centerText: fmtAbbrev(totalGastos),
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.label + ': ' + fmt(ctx.raw)
                        }
                    }
                }
            }
        });

        // Custom legend
        legend.innerHTML = rows.map(r =>
            `<span style="display:inline-flex;align-items:center;gap:4px;white-space:nowrap">
                <span style="width:10px;height:10px;border-radius:50%;background:${esc(r.cor||'#ccc')};display:inline-block"></span>
                <span style="font-size:11px;color:#555">${esc(r.nome)}</span>
            </span>`
        ).join('');
    } catch (e) {
        legend.innerHTML = `<span style="color:#dc3545;font-size:12px">Erro: ${e.message}</span>`;
    }
}

// ── Bar+line chart ────────────────────────────────────────────────────────
async function loadChart() {
    const year = getYear();
    document.getElementById('chartLabel').textContent = year;
    try {
        const j = await fetchJSON(`${API_DASH}?action=chart&year=${year}`);
        renderChart(j.labels, j.datasets);
    } catch (e) {
        showError('Erro ao carregar gráfico: ' + e.message);
    }
}

function renderChart(labels, datasets) {
    const ctx = document.getElementById('mainChart').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    const chartDatasets = datasets.map(ds => {
        if (ds.type === 'line') {
            return {
                type: 'line',
                label: ds.label,
                data: ds.data,
                borderColor: ds.color,
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                yAxisID: 'y',
                spanGaps: true,
            };
        }
        return {
            label:           ds.label,
            data:            ds.data,
            backgroundColor: ds.color + 'cc',
            borderColor:     ds.color,
            borderWidth:     1.5,
            borderRadius:    4,
        };
    });

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: chartDatasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ' + fmt(ctx.raw)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'R$ mil', font: { size: 11 }, color: '#6c757d' },
                    ticks: { callback: v => (v / 1000).toLocaleString('pt-BR', {maximumFractionDigits: 1}) }
                },
                x: {
                    ticks: { maxRotation: 45, minRotation: 0 }
                }
            }
        }
    });
}

function openTxModal(i) {
    const t = txDataMap[i];
    if (!t) return;
    const v   = parseFloat(t.valor);
    const cls = t.tipo === 'credito' ? 'color:#28a745' : 'color:#dc3545';
    const sig = t.tipo === 'credito' ? '+' : '-';
    const tipoHtml = (function(tipo){
        const isCredit = tipo === 'credito';
        const color = isCredit ? '#28a745' : '#dc3545';
        const label = isCredit ? 'Crédito' : 'Débito';
        return `<span style="display:inline-flex;align-items:center;gap:5px"><span style="width:10px;height:10px;min-width:10px;border-radius:50%;background:${color};display:inline-block"></span><span style="color:${color};border-bottom:2px solid ${color};font-weight:600;padding-bottom:1px">${label}</span></span>`;
    })(t.tipo);
    const fields = [
        ['Data',      fmtDate(t.data)],
        ['Tipo',      tipoHtml],
        ['Valor',     `<strong style="${cls}">${sig} ${fmt(v)}</strong>`],
        ['Descrição', esc(t.descricao || '—')],
    ];
    if (t.descricao_extrato && t.descricao_extrato !== t.descricao) {
        fields.push(['Extrato', esc(t.descricao_extrato)]);
    }
    fields.push(['Observação', esc(t.ref_observacoes || '—')]);
    fields.push(['Categoria',  t.categoria ? esc(t.categoria) : '—']);
    if (t.mes_referencia) fields.push(['Mês Ref.', esc(t.mes_referencia)]);
    document.getElementById('txModalContent').innerHTML = fields.map(([label, val]) =>
        `<dt style="font-weight:600;color:#6c757d;white-space:nowrap">${label}</dt><dd style="color:#212529;word-break:break-word">${val}</dd>`
    ).join('');
    document.getElementById('txModal').style.display = 'flex';
}
function closeTxModal() { document.getElementById('txModal').style.display = 'none'; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeTxModal(); closeTxHintModal(); } });

// ── Movimentos Financeiros hint popup ─────────────────────────────────────
// Key renamed to _v2 so returning users who had the old key set see it again
const TX_HINT_KEY = 'txHintSeen_v2';
let txHintShown = false;

function showTxHintModal() {
    if (txHintShown) return;
    try { if (localStorage.getItem(TX_HINT_KEY)) return; } catch(_){}
    txHintShown = true;
    const modal = document.getElementById('txHintModal');
    if (modal) modal.style.display = 'flex';
}
function closeTxHintModal() {
    txHintShown = true;
    const modal = document.getElementById('txHintModal');
    if (modal) modal.style.display = 'none';
    if (document.getElementById('txHintNoShow').checked) {
        try { localStorage.setItem(TX_HINT_KEY, '1'); } catch(_){}
    }
}
(function initHintObserver() {
    try { if (localStorage.getItem(TX_HINT_KEY)) return; } catch(_){}
    // Fallback: show 1.5 s after page load (covers fast readers who don't scroll)
    const t = setTimeout(showTxHintModal, 1500);
    // Primary: show as soon as the Movimentos Financeiros panel enters the viewport
    if (window.IntersectionObserver) {
        const txBody = document.getElementById('txBody');
        const panel = txBody && txBody.closest('.panel');
        if (panel) {
            new IntersectionObserver(function(entries, obs) {
                if (entries[0].isIntersecting) {
                    obs.disconnect();
                    clearTimeout(t);
                    showTxHintModal();
                }
            }, { threshold: 0 }).observe(panel);
        }
    }
})();

// ── Init ──────────────────────────────────────────────────────────────────
init();
</script>
</body>
</html>
