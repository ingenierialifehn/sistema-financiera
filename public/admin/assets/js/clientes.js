/**
 * Clientes JavaScript - Gestión de clientes
 */

let currentPage = 1;
let currentSearch = '';
let currentEstado = '';

// Inicializar
$(document).ready(function() {
    loadClientes();
    
    // Eventos
    $('#btnNuevoCliente').on('click', function() {
        openModal();
    });
    
    $('#btnBuscar').on('click', function() {
        currentPage = 1;
        currentSearch = $('#searchInput').val();
        currentEstado = $('#filterEstado').val();
        loadClientes();
    });
    
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            $('#btnBuscar').click();
        }

// Exponer funciones al ámbito global para uso en onclick en HTML generado
window.saveClienteUbicacion = saveClienteUbicacion;
window.openReferenciasModal = openReferenciasModal;
window.closeReferenciasModal = closeReferenciasModal;
window.editReferencia = editReferencia;
window.deleteReferencia = deleteReferencia;

// Guardar geolocalización del cliente
function saveClienteUbicacion(clienteId) {
    if (!navigator.geolocation) {
        showAlert('error', 'Geolocalización no soportada por el navegador');
        return;
    }
    navigator.geolocation.getCurrentPosition(function(pos){
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        const data = {
            cliente_id: clienteId,
            latitud: pos.coords.latitude,
            longitud: pos.coords.longitude
        };
        $.ajax({
            url: `${BASE_URL}/app/api/clientes/update_location.php`,
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
            data: JSON.stringify(data),
            success: function(){ showAlert('success', 'Ubicación del cliente guardada'); },
            error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar la ubicación'); }
        });
    }, function(err){
        const msg = err && err.message ? err.message : 'No se pudo obtener la ubicación';
        showAlert('error', msg);
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
}

// Referencias personales
let refClienteId = null;
let refClienteNombre = '';
let refsPage = 1;

// Bind de referencias (usar un ready separado o incluir en el principal)
$(function(){
    $('#btnNuevaReferencia').on('click', function(){ resetReferenciaForm(); $('#refFormTitle').text('Nueva referencia'); });
    $('#referenciaForm').on('submit', function(e){ e.preventDefault(); submitReferencia(); });
});

function openReferenciasModal(clienteId, clienteNombre) {
    refClienteId = clienteId;
    refClienteNombre = clienteNombre || '';
    $('#refClienteId').val(refClienteId);
    $('#refClienteNombre').text(refClienteNombre);
    $('#referenciasModal').removeClass('hidden').addClass('flex');
    loadReferencias(1);
}
function closeReferenciasModal() {
    $('#referenciasModal').addClass('hidden').removeClass('flex');
}
function resetReferenciaForm() {
    $('#referenciaId').val('');
    $('#refNombre').val('');
    $('#refTelefono').val('');
    $('#refRelacion').val('');
    $('#refDireccion').val('');
}
function loadReferencias(page = 1) {
    refsPage = page;
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    const tbody = $('#referenciasTableBody');
    tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
    $.ajax({
        url: `${BASE_URL}/app/api/referencias/list.php?cliente_id=${refClienteId}&page=${page}&limit=10`,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token },
        success: function(resp){
            if (!resp.success) { tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No se pudieron cargar las referencias</td></tr>'); return; }
            const items = resp.data.referencias || resp.data.items || [];
            if (items.length === 0) { tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Sin referencias</td></tr>'); renderRefsPagination(resp.data.pagination); return; }
            let html = '';
            items.forEach(function(r){
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm">${escapeHtml(r.nombre || '')}</td>
                        <td class="px-6 py-3 text-sm">${escapeHtml(r.telefono || '')}</td>
                        <td class="px-6 py-3 text-sm">${escapeHtml(r.relacion || '')}</td>
                        <td class="px-6 py-3 text-sm">${escapeHtml(r.direccion || '')}</td>
                        <td class="px-6 py-3 text-sm">
                            <button class="text-blue-600 hover:text-blue-900 mr-3" title="Editar" onclick="editReferencia(${r.id}, '${encodeURIComponent(r.nombre || '')}', '${encodeURIComponent(r.telefono || '')}', '${encodeURIComponent(r.relacion || '')}', '${encodeURIComponent(r.direccion || '')}')"><i class="fas fa-edit"></i></button>
                            <button class="text-red-600 hover:text-red-900" title="Eliminar" onclick="deleteReferencia(${r.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            tbody.html(html);
            renderRefsPagination(resp.data.pagination);
        },
        error: function(){ tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Error al cargar</td></tr>'); }
    });
}
function renderRefsPagination(pagination) {
    const container = $('#referenciasPagination');
    if (!pagination || pagination.total_pages <= 1) { container.html(''); return; }
    let html = '<div class="flex items-center justify-between">';
    html += `<div class=\"text-sm text-gray-700\">Mostrando ${((pagination.page - 1) * pagination.limit) + 1} a ${Math.min(pagination.page * pagination.limit, pagination.total)} de ${pagination.total}</div>`;
    html += '<div class="flex space-x-2">';
    if (pagination.page > 1) html += `<button onclick=\"loadReferencias(${pagination.page - 1})\" class=\"px-3 py-1 border rounded hover:bg-gray-100\">Anterior</button>`;
    for (let i=1;i<=pagination.total_pages;i++) {
        if (i === pagination.page) html += `<button class=\"px-3 py-1 bg-indigo-600 text-white rounded\">${i}</button>`;
        else if (i===1 || i===pagination.total_pages || (i>=pagination.page-1 && i<=pagination.page+1)) html += `<button onclick=\"loadReferencias(${i})\" class=\"px-3 py-1 border rounded hover:bg-gray-100\">${i}</button>`;
    }
    if (pagination.page < pagination.total_pages) html += `<button onclick=\"loadReferencias(${pagination.page + 1})\" class=\"px-3 py-1 border rounded hover:bg-gray-100\">Siguiente</button>`;
    html += '</div></div>';
    container.html(html);
}
function editReferencia(id, nombreEnc, telefonoEnc, relacionEnc, direccionEnc) {
    $('#referenciaId').val(id);
    $('#refNombre').val(decodeURIComponent(nombreEnc));
    $('#refTelefono').val(decodeURIComponent(telefonoEnc));
    $('#refRelacion').val(decodeURIComponent(relacionEnc));
    $('#refDireccion').val(decodeURIComponent(direccionEnc));
    $('#refFormTitle').text('Editar referencia');
}
function submitReferencia() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    const id = $('#referenciaId').val();
    const data = {
        cliente_id: refClienteId,
        nombre: $('#refNombre').val(),
        telefono: $('#refTelefono').val(),
        relacion: $('#refRelacion').val(),
        direccion: $('#refDireccion').val()
    };
    const url = id ? `${BASE_URL}/app/api/referencias/update.php` : `${BASE_URL}/app/api/referencias/create.php`;
    if (id) data.id = parseInt(id);
    $.ajax({
        url: url,
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
        data: JSON.stringify(data),
        success: function(){
            showAlert('success', id ? 'Referencia actualizada' : 'Referencia creada');
            resetReferenciaForm();
            $('#refFormTitle').text('Nueva referencia');
            loadReferencias(refsPage);
        },
        error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar referencia'); }
    });
}
function deleteReferencia(id) {
    Swal.fire({ title: '¿Eliminar referencia?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then((res)=>{
        if (!res.isConfirmed) return;
        const token = localStorage.getItem('auth_token') || getCookie('auth_token');
        $.ajax({
            url: `${BASE_URL}/app/api/referencias/delete.php`,
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
            data: JSON.stringify({ id: id }),
            success: function(){ showAlert('success', 'Referencia eliminada'); loadReferencias(refsPage); },
            error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al eliminar referencia'); }
        });
    });
}
    });
    
    $('#clienteForm').on('submit', function(e) {
        e.preventDefault();
        saveCliente();
    });
    
    $('#btnCerrarModal, #btnCancelar').on('click', function() {
        closeModal();
    });
    
    $('#clienteModal').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});

// Cargar clientes
function loadClientes(page = 1) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    let url = `${BASE_URL}/app/api/clientes/list.php?page=${page}&limit=20`;
    if (currentSearch) {
        url += `&search=${encodeURIComponent(currentSearch)}`;
    }
    
    $.ajax({
        url: url,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                renderClientes(response.data.clientes);
                renderPagination(response.data.pagination);
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            } else {
                showAlert('error', 'Error al cargar clientes');
            }
        }
    });
}

// Renderizar tabla de clientes
function renderClientes(clientes) {
    const tbody = $('#clientesTableBody');
    
    if (!clientes || clientes.length === 0) {
        tbody.html('<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay clientes registrados</td></tr>');
        return;
    }
    
    let html = '';
    clientes.forEach(function(cliente) {
        const estadoClass = getEstadoClass(cliente.estado);
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${escapeHtml(cliente.codigo_cliente || '')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(cliente.nombre_completo || '')}</div>
                    ${cliente.email ? `<div class="text-sm text-gray-500">${escapeHtml(cliente.email)}</div>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(cliente.tipo_documento || '')}: ${escapeHtml(cliente.numero_documento || '')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(cliente.telefono || '')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(cliente.cobrador_nombre || 'Sin asignar')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                        ${escapeHtml(cliente.estado || '')}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="openReferenciasModal(${cliente.id}, '${escapeHtml(cliente.nombre_completo || '')}')" class="text-purple-600 hover:text-purple-900 mr-3" title="Referencias">
                        <i class="fas fa-user-friends"></i>
                    </button>
                    <button onclick="saveClienteUbicacion(${cliente.id})" class="text-green-600 hover:text-green-900 mr-3" title="Guardar ubicación">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>
                    <button onclick="editCliente(${cliente.id})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteCliente(${cliente.id})" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

// Renderizar paginación
function renderPagination(pagination) {
    const container = $('#pagination');
    
    if (pagination.total_pages <= 1) {
        container.html('');
        return;
    }
    
    let html = '<div class="flex items-center justify-between">';
    html += `<div class="text-sm text-gray-700">Mostrando ${((pagination.page - 1) * pagination.limit) + 1} a ${Math.min(pagination.page * pagination.limit, pagination.total)} de ${pagination.total}</div>`;
    html += '<div class="flex space-x-2">';
    
    // Botón anterior
    if (pagination.page > 1) {
        html += `<button onclick="loadClientes(${pagination.page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Anterior</button>`;
    }
    
    // Páginas
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="px-3 py-1 bg-indigo-600 text-white rounded">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
            html += `<button onclick="loadClientes(${i})" class="px-3 py-1 border rounded hover:bg-gray-100">${i}</button>`;
        } else if (i === pagination.page - 2 || i === pagination.page + 2) {
            html += `<span class="px-3 py-1">...</span>`;
        }
    }
    
    // Botón siguiente
    if (pagination.page < pagination.total_pages) {
        html += `<button onclick="loadClientes(${pagination.page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Siguiente</button>`;
    }
    
    html += '</div></div>';
    container.html(html);
}

// Abrir modal
function openModal(cliente = null) {
    $('#clienteForm')[0].reset();
    $('#clienteId').val('');
    $('#modalTitle').text(cliente ? 'Editar Cliente' : 'Nuevo Cliente');
    
    if (cliente) {
        $('#clienteId').val(cliente.id);
        $('#nombreCompleto').val(cliente.nombre_completo || '');
        $('#tipoDocumento').val(cliente.tipo_documento || 'DNI');
        $('#numeroDocumento').val(cliente.numero_documento || '');
        $('#telefono').val(cliente.telefono || '');
        $('#email').val(cliente.email || '');
        $('#direccion').val(cliente.direccion || '');
        $('#fechaNacimiento').val(cliente.fecha_nacimiento || '');
        $('#ocupacion').val(cliente.ocupacion || '');
        $('#estado').val(cliente.estado || 'activo');
    }
    
    $('#clienteModal').removeClass('hidden').addClass('flex');
}

// Cerrar modal
function closeModal() {
    $('#clienteModal').addClass('hidden').removeClass('flex');
}

// Editar cliente
function editCliente(id) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/clientes/get.php?id=${id}`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                openModal(response.data);
            }
        },
        error: function() {
            showAlert('error', 'Error al cargar cliente');
        }
    });
}

// Guardar cliente
function saveCliente() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    const id = $('#clienteId').val();
    const isEdit = id !== '';
    
    const data = {
        nombre_completo: $('#nombreCompleto').val(),
        tipo_documento: $('#tipoDocumento').val(),
        numero_documento: $('#numeroDocumento').val(),
        telefono: $('#telefono').val(),
        email: $('#email').val(),
        direccion: $('#direccion').val(),
        fecha_nacimiento: $('#fechaNacimiento').val(),
        ocupacion: $('#ocupacion').val(),
        estado: $('#estado').val()
    };
    
    if (isEdit) {
        data.id = id;
    }
    
    const url = isEdit ? `${BASE_URL}/app/api/clientes/update.php` : `${BASE_URL}/app/api/clientes/create.php`;
    const method = isEdit ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showAlert('success', isEdit ? 'Cliente actualizado exitosamente' : 'Cliente creado exitosamente');
                closeModal();
                loadClientes(currentPage);
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON) {
                showAlert('error', xhr.responseJSON.message || 'Error al guardar cliente');
                if (xhr.responseJSON.errors) {
                    console.error('Errores:', xhr.responseJSON.errors);
                }
            } else {
                showAlert('error', 'Error al guardar cliente');
            }
        }
    });
}

// Eliminar cliente
function deleteCliente(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará permanentemente el cliente',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const token = localStorage.getItem('auth_token') || getCookie('auth_token');
            
            $.ajax({
                url: `${BASE_URL}/app/api/clientes/delete.php?id=${id}`,
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Cliente eliminado exitosamente');
                        loadClientes(currentPage);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON) {
                        showAlert('error', xhr.responseJSON.message || 'Error al eliminar cliente');
                    } else {
                        showAlert('error', 'Error al eliminar cliente');
                    }
                }
            });
        }
    });
}

// Funciones helper
function getEstadoClass(estado) {
    const estados = {
        'activo': 'bg-green-100 text-green-800',
        'inactivo': 'bg-gray-100 text-gray-800',
        'en_mora': 'bg-red-100 text-red-800',
        'bloqueado': 'bg-yellow-100 text-yellow-800'
    };
    return estados[estado] || 'bg-gray-100 text-gray-800';
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function showAlert(type, message) {
    const icons = {
        'success': 'success',
        'error': 'error',
        'info': 'info',
        'warning': 'warning'
    };
    
    Swal.fire({
        icon: icons[type] || 'info',
        title: type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : type === 'warning' ? 'Advertencia' : 'Información',
        text: message,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

