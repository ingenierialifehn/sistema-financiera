<?php
$pageTitle = 'Gestión de Documentación';
require_once __DIR__ . '/includes/layout.php';
?>

<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6"> <i class="fas fa-file-contract mr-2"></i>Centro de Documentación
    </h1>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-gray-200 mb-6">
        <button onclick="switchTab('reprint')" id="tab-reprint"
            class="py-2 px-4 text-indigo-600 border-b-2 border-indigo-600 font-medium focus:outline-none tab-btn">
            Reimpresión
        </button>
        <button onclick="switchTab('cuadres')" id="tab-cuadres"
            class="py-2 px-4 text-gray-500 hover:text-indigo-600 font-medium focus:outline-none tab-btn">
            Cuadres de Caja
        </button>
        <button onclick="switchTab('templates')" id="tab-templates"
            class="py-2 px-4 text-gray-500 hover:text-indigo-600 font-medium focus:outline-none tab-btn">
            Editor de Plantillas
        </button>
        <button onclick="switchTab('settings')" id="tab-settings"
            class="py-2 px-4 text-gray-500 hover:text-indigo-600 font-medium focus:outline-none tab-btn">
            Configuración (Logo)
        </button>
    </div>

    <!-- CUADRES SECTION -->
    <div id="cuadres-section" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Historial de Cuadres</h2>

            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Reporte</label>
                    <select id="tipoCuadre" onchange="toggleCuadreFilters()"
                        class="w-full p-2 border rounded font-semibold text-indigo-700">
                        <option value="asesor">Cuadres de Asesor</option>
                        <option value="agencia">Cierres de Agencia</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                    <input type="date" id="cuadreFechaInicio" class="w-full p-2 border rounded"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                    <input type="date" id="cuadreFechaFin" class="w-full p-2 border rounded"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="md:col-span-1" id="filterAsesorContainer">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asesor</label>
                    <select id="cuadreAsesor" class="w-full p-2 border rounded">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="md:col-span-1 hidden" id="filterAgenciaContainer"> <!-- Added Agency Filter -->
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agencia</label>
                    <select id="cuadreAgencia" class="w-full p-2 border rounded">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="searchCuadres()"
                        class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 w-full">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </div>
            </div>

            <!-- Resultados -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="cuadresTable">
                    <thead class="bg-gray-50" id="cuadresTableHead">
                        <!-- Dynamic Headers -->
                    </thead>
                    <tbody id="cuadresTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Seleccione filtros y busque...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- REPRINT SECTION -->
    <div id="reprint-section" class="tab-content">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Buscar Préstamo para Imprimir</h2>
            <div class="flex gap-4 mb-6">
                <input type="text" id="loanSearch" placeholder="Buscar por Nº Préstamo, Nombre o DNI..."
                    class="flex-1 p-2 border rounded">
                <button onclick="searchLoans()"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Buscar</button>
            </div>

            <div id="loansResults" class="hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto/Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="loansTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Ajax Content -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TEMPLATES SECTION -->
    <div id="templates-section" class="tab-content hidden">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar with Variables -->
            <div class="w-full md:w-1/4 bg-white rounded-lg shadow p-4 h-fit overflow-y-auto"
                style="max-height: 600px;">
                <h3 class="font-bold mb-4 text-gray-700 sticky top-0 bg-white pb-2">Variables Disponibles</h3>
                <div class="space-y-2 text-sm">
                    <!-- CONTRATO -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-2 sticky top-10 bg-white py-1">📄 Número
                        de Contrato</p>
                    <button onclick="insertVar('{{numero_contrato}}')"
                        class="w-full text-left p-2 bg-blue-50 hover:bg-blue-100 rounded border border-blue-300 text-gray-700 text-xs font-semibold">Nº
                        Contrato (000013)</button>
                    <button onclick="insertVar('{{numero_prestamo}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Nº
                        Préstamo (13)</button>

                    <!-- CLIENTE -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-2 sticky top-10 bg-white py-1">👤 Cliente
                    </p>
                    <button onclick="insertVar('{{nombre_cliente}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Nombre
                        Cliente</button>
                    <button onclick="insertVar('{{dni_cliente}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">DNI</button>
                    <button onclick="insertVar('{{direccion_cliente}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Dirección</button>
                    <button onclick="insertVar('{{telefono_cliente}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Teléfono</button>

                    <!-- MONTO -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">💰 Monto
                        del Préstamo</p>
                    <button onclick="insertVar('{{monto_prestamo}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Monto
                        (5,000.00)</button>
                    <button onclick="insertVar('{{monto_letras}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Monto
                        en Letras</button>

                    <!-- PLAZO -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">📅 Plazo
                    </p>
                    <button onclick="insertVar('{{plazo}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Plazo
                        (12)</button>
                    <button onclick="insertVar('{{plazo_meses}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Plazo
                        (12 meses)</button>
                    <button onclick="insertVar('{{plazo_letras}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Plazo
                        (doce meses)</button>

                    <!-- CUOTAS -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">🔢 Cuotas
                    </p>
                    <button onclick="insertVar('{{total_cuotas}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Total
                        Cuotas (48)</button>
                    <button onclick="insertVar('{{total_cuotas_texto}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Total
                        Cuotas (48 cuotas)</button>
                    <button onclick="insertVar('{{total_cuotas_letras}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Total
                        Cuotas (cuarenta y ocho cuotas)</button>

                    <!-- MODALIDAD -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">⏱️
                        Modalidad/Frecuencia</p>
                    <button onclick="insertVar('{{modalidad}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Modalidad
                        (Semanal)</button>
                    <button onclick="insertVar('{{frecuencia}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Frecuencia
                        (Semanal)</button>
                    <button onclick="insertVar('{{frecuencia_minuscula}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Frecuencia
                        (semanal)</button>

                    <!-- VALOR CUOTA -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">💵 Valor
                        de Cuota</p>
                    <button onclick="insertVar('{{cuota}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Cuota
                        (1,300.00)</button>
                    <button onclick="insertVar('{{valor_cuota}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Cuota
                        (L. 1,300.00)</button>
                    <button onclick="insertVar('{{cuota_letras}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-xs text-gray-700">Cuota
                        en Letras</button>

                    <!-- TASA -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">📊 Tasa de
                        Interés</p>
                    <button onclick="insertVar('{{tasa_interes}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Tasa
                        (3.5)</button>
                    <button onclick="insertVar('{{tasa_interes_porcentaje}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Tasa
                        (3.5%)</button>

                    <!-- FECHAS -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">📆 Fechas
                    </p>
                    <button onclick="insertVar('{{fecha_desembolso}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Fecha
                        Desembolso</button>
                    <button onclick="insertVar('{{fecha_primera_cuota}}')"
                        class="w-full text-left p-2 bg-green-50 hover:bg-green-100 rounded border border-green-300 text-gray-700 text-xs font-semibold">📅
                        Fecha 1ra Cuota</button>
                    <button onclick="insertVar('{{dia_primera_cuota}}')"
                        class="w-full text-left p-2 bg-green-50 hover:bg-green-100 rounded border border-green-300 text-gray-700 text-xs">Día
                        1ra Cuota</button>
                    <button onclick="insertVar('{{mes_primera_cuota}}')"
                        class="w-full text-left p-2 bg-green-50 hover:bg-green-100 rounded border border-green-300 text-gray-700 text-xs">Mes
                        1ra Cuota</button>
                    <button onclick="insertVar('{{anio_primera_cuota}}')"
                        class="w-full text-left p-2 bg-green-50 hover:bg-green-100 rounded border border-green-300 text-gray-700 text-xs">Año
                        1ra Cuota</button>
                    <button onclick="insertVar('{{fecha_actual}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Fecha
                        Actual</button>
                    <button onclick="insertVar('{{dia_actual}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Día
                        Actual (10)</button>
                    <button onclick="insertVar('{{dia_actual_letras}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Día
                        Actual (diez)</button>
                    <button onclick="insertVar('{{mes_actual}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Mes
                        Actual</button>
                    <button onclick="insertVar('{{anio_actual}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Año
                        Actual</button>

                    <!-- AGENCIA -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">🏢 Agencia
                    </p>
                    <button onclick="insertVar('{{nombre_agencia}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Nombre
                        Agencia</button>
                    <button onclick="insertVar('{{ciudad_agencia}}')"
                        class="w-full text-left p-2 bg-gray-50 hover:bg-indigo-50 rounded border border-gray-200 text-gray-700 text-xs">Ciudad
                        Agencia</button>

                    <!-- EXTRAS -->
                    <p class="text-xs text-gray-500 uppercase font-semibold mt-4 sticky top-10 bg-white py-1">✍️ Firmas
                    </p>
                    <button onclick="insertSignatureBlock()"
                        class="w-full text-left p-2 bg-yellow-50 hover:bg-yellow-100 rounded border border-yellow-300 text-yellow-800 text-xs font-bold">
                        <i class="fas fa-pen-nib mr-1"></i> Insertar Bloque de Firmas
                    </button>
                </div>
            </div>

            <!-- Editor -->
            <div class="w-full md:w-3/4 bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                    <div class="flex gap-2 flex-grow">
                        <select id="templateSelect" onchange="loadTemplate()"
                            class="p-2 border rounded flex-grow max-w-xs">
                            <option value="">Seleccione Plantilla...</option>
                            <!-- Ajax options -->
                        </select>
                        <button onclick="openNewTemplateModal()"
                            class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <!-- Config Panel Button -->
                    <button onclick="toggleConfigPanel()"
                        class="bg-gray-100 text-gray-700 px-3 py-2 rounded hover:bg-gray-200 text-sm border">
                        <i class="fas fa-cog mr-1"></i> Configuración de Página
                    </button>

                    <button onclick="saveTemplate()"
                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 text-sm font-bold"><i
                            class="fas fa-save mr-2"></i>Guardar</button>
                </div>

                <!-- Page Config Panel (Hidden by default) -->
                <div id="pageConfigPanel" class="hidden bg-gray-50 p-4 rounded mb-4 border border-gray-200 text-sm">
                    <h4 class="font-bold text-gray-700 mb-3 border-b pb-1">Configuración de Impresión</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Papel -->
                        <div>
                            <label class="block text-gray-600 mb-1">Tamaño Papel</label>
                            <select id="cfg_paper" class="w-full p-1 border rounded">
                                <option value="carta">Carta (Letter)</option>
                                <option value="a4">A4</option>
                                <option value="oficio">Oficio (Legal)</option>
                            </select>
                        </div>
                        <!-- Orientación -->
                        <div>
                            <label class="block text-gray-600 mb-1">Orientación</label>
                            <select id="cfg_orientation" class="w-full p-1 border rounded">
                                <option value="portrait">Vertical</option>
                                <option value="landscape">Horizontal</option>
                            </select>
                        </div>
                        <!-- Logo -->
                        <div>
                            <label class="block text-gray-600 mb-1">Ancho Logo (px)</label>
                            <input type="number" id="cfg_logo_width" class="w-full p-1 border rounded" value="150"
                                min="50" max="500">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-gray-600 mb-1">Márgenes (mm)</label>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div>
                                <span class="text-xs text-gray-400">Superior</span>
                                <input type="number" id="cfg_margin_top" class="w-full p-1 border rounded text-center"
                                    value="20">
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Derecho</span>
                                <input type="number" id="cfg_margin_right" class="w-full p-1 border rounded text-center"
                                    value="25">
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Inferior</span>
                                <input type="number" id="cfg_margin_bottom"
                                    class="w-full p-1 border rounded text-center" value="20">
                            </div>
                            <div>
                                <span class="text-xs text-gray-400">Izquierdo</span>
                                <input type="number" id="cfg_margin_left" class="w-full p-1 border rounded text-center"
                                    value="25">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Quill Editor container -->
                <div id="editor" style="height: 500px;"></div>
            </div>
        </div>
    </div>

    <!-- SETTINGS SECTION -->
    <div id="settings-section" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow p-6 max-w-md mx-auto text-center">
            <h2 class="text-xl font-semibold mb-4">Logo Institucional</h2>
            <p class="text-gray-500 mb-6">Este logo aparecerá en la esquina superior derecha de todos los documentos.
            </p>

            <div class="mb-6">
                <img id="currentLogo"
                    src="<?php echo BASE_URL; ?>/public/admin/assets/img/logo_empresa.png?v=<?php echo time(); ?>"
                    alt="Logo Actual" class="mx-auto h-32 object-contain border p-2 rounded"
                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3ESin Logo%3C/text%3E%3C/svg%3E'">
            </div>

            <input type="file" id="logoInput" accept="image/png, image/jpeg" class="hidden" onchange="uploadLogo()">
            <button onclick="document.getElementById('logoInput').click()"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-upload mr-2"></i> Subir Nuevo Logo
            </button>
        </div>
    </div>

</div>

<!-- Modal for New Template -->
<div id="newTemplateModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-xl font-bold mb-4">Nueva Plantilla</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Plantilla</label>
                    <input type="text" id="newTemplateName" class="w-full p-2 border rounded"
                        placeholder="Ej: Contrato de Arrendamiento">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select id="newTemplateType" class="w-full p-2 border rounded">
                        <option value="contrato">Contrato</option>
                        <option value="pagare">Pagaré</option>
                        <option value="garantia">Garantía</option>
                        <option value="recibo">Recibo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button onclick="closeNewTemplateModal()"
                    class="px-4 py-2 border rounded hover:bg-gray-100">Cancelar</button>
                <button onclick="createNewTemplate()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Crear</button>
            </div>
        </div>
    </div>
</div>

<!-- Load Quill Editor (No API Key Required) -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
    let quillEditor = null;

    // --- TABS LOGIC ---
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('text-indigo-600', 'border-b-2', 'border-indigo-600');
            el.classList.add('text-gray-500');
        });

        document.getElementById(tabId + '-section').classList.remove('hidden');
        document.getElementById('tab-' + tabId).classList.add('text-indigo-600', 'border-b-2', 'border-indigo-600');
        document.getElementById('tab-' + tabId).classList.remove('text-gray-500');

        if (tabId === 'templates') {
            initEditor();
            loadTemplatesList();
        } else if (tabId === 'cuadres') {
            loadAsesoresForFilter();
            loadAgenciasForFilter(); // NEW: Load agencies
            toggleCuadreFilters(); // Init headers
            searchCuadres(); // Auto search today
        }
    }

    // --- CUADRES LOGIC ---
    function toggleCuadreFilters() {
        const tipo = document.getElementById('tipoCuadre').value;
        const divAsesor = document.getElementById('filterAsesorContainer');
        const divAgencia = document.getElementById('filterAgenciaContainer');
        const thead = document.getElementById('cuadresTableHead');

        if (tipo === 'asesor') {
            divAsesor.style.display = 'block';
            divAgencia.style.display = 'none'; // Hide Agency filter for Advisor reports (or maybe keep it? Let's hide as per request)
            
            thead.innerHTML = `
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asesor</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Recaudado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Entregado</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
            `;
        } else {
            divAsesor.style.display = 'none';
            divAgencia.style.display = 'block'; // Show Agency filter for Agency Reports

            thead.innerHTML = `
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Control</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Apertura</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario Cierre</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Recaudado (Día)</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cierre Físico</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Diferencia</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
            `;
        }
        // Create empty state
        document.getElementById('cuadresTableBody').innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Haga clic en buscar...</td></tr>';
    }

    async function loadAsesoresForFilter() {
        if (document.getElementById('cuadreAsesor').options.length > 1) return; // Already loaded

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/app/api/usuarios/list.php');
            const json = await res.json();
            if (json.success) {
                const sel = document.getElementById('cuadreAsesor');
                // Filter users relevant for collection (asesor, cobrador)
                const asesores = json.data.filter(u => {
                    const rol = (u.rol_nombre || '').toLowerCase();
                    const cargo = (u.puesto_cargo || '').toLowerCase();
                    return rol.includes('asesor') || rol.includes('cobrador') || cargo.includes('asesor') || cargo.includes('cobrador');
                });

                asesores.forEach(a => {
                    sel.innerHTML += `<option value="${a.id_usuario}">${a.nombre_completo || a.username}</option>`;
                });
            }
        } catch (e) {
            console.error('Error loading advisors', e);
        }
    }

    async function loadAgenciasForFilter() {
        if (document.getElementById('cuadreAgencia').options.length > 1) return;

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/app/api/agencias/list.php');
            const json = await res.json();
            if (json.success) {
                const sel = document.getElementById('cuadreAgencia');
                json.data.forEach(a => {
                    sel.innerHTML += `<option value="${a.id_agencia}">${a.nombre_agencia}</option>`;
                });
            }
        } catch (e) {
            console.error('Error loading agencies', e);
        }
    }

    async function searchCuadres() {
        const tipo = document.getElementById('tipoCuadre').value;
        const fInicio = document.getElementById('cuadreFechaInicio').value;
        const fFin = document.getElementById('cuadreFechaFin').value;
        const tableBody = document.getElementById('cuadresTableBody');

        tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

        try {
            if (tipo === 'asesor') {
                const asesorId = document.getElementById('cuadreAsesor').value;
                let url = `<?php echo BASE_URL; ?>/app/api/caja/list_cuadres.php?fecha_inicio=${fInicio}&fecha_fin=${fFin}`;
                if (asesorId) url += `&asesor_id=${asesorId}`;

                const res = await fetch(url);
                const json = await res.json();

                if (json.success) {
                    if (json.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No se encontraron cuadres en este rango.</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = '';
                    json.data.forEach(c => {
                        const recaudado = parseFloat(c.monto_recaudado || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });
                        const entregado = parseFloat(c.monto_entregado || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 });

                        tableBody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-500">#${c.id}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">${new Date(c.fecha_registro).toLocaleString('es-HN')}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${c.nombre_completo || c.username}</td>
                                <td class="px-6 py-4 text-right text-sm text-green-600 font-semibold">L. ${recaudado}</td>
                                <td class="px-6 py-4 text-right text-sm text-blue-600 font-semibold">L. ${entregado}</td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="printCuadreTicket(${c.id})" class="text-indigo-600 hover:text-indigo-900 font-bold px-3 py-1 rounded hover:bg-indigo-50 transition">
                                        <i class="fas fa-print mr-1"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error: ${json.message}</td></tr>`;
                }

            } else { // AGENCIA
                const agenciaId = document.getElementById('cuadreAgencia').value;
                let url = `<?php echo BASE_URL; ?>/app/api/caja/list_cierres.php?fecha_inicio=${fInicio}&fecha_fin=${fFin}`;
                if (agenciaId) url += `&agencia_id=${agenciaId}`;

                const res = await fetch(url);
                const json = await res.json();

                if (json.success) {
                    if (json.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No se encontraron cierres en este rango.</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = '';
                    json.data.forEach(c => {
                        const sSistema = parseFloat(c.saldo_cierre_sistema || 0);
                        const sFisico = parseFloat(c.saldo_cierre_fisico || 0);
                        const dif = parseFloat(c.diferencia_cierre || 0);

                        const difClass = dif < 0 ? 'text-red-500' : (dif > 0 ? 'text-green-500' : 'text-gray-500');

                        tableBody.innerHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-500">#${c.id_control}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">${c.fecha_apertura}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">${c.nombre_usuario_cierre || c.usuario_cierre || 'N/A'}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-green-700">L. ${parseFloat(c.total_recaudado_dia || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium">L. ${sFisico.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                                <td class="px-6 py-4 text-center text-sm font-bold ${difClass}">L. ${dif.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="printCierreTicket(${c.id_control})" class="text-indigo-600 hover:text-indigo-900 font-bold px-3 py-1 rounded hover:bg-indigo-50 transition">
                                        <i class="fas fa-print mr-1"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Error: ${json.message}</td></tr>`;
                }
            }
        } catch (e) {
            console.error(e);
            tableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Error de conexión</td></tr>';
        }
    }

    async function printCuadreTicket(id) {
        try {
            const res = await fetch(`<?php echo BASE_URL; ?>/app/api/caja/get_cuadre_details.php?id=${id}`);
            const json = await res.json();

            if (json.success) {
                generateReceiptHtml(json.data);
            } else {
                alert('Error al obtener imagen del cuadre: ' + json.message);
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión al imprimir');
        }
    }

    async function printCierreTicket(idControl) {
        try {
            const res = await fetch(`<?php echo BASE_URL; ?>/app/api/caja/get_cierre_details.php?id_control=${idControl}`);
            const json = await res.json();

            if (json.success) {
                generateCierreReportHtml(json.data);
            } else {
                alert('Error al obtener datos del cierre: ' + json.message);
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión al imprimir');
        }
    }

    function generateCierreReportHtml(data) {
        const ventana = window.open('', '_blank', 'width=900,height=700');

        let filas = '';
        let totalRecaudado = 0;
        let totalCapital = 0;
        let totalInteres = 0;

        if (data.transacciones && data.transacciones.length > 0) {
            data.transacciones.forEach(t => {
                const monto = parseFloat(t.monto_pagado || 0);
                const capital = parseFloat(t.capital_pagado || 0);
                const interes = parseFloat(t.interes_pagado || 0);

                totalRecaudado += monto;
                totalCapital += capital;
                totalInteres += interes;

                filas += `
                    <tr>
                        <td>${t.hora}</td>
                        <td>${t.numero_cuota || '-'}</td>
                        <td>${t.cliente}</td>
                        <td>${t.cobrador}</td>
                        <td class="text-right">L. ${capital.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right">L. ${interes.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right font-bold">L. ${monto.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `;
            });
        } else {
            filas = '<tr><td colspan="7" class="text-center">No hay transacciones registradas este día</td></tr>';
        }

        const html = `
        <!DOCTYPE html>
        <html>
            <head>
                <title>Reporte de Cierre - ${data.nombre_agencia}</title>
                <style>
                    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; margin: 40px; color: #333; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #444; padding-bottom: 10px; }
                    h1 { margin: 0; font-size: 20px; text-transform: uppercase; color: #000; }
                    h2 { margin: 5px 0 0; font-size: 14px; font-weight: normal; color: #666; }
                    .info-grid { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 5px; }
                    .info-item label { display: block; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #888; margin-bottom: 3px; }
                    .info-item span { font-size: 14px; font-weight: 600; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
                    th { background: #eee; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #ccc; }
                    td { padding: 8px; border-bottom: 1px solid #eee; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .total-row td { border-top: 2px solid #000; font-weight: bold; font-size: 14px; padding-top: 10px; }
                    .signatures { margin-top: 100px; display: flex; justify-content: space-between; padding: 0 50px; }
                    .sig-box { width: 250px; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
                    .sig-name { font-weight: bold; margin-bottom: 3px; text-transform: uppercase; }
                    .sig-role { font-size: 11px; color: #666; }
                    .badge-reimpresion { position:absolute; top: 10px; right: 10px; border: 1px solid #000; padding: 5px; font-weight: bold; }
                </style>
            </head>
            <body onload="window.print();">
                <div class="badge-reimpresion">REIMPRESIÓN POR ${data.usuario_imprime}</div>
                <div class="header">
                    <h1>${data.nombre_agencia}</h1>
                    <h2>Reporte de Cierre de Agencia | ${data.fecha}</h2>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Oficial Responsable</label>
                        <span>${data.nombre_oficial}</span>
                    </div>
                </div>

                <div class="info-grid" style="background:#fff; border: 1px solid #ccc;">
                     <div class="info-item">
                        <label>${data.label_boveda || 'Saldo en Bóveda'}</label>
                        <span style="font-size: 16px;">L. ${parseFloat(data.saldo_boveda || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>
                     ${parseFloat(data.saldo_cierre_sistema) > 0 ? `
                     <div class="info-item">
                        <label>Saldo Sistema (Caja)</label>
                        <span>L. ${parseFloat(data.saldo_cierre_sistema).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>` : ''}
                    ${parseFloat(data.saldo_cierre_fisico) > 0 ? `
                    <div class="info-item">
                        <label>Saldo Físico (Caja)</label>
                        <span>L. ${parseFloat(data.saldo_cierre_fisico).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>` : ''}
                     <div class="info-item">
                        <label>Diferencia</label>
                        <span style="color: ${data.diferencia != 0 ? 'red' : 'green'}">L. ${parseFloat(data.diferencia).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>
                </div>

                <h3 style="border-left: 4px solid #000; padding-left: 10px;">Detalle de Transacciones del Día</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Ref</th>
                            <th>Cliente</th>
                            <th>Cobrado Por</th>
                            <th class="text-right">Capital</th>
                            <th class="text-right">Interés</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filas}
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="text-right">TOTALES DEL DÍA:</td>
                            <td class="text-right">L. ${totalCapital.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                            <td class="text-right">L. ${totalInteres.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                            <td class="text-right">L. ${totalRecaudado.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="signatures">
                    <div class="sig-box">
                        <div class="sig-name">${data.nombre_supervisor}</div>
                        <div class="sig-role">Supervisor de Agencia</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-name">${data.nombre_oficial}</div>
                        <div class="sig-role">Oficial de Operaciones</div>
                    </div>
                </div>
            </body>
        </html>
        `;

        ventana.document.write(html);
        ventana.document.close();
    }

    // --- RECEIPT GENERATION (Ported from control_caja.js) ---
    function generateReceiptHtml(data) {
        const ventana = window.open('', '_blank', 'width=900,height=800');

        // Header
        const fechaImpresion = new Date().toLocaleString('es-HN');

        // Transacciones
        let listaHtml = '';
        let sumCapital = 0;
        let sumInteres = 0;
        let sumTotal = 0;

        if (data.transacciones && data.transacciones.length > 0) {
            listaHtml = `
                <table class="table-items">
                    <thead>
                        <tr>
                            <th class="text-left" style="width: 35%">Cliente</th>
                            <th class="text-right" style="width: 20%">Capital</th>
                            <th class="text-right" style="width: 20%">Interés</th>
                            <th class="text-right" style="width: 25%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.transacciones.forEach(t => {
                const cap = parseFloat(t.capital_pagado || 0);
                const int = parseFloat(t.interes_pagado || 0);
                const tot = parseFloat(t.monto_pagado || 0);

                sumCapital += cap;
                sumInteres += int;
                sumTotal += tot;

                listaHtml += `
                    <tr>
                        <td>
                            <span class="item-title">${t.nombre_completo.substring(0, 25)}</span>
                            <span class="meta-info">${t.hora || ''}</span>
                        </td>
                        <td class="text-right valign-top">${cap.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right valign-top">${int.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right valign-top font-bold">${tot.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `;
            });

            // Add Totals Row
            listaHtml += `
                <tr class="totals-row">
                    <td class="text-right label-total">TOTALES:</td>
                    <td class="text-right value-total">${sumCapital.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="text-right value-total">${sumInteres.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    <td class="text-right value-total">${sumTotal.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                </tr>
            `;

            listaHtml += `</tbody></table>`;
        } else {
            listaHtml = '<p class="text-center italic my-4">- Sin transacciones registradas -</p>';
        }

        // Bancos
        let bancosHtml = '';
        if (data.detalle_bancos && data.detalle_bancos.length > 0) {
            bancosHtml = `
                <div class="section mt-4">
                    <div class="section-title">DETALLE DEPÓSITOS BANCARIOS</div>
                    <table class="table-items">
                        <thead>
                            <tr>
                                <th class="text-left">Banco / Cuenta</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            data.detalle_bancos.forEach(b => {
                const ref = b.referencia ? `Ref: ${b.referencia}` : '';
                bancosHtml += `
                    <tr>
                        <td>
                            <span class="item-title">${b.nombre_banco}</span><br>
                            <span class="meta-info">${ref}</span>
                        </td>
                        <td class="text-right valign-top">L ${parseFloat(b.total || b.monto).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `;
            });
            bancosHtml += `</tbody></table></div>`;
        }

        // Desembolsos Entregados
        let desembolsosHtml = '';
        let totalDesembolsado = 0;
        let totalNetoEntregarSum = 0;

        if (data.desembolsos_entregados && data.desembolsos_entregados.length > 0) {
            desembolsosHtml = `
                <div class="section mt-4">
                    <div class="section-title">PRÉSTAMOS ENTREGADOS (DESEMBOLSOS)</div>
                    <table class="table-items">
                        <thead>
                            <tr>
                                <th class="text-left" style="width: 35%">Cliente / Préstamo</th>
                                <th class="text-right" style="width: 20%">Monto Nuevo</th>
                                <th class="text-right" style="width: 20%">Saldo Ant.</th>
                                <th class="text-right" style="width: 25%">Neto Entregado</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            data.desembolsos_entregados.forEach(d => {
                const montoNuevo = parseFloat(d.monto_capital || 0);
                const netoEntregar = d.neto_entregar !== undefined ? parseFloat(d.neto_entregar) : montoNuevo;
                const saldoAnterior = d.monto_anterior !== undefined ? parseFloat(d.monto_anterior) : (montoNuevo - netoEntregar);

                totalDesembolsado += montoNuevo;
                totalNetoEntregarSum += netoEntregar;

                desembolsosHtml += `
                    <tr>
                        <td>
                            <span class="item-title">${d.nombre_completo.substring(0, 30)}</span>
                            <span class="meta-info">#${d.numero_prestamo || 'N/A'}</span>
                        </td>
                        <td class="text-right valign-top">L ${montoNuevo.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right valign-top" style="color:#666;"> - L ${saldoAnterior.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                        <td class="text-right valign-top font-bold">L ${netoEntregar.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `;
            });

            // Fila de totales
            desembolsosHtml += `
                <tr class="totals-row">
                    <td class="text-right label-total" colspan="3">TOTAL NETO ENTREGADO:</td>
                    <td class="text-right value-total">L ${totalNetoEntregarSum.toLocaleString('es-HN', { minimumFractionDigits: 2 })}</td>
                </tr>
            `;

            desembolsosHtml += `</tbody></table></div>`;
        }

        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reimpresión Recibo de Cuadre</title>
                <style>
                    @page { margin: 5mm; }
                    body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 0; padding: 10px; color: #000; }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
                    .header h2 { margin: 5px 0; font-size: 16px; }
                    .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
                    .section { margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; }
                    .section-title { font-weight: bold; margin-bottom: 10px; text-decoration: underline; }
                    .table-items { width: 100%; border-collapse: collapse; }
                    .table-items th { text-align: left; border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 11px; }
                    .table-items td { padding: 4px 0; vertical-align: top; }
                    .totals-row td { border-top: 1px solid #000; padding-top: 5px; font-weight: bold; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .meta-info { display: block; font-size: 10px; color: #555; }
                    .summary { margin-top: 20px; border: 1px solid #000; padding: 10px; background: #fdfdfd; }
                    .summary-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 5px; }
                    .total-final { font-weight: bold; font-size: 16px; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; }
                    .footer { margin-top: 30px; text-align: center; font-size: 10px; }
                    .reprint-mark { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 10px; text-transform: uppercase; }
                </style>
            </head>
            <body>
                <div class="reprint-mark">*** REIMPRESIÓN ***</div>
                <div class="header">
                    <h2>RECIBO DE CUADRE DE ASESOR</h2>
                    <p>Fecha Original: ${data.fecha}</p>
                    <p>Reimpresión: ${fechaImpresion}</p>
                </div>

                <div class="info-row">
                    <span><strong>Asesor:</strong></span>
                    <span>${data.asesor_nombre}</span>
                </div>
                 <div class="info-row">
                    <span><strong>ID Cuadre:</strong></span>
                    <span>#${data.id_cuadre}</span>
                </div>

                <div class="section">
                    <div class="section-title">DETALLE DE COBRANZA (INGRESOS)</div>
                    ${listaHtml}
                </div>

                ${bancosHtml}

                ${desembolsosHtml}

                <div class="summary">
                    <div class="section-title text-center">RESUMEN DEL DÍA</div>
                    
                    <div class="summary-row">
                        <span>(+) Total Recaudado:</span>
                        <span>L. ${parseFloat(data.monto_recaudado).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>
                    
                    <!-- Entregado logic: Bank + Cash -->
                    <div class="summary-row">
                        <span>(-) Entregado Banco:</span>
                        <span>L. ${parseFloat(data.total_banco_dia || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>
                    
                    <div class="summary-row">
                         <span>(-) Entregado Bóveda/Caja:</span>
                         <span>L. ${parseFloat(data.total_efectivo_dia || 0).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>

                    <div class="summary-row total-final">
                        <span>(=) DIFERENCIA:</span>
                        <span>L. ${parseFloat(data.diferencia).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span>
                    </div>
                </div>

                <div class="footer">
                    <p>_____________________________________</p>
                    <p>Firma Conforme</p>
                    <p>${data.asesor_nombre}</p>
                    <br>
                    <p style="font-size:10px;">Impreso por: ${data.usuario_imprime || 'Sistema'} | Transacción #${data.id_cuadre || 'N/A'}</p>
                </div>
                
                <script>
                    window.onload = function() { window.print(); };
                <\/script>
            </body>
            </html>
        `;

        ventana.document.write(html);
        ventana.document.close();
    }

    // --- REPRINT LOGIC ---
    async function searchLoans() {
        const term = document.getElementById('loanSearch').value.trim();
        if (!term || term.length === 0) {
            alert('Ingrese un término de búsqueda');
            return;
        }

        try {
            const res = await fetch('<?php echo BASE_URL; ?>/app/api/prestamos/list.php');
            const json = await res.json();

            console.log('API Response:', json); // Debug

            if (json.success) {
                const table = document.getElementById('loansTableBody');
                table.innerHTML = '';

                // Ensure json.data is an array
                let loans = Array.isArray(json.data) ? json.data : (json.data.prestamos || []);

                const filtered = loans.filter(l => {
                    const searchTerm = term.toLowerCase();
                    const loanId = String(l.id || '');
                    const nombre = (l.nombre_completo || '').toLowerCase();
                    const dni = (l.numero_documento || '');

                    return loanId.includes(term) ||
                        nombre.includes(searchTerm) ||
                        dni.includes(term);
                });

                document.getElementById('loansResults').classList.remove('hidden');

                filtered.forEach(loan => {
                    const actions = `
                    <div class="relative inline-block text-left group">
                        <button class="bg-gray-100 px-3 py-1 rounded hover:bg-gray-200 text-sm">Imprimir <i class="fas fa-chevron-down ml-1"></i></button>
                        <div class="hidden group-hover:block absolute right-0 mt-0 w-48 bg-white rounded-md shadow-lg z-50 border">
                            <a href="#" onclick="printAll(event, ${loan.id})" class="block px-4 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50 border-b"><i class="fas fa-print mr-2"></i>Imprimir Todo</a>
                            <a href="#" onclick="printDoc(event, ${loan.id}, 'contrato')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Contrato</a>
                            <a href="#" onclick="printDoc(event, ${loan.id}, 'pagare')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pagaré</a>
                            <a href="#" onclick="printDoc(event, ${loan.id}, 'garantia')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Garantía</a>
                        </div>
                    </div>
                `;

                    table.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#${loan.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${loan.nombre_completo || 'N/A'}<br>
                            <span class="text-xs text-gray-500">${loan.numero_documento || 'N/A'}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            L. ${parseFloat(loan.monto_aprobado || loan.monto_capital || 0).toLocaleString()}<br>
                            <span class="text-xs">${loan.fecha_creacion || loan.created_at || 'N/A'}</span>
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">${loan.estado}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                           ${actions}
                        </td>
                    </tr>
                `;
                });

                if (filtered.length === 0) {
                    table.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No se encontraron resultados</td></tr>';
                }
            } else {
                alert('Error al cargar préstamos: ' + (json.message || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al buscar préstamos. Revise la consola para más detalles.');
        }
    }

    async function printDoc(e, loanId, type) {
        e.preventDefault();
        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/get_template.php`);
        const json = await res.json();

        if (json.success) {
            const tmpl = json.data.find(t => t.tipo === type);
            if (tmpl) {
                // Use the new Modal Preview
                const url = `print_dynamic.php?loan_id=${loanId}&template_id=${tmpl.id}&autoprint=false`;
                const title = `Vista Previa - ${tmpl.nombre}`;

                // Call the global function defined at the bottom
                if (typeof openPreviewModal === 'function') {
                    openPreviewModal(url, title);
                } else {
                    // Fallback just in case
                    window.open(url, '_blank');
                }
            } else {
                alert('No hay plantilla configurada para ' + type);
            }
        }
    }

    async function printAll(e, loanId) {
        e.preventDefault();

        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/get_template.php`);
        const json = await res.json();

        if (json.success) {
            const types = ['contrato', 'pagare', 'garantia'];
            let printed = 0;

            types.forEach((type, index) => {
                const tmpl = json.data.find(t => t.tipo === type);
                if (tmpl) {
                    // Delay each window opening slightly to avoid popup blocker
                    setTimeout(() => {
                        window.open(`print_dynamic.php?loan_id=${loanId}&template_id=${tmpl.id}`, '_blank');
                    }, index * 300);
                    printed++;
                }
            });

            if (printed === 0) {
                alert('No hay plantillas configuradas');
            } else if (printed < 3) {
                alert(`Se imprimirán ${printed} de 3 documentos. Algunas plantillas no están configuradas.`);
            }
        }
    }


    // --- TEMPLATE EDITOR LOGIC ---
    function initEditor() {
        if (quillEditor) return;

        quillEditor = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });
    }

    async function loadTemplatesList() {
        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/get_template.php`);
        const json = await res.json();
        if (json.success) {
            const sel = document.getElementById('templateSelect');
            sel.innerHTML = '<option value="">Seleccione Plantilla...</option>';
            json.data.forEach(t => {
                sel.innerHTML += `<option value="${t.id}">${t.nombre} (${t.tipo})</option>`;
            });
        }
    }

    async function loadTemplate() {
        const id = document.getElementById('templateSelect').value;
        if (!id) {
            quillEditor.root.innerHTML = '';
            return;
        }

        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/get_template.php?id=${id}`);
        const json = await res.json();
        if (json.success) {
            quillEditor.root.innerHTML = json.data.contenido;

            // Load Config
            document.getElementById('cfg_paper').value = json.data.tamano_papel || 'carta';
            document.getElementById('cfg_orientation').value = json.data.orientacion || 'portrait';
            document.getElementById('cfg_logo_width').value = json.data.logo_ancho || 150;
            document.getElementById('cfg_margin_top').value = json.data.margen_top || 20;
            document.getElementById('cfg_margin_right').value = json.data.margen_right || 25;
            document.getElementById('cfg_margin_bottom').value = json.data.margen_bottom || 20;
            document.getElementById('cfg_margin_left').value = json.data.margen_left || 25;
        }
    }

    function toggleConfigPanel() {
        document.getElementById('pageConfigPanel').classList.toggle('hidden');
    }

    function insertVar(variable) {
        const range = quillEditor.getSelection();
        if (range) {
            quillEditor.insertText(range.index, variable);
        } else {
            quillEditor.insertText(quillEditor.getLength(), variable);
        }
    }

    function insertSignatureBlock() {
        const html = `
        <p><br></p>
        <table style="width: 100%; margin-top: 40px; border-collapse: collapse; border: none;">
            <tbody>
            <tr>
                <td style="width: 40%; border-top: 1px solid #000; text-align: center; padding-top: 5px; vertical-align: top;">
                    <p><strong>{{nombre_cliente}}</strong></p>
                    <p>EL DEUDOR</p>
                </td>
                <td style="width: 20%; border: none;"></td>
                <td style="width: 40%; border-top: 1px solid #000; text-align: center; padding-top: 5px; vertical-align: top;">
                    <p><strong>SISTEMA FINANCIERA</strong></p>
                    <p>EL ACREEDOR</p>
                </td>
            </tr>
            </tbody>
        </table>
        <p><br></p>`;

        const range = quillEditor.getSelection();
        if (range) {
            quillEditor.clipboard.dangerouslyPasteHTML(range.index, html);
        } else {
            quillEditor.clipboard.dangerouslyPasteHTML(quillEditor.getLength(), html);
        }
    }

    async function saveTemplate() {
        const id = document.getElementById('templateSelect').value;
        if (!id) {
            alert('Seleccione una plantilla primero');
            return;
        }

        const content = quillEditor.root.innerHTML;

        // Gather config
        const data = {
            id: id,
            contenido: content,
            tamano_papel: document.getElementById('cfg_paper').value,
            orientacion: document.getElementById('cfg_orientation').value,
            logo_ancho: document.getElementById('cfg_logo_width').value,
            margen_top: document.getElementById('cfg_margin_top').value,
            margen_right: document.getElementById('cfg_margin_right').value,
            margen_bottom: document.getElementById('cfg_margin_bottom').value,
            margen_left: document.getElementById('cfg_margin_left').value
        };

        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/save_template.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            Swal.fire('Guardado', 'Plantilla actualizada correctamente', 'success');
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    }

    // --- NEW TEMPLATE LOGIC ---
    function openNewTemplateModal() {
        document.getElementById('newTemplateModal').classList.remove('hidden');
    }

    function closeNewTemplateModal() {
        document.getElementById('newTemplateModal').classList.add('hidden');
        document.getElementById('newTemplateName').value = '';
    }

    async function createNewTemplate() {
        const name = document.getElementById('newTemplateName').value;
        const type = document.getElementById('newTemplateType').value;

        if (!name) {
            alert('Ingrese un nombre para la plantilla');
            return;
        }

        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/create_template.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre: name, tipo: type })
        });
        const json = await res.json();

        if (json.success) {
            closeNewTemplateModal();
            await loadTemplatesList();
            document.getElementById('templateSelect').value = json.id;
            await loadTemplate();
            Swal.fire('Creado', 'Nueva plantilla creada correctamente', 'success');
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    }

    // --- LOGO LOGIC ---
    async function uploadLogo() {
        const file = document.getElementById('logoInput').files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('logo', file);

        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/upload_logo.php`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            document.getElementById('currentLogo').src = json.url + '?t=' + new Date().getTime();
            Swal.fire('Actualizado', 'Logo actualizado correctamente', 'success');
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    }

</script>

<!-- Modal for Document Preview -->
<div id="documentPreviewModal"
    class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-75 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl h-[90vh] flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-xl font-bold text-gray-800" id="previewTitle">Vista Previa del Documento</h3>
            <button onclick="closePreviewModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="flex-grow bg-gray-100 p-4 overflow-hidden relative">
            <iframe id="previewFrame" class="w-full h-full border rounded shadow-sm bg-white" src=""></iframe>
        </div>
        <div class="p-4 border-t bg-gray-50 flex justify-end gap-3">
            <button onclick="closePreviewModal()"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Cerrar</button>
            <button onclick="printFromPreview()"
                class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition shadow flex items-center">
                <i class="fas fa-print mr-2"></i> Imprimir Ahora
            </button>
        </div>
    </div>
</div>

<script>
    // --- PREVIEW MODAL LOGIC ---
    function openPreviewModal(url, title) {
        document.getElementById('previewTitle').innerText = title || 'Vista Previa';
        document.getElementById('previewFrame').src = url;
        document.getElementById('documentPreviewModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closePreviewModal() {
        document.getElementById('documentPreviewModal').classList.add('hidden');
        document.getElementById('previewFrame').src = ''; // Stop loading/playing
        document.body.style.overflow = ''; // Restore scrolling
    }

    function printFromPreview() {
        // Access the iframe and call print
        const iframe = document.getElementById('previewFrame');
        if (iframe.contentWindow) {
            iframe.contentWindow.print();
        }
    }

    // Close on Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === "Escape") {
            closePreviewModal();
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>