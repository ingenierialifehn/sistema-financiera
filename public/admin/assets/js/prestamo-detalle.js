/**
 * Detalle de Préstamo - JS
 */

let prestamoId = null;
// Garantías
let garantiasPage = 1;
function loadGarantias(page = 1) {
  garantiasPage = page;
  const token = localStorage.getItem('auth_token') || getCookie('auth_token');
  const tbody = $('#garantiasTableBody');
  tbody.html('<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
  $.ajax({
    url: `${BASE_URL}/app/api/garantias/list.php?prestamo_id=${prestamoId}&page=${page}&limit=10`,
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + token },
    success: function(resp){
      if (!resp.success) { tbody.html('<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No se pudieron cargar las garantías</td></tr>'); return; }
      const items = resp.data.garantias || resp.data.items || [];
      if (items.length === 0) { tbody.html('<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Sin garantías</td></tr>'); renderGarantiasPagination(resp.data.pagination); return; }
      let html = '';
      items.forEach(function(g){
        html += `
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-3 text-sm">${escapeHtml(g.tipo || '')}</td>
            <td class="px-6 py-3 text-sm font-medium">${formatMoney(g.monto || 0)}</td>
            <td class="px-6 py-3 text-sm">${escapeHtml(g.descripcion || '')}</td>
            <td class="px-6 py-3 text-sm">
              <button class="text-blue-600 hover:text-blue-900 mr-3" title="Editar" onclick="editGarantia(${g.id}, '${encodeURIComponent(g.tipo || '')}', ${parseFloat(g.monto || 0)}, '${encodeURIComponent(g.descripcion || '')}')"><i class="fas fa-edit"></i></button>
              <button class="text-red-600 hover:text-red-900" title="Eliminar" onclick="deleteGarantia(${g.id})"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        `;
      });
      tbody.html(html);
      renderGarantiasPagination(resp.data.pagination);
    },
    error: function(){ tbody.html('<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Error al cargar</td></tr>'); }
  });
}

function renderGarantiasPagination(pagination) {
  const container = $('#garantiasPagination');
  if (!pagination || pagination.total_pages <= 1) { container.html(''); return; }
  let html = '<div class="flex items-center justify-between">';
  html += `<div class=\"text-sm text-gray-700\">Mostrando ${((pagination.page - 1) * pagination.limit) + 1} a ${Math.min(pagination.page * pagination.limit, pagination.total)} de ${pagination.total}</div>`;
  html += '<div class="flex space-x-2">';
  if (pagination.page > 1) html += `<button onclick=\"loadGarantias(${pagination.page - 1})\" class=\"px-3 py-1 border rounded hover:bg-gray-100\">Anterior</button>`;
  for (let i=1;i<=pagination.total_pages;i++) {
    if (i === pagination.page) html += `<button class=\"px-3 py-1 bg-indigo-600 text-white rounded\">${i}</button>`;
    else if (i===1 || i===pagination.total_pages || (i>=pagination.page-1 && i<=pagination.page+1)) html += `<button onclick=\"loadGarantias(${i})\" class=\"px-3 py-1 border rounded hover:bg-gray-100\">${i}</button>`;
  }
  if (pagination.page < pagination.total_pages) html += `<button onclick=\"loadGarantias(${pagination.page + 1})\" class=\"px-3 py-1 border rounded hover:bg-gray-100\">Siguiente</button>`;
  html += '</div></div>';
  container.html(html);
}

function openGarantiaModal(g = null) {
  $('#garantiaForm')[0].reset();
  $('#garantiaId').val(g && g.id ? g.id : '');
  $('#garantiaPrestamoId').val(prestamoId);
  if (g) {
    $('#garantiaTipo').val(g.tipo || '');
    $('#garantiaMonto').val(g.monto || 0);
    $('#garantiaDescripcion').val(g.descripcion || '');
    $('#garantiaModalTitle').text('Editar garantía');
  } else {
    $('#garantiaModalTitle').text('Nueva garantía');
  }
  $('#garantiaModal').removeClass('hidden').addClass('flex');
}
function closeGarantiaModal() { $('#garantiaModal').addClass('hidden').removeClass('flex'); }
function editGarantia(id, tipoEnc, monto, descEnc) {
  openGarantiaModal({ id: id, tipo: decodeURIComponent(tipoEnc), monto: monto, descripcion: decodeURIComponent(descEnc) });
}
function submitGarantia() {
  const token = localStorage.getItem('auth_token') || getCookie('auth_token');
  const id = $('#garantiaId').val();
  const data = {
    prestamo_id: prestamoId,
    tipo: $('#garantiaTipo').val(),
    monto: parseFloat($('#garantiaMonto').val() || 0),
    descripcion: $('#garantiaDescripcion').val()
  };
  const url = id ? `${BASE_URL}/app/api/garantias/update.php` : `${BASE_URL}/app/api/garantias/create.php`;
  if (id) data.id = parseInt(id);
  $.ajax({
    url: url,
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
    data: JSON.stringify(data),
    success: function(){
      showAlert('success', id ? 'Garantía actualizada' : 'Garantía creada');
      closeGarantiaModal();
      loadGarantias(garantiasPage);
    },
    error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar garantía'); }
  });
}
function deleteGarantia(id) {
  Swal.fire({ title: '¿Eliminar garantía?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then((res)=>{
    if (!res.isConfirmed) return;
    const token = localStorage.getItem('auth_token') || getCookie('auth_token');
    $.ajax({
      url: `${BASE_URL}/app/api/garantias/delete.php`,
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
      data: JSON.stringify({ id: id }),
      success: function(){ showAlert('success', 'Garantía eliminada'); loadGarantias(garantiasPage); },
      error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al eliminar garantía'); }
    });
  });
}

$(document).ready(function() {
  const params = new URLSearchParams(window.location.search);
  prestamoId = parseInt(params.get('id')) || null;
  if (!prestamoId) {
    window.location.href = BASE_URL + '/public/admin/prestamos.php';
    return;
  }

  $('#btnAbono').on('click', function(){ openAbonoModal(prestamoId); });
  $('#btnRefi').on('click', function(){ openRefiModal(prestamoId); });
  $('#abonoForm').on('submit', function(e){ e.preventDefault(); submitAbono(); });
  $('#refiForm').on('submit', function(e){ e.preventDefault(); submitRefi(); });
  $('#btnNuevaGarantia').on('click', function(){ openGarantiaModal(); });
  $('#garantiaForm').on('submit', function(e){ e.preventDefault(); submitGarantia(); });

  loadPrestamo();
  loadCuotas();
  loadGarantias();
});

function loadPrestamo() {
  const token = localStorage.getItem('auth_token') || getCookie('auth_token');
  $.ajax({
    url: `${BASE_URL}/app/api/prestamos/get.php?id=${prestamoId}`,
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + token },
    success: function(resp) {
      if (!resp.success) return;
      const p = resp.data;
      $('#resNumero').text(p.numero_prestamo || p.id);
      $('#resCliente').text(p.cliente_nombre || '');
      $('#resEstado').text(p.estado || '');
      $('#resMontoPrestado').text(formatMoney(p.monto_prestado));
      $('#resMontoTotal').text(formatMoney(p.monto_total));
      const saldo = (p.saldo_pendiente != null) ? p.saldo_pendiente : (p.monto_total - (p.monto_pagado_total || 0));
      $('#resSaldo').text(formatMoney(saldo));
      $('#resTasa').text((p.tasa_interes || 0));
      $('#resModalidad').text(p.modalidad || '-');
      $('#resPeriodo').text(p.periodo_meses || '-');
      $('#resDiaPago').text(p.dia_pago || '-');
      $('#resFechaDesembolso').text(p.fecha_desembolso || '-');

      // Prefijar IDs para modales
      $('#abonoPrestamoId').val(prestamoId);
      $('#refiPrestamoId').val(prestamoId);
    },
    error: function(){ showAlert('error', 'Error al cargar préstamo'); }
  });
}

function loadCuotas() {
  const token = localStorage.getItem('auth_token') || getCookie('auth_token');
  const tbody = $('#cuotasTableBody');
  tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
  $.ajax({
    url: `${BASE_URL}/app/api/prestamos/cuotas.php?prestamo_id=${prestamoId}`,
    method: 'GET',
    headers: { 'Authorization': 'Bearer ' + token },
    success: function(resp) {
      if (!resp.success) return;
      const cuotas = resp.data.cuotas || [];
      if (cuotas.length === 0) {
        tbody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay cuotas</td></tr>');
        return;
      }
      let html = '';
      cuotas.forEach(function(cu) {
        const estadoClass = getEstadoClass(cu.estado);
        html += `
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-3 text-sm">${cu.numero_cuota}</td>
            <td class="px-6 py-3 text-sm">${(cu.fecha_vencimiento || '').slice(0,10)}</td>
            <td class="px-6 py-3 text-sm font-medium">${formatMoney(cu.monto_cuota)}</td>
            <td class="px-6 py-3 text-sm">${formatMoney(cu.monto_pagado || 0)}</td>
            <td class="px-6 py-3 text-sm"><span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">${cu.estado}</span></td>
          </tr>
        `;
      });
      tbody.html(html);
    },
    error: function(){ showAlert('error', 'Error al cargar cuotas'); }
  });
}

// Abono a capital
function openAbonoModal(id) {
  const today = new Date().toISOString().slice(0,10);
  $('#abonoPrestamoId').val(id);
  $('#abonoFecha').val(today);
  $('#abonoMonto').val('');
  $('#abonoObs').val('');
  $('#abonoModal').removeClass('hidden').addClass('flex');
}
function closeAbonoModal() { $('#abonoModal').addClass('hidden').removeClass('flex'); }
function submitAbono() {
  const token = localStorage.getItem('auth_token') || getCookie('auth_token');
  const data = {
    prestamo_id: parseInt($('#abonoPrestamoId').val()),
    monto: parseFloat($('#abonoMonto').val()),
    fecha: $('#abonoFecha').val(),
    observaciones: $('#abonoObs').val()
  };
  $.ajax({
    url: `${BASE_URL}/app/api/prestamos/abonos_capital/create.php`,
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
    data: JSON.stringify(data),
    success: function(){
      showAlert('success', 'Abono a capital registrado');
      closeAbonoModal();
      loadPrestamo();
      loadCuotas();
    },
    error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al registrar abono'); }
  });
}

// Refinanciar 50%
function openRefiModal(id) {
  const today = new Date().toISOString().slice(0,10);
  $('#refiPrestamoId').val(id);
  $('#refiFecha').val(today);
  $('#refiPeriodo').val(6);
  $('#refiDiaPago').val(15);
  $('#refiModalidad').val('mensual');
  $('#refiTasa').val(4.00);
  $('#refiObs').val('');
  $('#refiModal').removeClass('hidden').addClass('flex');
}
function closeRefiModal() { $('#refiModal').addClass('hidden').removeClass('flex'); }
function submitRefi() {
  const token = localStorage.getItem('auth_token') || getCookie('auth_token');
  const data = {
    prestamo_id: parseInt($('#refiPrestamoId').val()),
    modalidad: $('#refiModalidad').val(),
    tasa_interes: parseFloat($('#refiTasa').val()),
    periodo_meses: parseInt($('#refiPeriodo').val()),
    dia_pago: parseInt($('#refiDiaPago').val()),
    fecha_desembolso: $('#refiFecha').val(),
    observaciones: $('#refiObs').val()
  };
  $.ajax({
    url: `${BASE_URL}/app/api/prestamos/refinanciar.php`,
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
    data: JSON.stringify(data),
    success: function(){
      showAlert('success', 'Refinanciamiento realizado');
      closeRefiModal();
      loadPrestamo();
      loadCuotas();
    },
    error: function(xhr){ showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al refinanciar'); }
  });
}

// Helpers
function getEstadoClass(estado) {
  const estados = {
    'activo': 'bg-green-100 text-green-800',
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'completado': 'bg-blue-100 text-blue-800',
    'cancelado': 'bg-gray-100 text-gray-800',
    'en_mora': 'bg-red-100 text-red-800'
  };
  return estados[estado] || 'bg-gray-100 text-gray-800';
}
function formatMoney(amount) {
  return 'L ' + parseFloat(amount || 0).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return null;
}
function showAlert(type, message) {
  const icons = { success: 'success', error: 'error', info: 'info', warning: 'warning' };
  Swal.fire({ icon: icons[type] || 'info', title: type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : type === 'warning' ? 'Advertencia' : 'Información', text: message, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
}
