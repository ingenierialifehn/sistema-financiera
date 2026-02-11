<?php
/**
 * CONFIGURACIÓN MAESTRA DE PERMISOS (GRANULARIDAD TOTAL)
 * Define todos los módulos, sub-módulos y acciones específicas.
 * Estructura: 'modulo' => ['label', 'permissions' => ['key' => 'Description']]
 */

return [
    // =================================================================================
    // 1. DASHBOARD Y ACCESO INICIAL
    // =================================================================================
    'dashboard' => [
        'label' => 'Dashboard Operativo',
        'description' => 'Pantalla principal de inicio. Requerido para ingresar al sistema.',
        'permissions' => [
            'view' => 'Ver Dashboard (Acceso al Sistema)',
            'view_stats' => 'Ver Estadísticas Rápidas (Tarjetas)',
            'view_recent_activity' => 'Ver Actividad Reciente',
        ]
    ],
    'dashboard_gerencial' => [
        'label' => 'Dashboard Gerencial',
        'permissions' => [
            'view' => 'Ver Dashboard Gerencial',
            'filter_agency' => 'Filtrar por Agencia',
            'view_financial_kpis' => 'Ver Indicadores Financieros (KPIs)',
            'export_charts' => 'Exportar Gráficos',
        ]
    ],

    // =================================================================================
    // 2. GESTIÓN DE PERSONAL (RRHH)
    // =================================================================================
    'colaboradores' => [
        'label' => 'Gestión de Colaboradores',
        'permissions' => [
            'view' => 'Ver Listado de Colaboradores',
            'search' => 'Buscar Colaboradores (Filtros)',
            'create' => 'Agregar Nuevo Colaborador (Botón Nuevo)',
            'edit' => 'Modificar Datos de Colaborador',
            'change_status' => 'Cambiar Estado (Activo/Inactivo)',
            'delete' => 'Eliminar Colaborador',
            'assign_user' => 'Asignar Usuario/Credenciales',
            'reset_password' => 'Restablecer Contraseña',
            'view_salary' => 'Ver Información Salarial',
        ]
    ],
    'planillas' => [
        'label' => 'Planillas y Comisiones',
        'permissions' => [
            'view' => 'Ver Módulo de Planillas',
            'generate' => 'Generar/Calcular Planilla',
            'approve' => 'Aprobar Pago de Planilla',
            'view_history' => 'Ver Histórico de Pagos',
            'config_comisiones' => 'Configurar Metas y Comisiones',
            'export' => 'Exportar Planillas (Excel/PDF)',
        ]
    ],

    // =================================================================================
    // 3. INFRAESTRUCTURA Y AGENCIAS
    // =================================================================================
    'agencias' => [
        'label' => 'Gestión de Agencias',
        'permissions' => [
            'view' => 'Ver Listado de Agencias',
            'search' => 'Buscar Agencias',
            'create' => 'Registrar Nueva Agencia',
            'edit' => 'Editar Información de Agencia',
            'delete' => 'Eliminar Agencia',
            'view_balance' => 'Ver Saldos y Bóvedas de Agencia',
            'view_staff' => 'Ver Personal Asignado',
        ]
    ],

    // =================================================================================
    // 4. GESTIÓN DE CLIENTES
    // =================================================================================
    'clientes' => [
        'label' => 'Directorio de Clientes',
        'permissions' => [
            'view' => 'Ver Listado de Clientes',
            'search' => 'Buscar Clientes (DNI, Nombre)',
            'create' => 'Registrar Nuevo Cliente',
            'view_details' => 'Ver Perfil Completo (Ficha)',
            'edit' => 'Modificar Datos Personales',
            'change_status' => 'Bloquear/Desbloquear Cliente',
            'delete' => 'Eliminar Cliente',
            'manage_business' => 'Agregar/Editar Negocios',
            'manage_documents' => 'Subir/Ver Documentos Digitales',
            'export' => 'Exportar Datos de Clientes',
        ]
    ],

    // =================================================================================
    // 5. CICLO DE CRÉDITOS (FLUJO COMPLETO)
    // =================================================================================
    'prestamos_analisis' => [
        'label' => 'Análisis de Solicitudes',
        'permissions' => [
            'view' => 'Ver Bandeja de Solicitudes',
            'create' => 'Ingresar Nueva Solicitud',
            'edit' => 'Editar Solicitudes en Análisis',
            'delete' => 'Anular Solicitud',
            'view_guarantees' => 'Gestionar Garantías de la Solicitud',
            'send_verification' => 'Enviar a Verificación de Campo',
            'print_forms' => 'Imprimir Formularios de Solicitud',
        ]
    ],
    'verificacion_campo' => [
        'label' => 'Verificación de Campo',
        'permissions' => [
            'view' => 'Ver Solicitudes para Verificar',
            'search' => 'Buscar/Filtrar Solicitudes',
            'view_details' => 'Ver Detalles para Verificación',
            'edit_data' => 'Corregir Datos durante Verificación',
            'verify' => 'Emitir Dictamen (Botones Autorizar/Rechazar)',
            // 'approve' => 'Autorizar (Pasa a Aprobación)', // Included in verify usually, but let's be explicit
            // 'reject' => 'Rechazar (Cancela Solicitud)',
        ]
    ],
    'prestamos_aprobacion' => [
        'label' => 'Comité / Aprobación Final',
        'permissions' => [
            'view' => 'Ver Bandeja de Aprobación',
            'final_approve' => 'Aprobar Crédito (Pasa a Operaciones)',
            'final_reject' => 'Rechazar Crédito',
            'override_policies' => 'Autorizar Excepciones (Monto/Tasa)',
        ]
    ],
    'desembolsos' => [
        'label' => 'Desembolsos',
        'permissions' => [
            'view' => 'Ver Créditos Listos para Desembolso',
            'assign_route' => 'Programar Ruta de Desembolso',
            'execute' => 'Registrar Desembolso (Confirmar Entrega)',
            'print_contract' => 'Imprimir Contrato y Pagaré',
            'revert' => 'Anular/Revertir Desembolso',
        ]
    ],
    'prestamos_gestion' => [
        'label' => 'Gestión de Cartera Activa',
        'permissions' => [
            'view' => 'Ver Listado de Préstamos Activos',
            'view_details' => 'Ver Estado de Cuenta del Préstamo',
            'edit_terms' => 'Modificar Términos (Restructuración)',
            'refinance' => 'Refinanciar Préstamo',
            'special_discount' => 'Aplicar Descuentos/Condonaciones',
        ]
    ],

    // =================================================================================
    // 6. OPERACIONES DIARIAS
    // =================================================================================
    'operaciones' => [
        'label' => 'Mesa de Control (Operaciones)',
        'permissions' => [
            'view' => 'Ver Dashboard de Operaciones',
            'manage_vault' => 'Gestionar Bóvedas (Entrada/Salida)',
            'approve_cash_requests' => 'Autorizar Solicitudes de Caja',
            'view_cash_flow' => 'Ver Flujo de Efectivo en Tiempo Real',
        ]
    ],
    'caja' => [
        'label' => 'Ventanilla y Caja',
        'permissions' => [
            'view' => 'Acceso al Módulo de Caja',
            'open_close' => 'Abrir y Cerrar Turno',
            'register_income' => 'Registrar Otros Ingresos',
            'register_expense' => 'Registrar Gastos Menores',
            'transfer' => 'Transferir a Bóveda/Banco',
            'view_history' => 'Ver Historial de Movimientos',
        ]
    ],
    'gastos_operativos' => [
        'label' => 'Gastos Operativos',
        'permissions' => [
            'view' => 'Ver Listado de Gastos',
            'create' => 'Registrar Nuevo Gasto',
            'approve' => 'Autorizar Gasto',
            'analyze' => 'Ver Análisis de Gastos',
            'delete' => 'Anular Gasto',
        ]
    ],

    // =================================================================================
    // 7. COBRANZA
    // =================================================================================
    'cobranza' => [
        'label' => 'Gestión de Cobranza',
        'permissions' => [
            'view_routes' => 'Ver Rutas de Cobro',
            'assign_clients' => 'Asignar Carteras/Rutas',
            'register_payment' => 'Registrar Abonos/Pagos',
            'print_receipt' => 'Imprimir Recibos',
            'view_delinquency' => 'Ver Reportes de Mora',
            'map_view' => 'Ver Mapa de Cobranza (GPS)',
        ]
    ],

    // =================================================================================
    // 8. TESORERÍA Y FINANZAS
    // =================================================================================
    'tesoreria' => [
        'label' => 'Tesorería Central y Bancos',
        'permissions' => [
            'view' => 'Ver Cuentas Bancarias',
            'add_bank' => 'Agregar Cuenta Bancaria',
            'register_transaction' => 'Registrar Depósitos/Retiros',
            'transfer_between_accounts' => 'Transferencias entre Cuentas',
            'reconciliation' => 'Conciliación Bancaria',
            'inject_capital' => 'Inyectar Capital a Agencias',
        ]
    ],
    'reversiones' => [
        'label' => 'Centro de Anulaciones y Reversiones',
        'permissions' => [
            'view' => 'Ver Módulo de Reversiones',
            'revert_payment' => 'Revertir Pago Erróneo',
            'revert_disbursement' => 'Anular Desembolso',
            'revert_expense' => 'Revertir Gasto',
        ]
    ],
    'auditoria' => [
        'label' => 'Auditoría y Logs',
        'permissions' => [
            'view' => 'Ver Logs de Sistema',
            'search' => 'Buscar en Logs',
            'view_sensitive' => 'Ver Datos Sensibles en Logs',
            'export' => 'Exportar Auditoría',
        ]
    ],

    // =================================================================================
    // 9. CONFIGURACIÓN Y SEGURIDAD
    // =================================================================================
    'reportes' => [
        'label' => 'Reportes e Inteligencia de Negocios',
        'permissions' => [
            'view_consolidado' => 'Reporte Consolidado (Vista Global)',
            'view_agencia' => 'Reporte por Agencia (Vista Operativa)',
            'view_cartera' => 'Reporte de Cartera y Mora',
            'view_financieros' => 'Estados Financieros y Resultados',
            'view_colocacion' => 'Reporte de Colocación de Créditos',
            'export_excel' => 'Permitir Exportar a Excel',
            'export_pdf' => 'Permitir Exportar a PDF',
        ]
    ],
    'configuracion' => [
        'label' => 'Configuración del Sistema',
        'permissions' => [
            'view' => 'Ver Configuración General',
            'edit_company' => 'Editar Datos de la Empresa',
            'edit_params' => 'Editar Parámetros de Crédito (Tasas/Mora)',
            'system_maintenance' => 'Mantenimiento del Sistema (Backups)',
        ]
    ],
    'seguridad' => [
        'label' => 'Seguridad (Roles y Permisos)',
        'permissions' => [
            'view' => 'Ver Roles Existentes',
            'create_role' => 'Crear Nuevos Roles',
            'edit_role' => 'Modificar Roles y Permisos',
            'delete_role' => 'Eliminar Roles',
            'manage_puestos' => 'Gestionar Catálogo de Puestos',
        ]
    ],

    // =================================================================================
    // 10. EXCEPCIONES Y ALCANCE DE DATOS
    // =================================================================================
    'special_scopes' => [
        'label' => 'Excepciones y Alcances Especiales',
        'description' => 'Permite extender la visibilidad de datos más allá del usuario asignado (Agencia/Global).',
        'permissions' => [
            'clientes_view_agency' => 'Clientes: Ver Todos de mi Agencia',
            'clientes_view_global' => 'Clientes: Ver Todos (Global)',
            'prestamos_view_agency' => 'Préstamos: Ver Todos de mi Agencia',
            'prestamos_view_global' => 'Préstamos: Ver Todos (Global)',
            'caja_view_all' => 'Caja: Ver Todas las Cajas (Supervisor)',
            'dashboard_view_global' => 'Dashboard: Ver Datos Globales',
        ]
    ],
    // =================================================================================
    // 11. DOCUMENTACIÓN Y LEGAL
    // =================================================================================
    'documentacion' => [
        'label' => 'Centro de Documentación Legal',
        'permissions' => [
            'view' => 'Ver Módulo de Documentación',
            'reprint' => 'Reimprimir Documentos Históricos',
            'edit_templates' => 'Editar Plantillas de Contratos',
            'config_logo' => 'Cambiar Logo Institucional',
        ]
    ],
];
