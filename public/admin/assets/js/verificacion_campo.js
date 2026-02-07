document.addEventListener('DOMContentLoaded', function () {
    loadSolicitudes();

    document.getElementById('btnRefresh').addEventListener('click', loadSolicitudes);

    // Simple search debounce
    let timeout = null;
    document.getElementById('searchInput').addEventListener('input', function (e) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            loadSolicitudes(1, e.target.value);
        }, 500);
    });
});

async function loadSolicitudes(page = 1, search = '') {
    const tableBody = document.getElementById('verificacionTableBody');
    const mobileContainer = document.getElementById('mobileCardContainer');

    // Loading states
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin"></i> Cargando solicitudes...
                </td>
            </tr>
        `;
    }
    if (mobileContainer) {
        mobileContainer.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-spinner fa-spin"></i> Cargando solicitudes...
            </div>
        `;
    }

    try {
        // Encode the status to handle accents correctly
        const estado = encodeURIComponent('Verificación de Campo');
        let url = `${API_BASE_URL}/prestamos/list.php?page=${page}&estado=${estado}&search=${search}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            renderTable(result.data.prestamos);
            renderMobileCards(result.data.prestamos);
            renderPagination(result.data.pagination);
        } else {
            const errorHtml = `
                <div class="text-center py-4 text-red-500">
                    Error al cargar datos: ${result.message}
                </div>
            `;
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-red-500">
                            Error al cargar datos: ${result.message}
                        </td>
                    </tr>
                `;
            }
            if (mobileContainer) mobileContainer.innerHTML = errorHtml;
        }
    } catch (error) {
        console.error('Error:', error);
        const errorHtml = `
            <div class="text-center py-4 text-red-500">
                Ocurrió un error al conectar con el servidor.
            </div>
        `;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-red-500">
                        Ocurrió un error al conectar con el servidor.
                    </td>
                </tr>
            `;
        }
        if (mobileContainer) mobileContainer.innerHTML = errorHtml;
    }
}

function renderTable(prestamos) {
    const tableBody = document.getElementById('verificacionTableBody');
    if (!tableBody) return;

    if (prestamos.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    No hay solicitudes pendientes de verificación.
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = prestamos.map(p => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${new Date(p.created_at).toLocaleDateString()}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${p.numero_prestamo || 'N/A'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${p.cliente_nombre}</div>
                <div class="text-xs text-gray-500">${p.codigo_cliente || ''}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                L ${parseFloat(p.monto_capital).toLocaleString('es-HN', { minimumFractionDigits: 2 })}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${p.asesor_creditos_id ? `Asesor #${p.asesor_creditos_id}` : 'No asignado'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <a href="${VIEWS_BASE_URL}/verificar_prestamo.php?id=${p.id}" 
                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-check-circle mr-1"></i> Verificar
                </a>
            </td>
        </tr>
    `).join('');
}

function renderMobileCards(prestamos) {
    const container = document.getElementById('mobileCardContainer');
    if (!container) return;

    if (prestamos.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500 bg-white rounded-lg shadow">
                No hay solicitudes pendientes.
            </div>
        `;
        return;
    }

    container.innerHTML = prestamos.map(p => `
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        ${p.numero_prestamo || 'Sin Número'}
                    </span>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">${p.cliente_nombre}</h3>
                    <p class="text-xs text-gray-500">${p.codigo_cliente || ''}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-900">
                        L ${parseFloat(p.monto_capital).toLocaleString('es-HN', { minimumFractionDigits: 2 })}
                    </p>
                    <p class="text-xs text-gray-500">${new Date(p.created_at).toLocaleDateString()}</p>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-3 mt-2 flex justify-between items-center">
                <span class="text-xs text-gray-500">
                    <i class="fas fa-user-tie mr-1"></i> ${p.asesor_creditos_id ? `Asesor #${p.asesor_creditos_id}` : 'No asignado'}
                </span>
                <a href="${VIEWS_BASE_URL}/verificar_prestamo.php?id=${p.id}" 
                   class="flex-1 ml-4 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-check-circle mr-2"></i> Verificar
                </a>
            </div>
        </div>
    `).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    if (!container) return;

    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    // Mobile-friendly simplified pagination for small screens (handled by CSS classes usually, but here we can make it responsive)
    // We'll stick to a simple Next/Prev approach that works well on both

    let html = `
        <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <button onclick="loadSolicitudes(${pagination.page - 1})" 
                    ${pagination.page === 1 ? 'disabled class="opacity-50 cursor-not-allowed"' : ''}
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Anterior
                </button>
                <button onclick="loadSolicitudes(${pagination.page + 1})" 
                    ${pagination.page === pagination.total_pages ? 'disabled class="opacity-50 cursor-not-allowed"' : ''}
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Siguiente
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium">${((pagination.page - 1) * pagination.limit) + 1}</span> a 
                        <span class="font-medium">${Math.min(pagination.page * pagination.limit, pagination.total)}</span> de 
                        <span class="font-medium">${pagination.total}</span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
    `;

    // Checks for Desktop Pagination
    html += `
        <button onclick="loadSolicitudes(${pagination.page - 1})" 
            ${pagination.page === 1 ? 'disabled class="bg-gray-100 text-gray-400"' : 'class="bg-white text-gray-500 hover:bg-gray-50"'} 
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium">
            <span class="sr-only">Anterior</span>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `
                <button aria-current="page" class="z-10 bg-indigo-50 border-indigo-500 text-indigo-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                    ${i}
                </button>
            `;
        } else {
            html += `
                <button onclick="loadSolicitudes(${i})" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                    ${i}
                </button>
            `;
        }
    }

    html += `
        <button onclick="loadSolicitudes(${pagination.page + 1})" 
            ${pagination.page === pagination.total_pages ? 'disabled class="bg-gray-100 text-gray-400"' : 'class="bg-white text-gray-500 hover:bg-gray-50"'}
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium">
            <span class="sr-only">Siguiente</span>
            <i class="fas fa-chevron-right"></i>
        </button>
                    </nav>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}
