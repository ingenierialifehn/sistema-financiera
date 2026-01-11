<?php
/**
 * Módulo de Reportes de Agencia
 */

$pageTitle = 'Reportes de Agencia';
require_once __DIR__ . '/../auth_check.php';
// requireViewPermission('reportes');

// Obtener información del usuario actual
$userAgenciaId = $_SESSION['id_agencia'] ?? $user['id_agencia'] ?? null;
$nombreAgencia = $_SESSION['nombre_agencia'] ?? 'Sin agencia asignada';
$currentUser = $user ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema Financiero</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const USER_AGENCIA_ID = <?php echo $userAgenciaId ? $userAgenciaId : 'null'; ?>;
    </script>
    <style>
        .reportes-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-section h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .header-section p {
            margin: 0;
            opacity: 0.9;
        }

        .tabs-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .tab-button:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .stat-card.success {
            border-left-color: #10b981;
        }

        .stat-card.warning {
            border-left-color: #f59e0b;
        }

        .stat-card.danger {
            border-left-color: #ef4444;
        }

        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }

        .stat-card .label {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

        .report-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .report-section h2 {
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-section h2 i {
            color: #667eea;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f9fafb;
        }

        table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        table tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-a {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-b {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-c {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-d {
            background: #fed7aa;
            color: #9a3412;
        }

        .badge-e {
            background: #fecaca;
            color: #991b1b;
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;

            .btn-primary {
                background: #667eea;
                color: white;
            }

            .btn-primary:hover {
                background: #5568d3;
                transform: translateY(-2px);
                box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
            }

            /* Estilos generales para botones y headers ocultos en pantalla */
            .btn-print {
                background: #10b981;
                color: white;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .btn-print:hover {
                background: #059669;
            }

            /* Ocultar cabeceras de impresión en la vista normal */
            .print-header {
                display: none !important;
            }

            /* Visible por defecto en pantalla */
            .sidebar,
            .top-bar,
            .tab-buttons,
            .btn,
            .filter-section {
                display: block;
                /* O flex según corresponda en su definición original, pero aquí aseguramos que no se oculten por defecto */
            }

            /* =========================================
           ESTILOS EXCLUSIVOS PARA IMPRESIÓN 
           ========================================= */

            .chart-container {
                margin: 2rem 0;
                padding: 1.5rem;
                background: #f9fafb;
                border-radius: 8px;
            }

            .loading {
                text-align: center;
                padding: 3rem;
                color: #6b7280;
            }

            .loading i {
                font-size: 3rem;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            /* =========================================
           ESTILOS EXCLUSIVOS PARA IMPRESIÓN (FORZADO)
           ========================================= */
            @media print {

                /* Ocultar TODO lo que sea navegación o UI general */
                .sidebar,
                .top-bar,
                .tab-buttons,
                .btn,
                .filter-section,
                .chart-container,
                .header-section,
                .tabs-container {
                    display: none !important;
                }

                /* Quitar márgenes de layout */
                .main-content,
                .ml-64,
                .p-8 {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    background: white !important;
                }

                .reportes-container {
                    padding: 0 !important;
                    margin: 0 !important;
                }

                body {
                    background: white !important;
                    color: black !important;
                    font-size: 11px !important;
                    margin: 0 !important;
                }

                /* Cabecera SIMPLE de impresión (no la de pantalla) */
                .print-header {
                    display: block !important;
                    text-align: center;
                    margin-bottom: 0.5rem;
                    border-bottom: 2px solid #333;
                    padding-bottom: 0.5rem;
                }

                .print-header h2 {
                    margin: 0;
                    font-size: 16px !important;
                    text-transform: uppercase;
                    color: #000 !important;
                }

                .print-header p {
                    margin: 0;
                    font-size: 10px !important;
                    color: #444 !important;
                }

                /* TARJETAS DE DATOS: VOLVERLAS UNA LÍNEA DELGADA */
                .stats-grid {
                    display: flex !important;
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    border: 1px solid #999 !important;
                    border-radius: 4px;
                    padding: 0 !important;
                    margin-bottom: 1rem !important;
                    background: none !important;
                    gap: 0 !important;
                    width: 100% !important;
                }

                .stat-card {
                    background: none !important;
                    box-shadow: none !important;
                    border: none !important;
                    border-right: 1px solid #ccc !important;
                    border-radius: 0 !important;
                    padding: 4px !important;
                    flex: 1 !important;
                    text-align: center !important;
                    display: block !important;
                    /* Romper flex interno si existe */
                    min-height: 0 !important;
                    height: auto !important;
                }

                .stat-card:last-child {
                    border-right: none !important;
                }

                /* Textos dentro de las tarjetas */
                .stat-card h3 {
                    font-size: 9px !important;
                    color: #666 !important;
                    margin: 0 0 2px 0 !important;
                    text-transform: uppercase;
                    font-weight: bold !important;
                    display: block !important;
                }

                .stat-card .value {
                    font-size: 12px !important;
                    color: #000 !important;
                    font-weight: bold !important;
                    margin: 0 !important;
                    display: block !important;
                }

                /* Ocultar etiquetas extra y bordes decorativos */
                .stat-card .label,
                .stat-card::before {
                    display: none !important;
                }

                /* TABLAS */
                .report-section {
                    box-shadow: none !important;
                    padding: 0 !important;
                    margin-bottom: 1rem !important;
                    border: none !important;
                    background: none !important;
                }

                .report-section h2 {
                    font-size: 12px !important;
                    margin: 0.5rem 0 !important;
                    border-bottom: 1px solid #000;
                    color: #000 !important;
                }

                .report-section h2 i {
                    display: none !important;
                }

                .table-container {
                    box-shadow: none !important;
                    border: none !important;
                    overflow: visible !important;
                }

                table {
                    width: 100% !important;
                    border-collapse: collapse !important;
                    font-size: 10px !important;
                }

                th,
                td {
                    padding: 2px 4px !important;
                    border: 1px solid #999 !important;
                    color: #000 !important;
                }

                th {
                    background: #eee !important;
                }

                /* Asegurar tab activo visible */
                .tab-content {
                    display: none !important;
                }

                .tab-content.active {
                    display: block !important;
                }
            }
    </style>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <div class="reportes-container">
            <div class="header-section">
                <h1><i class="fas fa-chart-line"></i> Reportes de Agencia</h1>
                <p>Análisis y estadísticas de tu agencia | <span
                        id="agencia-nombre"><?php echo htmlspecialchars($_SESSION['nombre_agencia'] ?? 'Sin agencia asignada'); ?></span>
                    <span id="header-fecha" class="ml-2 pl-2 border-l border-indigo-300"></span>
                </p>
            </div>

            <div class="tabs-container">
                <button class="tab-button active" data-tab="recaudacion">
                    <i class="fas fa-money-bill-wave"></i> Recaudación Diaria
                </button>
                <button class="tab-button" data-tab="cartera">
                    <i class="fas fa-wallet"></i> Estado de Cartera
                </button>
                <button class="tab-button" data-tab="desembolsos">
                    <i class="fas fa-hand-holding-usd"></i> Desembolsos
                </button>
            </div>

            <!-- TAB 1: RECAUDACIÓN DIARIA -->
            <div class="tab-content active" id="tab-recaudacion">
                <div class="print-area">


                    <div class="stats-grid">
                        <div class="stat-card success">
                            <h3>Total Cobrado Hoy</h3>
                            <div class="value" id="total-cobrado">L 0.00</div>
                            <div class="label" id="fecha-actual"></div>
                        </div>
                        <div class="stat-card">
                            <h3>Capital</h3>
                            <div class="value" id="total-capital">L 0.00</div>
                            <div class="label">Monto principal</div>
                        </div>
                        <div class="stat-card warning">
                            <h3>Total Intereses (11%)</h3>
                            <div class="value" id="total-intereses-completo">L 0.00</div>
                            <div class="label">Interés + Gastos + Comisión</div>
                        </div>
                        <div class="stat-card">
                            <h3>Interés (4%)</h3>
                            <div class="value" id="total-interes">L 0.00</div>
                        </div>
                        <div class="stat-card">
                            <h3>Gastos (4%)</h3>
                            <div class="value" id="total-gastos">L 0.00</div>
                        </div>
                        <div class="stat-card">
                            <h3>Comisión (3%)</h3>
                            <div class="value" id="total-comision">L 0.00</div>
                        </div>
                    </div>

                    <div class="report-section">
                        <h2><i class="fas fa-list"></i> Transacciones del Día</h2>
                        <button class="btn btn-print" onclick="imprimirRecaudacion()"
                            style="float: right; margin-top: -3rem;">
                            <i class="fas fa-print"></i> Imprimir Tabla
                        </button>
                        <div class="table-container">
                            <table id="tabla-transacciones">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Cuota #</th>
                                        <th>Monto Pagado</th>
                                        <th>Capital</th>
                                        <th>Interés</th>
                                        <th>Gastos</th>
                                        <th>Comisión</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="loading">
                                            <i class="fas fa-spinner"></i><br>
                                            Cargando transacciones...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ESTADO DE CARTERA -->
            <div class="tab-content" id="tab-cartera">
                <div class="print-area">


                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="stat-card">
                            <h3>Capital en la Calle</h3>
                            <div class="value" id="capital-calle">L 0.00</div>
                            <div class="label">Saldo pendiente total</div>
                        </div>
                        <div class="stat-card" style="border-left: 4px solid #10b981;">
                            <h3>Diarios</h3>
                            <div class="value" id="count-diario">0</div>
                            <div class="label">Préstamos Activos</div>
                        </div>
                        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                            <h3>Semanales</h3>
                            <div class="value" id="count-semanal">0</div>
                            <div class="label">Préstamos Activos</div>
                        </div>
                        <div class="stat-card" style="border-left: 4px solid #6366f1;">
                            <h3>Catorcenales</h3>
                            <div class="value" id="count-catorcenal">0</div>
                            <div class="label">Préstamos Activos</div>
                        </div>
                    </div>

                    <div class="report-section">
                        <h2><i class="fas fa-users"></i> Desglose de Cartera por Asesor</h2>
                        <button class="btn btn-print"
                            onclick="imprimirTablaEspecifica('tabla-desglose-asesores', 'Desglose de Cartera por Asesor')"
                            style="float: right; margin-top: -3rem;">
                            <i class="fas fa-print"></i> Imprimir Tabla
                        </button>
                        <div class="table-container">
                            <table id="tabla-desglose-asesores">
                                <thead>
                                    <tr>
                                        <th>Asesor</th>
                                        <th>Cartera Activa</th>
                                        <th>Clientes</th>
                                        <th class="text-center bg-yellow-50">1-3 Días</th>
                                        <th class="text-center bg-orange-50">4-7 Días</th>
                                        <th class="text-center bg-red-50">8-14 Días</th>
                                        <th class="text-center bg-red-100">30+ Días</th>
                                        <th class="text-center font-bold bg-green-50">Normalidad</th>
                                        <th class="text-center font-bold bg-gray-100">% Mora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="loading">
                                            <i class="fas fa-spinner"></i><br>
                                            Cargando desglose...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-section">
                        <h2><i class="fas fa-chart-pie"></i> Resumen por Categoría de Riesgo</h2>
                        <button class="btn btn-print" onclick="imprimirReporte('cartera')"
                            style="float: right; margin-top: -3rem;">
                            <i class="fas fa-print"></i> Imprimir Reporte
                        </button>
                        <div class="table-container">
                            <table id="tabla-categorias">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Cantidad de Clientes</th>
                                        <th>Monto en Riesgo</th>
                                        <th>Promedio Días Mora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="loading">
                                            <i class="fas fa-spinner"></i><br>
                                            Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-section">
                        <h2><i class="fas fa-exclamation-triangle"></i> Clientes con Más de 30 Días de Atraso</h2>
                        <div class="table-container">
                            <table id="tabla-mora">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>DNI</th>
                                        <th>Teléfono</th>
                                        <th>Categoría</th>
                                        <th>Días Mora</th>
                                        <th>Saldo Pendiente</th>
                                        <th>Próxima Cuota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="loading">
                                            <i class="fas fa-spinner"></i><br>
                                            Cargando clientes en mora...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: DESEMBOLSOS -->
            <div class="tab-content" id="tab-desembolsos">
                <div class="print-area">


                    <div class="filter-section">
                        <div class="form-group">
                            <label>Fecha Desde</label>
                            <input type="date" id="fecha-desde" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Fecha Hasta</label>
                            <input type="date" id="fecha-hasta" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button class="btn btn-primary" onclick="cargarDesembolsos()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>

                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="stat-card success">
                            <h3>Total Colocado</h3>
                            <div class="value" id="monto-colocado">L 0.00</div>
                            <div class="label">En el periodo</div>
                        </div>
                        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                            <h3>Nuevos</h3>
                            <div class="value" id="cantidad-nuevos">0</div>
                            <div class="label">Préstamos Nuevos</div>
                        </div>
                        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                            <h3>Refinanciamientos</h3>
                            <div class="value" id="cantidad-refinanciamientos">0</div>
                            <div class="label">Préstamos Refinanciados</div>
                        </div>
                        <div class="stat-card">
                            <h3>Total Préstamos</h3>
                            <div class="value" id="cantidad-prestamos">0</div>
                            <div class="label">Desembolsos realizados</div>
                        </div>
                    </div>

                    <div class="report-section">
                        <h2><i class="fas fa-list-alt"></i> Detalle de Desembolsos</h2>
                        <button class="btn btn-print" onclick="imprimirDetalleDesembolsos()"
                            style="float: right; margin-top: -3rem;">
                            <i class="fas fa-print"></i> Imprimir Tabla
                        </button>
                        <div class="table-container">
                            <table id="tabla-desembolsos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>DNI</th>
                                        <th>Monto Capital</th>
                                        <th>Modalidad</th>
                                        <th>Plazo</th>
                                        <th>Total a Pagar</th>
                                        <th>Oficial</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="loading">
                                            <i class="fas fa-spinner"></i><br>
                                            Cargando desembolsos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-section">
                        <h2><i class="fas fa-chart-bar"></i> Resumen por Modalidad</h2>

                        <div class="table-container">
                            <table id="tabla-modalidades">
                                <thead>
                                    <tr>
                                        <th>Modalidad</th>
                                        <th>Cantidad</th>
                                        <th>Monto Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="loading">
                                            <i class="fas fa-spinner"></i><br>
                                            Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        // Manejo de tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.dataset.tab;

                // Actualizar botones
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                // Actualizar contenido
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(`tab-${tabName}`).classList.add('active');
            });
        });

        // Formatear moneda
        function formatMoney(amount) {
            return 'L ' + parseFloat(amount).toLocaleString('es-HN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Formatear fecha
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-HN', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Cargar Recaudación Diaria
        async function cargarRecaudacion() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/reportes/recaudacion_diaria.php`);
                const result = await response.json();

                console.log('Respuesta recaudación:', result);

                if (result.success) {
                    const data = result.data;

                    // Actualizar stats
                    document.getElementById('total-cobrado').textContent = formatMoney(data.total_cobrado);
                    document.getElementById('total-capital').textContent = formatMoney(data.desglose.capital);

                    // Calcular Total Intereses (11%) = Interés + Gastos + Comisión
                    const totalInteresesCompleto = parseFloat(data.desglose.interes) +
                        parseFloat(data.desglose.gastos) +
                        parseFloat(data.desglose.comision);
                    document.getElementById('total-intereses-completo').textContent = formatMoney(totalInteresesCompleto);

                    document.getElementById('total-interes').textContent = formatMoney(data.desglose.interes);
                    document.getElementById('total-gastos').textContent = formatMoney(data.desglose.gastos);
                    document.getElementById('total-comision').textContent = formatMoney(data.desglose.comision);
                    document.getElementById('fecha-actual').textContent = formatDate(data.fecha);

                    // Actualizar tabla de transacciones
                    const tbody = document.querySelector('#tabla-transacciones tbody');
                    if (data.transacciones.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #9ca3af;">No hay transacciones registradas hoy</td></tr>';
                    } else {
                        tbody.innerHTML = data.transacciones.map(t => `
                            <tr>
                                <td><strong>${t.nombre_completo}</strong></td>
                                <td>#${t.numero_cuota}</td>
                                <td><strong>${formatMoney(t.monto_pagado)}</strong></td>
                                <td>${formatMoney(t.capital_cuota || 0)}</td>
                                <td>${formatMoney(t.interes_cuota || 0)}</td>
                                <td>${formatMoney(t.gastos_cuota || 0)}</td>
                                <td>${formatMoney(t.comision_cuota || 0)}</td>
                                <td>${new Date(t.fecha_pago).toLocaleTimeString('es-HN')}</td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar fecha en el encabezado principal
                    document.getElementById('header-fecha').textContent = formatDate(data.fecha);
                    // document.getElementById('print-agencia-recaudacion').textContent = data.agencia; // Elemento eliminado
                } else {
                    console.error('Error en API:', result.message);
                    const tbody = document.querySelector('#tabla-transacciones tbody');
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #ef4444;">Error: ${result.message || 'No se pudieron cargar los datos'}</td></tr>`;
                }
            } catch (error) {
                console.error('Error cargando recaudación:', error);
                const tbody = document.querySelector('#tabla-transacciones tbody');
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #ef4444;">Error al cargar los datos. Verifica la consola para más detalles.</td></tr>';
            }
        }

        // Cargar Estado de Cartera
        async function cargarCartera() {
            try {
                const response = await fetch(`${BASE_URL}/app/api/reportes/estado_cartera.php`);
                const result = await response.json();

                console.log('Respuesta cartera:', result);

                if (result.success) {
                    const data = result.data;

                    // Actualizar capital en la calle
                    document.getElementById('capital-calle').textContent = formatMoney(data.capital_calle);

                    if (data.modalidades_activas) {
                        document.getElementById('count-diario').textContent = data.modalidades_activas.diario || 0;
                        document.getElementById('count-semanal').textContent = data.modalidades_activas.semanal || 0;
                        document.getElementById('count-catorcenal').textContent = data.modalidades_activas.catorcenal || 0;
                    }

                    // --- 1. NUEVA TABLA: DESGLOSE POR ASESOR ---
                    const tbodyAsesores = document.querySelector('#tabla-desglose-asesores tbody');
                    const theadAsesores = document.querySelector('#tabla-desglose-asesores thead tr');

                    // Asegurar que el Header tenga la columna extra (Hack simple sin recrear todo)
                    if (theadAsesores && !theadAsesores.innerHTML.includes('Nuevos')) {
                        // Insertar después de Clientes (index 2)
                        // Header actual: Asesor, Cartera Activa, Clientes, 1-3, 4-7...
                        const thNuevos = document.createElement('th');
                        thNuevos.className = "text-center bg-blue-50 text-blue-800";
                        thNuevos.innerText = "Nuevos";
                        // Insert in position 3 (index 2 is Clientes)
                        theadAsesores.insertBefore(thNuevos, theadAsesores.children[3]);
                    }

                    if (!data.desglose_asesores || data.desglose_asesores.length === 0) {
                        tbodyAsesores.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 2rem; color: #9ca3af;">No hay información de asesores</td></tr>';
                    } else {
                        tbodyAsesores.innerHTML = data.desglose_asesores.map(adv => {
                            // Estilo especial para la fila de TOTAL
                            const isTotal = adv.es_total === true;
                            const rowClass = isTotal ? 'bg-indigo-50 font-bold border-t-2 border-indigo-200' : 'hover:bg-gray-50';

                            const mora30PlusReal = parseFloat(adv.mora_15_30 || 0) + parseFloat(adv.mora_30_plus || 0);

                            // Color del porcentaje de mora
                            let moraClass = 'text-green-600';
                            if (adv.porcentaje_mora > 25) moraClass = 'text-red-600 font-bold';
                            else if (adv.porcentaje_mora > 10) moraClass = 'text-orange-600 font-bold';
                            else if (adv.porcentaje_mora > 5) moraClass = 'text-yellow-600';

                            // Clientes Nuevos 
                            const nuevosCount = adv.clientes_tramite || 0;
                            const novosClass = nuevosCount > 0 ? "bg-blue-50 text-blue-800 font-bold" : "";

                            return `
                            <tr class="${rowClass}">
                                <td class="px-4 py-3">${isTotal ? 'TOTAL AGENCIA' : (adv.nombre || 'Desconocido')}</td>
                                <td class="px-4 py-3 text-indigo-700 font-semibold">${formatMoney(adv.total_cartera)}</td>
                                <td class="px-4 py-3 text-center" title="Clientes Activos">${adv.clientes_count}</td>
                                <td class="px-4 py-3 text-center ${novosClass}" title="Solicitados / En Análisis">${nuevosCount}</td>
                                <td class="px-4 py-3 text-right bg-yellow-50 text-yellow-700">${formatMoney(adv.mora_1_3)}</td>
                                <td class="px-4 py-3 text-right bg-orange-50 text-orange-700">${formatMoney(adv.mora_4_7)}</td>
                                <td class="px-4 py-3 text-right bg-red-50 text-red-700">${formatMoney(adv.mora_8_14)}</td>
                                <td class="px-4 py-3 text-right bg-red-100 text-red-800 font-bold">${formatMoney(mora30PlusReal)}</td>
                                <td class="px-4 py-3 text-center font-bold bg-green-50 text-green-700">${adv.porcentaje_normalidad}%</td>
                                <td class="px-4 py-3 text-center font-bold bg-gray-50 ${moraClass}">${adv.porcentaje_mora}%</td>
                            </tr>
                            `;
                        }).join('');
                    }

                    // Actualizar tabla de categorías (Existente)
                    const tbodyCat = document.querySelector('#tabla-categorias tbody');
                    if (data.categorias.length === 0) {
                        tbodyCat.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: #9ca3af;">No hay datos disponibles</td></tr>';
                    } else {
                        tbodyCat.innerHTML = data.categorias.map(cat => `
                            <tr>
                                <td><span class="badge badge-${cat.categoria_riesgo.toLowerCase()}">${cat.categoria_riesgo}</span></td>
                                <td>${cat.cantidad_clientes}</td>
                                <td><strong>${formatMoney(cat.monto_riesgo)}</strong></td>
                                <td>${Math.round(cat.promedio_dias_mora || 0)} días</td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar tabla de mora (Existente)
                    const tbodyMora = document.querySelector('#tabla-mora tbody');
                    if (data.clientes_mora.length === 0) {
                        tbodyMora.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #10b981;">✓ No hay clientes con más de 30 días de atraso</td></tr>';
                    } else {
                        tbodyMora.innerHTML = data.clientes_mora.map(cliente => `
                            <tr>
                                <td><strong>${cliente.nombre_completo}</strong></td>
                                <td>${cliente.numero_documento}</td>
                                <td>${cliente.telefono}</td>
                                <td><span class="badge badge-${cliente.categoria_riesgo.toLowerCase()}">${cliente.categoria_riesgo}</span></td>
                                <td><strong style="color: #ef4444;">${cliente.dias_mora} días</strong></td>
                                <td>${formatMoney(cliente.saldo_pendiente)}</td>
                                <td>${cliente.proxima_cuota ? formatDate(cliente.proxima_cuota) : 'N/A'}</td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar fecha en encabezado principal
                    document.getElementById('header-fecha').textContent = formatDate(data.fecha);
                    // document.getElementById('print-agencia-cartera').textContent = data.agencia; // Eliminado
                } else {
                    console.error('Error en API:', result.message);
                    const tbodyCat = document.querySelector('#tabla-categorias tbody');
                    tbodyCat.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 2rem; color: #ef4444;">Error: ${result.message || 'No se pudieron cargar los datos'}</td></tr>`;
                }
            } catch (error) {
                console.error('Error cargando cartera:', error);
                const tbodyCat = document.querySelector('#tabla-categorias tbody');
                tbodyCat.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: #ef4444;">Error al cargar los datos. Verifica la consola para más detalles.</td></tr>';
            }
        }

        // Cargar Desembolsos
        async function cargarDesembolsos() {
            try {
                const fechaDesde = document.getElementById('fecha-desde').value;
                const fechaHasta = document.getElementById('fecha-hasta').value;

                const response = await fetch(`${BASE_URL}/app/api/reportes/desembolsos_periodo.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`);
                const result = await response.json();

                console.log('Respuesta desembolsos:', result);

                if (result.success) {
                    const data = result.data;

                    // Actualizar stats
                    document.getElementById('monto-colocado').textContent = formatMoney(data.resumen.monto_total_colocado);
                    document.getElementById('cantidad-prestamos').textContent = data.resumen.cantidad_prestamos;
                    document.getElementById('cantidad-nuevos').textContent = data.resumen.cantidad_nuevos;
                    document.getElementById('cantidad-refinanciamientos').textContent = data.resumen.cantidad_refinanciamientos;

                    // Actualizar tabla de modalidades
                    const tbodyMod = document.querySelector('#tabla-modalidades tbody');
                    if (data.por_modalidad.length === 0) {
                        tbodyMod.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: #9ca3af;">No hay datos disponibles</td></tr>';
                    } else {
                        tbodyMod.innerHTML = data.por_modalidad.map(mod => `
                            <tr>
                                <td><strong>${mod.modalidad}</strong></td>
                                <td>${mod.cantidad}</td>
                                <td>${formatMoney(mod.monto_total)}</td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar tabla de desembolsos
                    const tbodyDesem = document.querySelector('#tabla-desembolsos tbody');
                    if (data.desembolsos.length === 0) {
                        tbodyDesem.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #9ca3af;">No hay desembolsos en este periodo</td></tr>';
                    } else {
                        tbodyDesem.innerHTML = data.desembolsos.map(d => `
                            <tr>
                                <td>${formatDate(d.fecha_desembolso)}</td>
                                <td><strong>${d.nombre_completo}</strong></td>
                                <td>${d.numero_documento}</td>
                                <td><strong>${formatMoney(d.monto_capital)}</strong></td>
                                <td>${d.modalidad}</td>
                                <td>${d.plazo_meses} meses</td>
                                <td>${formatMoney(d.total_a_pagar)}</td>
                                <td>${d.oficial_desembolso || 'N/A'}</td>
                            </tr>
                        `).join('');
                    }

                    // Actualizar fecha en encabezado principal (Rango)
                    document.getElementById('header-fecha').textContent = `${formatDate(data.fecha_desde)} - ${formatDate(data.fecha_hasta)}`;
                    // document.getElementById('print-agencia-desembolsos').textContent = data.agencia; // Eliminado
                } else {
                    console.error('Error en API:', result.message);
                    const tbodyMod = document.querySelector('#tabla-modalidades tbody');
                    tbodyMod.innerHTML = `<tr><td colspan="3" style="text-align: center; padding: 2rem; color: #ef4444;">Error: ${result.message || 'No se pudieron cargar los datos'}</td></tr>`;
                }
            } catch (error) {
                console.error('Error cargando desembolsos:', error);
                const tbodyMod = document.querySelector('#tabla-modalidades tbody');
                tbodyMod.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: #ef4444;">Error al cargar los datos. Verifica la consola para más detalles.</td></tr>';
            }
        }

        // Función de impresión general
        function imprimirReporte(tipo) {
            window.print();
        }

        // Helper para imprimir reporte de recaudación con datos extra
        function imprimirRecaudacion() {
            const stats = [
                { label: 'Total Cobrado', value: document.getElementById('total-cobrado').textContent },
                { label: 'Capital', value: document.getElementById('total-capital').textContent },
                { label: 'Total Interés', value: document.getElementById('total-intereses-completo').textContent }
            ];
            imprimirTablaEspecifica('tabla-transacciones', 'Reporte de Transacciones del Día', stats);
        }

        // Helper para imprimir reporte de desembolsos con stats
        function imprimirDetalleDesembolsos() {
            const stats = [
                { label: 'Total Colocado', value: document.getElementById('monto-colocado').textContent },
                { label: 'Nuevos', value: document.getElementById('cantidad-nuevos').textContent },
                { label: 'Refi.', value: document.getElementById('cantidad-refinanciamientos').textContent },
                { label: 'Total', value: document.getElementById('cantidad-prestamos').textContent }
            ];
            imprimirTablaEspecifica('tabla-desembolsos', 'Reporte de Desembolsos de Hoy', stats);
        }

        // Función de impresión específica para tablas con estilo profesional y stats opcionales
        function imprimirTablaEspecifica(tableId, titulo, extraStats = null) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const win = window.open('', '_blank');
            const fecha = new Date().toLocaleDateString('es-HN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const hora = new Date().toLocaleTimeString('es-HN');
            const agencia = document.getElementById('agencia-nombre') ? document.getElementById('agencia-nombre').innerText : 'Agencia Principal';

            // Clonar tabla para manipulaciones si es necesario
            const tableClone = table.cloneNode(true);
            tableClone.classList.add('print-friendly');

            // Generar HTML de Stats Extras si existen
            let statsHtml = '';
            if (extraStats && Array.isArray(extraStats)) {
                const statsItems = extraStats.map(stat => `
                    <div class="stat-item">
                        <span class="stat-label">${stat.label}:</span>
                        <span class="stat-value">${stat.value}</span>
                    </div>
                `).join('');

                statsHtml = `
                    <div class="extra-stats">
                        ${statsItems}
                    </div>
                `;
            }

            // HTML Estructurado para impresión profesional
            const htmlContent = `
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>${titulo}</title>
                    <style>
                        @page { margin: 1cm; size: portrait; }
                        body { 
                            font-family: 'Helvetica Neue', Arial, sans-serif; 
                            color: #333; 
                            line-height: 1.3; 
                            font-size: 11px;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 1rem; 
                            border-bottom: 2px solid #2563eb; 
                            padding-bottom: 0.5rem;
                        }
                        .header h1 { 
                            margin: 0; 
                            font-size: 16px; 
                            color: #1e3a8a; 
                            text-transform: uppercase; 
                            letter-spacing: 1px;
                        }
                        .header p { 
                            margin: 2px 0 0; 
                            font-size: 10px; 
                            color: #64748b; 
                        }
                        .meta-info {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 0.5rem;
                            font-size: 9px;
                            color: #666;
                        }
                        
                        /* Estilo para los stats extra en pequeño arriba */
                        .extra-stats {
                            display: flex;
                            justify-content: center;
                            gap: 2rem;
                            margin-bottom: 1rem;
                            padding: 0.5rem;
                            background-color: #f8fafc;
                            border: 1px solid #e2e8f0;
                            border-radius: 4px;
                        }
                        .stat-item {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                        }
                        .stat-label {
                            font-size: 9px;
                            color: #64748b;
                            text-transform: uppercase;
                        }
                        .stat-value {
                            font-size: 12px;
                            font-weight: bold;
                            color: #0f172a;
                        }

                        table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin-bottom: 1rem; 
                        }
                        thead th { 
                            background-color: #f1f5f9; 
                            color: #334155; 
                            font-weight: bold; 
                            text-transform: uppercase; 
                            font-size: 9px;
                            border-bottom: 2px solid #cbd5e1;
                            padding: 6px;
                            text-align: left; 
                        }
                        .text-center { text-align: center !important; }
                        .text-right { text-align: right !important; }
                        
                        tbody td { 
                            border-bottom: 1px solid #e2e8f0; 
                            padding: 6px;
                            vertical-align: middle;
                        }
                        tbody tr:nth-child(even) { background-color: #f8fafc; }
                        tbody tr:last-child { border-bottom: 2px solid #cbd5e1; }
                        
                        td[class*="bg-green"] { background-color: #f0fdf4 !important; color: #15803d !important; -webkit-print-color-adjust: exact; }
                        td[class*="bg-yellow"] { background-color: #fefce8 !important; color: #a16207 !important; -webkit-print-color-adjust: exact; }
                        td[class*="bg-orange"] { background-color: #fff7ed !important; color: #c2410c !important; -webkit-print-color-adjust: exact; }
                        td[class*="bg-red"] { background-color: #fef2f2 !important; color: #b91c1c !important; -webkit-print-color-adjust: exact; }
                        
                        .footer { 
                            margin-top: 1rem; 
                            text-align: center; 
                            font-size: 9px; 
                            color: #94a3b8; 
                            border-top: 1px solid #e2e8f0; 
                            padding-top: 0.5rem;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>${titulo}</h1>
                        <p>${agencia}</p>
                    </div>
                    
                    <div class="meta-info">
                        <span>Fecha: ${fecha} ${hora}</span>
                        <span>Sistema Financiero</span>
                    </div>

                    ${statsHtml}

                    ${tableClone.outerHTML}

                    <div class="footer">
                        Documento oficial interno. Generado el ${fecha}.
                    </div>

                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                            }, 500);
                        }
                    <\/script>
                </body>
                </html>
            `;

            win.document.write(htmlContent);
            win.document.close();
        }

        // Cargar datos iniciales
        document.addEventListener('DOMContentLoaded', () => {
            cargarRecaudacion();
            cargarCartera();
            cargarDesembolsos();
        });
    </script>
</body>

</html>