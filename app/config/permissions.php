<?php
/**
 * Configuración de Permisos Detallados del Sistema
 * Incluye TODOS los botones y acciones específicas de cada módulo
 */

// Definir TODOS los permisos granulares por módulo
$detailedPermissions = [
    'dashboard' => [
        'label' => 'Dashboard',
        'permissions' => [
            'view' => 'Ver Dashboard',
        ]
    ],

    'tesoreria' => [
        'label' => 'Tesorería y Bancos',
        'permissions' => [
            'view' => 'Ver Módulo',
            'view_balances' => 'Ver Saldos de Bancos',
            'create_bank' => 'Crear Banco',
            'edit_bank' => 'Editar Banco',
            'delete_bank' => 'Eliminar Banco',
            'inject_capital' => 'Inyectar Capital',
            'transfer_funds' => 'Transferir Fondos',
            'export' => 'Exportar Datos',
        ]
    ],

    'boveda' => [
        'label' => 'Bóveda (Jalar Fondos)',
        'permissions' => [
            'view' => 'Ver Módulo',
            'view_balance' => 'Ver Saldo de Bóveda',
            'pull_funds' => 'Jalar Fondos desde Banco',
            'register_income' => 'Registrar Ingreso a Bóveda',
            'view_movements' => 'Ver Movimientos',
        ]
    ],

    'operaciones' => [
        'label' => 'Operaciones de Agencia',
        'permissions' => [
            'view' => 'Ver Módulo',
            'view_dashboard' => 'Ver Dashboard de Operaciones',
            'view_vault_balance' => 'Ver Saldo de Bóveda',
            'view_cash_balance' => 'Ver Saldo de Caja',
            'withdraw_vault_to_cash' => 'Retirar de Bóveda a Caja',
            'view_disbursements' => 'Ver Próximos Desembolsos',
            'prepare_delivery' => 'Preparar Entrega de Préstamo',
        ]
    ],

    'caja' => [
        'label' => 'Control de Caja',
        'permissions' => [
            'view' => 'Ver Módulo',
            'open_cash' => 'Abrir Caja',
            'close_cash' => 'Cerrar Caja',
            'view_balance' => 'Ver Saldo de Caja',
            'pull_funds_bank' => 'Jalar Fondos desde Banco',
            'withdraw_vault' => 'Retirar de Bóveda',
            'return_vault' => 'Devolver a Bóveda',
            'return_bank' => 'Devolver a Banco',
            'view_movements' => 'Ver Movimientos',
        ]
    ],

    'agencias' => [
        'label' => 'Agencias',
        'permissions' => [
            'view' => 'Ver Listado',
            'create' => 'Crear Agencia',
            'edit' => 'Editar Agencia',
            'delete' => 'Eliminar Agencia',
            'view_collaborators' => 'Ver Colaboradores de Agencia',
            'switch_agency' => 'Cambiar de Agencia (Super Admin)',
        ]
    ],

    'colaboradores' => [
        'label' => 'Colaboradores (Usuarios)',
        'permissions' => [
            'view' => 'Ver Listado',
            'create' => 'Crear Colaborador',
            'edit' => 'Editar Colaborador',
            'delete' => 'Eliminar Colaborador',
            'assign_role' => 'Asignar Rol',
            'change_status' => 'Cambiar Estado',
            'reset_password' => 'Resetear Contraseña',
            'export' => 'Exportar Datos',
        ]
    ],

    'clientes' => [
        'label' => 'Clientes',
        'permissions' => [
            'view' => 'Ver Listado',
            'view_details' => 'Ver Detalles de Cliente',
            'create' => 'Crear Cliente',
            'edit' => 'Editar Cliente',
            'delete' => 'Eliminar Cliente',
            'change_status' => 'Cambiar Estado',
            'print_ficha' => 'Imprimir Ficha',
            'create_business' => 'Registrar Negocio',
            'edit_business' => 'Editar Negocio',
            'delete_business' => 'Eliminar Negocio',
            'upload_documents' => 'Subir Documentos/Fotos',
            'view_loans' => 'Ver Préstamos del Cliente',
            'view_payments' => 'Ver Pagos del Cliente',
            'export' => 'Exportar Datos',
        ]
    ],

    'prestamos' => [
        'label' => 'Préstamos',
        'permissions' => [
            'view' => 'Ver Listado',
            'view_details' => 'Ver Detalles de Préstamo',
            'create' => 'Crear Solicitud',
            'edit' => 'Editar Solicitud',
            'delete' => 'Eliminar Solicitud',
            'approve' => 'Aprobar Préstamo',
            'reject' => 'Rechazar Préstamo',
            'disburse' => 'Desembolsar Préstamo',
            'cancel' => 'Cancelar Préstamo',
            'view_schedule' => 'Ver Plan de Pagos',
            'print_contract' => 'Imprimir Contrato',
            'export' => 'Exportar Datos',
        ]
    ],

    'garantias' => [
        'label' => 'Garantías',
        'permissions' => [
            'view' => 'Ver Listado',
            'create' => 'Registrar Garantía',
            'edit' => 'Editar Garantía',
            'delete' => 'Eliminar Garantía',
            'upload_documents' => 'Subir Documentos',
            'view_documents' => 'Ver Documentos',
        ]
    ],

    'referencias' => [
        'label' => 'Referencias',
        'permissions' => [
            'view' => 'Ver Listado',
            'create' => 'Registrar Referencia',
            'edit' => 'Editar Referencia',
            'delete' => 'Eliminar Referencia',
            'verify' => 'Verificar Referencia',
        ]
    ],

    'pagos' => [
        'label' => 'Pagos y Cuotas',
        'permissions' => [
            'view' => 'Ver Listado',
            'view_details' => 'Ver Detalles de Pago',
            'register_payment' => 'Registrar Pago',
            'cancel_payment' => 'Cancelar Pago',
            'approve_payment' => 'Aprobar Pago',
            'print_receipt' => 'Imprimir Recibo',
            'view_schedule' => 'Ver Calendario de Pagos',
            'export' => 'Exportar Datos',
        ]
    ],

    'cobrador' => [
        'label' => 'Cobrador (Ruta de Cobro)',
        'permissions' => [
            'view' => 'Ver Módulo',
            'view_route' => 'Ver Ruta de Cobro',
            'collect_payment' => 'Cobrar Cuota',
            'export' => 'Exportar Ruta',
        ]
    ],

    'reportes' => [
        'label' => 'Reportes',
        'permissions' => [
            'view' => 'Ver Módulo',
            'report_loans' => 'Reporte de Préstamos',
            'report_payments' => 'Reporte de Pagos',
            'report_portfolio' => 'Reporte de Cartera',
            'report_delinquency' => 'Reporte de Morosidad',
            'report_cash_flow' => 'Reporte de Flujo de Caja',
            'report_treasury' => 'Reporte de Tesorería',
            'export_excel' => 'Exportar a Excel',
            'export_pdf' => 'Exportar a PDF',
        ]
    ],

    'seguridad' => [
        'label' => 'Seguridad (Roles y Permisos)',
        'permissions' => [
            'view' => 'Ver Módulo',
            'create_role' => 'Crear Rol',
            'edit_role' => 'Editar Rol',
            'delete_role' => 'Eliminar Rol',
            'assign_permissions' => 'Asignar Permisos',
            'manage_positions' => 'Gestionar Puestos',
        ]
    ],

    'configuracion' => [
        'label' => 'Configuración General',
        'permissions' => [
            'view' => 'Ver Configuración',
            'edit_general' => 'Editar Configuración General',
            'edit_interest_rates' => 'Editar Tasas de Interés',
            'edit_fees' => 'Editar Comisiones',
            'edit_system' => 'Editar Configuración del Sistema',
        ]
    ],
];

return $detailedPermissions;
