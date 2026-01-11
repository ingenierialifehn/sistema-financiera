document.addEventListener('DOMContentLoaded', function () {
    loadBancos();
    loadAgencias();
    loadGastosHistory();

    document.getElementById('formGasto').addEventListener('submit', handleFormSubmit);
});

function loadGastosHistory() {
    const fechaDesde = document.getElementById('filtroFechaDesde').value;
    const fechaHasta = document.getElementById('filtroFechaHasta').value;
    const tbody = document.querySelector('#tablaGastos tbody');
    const totalSpan = document.getElementById('totalGastosPeriodo');

    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

    fetch(`../../app/api/gastos/list.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No se encontraron gastos en este periodo.</td></tr>';
                    totalSpan.textContent = formatCurrency(0);
                    return;
                }

                let total = 0;
                tbody.innerHTML = data.data.map(gasto => {
                    total += parseFloat(gasto.monto);

                    // Limpiar descripción de prefijos técnicos si existen
                    let desc = gasto.observaciones || '';
                    if (desc.startsWith('Pago via Banco: ')) {
                        desc = desc.replace('Pago via Banco: ', '');
                    }

                    return `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${new Date(gasto.fecha_movimiento).toLocaleDateString('es-HN')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">${gasto.nombre_agencia}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                    ${gasto.categoria}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">${desc}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 mr-2">
                                        ${gasto.usuario_registro ? gasto.usuario_registro.substring(0, 2).toUpperCase() : '??'}
                                    </div>
                                    ${gasto.usuario_registro || 'Sistema'}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">${formatCurrency(gasto.monto)}</td>
                        </tr>
                    `;
                }).join('');

                totalSpan.textContent = formatCurrency(total);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error de conexión al cargar historial.</td></tr>';
        });
}

function loadBancos() {
    const select = document.getElementById('selectBanco');
    fetch('../../app/api/tesoreria/get_bancos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                select.innerHTML = '<option value="">Seleccione Cuenta Bancaria...</option>';
                data.data.forEach(banco => {
                    const option = document.createElement('option');
                    option.value = banco.id;
                    option.textContent = `${banco.nombre_banco} - ${banco.numero_cuenta} (${formatCurrency(banco.saldo_actual, banco.moneda)})`;
                    // Disable if balance is 0
                    if (parseFloat(banco.saldo_actual) <= 0) {
                        option.disabled = true;
                        option.textContent += ' (Sin Fondos)';
                    }
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">Error cargando bancos</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error de conexión</option>';
        });
}

function loadAgencias() {
    const select = document.getElementById('selectAgencia');
    fetch('../../app/api/agencias/list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                select.innerHTML = '<option value="">Seleccione Agencia...</option>';
                data.data.forEach(agencia => {
                    const option = document.createElement('option');
                    option.value = agencia.id_agencia;
                    option.textContent = agencia.nombre_agencia;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">Error cargando agencias</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error de conexión</option>';
        });
}

function handleFormSubmit(e) {
    e.preventDefault();

    // Confirm Action
    if (!confirm('¿Está seguro de registrar este gasto? Se descontará del banco seleccionado.')) {
        return;
    }

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    fetch('../../app/api/gastos/registrar_gasto.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Gasto registrado exitosamente.');
                this.reset();
                loadBancos(); // Reload banks to show updated balance
                loadGastosHistory(); // Reload history
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión con el servidor.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
}

function formatCurrency(amount, currency = 'HNL') {
    return new Intl.NumberFormat('es-HN', { style: 'currency', currency: currency }).format(amount);
}
