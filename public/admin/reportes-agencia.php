<?php
/**
 * Reportes de Agencia - Vista Móvil Responsive
 * Filtrado automático por agencia del usuario en sesión
 */

session_start();
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/Auth.php';

// Verificar autenticación
$user = Auth::checkSession();
if (!$user) {
    header('Location: ../login.php');
    exit;
}

// Obtener datos del usuario
$idAgencia = $user['id_agencia'] ?? null;
$nombreAgencia = $user['nombre_agencia'] ?? 'Sin Agencia';
$nombreUsuario = $user['nombre_completo'] ?? 'Usuario';
$rolNombre = $user['rol_nombre'] ?? '';

// Verificar si es administrador (puede ver todas las agencias)
$esAdministrador = in_array($rolNombre, ['Administrador', 'Gerente']);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Agencia - Sistema Financiero</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }

        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            border-left: 4px solid;
            padding: 1.5rem;
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .badge-category {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge-a {
            background: #10b981;
            color: white;
        }

        .badge-b {
            background: #3b82f6;
            color: white;
        }

        .badge-c {
            background: #f59e0b;
            color: white;
        }

        .badge-d {
            background: #ef4444;
            color: white;
        }

        .badge-e {
            background: #7c3aed;
            color: white;
        }

        .section-title {
            color: white;
            font-weight: 600;
            margin: 2rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        @media (max-width: 768px) {
            .stat-value {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chart-line text-primary"></i>
                <strong>Reportes de Agencia</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-building text-primary"></i>
                            <?php echo htmlspecialchars($nombreAgencia); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user text-primary"></i>
                            <?php echo htmlspecialchars($nombreUsuario); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if ($esAdministrador): ?>
            <!-- Selector de Agencia (Solo Administradores) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-filter text-primary"></i> Filtrar por Agencia
                            </h5>
                            <select id="selectorAgencia" class="form-select form-select-lg">
                                <option value="">Cargando agencias...</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resumen de Recaudación del Día -->
        <h2 class="section-title">
            <i class="fas fa-calendar-day"></i> Recaudación del Día
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card card-custom stat-card success">
                    <div class="stat-label">Total Recaudado Hoy</div>
                    <div class="stat-value text-success" id="totalRecaudadoHoy">L. 0.00</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card card-custom stat-card info">
                    <div class="stat-label">Capital Cobrado</div>
                    <div class="stat-value text-info" id="capitalHoy">L. 0.00</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card card-custom stat-card warning">
                    <div class="stat-label">Interés (4%)</div>
                    <div class="stat-value text-warning" id="interesHoy">L. 0.00</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card card-custom stat-card danger">
                    <div class="stat-label">Gastos + Comisión (7%)</div>
                    <div class="stat-value text-danger" id="gastosComisionHoy">L. 0.00</div>
                </div>
            </div>
        </div>

        <!-- Tabla de Cobros del Día -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Detalle de Cobros del Día
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="loading-spinner" id="loadingCobros">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tablaCobrosHoy">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Capital</th>
                                        <th>Interés (4%)</th>
                                        <th>Gastos/Comisión (7%)</th>
                                        <th>Total</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos cargados dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado de Cartera y Mora -->
        <h2 class="section-title">
            <i class="fas fa-chart-pie"></i> Estado de Cartera y Mora
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card card-custom">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-wallet"></i> Resumen de Cartera
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartCartera"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card card-custom">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Distribución de Mora
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartMora"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categorías de Riesgo -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-lg-2">
                <div class="card card-custom stat-card success">
                    <div class="stat-label">Categoría A</div>
                    <div class="stat-value text-success" id="categoriaA">0</div>
                    <small class="text-muted" id="montoA">L. 0.00</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <div class="card card-custom stat-card info">
                    <div class="stat-label">Categoría B</div>
                    <div class="stat-value text-info" id="categoriaB">0</div>
                    <small class="text-muted" id="montoB">L. 0.00</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <div class="card card-custom stat-card warning">
                    <div class="stat-label">Categoría C</div>
                    <div class="stat-value text-warning" id="categoriaC">0</div>
                    <small class="text-muted" id="montoC">L. 0.00</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card card-custom stat-card danger">
                    <div class="stat-label">Categoría D</div>
                    <div class="stat-value text-danger" id="categoriaD">0</div>
                    <small class="text-muted" id="montoD">L. 0.00</small>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card card-custom stat-card" style="border-left-color: #7c3aed;">
                    <div class="stat-label">Categoría E</div>
                    <div class="stat-value" style="color: #7c3aed;" id="categoriaE">0</div>
                    <small class="text-muted" id="montoE">L. 0.00</small>
                </div>
            </div>
        </div>

        <!-- Clientes en Mora (>30 días) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-circle"></i> Clientes en Mora (+30 días)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="loading-spinner" id="loadingMora">
                            <div class="spinner-border text-danger" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tablaClientesMora">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Préstamo</th>
                                        <th>Días Mora</th>
                                        <th>Categoría</th>
                                        <th>Monto en Riesgo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos cargados dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // Variables globales
        const esAdministrador = <?php echo $esAdministrador ? 'true' : 'false'; ?>;
        const idAgenciaUsuario = <?php echo $idAgencia ? $idAgencia : 'null'; ?>;
        let idAgenciaActual = idAgenciaUsuario;
        let chartCartera = null;
        let chartMora = null;

        // Función para formatear moneda
        function formatCurrency(amount) {
            return 'L. ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Función para obtener la URL base
        function getBaseUrl() {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const path = window.location.pathname;
            const basePath = path.substring(0, path.indexOf('/public'));
            return protocol + '//' + host + basePath;
        }

        const baseUrl = getBaseUrl();

        // Cargar agencias (solo administradores)
        async function cargarAgencias() {
            if (!esAdministrador) return;

            try {
                const response = await fetch(baseUrl + '/app/api/agencias/list.php');
                const data = await response.json();

                if (data.success) {
                    const selector = $('#selectorAgencia');
                    selector.empty();
                    selector.append('<option value="">Todas las Agencias</option>');

                    data.data.forEach(agencia => {
                        const selected = agencia.id_agencia == idAgenciaActual ? 'selected' : '';
                        selector.append(`<option value="${agencia.id_agencia}" ${selected}>${agencia.nombre_agencia}</option>`);
                    });

                    selector.on('change', function () {
                        idAgenciaActual = $(this).val();
                        cargarTodosLosDatos();
                    });
                }
            } catch (error) {
                console.error('Error cargando agencias:', error);
            }
        }

        // Cargar resumen de recaudación del día
        async function cargarRecaudacionDia() {
            $('#loadingCobros').show();

            try {
                const url = baseUrl + '/app/api/reportes/recaudacion-dia.php' +
                    (idAgenciaActual ? '?id_agencia=' + idAgenciaActual : '');

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    const resumen = data.data.resumen;
                    const cobros = data.data.cobros;

                    // Actualizar totales
                    $('#totalRecaudadoHoy').text(formatCurrency(resumen.total_recaudado));
                    $('#capitalHoy').text(formatCurrency(resumen.capital_total));
                    $('#interesHoy').text(formatCurrency(resumen.interes_total));
                    $('#gastosComisionHoy').text(formatCurrency(resumen.gastos_comision_total));

                    // Llenar tabla de cobros
                    const tbody = $('#tablaCobrosHoy tbody');
                    tbody.empty();

                    if (cobros.length === 0) {
                        tbody.append('<tr><td colspan="6" class="text-center text-muted py-4">No hay cobros registrados hoy</td></tr>');
                    } else {
                        cobros.forEach(cobro => {
                            tbody.append(`
                                <tr>
                                    <td><strong>${cobro.cliente}</strong></td>
                                    <td>${formatCurrency(cobro.capital)}</td>
                                    <td>${formatCurrency(cobro.interes)}</td>
                                    <td>${formatCurrency(cobro.gastos_comision)}</td>
                                    <td><strong>${formatCurrency(cobro.total)}</strong></td>
                                    <td><small class="text-muted">${cobro.hora}</small></td>
                                </tr>
                            `);
                        });
                    }
                }
            } catch (error) {
                console.error('Error cargando recaudación:', error);
            } finally {
                $('#loadingCobros').hide();
            }
        }

        // Cargar estado de cartera
        async function cargarEstadoCartera() {
            try {
                const url = baseUrl + '/app/api/reportes/estado-cartera.php' +
                    (idAgenciaActual ? '?id_agencia=' + idAgenciaActual : '');

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    const categorias = data.data.categorias;

                    // Actualizar contadores
                    $('#categoriaA').text(categorias.A?.clientes || 0);
                    $('#categoriaB').text(categorias.B?.clientes || 0);
                    $('#categoriaC').text(categorias.C?.clientes || 0);
                    $('#categoriaD').text(categorias.D?.clientes || 0);
                    $('#categoriaE').text(categorias.E?.clientes || 0);

                    $('#montoA').text(formatCurrency(categorias.A?.monto || 0));
                    $('#montoB').text(formatCurrency(categorias.B?.monto || 0));
                    $('#montoC').text(formatCurrency(categorias.C?.monto || 0));
                    $('#montoD').text(formatCurrency(categorias.D?.monto || 0));
                    $('#montoE').text(formatCurrency(categorias.E?.monto || 0));

                    // Actualizar gráficos
                    actualizarGraficos(categorias);
                }
            } catch (error) {
                console.error('Error cargando estado de cartera:', error);
            }
        }

        // Actualizar gráficos
        function actualizarGraficos(categorias) {
            const labels = ['A', 'B', 'C', 'D', 'E'];
            const clientes = labels.map(cat => categorias[cat]?.clientes || 0);
            const montos = labels.map(cat => categorias[cat]?.monto || 0);

            // Gráfico de Cartera (Clientes)
            if (chartCartera) chartCartera.destroy();
            const ctxCartera = document.getElementById('chartCartera').getContext('2d');
            chartCartera = new Chart(ctxCartera, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: clientes,
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#7c3aed']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Clientes por Categoría'
                        }
                    }
                }
            });

            // Gráfico de Mora (Montos)
            if (chartMora) chartMora.destroy();
            const ctxMora = document.getElementById('chartMora').getContext('2d');
            chartMora = new Chart(ctxMora, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Monto en Riesgo',
                        data: montos,
                        backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#7c3aed']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Monto en Riesgo por Categoría'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return 'L. ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Cargar clientes en mora
        async function cargarClientesMora() {
            $('#loadingMora').show();

            try {
                const url = baseUrl + '/app/api/reportes/clientes-mora.php' +
                    (idAgenciaActual ? '?id_agencia=' + idAgenciaActual : '');

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    const tbody = $('#tablaClientesMora tbody');
                    tbody.empty();

                    if (data.data.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center text-muted py-4">No hay clientes en mora</td></tr>');
                    } else {
                        data.data.forEach(cliente => {
                            const badgeClass = `badge-${cliente.categoria.toLowerCase()}`;
                            tbody.append(`
                                <tr>
                                    <td><strong>${cliente.nombre_cliente}</strong></td>
                                    <td>${cliente.id_prestamo}</td>
                                    <td><span class="badge bg-danger">${cliente.dias_mora} días</span></td>
                                    <td><span class="badge badge-category ${badgeClass}">${cliente.categoria}</span></td>
                                    <td><strong>${formatCurrency(cliente.monto_riesgo)}</strong></td>
                                </tr>
                            `);
                        });
                    }
                }
            } catch (error) {
                console.error('Error cargando clientes en mora:', error);
            } finally {
                $('#loadingMora').hide();
            }
        }

        // Cargar todos los datos
        function cargarTodosLosDatos() {
            cargarRecaudacionDia();
            cargarEstadoCartera();
            cargarClientesMora();
        }

        // Inicializar
        $(document).ready(function () {
            if (esAdministrador) {
                cargarAgencias();
            }
            cargarTodosLosDatos();

            // Actualizar cada 5 minutos
            setInterval(cargarTodosLosDatos, 5 * 60 * 1000);
        });
    </script>
</body>

</html>