<?php
// index.php - Dashboard Financeiro AEMFPAR
require_once 'includes/config.php';

// ── Conecta ao banco e carrega meses disponíveis ──────────────────────────────
$availableMonths = [];
$defaultMonth    = date('Y-m');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $stmt = $pdo->query(
        "SELECT DISTINCT mes_referencia FROM transacoes
         WHERE  mes_referencia IS NOT NULL
         ORDER  BY mes_referencia DESC LIMIT 36"
    );
    foreach ($stmt->fetchAll() as $row) {
        $availableMonths[] = $row['mes_referencia'];
    }
    if (!empty($availableMonths)) {
        $defaultMonth = $availableMonths[0]; // mês mais recente
    }
} catch (Exception $e) {
    // DB indisponível — o JS tentará carregar via API
}

if (empty($availableMonths)) {
    $availableMonths = [$defaultMonth];
}

$ptMonths = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril',   '05' => 'Maio',      '06' => 'Junho',
    '07' => 'Julho',   '08' => 'Agosto',    '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro',  '12' => 'Dezembro',
];
function monthLabel(string $ym, array $names): string {
    [$y, $m] = explode('-', $ym, 2);
    return ($names[$m] ?? $m) . ' ' . $y;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Executivo - AEMFPAR</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #17a2b8;
            --secondary-color: #d4af37;
            --accent-color: #138496;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
            --border-light: #dee2e6;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 8px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 16px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: var(--white);
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .company-info h1 {
            font-size: 1.8rem;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .company-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn-header {
            padding: 10px 20px;
            border: 1px solid var(--border-light);
            background: var(--white);
            color: var(--text-dark);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-header:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        /* Period Selector */
        .period-selector {
            background: var(--white);
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .period-tabs {
            display: flex;
            gap: 10px;
        }

        .period-tab {
            padding: 8px 20px;
            border: 1px solid var(--border-light);
            background: var(--white);
            color: var(--text-muted);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .period-tab.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .month-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .month-selector select {
            padding: 8px 15px;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            background: var(--white);
            color: var(--text-dark);
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid transparent;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .summary-card.primary {
            border-left-color: var(--primary-color);
        }

        .summary-card.success {
            border-left-color: var(--success-color);
        }

        .summary-card.danger {
            border-left-color: var(--danger-color);
        }

        .summary-card.warning {
            border-left-color: var(--secondary-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .card-title {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .card-icon.primary {
            background: rgba(23, 162, 184, 0.1);
            color: var(--primary-color);
        }

        .card-icon.success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .card-icon.danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .card-icon.warning {
            background: rgba(212, 175, 55, 0.1);
            color: var(--secondary-color);
        }

        .card-value {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .card-description {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Chart Container */
        .chart-container {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .chart-options {
            display: flex;
            gap: 10px;
        }

        .chart-option {
            padding: 6px 12px;
            border: 1px solid var(--border-light);
            background: var(--white);
            color: var(--text-muted);
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .chart-option.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }

        /* Pie Chart */
        .pie-chart-container {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
        }

        .pie-chart-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .pie-chart {
            width: 200px;
            height: 200px;
            position: relative;
        }

        .pie-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--white);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .pie-total {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .pie-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .legend {
            width: 100%;
        }

        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .legend-item:last-child {
            border-bottom: none;
        }

        .legend-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        .legend-text {
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .legend-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Table */
        .table-container {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
        }

        .search-box {
            padding: 8px 15px;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            width: 300px;
            font-size: 0.9rem;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            padding: 12px;
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-light);
        }

        tbody td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.95rem;
        }

        tbody tr:hover {
            background: var(--light-bg);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .amount {
            font-weight: 600;
        }

        .amount.positive {
            color: var(--success-color);
        }

        .amount.negative {
            color: var(--danger-color);
        }

        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .category-receita {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .category-despesa {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border-light);
        }

        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text-dark);
            border: 1px solid var(--border-light);
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--light-bg);
        }

        .btn-secondary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .footer-logo {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .period-selector {
                flex-direction: column;
                gap: 15px;
            }

            .search-box {
                width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <img src="images/logo_aemf.png" alt="AEMFPAR" style="height: 60px; width: auto;">
                <div class="company-info">
                    <h1>Dashboard Financeiro</h1>
                    <div class="company-subtitle">AEMF I Participações Ltda. • CNPJ: 08.743.034/0001-88</div>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-header">
                    <i class="fas fa-download"></i> Exportar
                </button>
                <button class="btn-header">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="period-selector">
            <div class="period-tabs">
                <button class="period-tab active" onclick="updatePeriod('mensal')">Mensal</button>
                <button class="period-tab" onclick="updatePeriod('trimestral')">Trimestral</button>
                <button class="period-tab" onclick="updatePeriod('semestral')">Semestral</button>
                <button class="period-tab" onclick="updatePeriod('anual')">Anual</button>
            </div>
            <div class="month-selector">
                <label>Período:</label>
                <select id="monthSelect" onchange="onMonthChange()">
                    <?php foreach ($availableMonths as $ym): ?>
                    <option value="<?= htmlspecialchars($ym) ?>"<?= $ym === $defaultMonth ? ' selected' : '' ?>>
                        <?= htmlspecialchars(monthLabel($ym, $ptMonths)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card primary">
                <div class="card-header">
                    <div>
                        <div class="card-title">Entradas do Mês</div>
                        <div class="card-value" id="aportes-valor">—</div>
                        <div class="card-description">
                            <span id="aportes-desc"><?= htmlspecialchars(monthLabel($defaultMonth, $ptMonths)) ?></span>
                        </div>
                    </div>
                    <div class="card-icon primary">
                        <i class="fas fa-arrow-circle-down"></i>
                    </div>
                </div>
            </div>

            <div class="summary-card danger">
                <div class="card-header">
                    <div>
                        <div class="card-title">Despesas AEMF I</div>
                        <div class="card-value" id="despesas-aemf">—</div>
                        <div class="card-description">Despesas operacionais do mês</div>
                    </div>
                    <div class="card-icon danger">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>

            <div class="summary-card warning">
                <div class="card-header">
                    <div>
                        <div class="card-title">Despesas Pessoa Física</div>
                        <div class="card-value" id="despesas-pf">—</div>
                        <div class="card-description">Cartões e veículos do mês</div>
                    </div>
                    <div class="card-icon warning">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>

            <div class="summary-card success">
                <div class="card-header">
                    <div>
                        <div class="card-title">Fluxo Líquido do Mês</div>
                        <div class="card-value" id="saldo-mes">—</div>
                        <div class="card-description">Entradas − Saídas do período</div>
                    </div>
                    <div class="card-icon success">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fluxo de Caixa -->
        <div class="table-container" id="fluxoCaixaSection" style="margin-bottom:25px;">
            <div class="table-header" style="justify-content:space-between;align-items:center;">
                <h3 class="chart-title"><i class="fas fa-stream" style="color:#17a2b8;margin-right:8px;"></i>Fluxo de Caixa</h3>
                <button class="btn btn-secondary" onclick="toggleSaldoInicialForm()" id="btnDefinirSaldo">
                    <i class="fas fa-pencil-alt"></i> Definir Saldo Inicial
                </button>
            </div>
            <!-- Formulário para definir saldo inicial -->
            <div id="saldoInicialForm" style="display:none;background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:15px;">
                <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                    <div>
                        <label style="display:block;font-size:.85rem;color:#6c757d;margin-bottom:4px;">Saldo em 31/dez do mês anterior (R$)</label>
                        <input type="number" id="saldoInicialInput" step="0.01" placeholder="Ex: 12345.67"
                               style="padding:8px 12px;border:1px solid #dee2e6;border-radius:6px;width:220px;">
                    </div>
                    <div>
                        <label style="display:block;font-size:.85rem;color:#6c757d;margin-bottom:4px;">Observação (opcional)</label>
                        <input type="text" id="saldoInicialObs" placeholder="Ex: Extrato Itaú Jan/26"
                               style="padding:8px 12px;border:1px solid #dee2e6;border-radius:6px;width:250px;">
                    </div>
                    <button onclick="saveSaldoInicial()" style="padding:9px 20px;background:#17a2b8;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
                <p style="font-size:.8rem;color:#6c757d;margin-top:8px;">
                    <i class="fas fa-info-circle"></i>
                    O saldo inicial é o saldo bancário no último dia do mês anterior.
                    O sistema extrai o <strong>LEDGERBAL</strong> (saldo atual na data de exportação) do OFX, mas isso <em>não</em>
                    é necessariamente o saldo de abertura do período. Informe aqui o saldo bancário no último dia do mês anterior para obter o fluxo de caixa completo.
                </p>
            </div>
            <!-- Resumo do fluxo -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:15px;padding:10px 0;">
                <div style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;">
                    <div style="font-size:.8rem;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Saldo Inicial</div>
                    <div id="fc-saldo-inicial" style="font-size:1.4rem;font-weight:700;color:#2c3e50;margin-top:6px;">—</div>
                    <div id="fc-saldo-inicial-hint" style="font-size:.75rem;color:#adb5bd;margin-top:3px;"></div>
                </div>
                <div style="text-align:center;padding:15px;background:#e8f5e9;border-radius:8px;">
                    <div style="font-size:.8rem;color:#28a745;text-transform:uppercase;letter-spacing:.5px;">+ Entradas</div>
                    <div id="fc-entradas" style="font-size:1.4rem;font-weight:700;color:#28a745;margin-top:6px;">—</div>
                </div>
                <div style="text-align:center;padding:15px;background:#fdecea;border-radius:8px;">
                    <div style="font-size:.8rem;color:#dc3545;text-transform:uppercase;letter-spacing:.5px;">− Saídas</div>
                    <div id="fc-saidas" style="font-size:1.4rem;font-weight:700;color:#dc3545;margin-top:6px;">—</div>
                </div>
                <div style="text-align:center;padding:15px;background:#e3f2fd;border-radius:8px;border:2px solid #17a2b8;">
                    <div style="font-size:.8rem;color:#17a2b8;text-transform:uppercase;letter-spacing:.5px;">= Saldo Final</div>
                    <div id="fc-saldo-final" style="font-size:1.4rem;font-weight:700;color:#17a2b8;margin-top:6px;">—</div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Despesas Acumuladas AEMF I</h3>
                    <div class="chart-options">
                        <button class="chart-option active">Mensal</button>
                        <button class="chart-option">Gráfico</button>
                    </div>
                </div>
                <div style="padding: 20px;">
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                            <span style="color: #6c757d;">Despesas com Escritório</span>
                            <div style="text-align: right;">
                                <strong>R$ 59.939,13</strong>
                                <div style="width: 200px; height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 5px;">
                                    <div style="width: 10%; height: 100%; background: #17a2b8; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                            <span style="color: #6c757d;">Despesas com Pessoal</span>
                            <div style="text-align: right;">
                                <strong>R$ 306.598,76</strong>
                                <div style="width: 200px; height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 5px;">
                                    <div style="width: 52%; height: 100%; background: #d4af37; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                            <span style="color: #6c757d;">Contabilidade</span>
                            <div style="text-align: right;">
                                <strong>R$ 27.176,81</strong>
                                <div style="width: 200px; height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 5px;">
                                    <div style="width: 5%; height: 100%; background: #28a745; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                            <span style="color: #6c757d;">Assessoria Jurídica</span>
                            <div style="text-align: right;">
                                <strong>R$ 190.077,50</strong>
                                <div style="width: 200px; height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 5px;">
                                    <div style="width: 33%; height: 100%; background: #dc3545; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0;">
                            <span style="color: #6c757d;">Taxas e Despesas Financeiras</span>
                            <div style="text-align: right;">
                                <strong>R$ 1.566,23</strong>
                                <div style="width: 200px; height: 4px; background: #e9ecef; border-radius: 2px; margin-top: 5px;">
                                    <div style="width: 1%; height: 100%; background: #6c757d; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Despesas Pessoa Física -->
            <div class="pie-chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Despesas Vinculadas Pessoa Física</h3>
                </div>
                <div class="pie-chart-wrapper">
                    <div class="pie-chart" style="background: conic-gradient(#ffc107 0deg 83deg, #fd7e14 83deg 310deg, #dc3545 310deg 338deg, #6c757d 338deg 360deg);">
                        <div class="pie-center">
                            <div class="pie-total">457K</div>
                            <div class="pie-label">Total PF</div>
                        </div>
                    </div>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-color" style="background: #ffc107;"></div>
                                <span class="legend-text">Gastos com Veículos</span>
                            </div>
                            <span class="legend-value">R$ 105.485</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-color" style="background: #fd7e14;"></div>
                                <span class="legend-text">Cartão Corp. Wagner SP</span>
                            </div>
                            <span class="legend-value">R$ 295.000</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-color" style="background: #dc3545;"></div>
                                <span class="legend-text">Cartão Corp. Wagner MG</span>
                            </div>
                            <span class="legend-value">R$ 35.000</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-color" style="background: #6c757d;"></div>
                                <span class="legend-text">Outras</span>
                            </div>
                            <span class="legend-value">R$ 21.706</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="chart-title">Movimentações Financeiras</h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" class="search-box" placeholder="Pesquisar transação..." id="searchInput">
                    <select id="filterType" style="padding: 8px 12px; border: 1px solid var(--border-light); border-radius: 6px;">
                        <option value="all">Todas</option>
                        <option value="receita">Receitas</option>
                        <option value="despesa">Despesas</option>
                    </select>
                </div>
            </div>
            <table id="transactionsTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th style="text-align: right;">Valor</th>
                    </tr>
                </thead>
                <tbody id="transactionsBody">
                    <!-- Rows will be populated by JavaScript -->
                </tbody>
            </table>
            <!-- Pagination -->
            <div class="pagination">
                <div style="color: var(--text-muted); font-size: 0.9rem;">
                    Exibindo <span id="showingStart">1</span>-<span id="showingEnd">8</span> de <span id="totalRecords">45</span> registros
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="btn btn-secondary" id="prevPage" onclick="changePage(-1)">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                    <div id="pageNumbers" style="display: flex; gap: 5px; align-items: center;">
                        <!-- Page numbers will be generated by JavaScript -->
                    </div>
                    <button class="btn btn-secondary" id="nextPage" onclick="changePage(1)">
                        Próxima <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">AEMFPAR</div>
            <p>© 2025 AEMF I Participações Ltda. • Todos os direitos reservados</p>
            <p>Dashboard Financeiro Executivo • Versão 1.0</p>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentPeriod = 'mensal';
        let currentMonth = '<?= htmlspecialchars($defaultMonth) ?>';

        // ── Período (tabs) ───────────────────────────────────────────────────
        function updatePeriod(period) {
            document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            currentPeriod = period;
            loadDashboardData();
        }

        // ── Mudança de mês ───────────────────────────────────────────────────
        function onMonthChange() {
            currentMonth = document.getElementById('monthSelect').value;
            currentPage  = 1;
            loadDashboardData();
        }

        // ── Carregar tudo ────────────────────────────────────────────────────
        function loadDashboardData() {
            currentMonth = document.getElementById('monthSelect').value;
            loadSummary();
            loadTransactions();
        }

        // ── Resumo financeiro ────────────────────────────────────────────────
        function loadSummary() {
            fetch(`api/dashboard_api.php?action=summary&month=${currentMonth}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    document.getElementById('aportes-valor').textContent  = fmt(data.aportes       || 0);
                    document.getElementById('aportes-desc').textContent   = data.month_label        || '';
                    document.getElementById('despesas-aemf').textContent  = fmt(data.despesas_aemf || 0);
                    document.getElementById('despesas-pf').textContent    = fmt(data.despesas_pf   || 0);
                    document.getElementById('saldo-mes').textContent      = fmt(data.saldo         || 0);

                    // Fluxo de caixa
                    document.getElementById('fc-entradas').textContent = fmt(data.entradas || 0);
                    document.getElementById('fc-saidas').textContent   = fmt(data.saidas   || 0);

                    if (data.saldo_inicial !== null && data.saldo_inicial !== undefined) {
                        document.getElementById('fc-saldo-inicial').textContent = fmt(data.saldo_inicial);
                        document.getElementById('fc-saldo-inicial-hint').textContent = '';
                        document.getElementById('saldoInicialInput').value = data.saldo_inicial;
                    } else {
                        document.getElementById('fc-saldo-inicial').textContent      = 'Não definido';
                        document.getElementById('fc-saldo-inicial-hint').textContent = 'Clique em "Definir Saldo Inicial"';
                    }

                    if (data.saldo_final !== null && data.saldo_final !== undefined) {
                        document.getElementById('fc-saldo-final').textContent = fmt(data.saldo_final);
                    } else {
                        document.getElementById('fc-saldo-final').textContent = '—';
                    }
                })
                .catch(() => {/* falha silenciosa */});
        }

        // ── Transações ───────────────────────────────────────────────────────
        function loadTransactions() {
            fetch(`api/dashboard_api.php?action=transactions&month=${currentMonth}&page=${currentPage}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const tbody = document.getElementById('transactionsBody');
                    if (data.data && data.data.length > 0) {
                        tbody.innerHTML = data.data.map(t => {
                            const isCredito = t.tipo === 'credito';
                            const sign      = isCredito ? '+' : '-';
                            const cls       = isCredito ? 'positive' : 'negative';
                            const badgeCls  = isCredito ? 'category-receita' : 'category-despesa';
                            const label     = isCredito ? 'Entrada' : 'Saída';
                            const dateStr   = t.data ? new Date(t.data + 'T00:00:00').toLocaleDateString('pt-BR') : '';
                            return `<tr>
                                <td>${dateStr}</td>
                                <td>${escHtml(t.descricao)}</td>
                                <td>${escHtml(t.categoria_nome || 'Sem categoria')}</td>
                                <td><span class="category-badge ${badgeCls}">${label}</span></td>
                                <td style="text-align:right;">
                                    <span class="amount ${cls}">${sign}${fmt(Math.abs(t.valor))}</span>
                                </td>
                            </tr>`;
                        }).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6c757d;padding:30px;">Nenhuma transação encontrada para o período selecionado.</td></tr>';
                    }
                    updatePagination(data);

                    // Search filter
                    const q = document.getElementById('searchInput').value.toLowerCase();
                    if (q) filterTable(q);
                })
                .catch(() => {});
        }

        // ── Paginação ────────────────────────────────────────────────────────
        function changePage(direction) {
            currentPage = Math.max(1, currentPage + direction);
            loadTransactions();
        }

        function updatePagination(data) {
            const perPage = data.per_page || 20;
            const total   = data.total    || 0;
            const page    = data.page     || 1;
            document.getElementById('showingStart').textContent = total === 0 ? 0 : (page - 1) * perPage + 1;
            document.getElementById('showingEnd').textContent   = Math.min(page * perPage, total);
            document.getElementById('totalRecords').textContent = total;
            document.getElementById('prevPage').disabled = page <= 1;
            document.getElementById('nextPage').disabled = page * perPage >= total;
        }

        // ── Filtro de busca ──────────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTable(this.value.toLowerCase());
        });

        function filterTable(q) {
            document.querySelectorAll('#transactionsBody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        // ── Saldo inicial ────────────────────────────────────────────────────
        function toggleSaldoInicialForm() {
            const form = document.getElementById('saldoInicialForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function saveSaldoInicial() {
            const saldo = document.getElementById('saldoInicialInput').value;
            const obs   = document.getElementById('saldoInicialObs').value;
            if (saldo === '') { alert('Informe o saldo inicial.'); return; }

            const body = new URLSearchParams({
                mes_referencia: currentMonth,
                saldo:          saldo,
                observacoes:    obs,
            });

            fetch('api/dashboard_api.php?action=setSaldoInicial', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    body.toString(),
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        document.getElementById('saldoInicialForm').style.display = 'none';
                        loadSummary();
                    } else {
                        alert('Erro: ' + (d.error || 'Falha ao salvar.'));
                    }
                })
                .catch(() => alert('Erro de comunicação com o servidor.'));
        }

        // ── Utilitários ──────────────────────────────────────────────────────
        function fmt(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        // ── Init ─────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            loadDashboardData();
        });
    </script>
</body>
</html>