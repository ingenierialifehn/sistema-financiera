document.addEventListener('DOMContentLoaded', function () {
    loadBancos();
    loadAgencias();

    document.getElementById('formGasto').addEventListener('submit', handleFormSubmit);
});

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
