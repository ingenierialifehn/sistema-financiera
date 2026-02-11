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
        <button onclick="switchTab('templates')" id="tab-templates"
            class="py-2 px-4 text-gray-500 hover:text-indigo-600 font-medium focus:outline-none tab-btn">
            Editor de Plantillas
        </button>
        <button onclick="switchTab('settings')" id="tab-settings"
            class="py-2 px-4 text-gray-500 hover:text-indigo-600 font-medium focus:outline-none tab-btn">
            Configuración (Logo)
        </button>
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
                </div>
            </div>

            <!-- Editor -->
            <div class="w-full md:w-3/4 bg-white rounded-lg shadow p-4">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2">
                        <select id="templateSelect" onchange="loadTemplate()" class="p-2 border rounded w-64">
                            <option value="">Seleccione Plantilla...</option>
                            <!-- Ajax options -->
                        </select>
                        <button onclick="openNewTemplateModal()"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Nueva
                        </button>
                    </div>
                    <button onclick="saveTemplate()"
                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"><i
                            class="fas fa-save mr-2"></i>Guardar Cambios</button>
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
        }
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
                window.open(`print_dynamic.php?loan_id=${loanId}&template_id=${tmpl.id}`, '_blank');
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
        }
    }

    function insertVar(variable) {
        const range = quillEditor.getSelection();
        if (range) {
            quillEditor.insertText(range.index, variable);
        } else {
            quillEditor.insertText(quillEditor.getLength(), variable);
        }
    }

    async function saveTemplate() {
        const id = document.getElementById('templateSelect').value;
        if (!id) {
            alert('Seleccione una plantilla primero');
            return;
        }

        const content = quillEditor.root.innerHTML;

        const res = await fetch(`<?php echo BASE_URL; ?>/app/api/documentos/save_template.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, contenido: content })
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

<?php include __DIR__ . '/includes/footer.php'; ?>