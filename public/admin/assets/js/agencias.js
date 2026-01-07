/**
 * Agencias JavaScript
 */

$(document).ready(function () {
    loadAgencias();

    // Eventos
    $('#btnNuevaAgencia').on('click', function () {
        openModal();
    });

    $('#agenciaForm').on('submit', function (e) {
        e.preventDefault();
        saveAgencia();
    });

    $('#btnCerrarModal, #btnCancelar').on('click', function () {
        closeModal();
    });
});

function loadAgencias() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');

    $.ajax({
        url: `${BASE_URL}/app/api/agencias/list.php`,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token },
        success: function (response) {
            if (response.success) {
                renderAgencias(response.data);
            }
        },
        error: handleAjaxError
    });
}

function renderAgencias(agencias) {
    const tbody = $('#agenciasTableBody');

    if (!agencias || agencias.length === 0) {
        tbody.html('<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay agencias registradas</td></tr>');
        return;
    }

    let html = '';
    agencias.forEach(function (a) {
        const estadoClass = a.estado === 'Activa'
            ? 'bg-green-100 text-green-800'
            : 'bg-red-100 text-red-800';

        html += `
            <tr class="hover:bg-indigo-50/30 transition border-b border-gray-100 last:border-0">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg mr-3">
                             <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900">${escapeHtml(a.nombre_agencia)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(a.ciudad || '')}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <div>${escapeHtml(a.direccion || '-')}</div>
                    <div class="text-xs text-gray-400 mt-1"><i class="fas fa-phone mr-1"></i>${escapeHtml(a.telefono_agencia || 'Sin teléfono')}</div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${a.total_colaboradores || 0}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                        ${escapeHtml(a.estado)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="editAgencia(${a.id_agencia})" class="text-indigo-600 hover:text-indigo-900 mx-2 p-1 hover:bg-indigo-50 rounded transition" title="Editar">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.html(html);
}

function openModal(agencia = null) {
    $('#agenciaForm')[0].reset();
    $('#agenciaId').val('');
    $('#modalTitle').text(agencia ? 'Editar Agencia' : 'Nueva Agencia');

    if (agencia) {
        $('#agenciaId').val(agencia.id_agencia);
        $('#nombreAgencia').val(agencia.nombre_agencia);
        $('#ciudad').val(agencia.ciudad);
        $('#telefonoAgencia').val(agencia.telefono_agencia);
        $('#direccion').val(agencia.direccion);
        $('#estado').val(agencia.estado);
    } else {
        $('#estado').val('Activa');
    }

    $('#agenciaModal').removeClass('hidden').addClass('flex');
}

function closeModal() {
    $('#agenciaModal').removeClass('flex').addClass('hidden');
}

function saveAgencia() {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    const id = $('#agenciaId').val();
    const isEdit = id !== '';

    const data = {
        nombre_agencia: $('#nombreAgencia').val(),
        ciudad: $('#ciudad').val(),
        telefono_agencia: $('#telefonoAgencia').val(),
        direccion: $('#direccion').val(),
        estado: $('#estado').val()
    };

    if (isEdit) {
        data.id_agencia = id;
    }

    const url = isEdit ? `${BASE_URL}/app/api/agencias/update.php` : `${BASE_URL}/app/api/agencias/create.php`;
    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                showAlert('success', response.data ? response.data.message : 'Operación exitosa');
                closeModal();
                loadAgencias();
            }
        },
        error: handleAjaxError
    });
}

function editAgencia(id) {
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    $.ajax({
        url: `${BASE_URL}/app/api/agencias/get.php?id=${id}`,
        method: 'GET',
        headers: { 'Authorization': 'Bearer ' + token },
        success: function (response) {
            if (response.success) {
                openModal(response.data);
            }
        },
        error: handleAjaxError
    });
}

function handleAjaxError(xhr) {
    if (xhr.status === 401 || xhr.status === 403) {
        window.location.href = BASE_URL + '/public/login.php';
        return;
    }
    const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Ocurrió un error inesperado';
    showAlert('error', msg);
}

function showAlert(type, message) {
    Swal.fire({
        icon: type,
        title: type === 'success' ? 'Éxito' : 'Error',
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>"']/g, function (m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}
