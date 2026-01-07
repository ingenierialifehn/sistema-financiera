document.addEventListener('DOMContentLoaded', function () {
    loadTesoreriaData();
    loadCajeros();

    // Auto refresh every 30 seconds
    setInterval(loadTesoreriaData, 30000);
});

// Load Dashboard and Banks
function loadTesoreriaData() {
    // 1. Dashboard
    fetch('../../app/api/tesoreria/get_dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('dashSaldoBancos').textContent = formatCurrency(data.data.saldo_bancos);
                document.getElementById('dashSaldoCajas').textContent = formatCurrency(data.data.saldo_cajas);
                document.getElementById('dashPatrimonio').textContent = formatCurrency(data.data.patrimonio);
            }
        });

    // 2. Bancos Table
    fetch('../../app/api/tesoreria/get_bancos.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderBancosTable(data.data);
                populateBankSelect(data.data);
            }
        });
}

function renderBancosTable(bancos) {
    const tbody = document.getElementById('bancosTableBody');
    tbody.innerHTML = '';

    if (bancos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay cuentas bancarias registradas.</td></tr>`;
        return;
    }

    bancos.forEach(banco => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${banco.nombre_banco}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${banco.numero_cuenta}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${banco.tipo_cuenta}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${banco.moneda}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-right">${formatCurrency(banco.saldo_actual, banco.moneda)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="openTransferModal(${banco.id}, '${banco.nombre_banco}', '${banco.numero_cuenta}')" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100 transition">
                    <i class="fas fa-exchange-alt mr-1"></i> Traspasar
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function populateBankSelect(bancos) {
    const select = document.getElementById('selectBancoInyectar');
    select.innerHTML = '';
    bancos.forEach(banco => {
        const option = document.createElement('option');
        option.value = banco.id;
        option.textContent = `${banco.nombre_banco} - ${banco.numero_cuenta} (${formatCurrency(banco.saldo_actual, banco.moneda)})`;
        select.appendChild(option);
    });
}

function loadCajeros() {
    fetch('../../app/api/tesoreria/get_cajeros.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('selectCajero');
                select.innerHTML = '<option value="">Seleccione Cajero...</option>';
                data.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id_usuario;
                    option.textContent = `${user.nombre_completo} (${user.rol})`;
                    select.appendChild(option);
                });
            }
        });
}

// Form Handlers
document.getElementById('formBanco').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    fetch('../../app/api/tesoreria/create_banco.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Banco creado exitosamente');
                closeModal('modalBanco');
                this.reset();
                loadTesoreriaData();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => alert('Error de conexión'));
});

document.getElementById('formInyectar').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    fetch('../../app/api/tesoreria/inyectar_capital.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Capital inyectado exitosamente');
                closeModal('modalInyectar');
                this.reset();
                loadTesoreriaData();
            } else {
                alert('Error: ' + data.message);
            }
        });
});

document.getElementById('formTransferencia').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    fetch('../../app/api/tesoreria/transferir_caja.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Transferencia realizada exitosamente');
                closeModal('modalTransferencia');
                this.reset();
                loadTesoreriaData();
            } else {
                alert('Error: ' + data.message);
            }
        });
});

// UI Helpers
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function openTransferModal(bancoId, bancoNombre, bancoCuenta) {
    document.getElementById('transferOrigenId').value = bancoId;
    document.getElementById('transferOrigenNombre').value = `${bancoNombre} - ${bancoCuenta}`;
    openModal('modalTransferencia');
}

function formatCurrency(amount, currency = 'HNL') {
    return new Intl.NumberFormat('es-HN', { style: 'currency', currency: currency }).format(amount);
}
