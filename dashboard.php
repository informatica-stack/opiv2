<?php 
// dashboard.php - Panel de Control y Analítica 360° Presupuestario y de Administración (V1.0)
require_once __DIR__ . '/dashboard_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Dashboard Presupuestario & Administración";
    include __DIR__ . '/head.php'; 
    ?>
    <!-- Chart.js 4.4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .kpi-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 12px;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        }
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }
        .badge-estado-pill {
            font-size: 11px;
            padding: 4px 10px;
            font-weight: 600;
            border-radius: 20px;
        }
        .table-custom-dashboard td {
            padding: 12px 14px;
            vertical-align: middle;
            font-size: 13px;
        }
        .table-custom-dashboard th {
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 12px 14px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4">

        <!-- CABECERA PRINCIPAL DEL DASHBOARD -->
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill small fw-bold">
                        <i class="bi bi-pie-chart-fill me-1"></i> Módulo de Administración & Control
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 rounded-pill small">
                        Año <?= htmlspecialchars($f_anio === 'TODOS' ? 'Histórico Completo' : $f_anio) ?>
                    </span>
                </div>
                <h1 class="h3 fw-black text-dark mb-0 d-flex align-items-center gap-2">
                    Dashboard de Control Presupuestario & OPIs
                </h1>
                <p class="text-muted small mb-0 mt-0.5">
                    Monitoreo financiero en tiempo real, análisis de gasto y trazabilidad 360° de requerimientos de compra municipal.
                </p>
            </div>
            
            <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-lg-auto justify-content-start justify-content-lg-end">
                <a href="control_presupuestario.php" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 shadow-sm d-flex align-items-center gap-1.5 rounded-3">
                    <i class="bi bi-wallet2"></i>
                    Bandeja VB Presupuestario
                    <?php if ($kpi_urg_total > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-1"><?= $kpi_urg_total ?></span>
                    <?php endif; ?>
                </a>
                <a href="nueva_solicitud.php" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm d-flex align-items-center gap-1.5 rounded-3">
                    <i class="bi bi-plus-circle"></i>
                    Nueva Solicitud
                </a>
                <button type="button" onclick="window.print()" class="btn btn-light border btn-sm fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-1.5 rounded-3 text-secondary">
                    <i class="bi bi-printer"></i>
                    Imprimir Vista
                </button>
            </div>
        </div>

        <!-- ALERTA DE ACCIONES INMEDIATAS EN PRESUPUESTO (SI HAY PENDIENTES) -->
        <?php if ($kpi_urg_total > 0): ?>
            <div class="alert alert-warning border-warning-subtle shadow-sm rounded-3 p-3 mb-4 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <?= $kpi_urg_total ?> Requerimiento(s) en espera de acción presupuestaria
                        </h6>
                        <div class="text-muted small mt-0.5">
                            <strong><?= $kpi_urg_inicial ?></strong> en Visación Inicial de Saldos &bull; 
                            <strong><?= $kpi_urg_final ?></strong> en Visación Final / Tramitación CDP con Finanzas.
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="control_presupuestario.php?view=pendientes_inicial" class="btn btn-warning btn-sm fw-bold px-3 py-1.5 shadow-sm">
                        Visar Pendientes <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- BARRA DE FILTROS GLOBALES -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body p-3 bg-white">
                <form method="GET" action="dashboard.php" class="row g-2.5 align-items-end">
                    <div class="col-6 col-md-2 col-lg-2">
                        <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size: 10px;">
                            <i class="bi bi-calendar-event me-1"></i> Año
                        </label>
                        <select name="f_anio" class="form-select form-select-sm bg-light border-secondary-subtle fw-semibold">
                            <option value="TODOS" <?= $f_anio === 'TODOS' ? 'selected' : '' ?>>Todos los Años</option>
                            <?php foreach($anios_disponibles as $a): ?>
                                <option value="<?= $a ?>" <?= $f_anio == $a ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size: 10px;">
                            <i class="bi bi-building me-1"></i> Centro de Costos
                        </label>
                        <select name="f_cc" class="form-select form-select-sm bg-light border-secondary-subtle">
                            <option value="">Todos los Centros de Costos</option>
                            <?php foreach($centros_costo as $cc): ?>
                                <option value="<?= $cc['id'] ?>" <?= $f_cc == $cc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(($cc['codigo_cuenta'] ?? '') . ' - ' . $cc['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size: 10px;">
                            <i class="bi bi-tag me-1"></i> Tipo de Compra
                        </label>
                        <select name="f_tipo" class="form-select form-select-sm bg-light border-secondary-subtle">
                            <option value="">Todos los Tipos</option>
                            <?php foreach($tipos_compra as $tc): ?>
                                <option value="<?= $tc['id'] ?>" <?= $f_tipo == $tc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tc['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-2 col-lg-2">
                        <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size: 10px;">
                            <i class="bi bi-calendar3 me-1"></i> Desde
                        </label>
                        <input type="date" name="f_desde" value="<?= htmlspecialchars($f_desde) ?>" class="form-control form-control-sm bg-light border-secondary-subtle">
                    </div>

                    <div class="col-6 col-md-2 col-lg-2">
                        <label class="form-label fw-bold text-secondary text-uppercase mb-1" style="font-size: 10px;">
                            <i class="bi bi-calendar3 me-1"></i> Hasta
                        </label>
                        <input type="date" name="f_hasta" value="<?= htmlspecialchars($f_hasta) ?>" class="form-control form-control-sm bg-light border-secondary-subtle">
                    </div>

                    <div class="col-12 col-md-12 col-lg-1 d-flex gap-1.5 justify-content-end mt-2 mt-lg-0">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm" title="Aplicar Filtros">
                            <i class="bi bi-funnel-fill"></i>
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm fw-semibold" title="Restablecer Filtros">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- TARJETAS PRINCIPALES DE KPIS -->
        <div class="row g-3 mb-4">
            
            <!-- KPI 1: MONTO TOTAL IMPUTADO / COMPROMETIDO -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-card border-0 shadow-sm bg-white p-3.5 h-100 position-relative overflow-hidden" style="border-left: 4px solid #0d6efd !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Monto Total Imputado</span>
                            <h3 class="fw-black text-dark mb-0 mt-1 font-monospace fs-4">
                                $ <?= number_format($kpi_monto_comprometido, 0, ',', '.') ?>
                            </h3>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle p-2.5 d-flex align-items-center justify-content-center">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small" style="font-size: 11px;">
                        <span>Neto: <strong>$ <?= number_format($kpi_monto_neto_est, 0, ',', '.') ?></strong></span>
                        <span>IVA 19%: <strong>$ <?= number_format($kpi_monto_iva_est, 0, ',', '.') ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- KPI 2: TOTAL DE TRÁMITES Y ESTADO -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-card border-0 shadow-sm bg-white p-3.5 h-100 position-relative overflow-hidden" style="border-left: 4px solid #198754 !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">OPIs Registradas</span>
                            <h3 class="fw-black text-dark mb-0 mt-1 font-monospace fs-4">
                                <?= number_format($kpi_total_expedientes, 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">trámites</span>
                            </h3>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle p-2.5 d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-check fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top small" style="font-size: 11px;">
                        <span class="text-success fw-bold"><i class="bi bi-check-circle me-0.5"></i> <?= $kpi_count_finalizados ?> Finalizados</span>
                        <span class="text-primary fw-bold"><i class="bi bi-arrow-repeat me-0.5"></i> <?= $kpi_count_en_curso ?> En Curso</span>
                    </div>
                </div>
            </div>

            <!-- KPI 3: MONTO FINALIZADO (ORDEN DE COMPRA ACEPTADA) -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-card border-0 shadow-sm bg-white p-3.5 h-100 position-relative overflow-hidden" style="border-left: 4px solid #20c997 !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Monto Finalizado (Con OC)</span>
                            <h3 class="fw-black text-success mb-0 mt-1 font-monospace fs-4">
                                $ <?= number_format($kpi_monto_finalizado, 0, ',', '.') ?>
                            </h3>
                        </div>
                        <div class="bg-teal-subtle text-teal rounded-circle p-2.5 d-flex align-items-center justify-content-center" style="background-color: #e6fcf5; color: #0ca678;">
                            <i class="bi bi-cart-check-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small" style="font-size: 11px;">
                        <span>Efectividad: <strong><?= $kpi_total_expedientes > 0 ? round(($kpi_count_finalizados / $kpi_total_expedientes) * 100, 1) : 0 ?>%</strong> de trámites</span>
                        <span><?= $kpi_count_finalizados ?> OCs emitidas</span>
                    </div>
                </div>
            </div>

            <!-- KPI 4: MONTO EN TRAMITACIÓN ACTIVA -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-card border-0 shadow-sm bg-white p-3.5 h-100 position-relative overflow-hidden" style="border-left: 4px solid #6f42c1 !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Monto en Tramitación</span>
                            <h3 class="fw-black text-purple mb-0 mt-1 font-monospace fs-4" style="color: #6f42c1;">
                                $ <?= number_format($kpi_monto_en_curso, 0, ',', '.') ?>
                            </h3>
                        </div>
                        <div class="bg-purple-subtle text-purple rounded-circle p-2.5 d-flex align-items-center justify-content-center" style="background-color: #f3f0ff; color: #7048e8;">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small" style="font-size: 11px;">
                        <span>En Adquisiciones / Firmas</span>
                        <span class="text-danger fw-bold"><?= $kpi_count_rechazados ?> Rechazados</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- FILA DE GRÁFICOS ANALÍTICOS (CHART.JS) -->
        <div class="row g-4 mb-4">
            
            <!-- GRÁFICO 1: EMBUDO / PIPELINE DE FASES OPI -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-diagram-3-fill text-primary"></i>
                            Distribución por Fases del Flujo OPI
                        </h6>
                        <span class="badge bg-light text-secondary border small">Pipeline Global</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="chart-container">
                            <canvas id="chartPipeline"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRÁFICO 2: EJECUCIÓN POR CENTRO DE COSTOS / DIRECCIÓN -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart-fill text-success"></i>
                            Gasto Imputado por Centro de Costos
                        </h6>
                        <span class="badge bg-light text-secondary border small">Top Direcciones</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="chart-container">
                            <canvas id="chartCentrosCosto"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRÁFICO 3: DISTRIBUCIÓN POR TIPO DE COMPRA -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-pie-chart text-info"></i>
                            Distribución por Modalidad de Compra
                        </h6>
                        <span class="badge bg-light text-secondary border small">% del Total</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="chart-container">
                            <canvas id="chartTiposCompra"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRÁFICO 4: EVOLUCIÓN MENSUAL DEL GASTO E INGRESOS -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-graph-up-arrow text-primary"></i>
                            Evolución Mensual del Gasto Imputado
                        </h6>
                        <span class="badge bg-light text-secondary border small">Año <?= htmlspecialchars($anio_timeline) ?></span>
                    </div>
                    <div class="card-body p-3">
                        <div class="chart-container">
                            <canvas id="chartEvolucionMensual"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLA MAESTRA DE CONTROL Y AUDITORÍA DE OPIS -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-table text-primary"></i>
                            Registro y Auditoría Integral de OPIs
                        </h5>
                        <p class="text-muted small mb-0 mt-0.5">Listado detallado de expedientes con trazabilidad y estado de avance.</p>
                    </div>

                    <!-- Píldoras de Filtro Rápido de Estado -->
                    <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Filtro Rápido Macro">
                        <a href="dashboard.php?<?= http_build_query(array_merge($_GET, ['f_macro' => 'TODOS'])) ?>" class="btn <?= ($f_macro === 'TODOS' || empty($f_macro)) ? 'btn-primary' : 'btn-outline-secondary' ?> fw-semibold">
                            Todas (<?= count($expedientes_lista) ?>)
                        </a>
                        <a href="dashboard.php?<?= http_build_query(array_merge($_GET, ['f_macro' => 'PENDIENTES_PRESUPUESTO'])) ?>" class="btn <?= $f_macro === 'PENDIENTES_PRESUPUESTO' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?> fw-semibold">
                            Pendientes Presupuesto
                        </a>
                        <a href="dashboard.php?<?= http_build_query(array_merge($_GET, ['f_macro' => 'EN_CURSO'])) ?>" class="btn <?= $f_macro === 'EN_CURSO' ? 'btn-primary' : 'btn-outline-secondary' ?> fw-semibold">
                            En Curso
                        </a>
                        <a href="dashboard.php?<?= http_build_query(array_merge($_GET, ['f_macro' => 'FINALIZADOS'])) ?>" class="btn <?= $f_macro === 'FINALIZADOS' ? 'btn-success' : 'btn-outline-secondary' ?> fw-semibold">
                            Finalizadas
                        </a>
                        <a href="dashboard.php?<?= http_build_query(array_merge($_GET, ['f_macro' => 'RECHAZADOS_DEVUELTOS'])) ?>" class="btn <?= $f_macro === 'RECHAZADOS_DEVUELTOS' ? 'btn-danger' : 'btn-outline-secondary' ?> fw-semibold">
                            Devueltas / Rechazadas
                        </a>
                    </div>
                </div>

                <!-- Buscador de Texto Libre -->
                <form method="GET" action="dashboard.php" class="mt-3">
                    <input type="hidden" name="f_anio" value="<?= htmlspecialchars($f_anio) ?>">
                    <input type="hidden" name="f_cc" value="<?= htmlspecialchars($f_cc) ?>">
                    <input type="hidden" name="f_tipo" value="<?= htmlspecialchars($f_tipo) ?>">
                    <input type="hidden" name="f_macro" value="<?= htmlspecialchars($f_macro) ?>">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-secondary-subtle text-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" name="f_q" class="form-control bg-light border-secondary-subtle" placeholder="Buscar por Código REQ, Título, Proveedor, RUT o Usuario Creador..." value="<?= htmlspecialchars($f_q) ?>">
                        <button type="submit" class="btn btn-primary fw-bold">Buscar</button>
                        <?php if ($f_q): ?>
                            <a href="dashboard.php?<?= http_build_query(array_merge($_GET, ['f_q' => ''])) ?>" class="btn btn-outline-secondary">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom-dashboard">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th class="text-center" style="width: 130px;">Código OPI</th>
                                <th>Fecha / Solicitante</th>
                                <th>Centro de Costos</th>
                                <th>Título & Modalidad</th>
                                <th>Proveedor</th>
                                <th class="text-end" style="width: 140px;">Monto OPI</th>
                                <th class="text-center" style="width: 180px;">Estado de Flujo</th>
                                <th class="text-center" style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if (empty($expedientes_lista)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        <span class="fw-semibold">No se encontraron expedientes con los filtros seleccionados.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expedientes_lista as $exp): ?>
                                    <?php 
                                    $dias = (int)($exp['dias_en_estado'] ?? 0);
                                    $dias_badge_class = ($dias > 7) ? 'bg-danger-subtle text-danger' : (($dias > 3) ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-secondary');
                                    $es_pendiente_presupuesto = in_array($exp['estado_actual'], ['EN_VALIDACION_PRESUPUESTARIA', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS']);
                                    ?>
                                    <tr>
                                        <!-- Código OPI -->
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1 fs-7">
                                                <?= htmlspecialchars($exp['codigo_interno']) ?>
                                            </span>
                                        </td>

                                        <!-- Fecha / Solicitante -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($exp['created_at'])) ?></div>
                                            <div class="text-muted small" style="font-size: 11px;">
                                                <i class="bi bi-person me-0.5"></i> <?= htmlspecialchars($exp['usuario_nombre'] ?? 'N/A') ?>
                                            </div>
                                        </td>

                                        <!-- Centro de Costos -->
                                        <td>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($exp['cc_codigo'] ?? '') ?> - <?= htmlspecialchars($exp['cc_nombre'] ?? 'Sin Centro') ?></div>
                                            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($exp['unidad_nombre'] ?? '') ?></div>
                                        </td>

                                        <!-- Título & Modalidad -->
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($exp['titulo_compra']) ?>">
                                                <?= htmlspecialchars($exp['titulo_compra']) ?>
                                            </div>
                                            <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                                                <?= htmlspecialchars($exp['tipo_compra_nombre']) ?>
                                            </span>
                                        </td>

                                        <!-- Proveedor -->
                                        <td>
                                            <?php if ($exp['prov_nombre']): ?>
                                                <div class="fw-semibold text-dark small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($exp['prov_nombre']) ?>">
                                                    <?= htmlspecialchars($exp['prov_nombre']) ?>
                                                </div>
                                                <div class="text-secondary font-monospace" style="font-size: 10px;"><?= htmlspecialchars($exp['prov_rut'] ?? '') ?></div>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic small">En Cotización / Por Asignar</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Monto OPI -->
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            $ <?= number_format($exp['monto_estimado'], 0, ',', '.') ?>
                                            <div class="text-muted" style="font-size: 9px; font-weight: normal;">
                                                <?= ($exp['tipo_impuesto'] === 'IVA_INCLUIDO') ? 'IVA Inc.' : 'Neto+IVA' ?>
                                            </div>
                                        </td>

                                        <!-- Estado de Flujo & Días -->
                                        <td class="text-center">
                                            <span class="badge badge-estado-pill <?= color_estado($exp['estado_actual']) ?> text-truncate d-inline-block" style="max-width: 170px;">
                                                <?= htmlspecialchars($exp['estado_nombre'] ?? $exp['estado_actual']) ?>
                                            </span>
                                            <div class="mt-1">
                                                <span class="badge <?= $dias_badge_class ?> rounded-pill" style="font-size: 9px;">
                                                    <i class="bi bi-clock-history me-0.5"></i> <?= $dias ?> d en etapa
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Acciones Rápidas -->
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <!-- Ver OPI PDF -->
                                                <a href="imprimir_opi.php?id=<?= $exp['id'] ?>" target="_blank" class="btn btn-outline-danger btn-xs py-1 px-2 shadow-sm rounded-2" title="Ver / Descargar OPI Oficial en PDF">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>

                                                <!-- Trazabilidad Historial -->
                                                <button type="button" onclick="verTrazabilidad(<?= $exp['id'] ?>)" class="btn btn-outline-primary btn-xs py-1 px-2 shadow-sm rounded-2" title="Trazabilidad y Auditoría">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>

                                                <!-- Si está pendiente de presupuesto, botón para visar -->
                                                <?php if ($es_pendiente_presupuesto): ?>
                                                    <a href="control_presupuestario.php?view=revisar&id=<?= $exp['id'] ?>" class="btn btn-warning btn-xs py-1 px-2 fw-bold shadow-sm rounded-2 text-dark" title="Revisar y Visar en Presupuesto">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-2.5 px-3 border-top text-muted small d-flex justify-content-between align-items-center">
                <span>Mostrando <?= count($expedientes_lista) ?> registro(s)</span>
                <span>Actualizado automáticamente &bull; Presupuesto y Administración Municipal</span>
            </div>
        </div>

    </div>

    <!-- MODAL UNIVERSAL DE TRAZABILIDAD -->
    <?php include __DIR__ . '/modal_trazabilidad.php'; ?>

    <!-- INICIALIZACIÓN DE GRÁFICOS (CHART.JS) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currencyFormatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });

            // 1. Gráfico Pipeline / Fases OPI
            const ctxPipeline = document.getElementById('chartPipeline').getContext('2d');
            const pipelineLabels = <?= json_encode(array_keys($pipeline_fases)) ?>;
            const pipelineCounts = <?= json_encode(array_column($pipeline_fases, 'count')) ?>;
            const pipelineColors = <?= json_encode(array_column($pipeline_fases, 'color')) ?>;

            new Chart(ctxPipeline, {
                type: 'doughnut',
                data: {
                    labels: pipelineLabels,
                    datasets: [{
                        data: pipelineCounts,
                        backgroundColor: pipelineColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw || 0;
                                    return ` ${context.label}: ${val} trámites`;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });

            // 2. Gráfico Centros de Costo (Gasto Imputado)
            const ctxCC = document.getElementById('chartCentrosCosto').getContext('2d');
            const ccLabels = <?= json_encode(array_column($cc_gasto_list, 'codigo')) ?>;
            const ccMontos = <?= json_encode(array_column($cc_gasto_list, 'total_monto')) ?>;
            const ccFullNames = <?= json_encode(array_column($cc_gasto_list, 'nombre')) ?>;

            new Chart(ctxCC, {
                type: 'bar',
                data: {
                    labels: ccLabels,
                    datasets: [{
                        label: 'Monto Imputado',
                        data: ccMontos,
                        backgroundColor: '#198754',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(items) {
                                    const idx = items[0].dataIndex;
                                    return `${ccLabels[idx]} - ${ccFullNames[idx]}`;
                                },
                                label: function(context) {
                                    return ` Total: ${currencyFormatter.format(context.raw)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                callback: function(value) {
                                    return '$' + (value / 1000000).toFixed(1) + 'M';
                                },
                                font: { size: 10 }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        y: {
                            ticks: { font: { size: 11, weight: 'bold' } },
                            grid: { display: false }
                        }
                    }
                }
            });

            // 3. Gráfico Tipos de Compra
            const ctxTipos = document.getElementById('chartTiposCompra').getContext('2d');
            const tiposLabels = <?= json_encode(array_column($tipos_compra_gasto, 'nombre')) ?>;
            const tiposMontos = <?= json_encode(array_column($tipos_compra_gasto, 'monto')) ?>;
            const palette = ['#0d6efd', '#20c997', '#fd7e14', '#6f42c1', '#17a2b8', '#ffc107', '#d63384'];

            new Chart(ctxTipos, {
                type: 'polarArea',
                data: {
                    labels: tiposLabels,
                    datasets: [{
                        data: tiposMontos,
                        backgroundColor: palette.slice(0, tiposLabels.length).map(c => c + 'cc'),
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, font: { size: 10 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.label}: ${currencyFormatter.format(context.raw)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        r: { ticks: { display: false }, grid: { color: '#e2e8f0' } }
                    }
                }
            });

            // 4. Gráfico Evolución Mensual (Gasto e Ingresos)
            const ctxEvolucion = document.getElementById('chartEvolucionMensual').getContext('2d');
            const mesesLabels = <?= json_encode($meses_labels) ?>;
            const mesesMontos = <?= json_encode($meses_montos) ?>;
            const mesesCantidades = <?= json_encode($meses_cantidades) ?>;

            new Chart(ctxEvolucion, {
                type: 'bar',
                data: {
                    labels: mesesLabels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'N° de OPIs',
                            data: mesesCantidades,
                            borderColor: '#fd7e14',
                            backgroundColor: '#fd7e14',
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            yAxisID: 'y1'
                        },
                        {
                            type: 'bar',
                            label: 'Monto Imputado ($)',
                            data: mesesMontos,
                            backgroundColor: '#0d6efd',
                            borderRadius: 4,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.dataset.type === 'line') {
                                        return ` OPIs creadas: ${context.raw}`;
                                    }
                                    return ` Monto: ${currencyFormatter.format(context.raw)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                callback: function(value) {
                                    return '$' + (value / 1000000).toFixed(0) + 'M';
                                },
                                font: { size: 10 }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { precision: 0, font: { size: 10 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        });
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
