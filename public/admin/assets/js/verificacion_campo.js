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
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin"></i> Cargando solicitudes...
            </td>
        </tr>
    `;

    try {
        // Encode the status to handle accents correctly
        const estado = encodeURIComponent('Verificación de Campo');
        let url = `${API_BASE_URL}/prestamos/list.php?page=${page}&estado=${estado}&search=${search}`;

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            renderTable(result.data.prestamos);
            renderPagination(result.data.pagination);
        } else {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-red-500">
                        Error al cargar datos: ${result.message}
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-red-500">
                    Ocurrió un error al conectar con el servidor.
                </td>
            </tr>
        `;
    }
}

function renderTable(prestamos) {
    const tableBody = document.getElementById('verificacionTableBody');

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

function renderPagination(pagination) {
    const container = document.getElementById('pagination');

    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = `
        <div class="flex items-center justify-between">
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

    // Previous button
    html += `
        <button onclick="loadSolicitudes(${pagination.page - 1})" 
            ${pagination.page === 1 ? 'disabled class="bg-gray-100 text-gray-400 cursor-not-allowed"' : 'class="bg-white text-gray-500 hover:bg-gray-50"'} 
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium">
            <span class="sr-only">Anterior</span>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    // Page numbers (simplified)
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

    // Next button
    html += `
        <button onclick="loadSolicitudes(${pagination.page + 1})" 
            ${pagination.page === pagination.total_pages ? 'disabled class="bg-gray-100 text-gray-400 cursor-not-allowed"' : 'class="bg-white text-gray-500 hover:bg-gray-50"'}
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium">
            <span class="sr-only">Siguiente</span>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

    html += `
                    </nav>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}
