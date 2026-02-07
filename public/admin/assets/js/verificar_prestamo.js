
document.addEventListener('DOMContentLoaded', function () {
    loadData();
});

let currentPrestamo = null;
let currentClienteId = null;
let currentNegocioId = null;

async function loadData() {
    try {
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo información del préstamo',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        // 1. Get Loan Details
        const respPrestamo = await fetch(`${API_BASE_URL}/prestamos/get_detalle.php?id=${PRESTAMO_ID}`);
        const dataPrestamo = await respPrestamo.json();

        if (!dataPrestamo.success) {
            Swal.fire('Error', dataPrestamo.message || 'No se pudo cargar el préstamo', 'error');
            return;
        }

        currentPrestamo = dataPrestamo.data.prestamo;
        currentClienteId = currentPrestamo.id_cliente; // Fixed from cliente_id

        // Update Header
        document.getElementById('headerPrestamoId').textContent = currentPrestamo.id || currentPrestamo.numero_prestamo;
        document.getElementById('badgeEstado').textContent = currentPrestamo.estado;
        document.getElementById('clienteNombreHeader').textContent = currentPrestamo.cliente_nombre || 'Cliente'; // Fixed from nombre_cliente

        if (currentPrestamo.comentario_verificacion) {
            if (document.getElementById('comentarioVerificacionDesktop'))
                document.getElementById('comentarioVerificacionDesktop').value = currentPrestamo.comentario_verificacion;
            if (document.getElementById('comentarioVerificacionMobile'))
                document.getElementById('comentarioVerificacionMobile').value = currentPrestamo.comentario_verificacion;
        }

        // Fill Prestamo Form
        fillPrestamoForm(currentPrestamo);

        // 2. Get Client Details
        const respCliente = await fetch(`${API_BASE_URL}/clientes/get.php?id=${currentClienteId}`);
        const dataCliente = await respCliente.json();

        if (dataCliente.success) {
            fillClienteForm(dataCliente.data);
            if (dataCliente.data.nombre_completo) {
                document.getElementById('clienteNombreHeader').textContent = dataCliente.data.nombre_completo;
            }
        }

        // 3. Get Business Details (Negocios)
        const respNegocio = await fetch(`${API_BASE_URL}/clientes/negocios/list.php?cliente_id=${currentClienteId}`);
        const dataNegocio = await respNegocio.json();

        if (dataNegocio.success && dataNegocio.data.length > 0) {
            const negocio = dataNegocio.data[0]; // Take the first business
            currentNegocioId = negocio.id;
            fillNegocioForm(negocio);
            document.getElementById('content-negocio').classList.remove('hidden');
        } else {
            console.log('No registered business found.');
        }

        Swal.close();
        calcularProyeccion(); // Initial calc

    } catch (error) {
        console.error('Error loading data:', error);
        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
    }
}

function fillPrestamoForm(p) {
    document.getElementById('prestamoMonto').value = parseFloat(p.monto_capital || 0);
    document.getElementById('prestamoPlazo').value = parseInt(p.plazo_meses || 0);
    // Use tasa_total if available, otherwise tasa_interes
    // Note: Backend update uses tasa_total. Frontend creates usually hardcode 11?
    // Let's use p.tasa_total if > 0, else p.tasa_interes
    const tasa = (parseFloat(p.tasa_total) > 0) ? p.tasa_total : p.tasa_interes;
    document.getElementById('prestamoTasa').value = parseFloat(tasa || 0);

    // Modalidad
    const modalSelect = document.getElementById('prestamoModalidad');
    // Try to match value case-insensitively
    for (let i = 0; i < modalSelect.options.length; i++) {
        if (modalSelect.options[i].value.toLowerCase() === (p.modalidad || '').toLowerCase()) {
            modalSelect.selectedIndex = i;
            break;
        }
    }

    // Tipo Prestamo
    const tipoSelect = document.getElementById('prestamoTipo');
    if (p.tipo_prestamo) {
        for (let i = 0; i < tipoSelect.options.length; i++) {
            if (tipoSelect.options[i].value.toLowerCase() === p.tipo_prestamo.toLowerCase()) {
                tipoSelect.selectedIndex = i;
                break;
            }
        }
    } else {
        // Infer default or leave as 'Nuevo'
        tipoSelect.value = 'Nuevo';
    }

    if (p.fecha_desembolso) {
        document.getElementById('prestamoFecha').value = p.fecha_desembolso.split(' ')[0];
    }

    // Neto a Entregar Update
    const neto = parseFloat(p.neto_entregar || p.monto_capital);
    const elNeto = document.getElementById('displayNeto');
    if (elNeto) elNeto.textContent = 'L ' + neto.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fillClienteForm(c) {
    document.getElementById('clienteId').value = c.id;
    document.getElementById('clienteNombre').value = c.nombre_completo;
    document.getElementById('clienteDni').value = c.numero_documento || c.identidad || c.dni || '';
    document.getElementById('clienteTelefono').value = c.telefono;
    document.getElementById('btnCall').href = `tel:${c.telefono}`;
    document.getElementById('clienteDireccion').value = c.direccion;
}

function fillNegocioForm(n) {
    document.getElementById('negocioId').value = n.id;
    document.getElementById('negocioNombre').value = n.nombre_negocio;
    document.getElementById('negocioRubro').value = n.tipo_negocio || n.rubro || '';
    document.getElementById('negocioDireccion').value = n.direccion_negocio;
    document.getElementById('negocioIngresos').value = n.ingresos_promedio_mensual || n.ingresos_promedio || 0;

    // --- RENDER BUSINESS PHOTOS ---
    const galleryEl = document.getElementById('galleryNegocio');
    let photosHtml = '';
    const uploadsUrl = `${API_BASE_URL}/../../uploads/negocios/`; // Relative to API Base

    for (let i = 1; i <= 5; i++) {
        const photoKey = `foto_negocio_${i}`;
        if (n[photoKey]) {
            photosHtml += `
                <a href="${uploadsUrl}${n[photoKey]}" target="_blank" class="block group relative aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 hover:shadow-md transition">
                    <img src="${uploadsUrl}${n[photoKey]}" alt="Foto ${i}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition"></div>
                </a>
            `;
        }
    }
    if (photosHtml) {
        galleryEl.innerHTML = photosHtml;
    } else {
        galleryEl.innerHTML = '<p class="col-span-full text-sm text-gray-400 italic">No hay fotos registradas.</p>';
    }

    // --- RENDER GUARANTEES ---
    const garantiasEl = document.getElementById('listGarantias');
    let garantiasHtml = '';

    // Check if warranties are in a separate list (from backend change) or flat columns (legacy)
    // list.php now returns 'garantias' array.
    if (n.garantias && n.garantias.length > 0) {
        n.garantias.forEach((g, idx) => {
            const photoUrl = g.foto ? `${uploadsUrl}${g.foto}` : null;
            garantiasHtml += `
                <div class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex-shrink-0 mr-4">
                        ${photoUrl
                    ? `<a href="${photoUrl}" target="_blank">
                                <img src="${photoUrl}" class="w-16 h-16 object-cover rounded shadow-sm border" alt="Garantía">
                               </a>`
                    : `<div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-gray-400">
                                <i class="fas fa-image"></i>
                               </div>`
                }
                    </div>
                    <div class="flex-1">
                        <h5 class="text-sm font-bold text-gray-800">${g.descripcion || 'Sin descripción'}</h5>
                        <p class="text-xs text-gray-500 mt-1">Valor Estimado: <span class="font-semibold text-gray-700">L ${parseFloat(g.valor).toLocaleString('es-HN', { minimumFractionDigits: 2 })}</span></p>
                    </div>
                </div>
            `;
        });
    } else {
        // Fallback for flat columns (legacy schema support if needed, though we moved to table)
        // If data comes flat:
        if (n.garantia_descripcion) {
            garantiasHtml += `
                <div class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex-1">
                        <h5 class="text-sm font-bold text-gray-800">${n.garantia_descripcion}</h5>
                        <p class="text-xs text-gray-500 mt-1">Valor: L ${n.garantia_valor}</p>
                    </div>
                </div>`;
        }
    }

    if (garantiasHtml) {
        garantiasEl.innerHTML = garantiasHtml;
    } else {
        garantiasEl.innerHTML = '<p class="text-sm text-gray-400 italic">No hay garantías registradas.</p>';
    }
}

function switchTab(tab) {
    // Hide all
    document.getElementById('content-cliente').classList.add('hidden');
    document.getElementById('content-negocio').classList.add('hidden');
    document.getElementById('content-prestamo').classList.add('hidden');

    // Reset styles
    const baseClass = "flex-1 py-4 px-4 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap transition-colors";
    const activeClass = "flex-1 py-4 px-4 text-center border-b-2 font-semibold text-sm border-indigo-500 text-indigo-600 whitespace-nowrap transition-colors";

    document.getElementById('tab-cliente').className = baseClass;
    document.getElementById('tab-negocio').className = baseClass;
    document.getElementById('tab-prestamo').className = baseClass;

    // Show selected
    document.getElementById(`content-${tab}`).classList.remove('hidden');
    document.getElementById(`tab-${tab}`).className = activeClass;
}

function calcularProyeccion() {
    const monto = parseFloat(document.getElementById('prestamoMonto').value) || 0;
    const plazo = parseInt(document.getElementById('prestamoPlazo').value) || 0;
    const tasa = parseFloat(document.getElementById('prestamoTasa').value) || 0;
    const modalidad = document.getElementById('prestamoModalidad').value.toLowerCase();
    const tipo = document.getElementById('prestamoTipo').value.toLowerCase();

    // Live update for Neto if NOT refinance (Refinance needs backend check for balance)
    const elNeto = document.getElementById('displayNeto');
    if (elNeto) {
        if (tipo !== 'refinanciamiento' && tipo !== 'readecuacion' && tipo !== 'readecuación') {
            elNeto.textContent = 'L ' + monto.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            elNeto.classList.remove('text-gray-400', 'italic');
            elNeto.classList.add('text-green-600');
        } else {
            // For Refinance, if Amount changed, we don't know the exact net yet without backend
            if (currentPrestamo && Math.abs(parseFloat(currentPrestamo.monto_capital) - monto) > 1) {
                elNeto.textContent = 'Guardar para calcular...';
                elNeto.classList.remove('text-green-600');
                elNeto.classList.add('text-gray-400', 'italic');
            } else {
                // Keep original if amount not changed
                const original = parseFloat(currentPrestamo ? (currentPrestamo.neto_entregar || currentPrestamo.monto_capital) : monto);
                elNeto.textContent = 'L ' + original.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                elNeto.classList.remove('text-gray-400', 'italic');
                elNeto.classList.add('text-green-600');
            }
        }
    }

    if (monto > 0 && plazo > 0) {
        // Calc matching backend Logic
        // Interes Total = Monto * (Tasa/100) * Plazo (Simplest Logic, check backend PrestamoHelper logic if complex)
        // Note: Tasa is usually monthly? Or Annual? In this system it seems treated as Monthly rate in the simplified calc in `create.php`:
        // create.php line 47: $totalInteresMonto = $monto * ($tasaTotal / 100) * $plazoMeses; (Assuming Tasa is Monthly % rate applied flatly?)
        // Actually 11% monthly is HUGE. 11% annual is small.
        // Let's assume the system logic: `tasa` is the Rate Percentage per Month? 
        // Debugging `create.php` logic ($tasaTotal = 11.00; ... $monto * ($tasaTotal/100) * $plazo).
        // If 1000 loan, 1 month, 11% -> 110 interest. Total 1110.
        // Yes, likely monthly flat rate.

        const totalInteres = monto * (tasa / 100) * plazo;
        const totalPagar = monto + totalInteres;

        let numCuotas = 1;
        switch (modalidad) {
            case 'diario': numCuotas = plazo * 20; break;
            case 'semanal': numCuotas = plazo * 4; break;
            case 'catorcenal': numCuotas = plazo * 2; break;
            case 'mensual': numCuotas = plazo * 1; break;
            default: numCuotas = plazo;
        }

        const cuota = (numCuotas > 0) ? (totalPagar / numCuotas) : 0;

        document.getElementById('displayCuota').textContent = 'L ' + cuota.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('displayTotal').textContent = 'L ' + totalPagar.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } else {
        document.getElementById('displayCuota').textContent = 'L 0.00';
        document.getElementById('displayTotal').textContent = 'L 0.00';
    }
}

function verFichaCliente() {
    if (currentClienteId) {
        window.open(`${VIEWS_BASE_URL}/ficha_cliente.php?id=${currentClienteId}`, '_blank');
    }
}

// ------ SAVES ------

async function saveCliente() {
    const id = document.getElementById('clienteId').value;
    const data = {
        id: id,
        nombre_completo: document.getElementById('clienteNombre').value,
        numero_documento: document.getElementById('clienteDni').value,
        telefono: document.getElementById('clienteTelefono').value,
        direccion: document.getElementById('clienteDireccion').value
    };

    try {
        const response = await fetch(`${API_BASE_URL}/clientes/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) Swal.fire('Éxito', 'Cliente actualizado', 'success');
        else Swal.fire('Error', result.message, 'error');
    } catch (e) { console.error(e); Swal.fire('Error', 'Error de red', 'error'); }
}

async function saveNegocio() {
    const id = document.getElementById('negocioId').value;
    // Decision: Update or Create?
    const isUpdate = (id && id !== '');
    const url = isUpdate
        ? `${API_BASE_URL}/clientes/negocios/update.php`
        : `${API_BASE_URL}/clientes/negocios/create.php`;

    // Needs FormData to support files (future proofing) or just JSON for text
    // The previous implementation used JSON. The Backend now supports both.
    // However, create.php needs cliente_id. update.php needs negocio_id.

    // Let's use FormData to allow robust handling and future file support
    const formData = new FormData();
    if (isUpdate) formData.append('negocio_id', id);
    // For create, we need cliente_id
    if (currentClienteId) formData.append('cliente_id', currentClienteId);

    formData.append('nombre_negocio', document.getElementById('negocioNombre').value);
    formData.append('tipo_negocio', document.getElementById('negocioRubro').value); // Alias handled by backend
    formData.append('rubro', document.getElementById('negocioRubro').value);
    formData.append('direccion_negocio', document.getElementById('negocioDireccion').value);
    formData.append('ingresos_promedio', document.getElementById('negocioIngresos').value);

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
            // No Content-Type header for FormData, browser sets it with boundary
        });
        const result = await response.json();

        if (result.success) {
            Swal.fire('Éxito', isUpdate ? 'Negocio actualizado' : 'Negocio registrado', 'success');
            // If created, update the ID so subsequent saves are updates
            if (!isUpdate && result.data && result.data.id) {
                document.getElementById('negocioId').value = result.data.id;
                currentNegocioId = result.data.id;
            }
        }
        else Swal.fire('Error', result.message, 'error');
    } catch (e) { console.error(e); Swal.fire('Error', 'Error de red', 'error'); }
}

async function savePrestamo() {
    const data = {
        id: PRESTAMO_ID,
        monto_capital: document.getElementById('prestamoMonto').value,
        plazo_meses: document.getElementById('prestamoPlazo').value,
        tasa_interes: document.getElementById('prestamoTasa').value,
        modalidad: document.getElementById('prestamoModalidad').value,
        tipo_prestamo: document.getElementById('prestamoTipo').value,
        fecha_desembolso: document.getElementById('prestamoFecha').value !== 'dd/mm/aaaa' && document.getElementById('prestamoFecha').value !== ''
            ? document.getElementById('prestamoFecha').value
            : null
    };

    try {
        const response = await fetch(`${API_BASE_URL}/prestamos/update.php`, {
            method: 'POST', // Now accepted by API
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            Swal.fire('Éxito', 'Préstamo actualizado', 'success');

            // Update currentPrestamo with the new data from server (including Recalculated Neto)
            if (result.data && result.data.id) {
                currentPrestamo = result.data; // Update global state
                fillPrestamoForm(currentPrestamo);
                calcularProyeccion(); // Refresh UI
            }
        }
        else Swal.fire('Error', result.message, 'error');
    } catch (e) { console.error(e); Swal.fire('Error', 'Error de red', 'error'); }
}

async function verificar(accion, source) {
    let comentario = '';

    // Determine comment source
    if (source === 'mobile') {
        comentario = document.getElementById('comentarioVerificacionMobile').value;
    } else {
        comentario = document.getElementById('comentarioVerificacionDesktop').value;
    }

    // Fallback: Check the other if empty? No, respect the view.
    // If user typed in desktop but clicked mobile (responsive resize?), ideally sync them.
    // But for simplicity, take from the active view logic.
    if (!comentario.trim()) {
        const other = (source === 'mobile')
            ? document.getElementById('comentarioVerificacionDesktop').value
            : document.getElementById('comentarioVerificacionMobile').value;
        if (other.trim()) comentario = other;
    }

    if (accion === 'rechazar' && !comentario.trim()) {
        Swal.fire('Atención', 'Debe agregar un comentario para rechazar.', 'warning');
        return;
    }

    const confirmResult = await Swal.fire({
        title: accion === 'autorizar' ? '¿Autorizar Préstamo?' : '¿Rechazar Préstamo?',
        text: accion === 'autorizar'
            ? 'El préstamo cambiará a estado "verificado" y pasará al Analista.'
            : 'El préstamo será rechazado definitivamente.',
        icon: accion === 'autorizar' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: accion === 'autorizar' ? '#16a34a' : '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: accion === 'autorizar' ? 'Sí, Autorizar' : 'Sí, Rechazar'
    });

    if (!confirmResult.isConfirmed) return;

    try {
        // Ensure comments are synced to update logic if needed, but verify.php takes it as arg
        const response = await fetch(`${API_BASE_URL}/prestamos/verificar.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prestamo_id: PRESTAMO_ID,
                accion: accion,
                comentario: comentario
            })
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Éxito', result.message, 'success').then(() => {
                window.location.href = `${VIEWS_BASE_URL}/verificacion_campo.php`;
            });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
    }
}
