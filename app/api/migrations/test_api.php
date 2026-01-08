<?php
session_start();
$_SESSION['id_usuario'] = 1;
$_SESSION['rol_nombre'] = 'Admin';
$_GET['fecha'] = date('Y-m-d');

include __DIR__ . '/../../api/cobranza/historial_pagos.php';
