/**
 * Colaboradores JavaScript - Gestión de colaboradores
 */

let currentPage = 1;
let currentSearch = '';
let currentEstado = '';
let currentColaboradorId = null;

// Inicializar
$(document).ready(function() {
    loadColaboradores();
    
    // Eventos
    $('#btnNuevoColaborador').on('click', function() {
        openModal();
    });
    
    $('#btnBuscar').on('click', function() {
        currentPage = 1;
        currentSearch = $('#searchInput').val();
        currentEstado = $('#filterEstado').val();
        loadColaboradores();
    });
    
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            $('#btnBuscar').click();
        }
    });
    
    $('#colaboradorForm').on('submit', function(e) {
        e.preventDefault();
        saveColaborador();
    });
    
    $('#btnCerrarModal, #btnCancelar').on('click', function() {
        closeModal();
    });
    
    // Cambiar requerimiento de contraseña según modo
    $('#colaboradorForm').on('change', '#colaboradorId', function() {
        const isEdit = $(this).val() !== '';
        if (isEdit) {
            $('#password').removeAttr('required');
            $('#passwordRequired').hide();
            $('#passwordHelp').text('Dejar vacío para mantener la contraseña actual');
        } else {
            $('#password').attr('required', 'required');
            $('#passwordRequired').show();
            $('#passwordHelp').text('Mínimo 6 caracteres');
        }
    });
});

// Cargar colaboradores
function loadColaboradores(page = 1) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    let url = `${BASE_URL}/app/api/colaboradores/list.php?page=${page}&limit=20`;
    if (currentSearch) {
        url += `&search=${encodeURIComponent(currentSearch)}`;
    }
    if (currentEstado) {
        url += `&estado=${encodeURIComponent(currentEstado)}`;
    }
    
    $.ajax({
        url: url,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function(response) {
            if (response.success) {
                renderColaboradores(response.data.colaboradores);
                renderPagination(response.data.pagination);
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = BASE_URL + '/public/login.php';
            } else {
                showAlert('error', 'Error al cargar colaboradores');
            }
        }
    });
}

// Renderizar tabla
function renderColaboradores(colaboradores) {
    const tbody = $('#colaboradoresTableBody');
    
    if (!colaboradores || colaboradores.length === 0) {
        tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay colaboradores registrados</td></tr>');
        return;
    }
    
    let html = '';
    colaboradores.forEach(function(colaborador) {
        const estadoClass = getEstadoClass(colaborador.estado);
        const fechaRegistro = new Date(colaborador.created_at).toLocaleDateString('es-HN');
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(colaborador.nombre_completo || '')}</div>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-user mr-1"></i> Usuario: <span class="font-mono">@${escapeHtml(colaborador.usuario || '')}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${escapeHtml(colaborador.email || '')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${fechaRegistro}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                        ${escapeHtml(colaborador.estado || '')}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="editColaborador(${colaborador.id})" 
                            class="text-indigo-600 hover:text-indigo-900 mr-3" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteColaborador(${colaborador.id})" 
                            class="text-red-600 hover:text-red-900" title="Eliminar">
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
    
    if (pagination.page > 1) {
        html += `<button onclick="loadColaboradores(${pagination.page - 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Anterior</button>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="px-3 py-1 bg-indigo-600 text-white rounded">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
            html += `<button onclick="loadColaboradores(${i})" class="px-3 py-1 border rounded hover:bg-gray-100">${i}</button>`;
        }
    }
    
    if (pagination.page < pagination.total_pages) {
        html += `<button onclick="loadColaboradores(${pagination.page + 1})" class="px-3 py-1 border rounded hover:bg-gray-100">Siguiente</button>`;
    }
    
    html += '</div></div>';
    container.html(html);
}

// Abrir modal
function openModal(colaborador = null) {
    $('#colaboradorForm')[0].reset();
    $('#colaboradorId').val('');
    $('#modalTitle').text(colaborador ? 'Editar Colaborador' : 'Nuevo Colaborador');
    $('#passwordRequired').show();
    $('#password').attr('required', 'required');
    $('#passwordHelp').text('Mínimo 6 caracteres');
    
    if (colaborador) {
        $('#colaboradorId').val(colaborador.id);
        $('#usuario').val(colaborador.usuario || '').prop('disabled', true);
        $('#nombreCompleto').val(colaborador.nombre_completo || '');
        $('#email').val(colaborador.email || '');
        $('#estado').val(colaborador.estado || 'activo');
        $('#password').removeAttr('required');
        $('#passwordRequired').hide();
        $('#passwordHelp').text('Dejar vacío para mantener la contraseña actual');
    } else {
        $('#usuario').prop('disabled', false);
    }
    
    $('#colaboradorModal').removeClass('hidden').addClass('flex');
}

// Cerrar modal
function closeModal() {
    $('#colaboradorModal').addClass('hidden').removeClass('flex');
}

// Editar colaborador
function editColaborador(id) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    
    $.ajax({
        url: `${BASE_URL}/app/api/colaboradores/get.php?id=${id}`,
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
            showAlert('error', 'Error al cargar colaborador');
        }
    });
}

// Guardar colaborador
function saveColaborador() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    const id = $('#colaboradorId').val();
    const isEdit = id !== '';
    
    const data = {
        nombre_completo: $('#nombreCompleto').val(),
        email: $('#email').val(),
        estado: $('#estado').val()
    };
    
    if (!isEdit) {
        data.usuario = $('#usuario').val();
        data.password = $('#password').val();
    } else {
        data.id = id;
        const password = $('#password').val();
        if (password) {
            data.password = password;
        }
    }
    
    const url = isEdit ? `${BASE_URL}/app/api/colaboradores/update.php` : `${BASE_URL}/app/api/colaboradores/create.php`;
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
                showAlert('success', isEdit ? 'Colaborador actualizado exitosamente' : 'Colaborador creado exitosamente');
                closeModal();
                loadColaboradores(currentPage);
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON) {
                showAlert('error', xhr.responseJSON.message || 'Error al guardar colaborador');
                if (xhr.responseJSON.errors) {
                    console.error('Errores:', xhr.responseJSON.errors);
                }
            } else {
                showAlert('error', 'Error al guardar colaborador');
            }
        }
    });
}

// Eliminar colaborador
function deleteColaborador(id) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará o desactivará el colaborador',
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
                url: `${BASE_URL}/app/api/colaboradores/delete.php?id=${id}`,
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message || 'Colaborador eliminado exitosamente');
                        loadColaboradores(currentPage);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON) {
                        showAlert('error', xhr.responseJSON.message || 'Error al eliminar colaborador');
                    } else {
                        showAlert('error', 'Error al eliminar colaborador');
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
        'inactivo': 'bg-gray-100 text-gray-800'
    };
    return estados[estado] || 'bg-gray-100 text-gray-800';
}

function escapeHtml(text) {
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
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
