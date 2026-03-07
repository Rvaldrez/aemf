<?php
// index.php - Dashboard Financeiro AEMFPAR (Versão Correta)
require_once 'includes/config.php';
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
                <select id="monthSelect">
                    <option value="2025-07">Julho 2025</option>
                    <option value="2025-06">Junho 2025</option>
                    <option value="2025-05">Maio 2025</option>
                </select>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card primary">
                <div class="card-header">
                    <div>
                        <div class="card-title">Aportes do Mês</div>
                        <div class="card-value" id="aportes-valor">R$ 170.000,00</div>
                        <div class="card-description">
                            <span id="aportes-desc">Julho 2025</span>
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
                        <div class="card-value" id="despesas-aemf">R$ 83.622,57</div>
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
                        <div class="card-value" id="despesas-pf">R$ 65.313,00</div>
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
                        <div class="card-title">Saldo do Mês</div>
                        <div class="card-value" id="saldo-mes">R$ 21.064,43</div>
                        <div class="card-description">Aportes - Despesas totais</div>
                    </div>
                    <div class="card-icon success">
                        <i class="fas fa-wallet"></i>
                    </div>
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
        // Continuação do JavaScript (incluindo todas as funções da versão 4)...
        // [O JavaScript completo da versão 4 deve ser incluído aqui]
        
        // Vou incluir apenas a parte essencial para conectar com a API
        let currentPage = 1;
        let currentPeriod = 'mensal';
        
        function updatePeriod(period) {
            document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            currentPeriod = period;
            loadDashboardData();
        }
        
        function loadDashboardData() {
            fetch(`api/dashboard_api.php?action=summary&period=${currentPeriod}&month=2025-07`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('aportes-valor').textContent = formatCurrency(data.aportes || 0);
                    document.getElementById('despesas-aemf').textContent = formatCurrency(data.despesas_aemf || 0);
                    document.getElementById('despesas-pf').textContent = formatCurrency(data.despesas_pf || 0);
                    document.getElementById('saldo-mes').textContent = formatCurrency(data.saldo || 0);
                });
            
            loadTransactions();
        }
        
        function loadTransactions() {
            fetch(`api/dashboard_api.php?action=transactions&page=${currentPage}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('transactionsBody');
                    if (data.data && data.data.length > 0) {
                        tbody.innerHTML = data.data.map(trans => `
                            <tr>
                                <td>${new Date(trans.data).toLocaleDateString('pt-BR')}</td>
                                <td>${trans.descricao}</td>
                                <td>${trans.categoria_nome || 'Sem categoria'}</td>
                                <td><span class="category-badge category-${trans.tipo == 'credito' ? 'receita' : 'despesa'}">
                                    ${trans.tipo == 'credito' ? 'Receita' : 'Despesa'}
                                </span></td>
                                <td style="text-align: right;">
                                    <span class="amount ${trans.tipo == 'credito' ? 'positive' : 'negative'}">
                                        ${trans.tipo == 'credito' ? '+' : '-'}R$ ${Math.abs(trans.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                    }
                    updatePagination(data);
                });
        }
        
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }
        
        function changePage(direction) {
            currentPage += direction;
            loadTransactions();
        }
        
        function updatePagination(data) {
            // Implementar paginação
            document.getElementById('showingStart').textContent = ((currentPage - 1) * 8) + 1;
            document.getElementById('showingEnd').textContent = Math.min(currentPage * 8, data.total || 0);
            document.getElementById('totalRecords').textContent = data.total || 0;
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });
    </script>
</body>
</html>