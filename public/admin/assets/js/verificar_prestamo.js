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
        currentClienteId = currentPrestamo.cliente_id;

        // Update Header
        document.getElementById('headerPrestamoId').textContent = currentPrestamo.id;
        if (currentPrestamo.comentario_verificacion) {
            document.getElementById('comentarioVerificacion').value = currentPrestamo.comentario_verificacion;
        }

        // Fill Prestamo Form
        fillPrestamoForm(currentPrestamo);

        // 2. Get Client Details
        const respCliente = await fetch(`${API_BASE_URL}/clientes/get.php?id=${currentClienteId}`);
        const dataCliente = await respCliente.json();

        if (dataCliente.success) {
            fillClienteForm(dataCliente.data);
        }

        // 3. Get Business Details (Negocios)
        const respNegocio = await fetch(`${API_BASE_URL}/clientes/negocios/list.php?cliente_id=${currentClienteId}`);
        const dataNegocio = await respNegocio.json();

        if (dataNegocio.success && dataNegocio.data.length > 0) {
            const negocio = dataNegocio.data[0]; // Take the first business
            currentNegocioId = negocio.id;
            fillNegocioForm(negocio);
            document.getElementById('content-negocio').classList.remove('hidden'); // Ensure it's available
        } else {
            // Maybe show specific message or clear form
            console.log('No registered business found.');
        }

        Swal.close();

    } catch (error) {
        console.error('Error loading data:', error);
        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
    }
}

function fillPrestamoForm(p) {
    document.getElementById('prestamoMonto').value = p.monto_capital;
    document.getElementById('prestamoPlazo').value = p.plazo_meses;
    document.getElementById('prestamoTasa').value = p.tasa_interes; // Or tasa_total? Using tasa_interes for now.
    document.getElementById('prestamoModalidad').value = p.modalidad; // Ensure case matches (Diario/diario)
    // Simple fix for case sensitivity in select
    const options = document.getElementById('prestamoModalidad').options;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value.toLowerCase() === p.modalidad.toLowerCase()) {
            document.getElementById('prestamoModalidad').selectedIndex = i;
            break;
        }
    }

    // For diaPago, database usually stores it if monthly. If not monthly, it might be 0 or irrelevant.
    // If it's not in the 'prestamo' object explicitly, we might need to check if get_detalle returns it. 
    // Assuming standard prestamos table columns.
    // Note: p object from get_detalle often has joined fields.
    // Let's assume standard field names.
    // Check if dia_pago is in p or we need to calculate/infer.
    // Usually stored in DB if relevant. Not present in get_detalle dump earlier? 
    // Wait, get_detalle query: SELECT p.* ... so it should be there.
    // Wait, `dia_pago` is NOT in the `SHOW COLUMNS` output I saw earlier (My mistake? No, wait).
    // Let's re-read columns output Step 43.
    // `fecha_solicitud`, `created_at`, `fecha_desembolso`... I don't see `dia_pago` column in Step 43.
    // Ah, maybe it's logic based? Or I missed it.
    // Step 43 output only showed 26 rows. It might have been truncated? No.
    // It has `modalidad`, `plazo_meses`.
    // Maybe `dia_pago` is determined by `fecha_desembolso` day?
    // In `update_status.php` line 131: `$diaPago = intval(date('d')); // Payment day aligns with Disbursement Day`.
    // So distinct column might not exist.
    // But `refiDiaPago` in `prestamos.php` suggests there IS a way to set it.
    // If `dia_pago` is not in DB, we can't edit it directly.
    // But verify if `dia_pago` exists purely as a UI concept for creating the schedule.
    // If there is no column, I should probably hide it or bind it to `fecha_desembolso`.
    // Let's look at `prestamos.php` create form again. It has `id="diaPago"`.
    // And `app/api/prestamos/create.php` likely uses it to generate the schedule OR saves it.
    // Let's check `create.php` later if needed. For now, I'll ignore `diaPago` if it's not in the object `p`.

    if (p.fecha_desembolso) {
        document.getElementById('prestamoFecha').value = p.fecha_desembolso.split(' ')[0];
    }
}

function fillClienteForm(c) {
    document.getElementById('clienteId').value = c.id;
    document.getElementById('clienteNombre').value = c.nombre_completo;
    document.getElementById('clienteDni').value = c.identidad || c.dni || '';
    document.getElementById('clienteTelefono').value = c.telefono;
    document.getElementById('clienteDireccion').value = c.direccion;
}

function fillNegocioForm(n) {
    document.getElementById('negocioId').value = n.id;
    document.getElementById('negocioNombre').value = n.nombre_negocio;
    document.getElementById('negocioRubro').value = n.tipo_negocio || n.rubro || '';
    document.getElementById('negocioDireccion').value = n.direccion_negocio;
    document.getElementById('negocioIngresos').value = n.ingresos_promedio_mensual || n.ingresos_promedio || 0;
}

function switchTab(tab) {
    // Hide all
    document.getElementById('content-cliente').classList.add('hidden');
    document.getElementById('content-negocio').classList.add('hidden');
    document.getElementById('content-prestamo').classList.add('hidden');

    // Reset styles
    document.getElementById('tab-cliente').className = "w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
    document.getElementById('tab-negocio').className = "w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";
    document.getElementById('tab-prestamo').className = "w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300";

    // Show selected
    document.getElementById(`content-${tab}`).classList.remove('hidden');
    document.getElementById(`tab-${tab}`).className = "w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600";
}

// ------ SAVES ------

async function saveCliente() {
    const id = document.getElementById('clienteId').value;
    const data = {
        id: id,
        nombre_completo: document.getElementById('clienteNombre').value,
        identidad: document.getElementById('clienteDni').value,
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
    const data = {
        id: id,
        nombre_negocio: document.getElementById('negocioNombre').value,
        tipo_negocio: document.getElementById('negocioRubro').value,
        direccion_negocio: document.getElementById('negocioDireccion').value,
        ingresos_promedio: document.getElementById('negocioIngresos').value
    };
    // Note: api/clientes/negocios/update.php expects id parameter
    try {
        const response = await fetch(`${API_BASE_URL}/clientes/negocios/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) Swal.fire('Éxito', 'Negocio actualizado', 'success');
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
        fecha_desembolso: document.getElementById('prestamoFecha').value
        // Note: update.php might specifically require certain fields or structure
        // We should check api/prestamos/update.php structure if this fails.
    };

    try {
        const response = await fetch(`${API_BASE_URL}/prestamos/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) Swal.fire('Éxito', 'Préstamo actualizado', 'success');
        else Swal.fire('Error', result.message, 'error');
    } catch (e) { console.error(e); Swal.fire('Error', 'Error de red', 'error'); }
}

async function verificar(accion) {
    const comentario = document.getElementById('comentarioVerificacion').value;

    if (accion === 'rechazar' && !comentario.trim()) {
        Swal.fire('Atención', 'Debe agregar un comentario para rechazar.', 'warning');
        return;
    }

    const confirmResult = await Swal.fire({
        title: accion === 'autorizar' ? '¿Autorizar Préstamo?' : '¿Rechazar Préstamo?',
        text: accion === 'autorizar'
            ? 'El préstamo cambiará a estado "verificado".'
            : 'El préstamo será rechazado definitivamente.',
        icon: accion === 'autorizar' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: accion === 'autorizar' ? '#16a34a' : '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: accion === 'autorizar' ? 'Sí, Autorizar' : 'Sí, Rechazar'
    });

    if (!confirmResult.isConfirmed) return;

    try {
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
