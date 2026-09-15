<?php
// cuentas_centros_costo.php - Gestión Integral de Cuentas, Centros de Costo y Áreas de Gestión (SaaS Minimalist)
require_once __DIR__ . '/cuentas_centros_costo_controller.php';
$titulo_pagina = "Cuentas y Centros de Costo";
include __DIR__ . '/head.php';
?>
<body class="bg-body">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="saas-container">

        <!-- ============================================================== -->
        <!-- CABECERA PRINCIPAL DEL MÓDULO                                   -->
        <!-- ============================================================== -->
        <div class="module-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="module-title">
                    <i class="bi bi-wallet2 text-primary"></i>
                    Cuentas y Centros de Costo
                </div>
                <div class="module-subtitle">
                    Administración integral del catálogo presupuestario, cuentas complementarias, áreas de gestión y centros de costo municipales.
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($tab === 'detalle_centro'): ?>
                    <a href="cuentas_centros_costo.php?tab=centros" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Volver a Centros de Costo
                    </a>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAsignarCuenta">
                        <i class="bi bi-plus-circle me-1"></i> Asignar Cuenta a este Centro
                    </button>
                <?php elseif ($tab === 'cuentas'): ?>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="abrirModalCuenta()">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Cuenta Maestra
                    </button>
                <?php elseif ($tab === 'areas'): ?>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="abrirModalArea()">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Área de Gestión
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="abrirModalCC()">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Centro de Costo
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- ALERTAS DE FEEDBACK                                            -->
        <!-- ============================================================== -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-<?= $tipo_mensaje === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill' ?> fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- TARJETAS DE MÉTRICAS KPI                                       -->
        <!-- ============================================================== -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card">
                <div class="kpi-icon primary"><i class="bi bi-building"></i></div>
                <div>
                    <div class="kpi-label">Centros de Costo Activos</div>
                    <div class="kpi-value tabular-nums"><?= number_format($kpi_centros) ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon info"><i class="bi bi-journal-bookmark-fill"></i></div>
                <div>
                    <div class="kpi-label">Cuentas Presupuestarias</div>
                    <div class="kpi-value tabular-nums"><?= number_format($kpi_cuentas_presup) ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon warning"><i class="bi bi-bookmark-star-fill"></i></div>
                <div>
                    <div class="kpi-label">Cuentas Complementarias</div>
                    <div class="kpi-value tabular-nums"><?= number_format($kpi_cuentas_complem) ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon success"><i class="bi bi-diagram-3-fill"></i></div>
                <div>
                    <div class="kpi-label">Áreas de Gestión</div>
                    <div class="kpi-value tabular-nums"><?= number_format($kpi_areas) ?></div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- PESTAÑAS DE NAVEGACIÓN SAAS                                    -->
        <!-- ============================================================== -->
        <?php if ($tab !== 'detalle_centro'): ?>
            <div class="d-flex align-items-center gap-2 border-bottom mb-4 pb-1">
                <a href="cuentas_centros_costo.php?tab=centros" class="btn btn-sm <?= $tab === 'centros' ? 'btn-primary shadow-sm' : 'btn-ghost text-secondary' ?> rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5">
                    <i class="bi bi-building"></i> Centros de Costo
                    <span class="badge <?= $tab === 'centros' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' ?> rounded-pill ms-1"><?= count($centros) ?></span>
                </a>
                <a href="cuentas_centros_costo.php?tab=cuentas" class="btn btn-sm <?= $tab === 'cuentas' ? 'btn-primary shadow-sm' : 'btn-ghost text-secondary' ?> rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5">
                    <i class="bi bi-list-columns-reverse"></i> Catálogo de Cuentas
                    <span class="badge <?= $tab === 'cuentas' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' ?> rounded-pill ms-1"><?= count($cuentas) ?></span>
                </a>
                <a href="cuentas_centros_costo.php?tab=areas" class="btn btn-sm <?= $tab === 'areas' ? 'btn-primary shadow-sm' : 'btn-ghost text-secondary' ?> rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5">
                    <i class="bi bi-diagram-3"></i> Áreas de Gestión
                    <span class="badge <?= $tab === 'areas' ? 'bg-white text-primary' : 'bg-secondary-subtle text-secondary' ?> rounded-pill ms-1"><?= count($areas) ?></span>
                </a>
            </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- VISTA 1: LISTADO DE CENTROS DE COSTO                           -->
        <!-- ============================================================== -->
        <?php if ($tab === 'centros'): ?>
            <div class="panel-card">
                <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="panel-title d-flex align-items-center gap-2">
                        <i class="bi bi-building text-primary"></i>
                        <span>Centros de Costo Municipales</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroCentros" class="form-control border-start-0" placeholder="Buscar por código o nombre...">
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table-saas align-middle" id="tablaCentros">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Código</th>
                                <th>Nombre del Centro de Costo</th>
                                <th style="width: 120px;" class="text-center">Año Fiscal</th>
                                <th style="width: 170px;" class="text-center">Cuentas Asignadas</th>
                                <th style="width: 130px;" class="text-center">Items OPI</th>
                                <th style="width: 100px;" class="text-center">Estado</th>
                                <th style="width: 180px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($centros)): ?>
                                <tr>
                                    <td colspan="7" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-2 text-slate-300 d-block mb-2"></i>
                                        No se han registrado Centros de Costo en el sistema.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($centros as $c): ?>
                                    <tr class="item-fila <?= $c['activo'] ? '' : 'opacity-75 bg-light' ?>">
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary border fw-bold px-2 py-1 tabular-nums">
                                                <?= htmlspecialchars($c['codigo_cuenta']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark item-nombre"><?= htmlspecialchars($c['nombre']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1 tabular-nums">
                                                <i class="bi bi-calendar3 me-1 text-muted"></i><?= htmlspecialchars($c['anio_fiscal']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="cuentas_centros_costo.php?tab=detalle_centro&id=<?= $c['id'] ?>" class="badge <?= $c['total_cuentas_asig'] > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' ?> px-2.5 py-1.5 text-decoration-none fw-bold" title="Ver matriz presupuestaria">
                                                <i class="bi bi-link-45deg me-0.5"></i> <?= $c['total_cuentas_asig'] ?> Cuentas
                                            </a>
                                        </td>
                                        <td class="text-center tabular-nums text-muted">
                                            <span class="badge bg-slate-100 text-secondary border px-2 py-1">
                                                <?= number_format($c['total_items_compra']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($c['activo']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="cuentas_centros_costo.php?tab=detalle_centro&id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm text-primary" title="Gestionar Cuentas del Centro">
                                                    <i class="bi bi-sliders"></i>
                                                </a>
                                                <button class="btn btn-ghost btn-sm text-secondary" title="Editar Centro de Costo" onclick="editarCC(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Alternar estado activo/inactivo para este Centro de Costo?');">
                                                    <input type="hidden" name="accion" value="toggle_cc">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm <?= $c['activo'] ? 'text-warning' : 'text-success' ?>" title="<?= $c['activo'] ? 'Desactivar' : 'Activar' ?>">
                                                        <i class="bi bi-<?= $c['activo'] ? 'toggle-on' : 'toggle-off' ?> fs-6"></i>
                                                    </button>
                                                </form>
                                                <?php if ($c['total_cuentas_asig'] == 0 && $c['total_items_compra'] == 0): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este Centro de Costo?');">
                                                        <input type="hidden" name="accion" value="eliminar_cc">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Eliminar Centro">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
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

        <!-- ============================================================== -->
        <!-- VISTA 1.B: DETALLE Y MATRIZ DE ASIGNACIÓN DE UN CENTRO         -->
        <!-- ============================================================== -->
        <?php elseif ($tab === 'detalle_centro' && $centro_actual): ?>
            
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary rounded-3 p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-building fs-3"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary border fw-bold px-2 py-1 tabular-nums">
                                        <?= htmlspecialchars($centro_actual['codigo_cuenta']) ?>
                                    </span>
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($centro_actual['nombre']) ?></h5>
                                    <?php if ($centro_actual['activo']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Inactivo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar3 me-1"></i> Año Fiscal: <b><?= htmlspecialchars($centro_actual['anio_fiscal']) ?></b> &bull; Total Asignaciones: <b><?= count($asignaciones_centro) ?></b> cuentas habilitadas
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAsignarCuenta">
                                <i class="bi bi-plus-lg me-1"></i> Vincular Cuenta al Centro
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="panel-title d-flex align-items-center gap-2">
                        <i class="bi bi-link-45deg text-primary"></i>
                        <span>Cuentas Presupuestarias y Áreas de Gestión Habilitadas</span>
                    </div>
                    <div class="text-muted small">
                        Estas son las cuentas disponibles para que los solicitantes creen requerimientos de compra en este Centro.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table-saas align-middle">
                        <thead>
                            <tr>
                                <th style="width: 170px;">Código Cuenta</th>
                                <th>Nombre de la Cuenta</th>
                                <th style="width: 170px;">Tipo de Cuenta</th>
                                <th style="width: 220px;">Área de Gestión (AG)</th>
                                <th style="width: 130px;" class="text-center">Items OPI</th>
                                <th style="width: 100px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($asignaciones_centro)): ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <i class="bi bi-folder2-open fs-2 text-slate-300 d-block mb-2"></i>
                                        Este Centro de Costo aún no tiene cuentas asignadas.<br>
                                        <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalAsignarCuenta">
                                            <i class="bi bi-plus-lg me-1"></i> Asignar la primera cuenta
                                        </button>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($asignaciones_centro as $asig): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-secondary border font-monospace px-2.5 py-1.5 fs-7 fw-bold">
                                                <?= htmlspecialchars($asig['cuenta_codigo']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($asig['cuenta_nombre']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($asig['cuenta_tipo'] === 'COMPLEMENTARIA'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-bookmark-star-fill me-1"></i> Complementaria
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                                    <i class="bi bi-journal-bookmark-fill me-1"></i> Presupuestaria
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark border px-2 py-1">
                                                <b><?= htmlspecialchars($asig['ag_codigo']) ?></b> - <?= htmlspecialchars($asig['ag_nombre']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center tabular-nums text-muted">
                                            <span class="badge bg-slate-100 text-secondary border px-2 py-1">
                                                <?= number_format($asig['items_usados']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($asig['items_usados'] == 0): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Desvincular esta cuenta de este Centro de Costo?');">
                                                    <input type="hidden" name="accion" value="eliminar_asignacion">
                                                    <input type="hidden" name="asignacion_id" value="<?= $asig['asignacion_id'] ?>">
                                                    <input type="hidden" name="centro_costo_id" value="<?= $centro_actual['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Eliminar asignación">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border" title="No se puede eliminar: Cuenta con solicitudes de compra registradas">
                                                    <i class="bi bi-lock-fill"></i> En uso
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- ============================================================== -->
        <!-- VISTA 2: CATÁLOGO MAESTRO DE CUENTAS                           -->
        <!-- ============================================================== -->
        <?php elseif ($tab === 'cuentas'): ?>
            <div class="panel-card">
                <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="panel-title d-flex align-items-center gap-2">
                        <i class="bi bi-list-columns-reverse text-primary"></i>
                        <span>Catálogo Global de Cuentas</span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" onclick="filtrarTipoCuenta('TODAS', this)">Todas</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filtrarTipoCuenta('PRESUPUESTARIA', this)">Presupuestarias</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filtrarTipoCuenta('COMPLEMENTARIA', this)">Complementarias</button>
                        </div>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroCuentas" class="form-control border-start-0" placeholder="Buscar código o nombre...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table-saas align-middle" id="tablaCuentas">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Código Cuenta</th>
                                <th>Nombre / Descripción</th>
                                <th style="width: 170px;">Tipo de Cuenta</th>
                                <th style="width: 170px;" class="text-center">Centros Vinculados</th>
                                <th style="width: 130px;" class="text-center">Items OPI</th>
                                <th style="width: 100px;" class="text-center">Estado</th>
                                <th style="width: 150px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cuentas)): ?>
                                <tr>
                                    <td colspan="7" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-2 text-slate-300 d-block mb-2"></i>
                                        No se han registrado Cuentas en el catálogo.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cuentas as $c): ?>
                                    <tr class="item-cuenta-fila <?= $c['activo'] ? '' : 'opacity-75 bg-light' ?>" data-tipo="<?= htmlspecialchars($c['tipo_cuenta']) ?>">
                                        <td>
                                            <span class="badge bg-light text-secondary border font-monospace px-2.5 py-1.5 fs-7 fw-bold item-codigo">
                                                <?= htmlspecialchars($c['codigo']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark item-nombre"><?= htmlspecialchars($c['nombre']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($c['tipo_cuenta'] === 'COMPLEMENTARIA'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-bookmark-star-fill me-1"></i> Complementaria
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                                    <i class="bi bi-journal-bookmark-fill me-1"></i> Presupuestaria
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <?= $c['total_centros_asig'] ?> Centros
                                            </span>
                                        </td>
                                        <td class="text-center tabular-nums text-muted">
                                            <span class="badge bg-slate-100 text-secondary border px-2 py-1">
                                                <?= number_format($c['total_items_compra']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($c['activo']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button class="btn btn-ghost btn-sm text-secondary" title="Editar Cuenta" onclick="editarCuenta(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Alternar estado de esta cuenta?');">
                                                    <input type="hidden" name="accion" value="toggle_cuenta">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm <?= $c['activo'] ? 'text-warning' : 'text-success' ?>" title="<?= $c['activo'] ? 'Desactivar' : 'Activar' ?>">
                                                        <i class="bi bi-<?= $c['activo'] ? 'toggle-on' : 'toggle-off' ?> fs-6"></i>
                                                    </button>
                                                </form>
                                                <?php if ($c['total_centros_asig'] == 0 && $c['total_items_compra'] == 0): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente esta cuenta del catálogo?');">
                                                        <input type="hidden" name="accion" value="eliminar_cuenta">
                                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Eliminar Cuenta">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
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

        <!-- ============================================================== -->
        <!-- VISTA 3: MANTENEDOR DE ÁREAS DE GESTIÓN                        -->
        <!-- ============================================================== -->
        <?php elseif ($tab === 'areas'): ?>
            <div class="panel-card">
                <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="panel-title d-flex align-items-center gap-2">
                        <i class="bi bi-diagram-3 text-primary"></i>
                        <span>Catálogo de Áreas de Gestión</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroAreas" class="form-control border-start-0" placeholder="Buscar código o nombre...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table-saas align-middle" id="tablaAreas">
                        <thead>
                            <tr>
                                <th style="width: 140px;">Código</th>
                                <th>Nombre / Clasificación del Área</th>
                                <th style="width: 190px;" class="text-center">Asignaciones Presupuestarias</th>
                                <th style="width: 130px;" class="text-center">Items OPI</th>
                                <th style="width: 100px;" class="text-center">Estado</th>
                                <th style="width: 150px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($areas)): ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-2 text-slate-300 d-block mb-2"></i>
                                        No se han registrado Áreas de Gestión en el sistema.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($areas as $a): ?>
                                    <tr class="item-area-fila <?= $a['activo'] ? '' : 'opacity-75 bg-light' ?>">
                                        <td>
                                            <span class="badge bg-light text-secondary border font-monospace px-2.5 py-1.5 fs-7 fw-bold item-codigo">
                                                <?= htmlspecialchars($a['codigo']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark item-nombre"><?= htmlspecialchars($a['nombre']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <?= $a['total_asignaciones'] ?> Asignaciones
                                            </span>
                                        </td>
                                        <td class="text-center tabular-nums text-muted">
                                            <span class="badge bg-slate-100 text-secondary border px-2 py-1">
                                                <?= number_format($a['total_items_compra']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($a['activo']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button class="btn btn-ghost btn-sm text-secondary" title="Editar Área" onclick="editarArea(<?= htmlspecialchars(json_encode($a)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Alternar estado de esta Área de Gestión?');">
                                                    <input type="hidden" name="accion" value="toggle_area">
                                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm <?= $a['activo'] ? 'text-warning' : 'text-success' ?>" title="<?= $a['activo'] ? 'Desactivar' : 'Activar' ?>">
                                                        <i class="bi bi-<?= $a['activo'] ? 'toggle-on' : 'toggle-off' ?> fs-6"></i>
                                                    </button>
                                                </form>
                                                <?php if ($a['total_asignaciones'] == 0 && $a['total_items_compra'] == 0): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente esta Área de Gestión?');">
                                                        <input type="hidden" name="accion" value="eliminar_area">
                                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Eliminar Área">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
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

        <?php endif; ?>

    </div>

    <!-- ============================================================== -->
    <!-- MODAL: CREAR / EDITAR CENTRO DE COSTO                          -->
    <!-- ============================================================== -->
    <div class="modal fade" id="modalCC" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_cc">
                    <input type="hidden" name="id" id="cc_id">
                    
                    <div class="modal-header border-bottom py-3 px-4">
                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalCCTitle">
                            <i class="bi bi-building text-primary"></i>
                            <span>Nuevo Centro de Costo</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Código Cuenta / ID <span class="text-danger">*</span></label>
                                <input type="text" name="codigo_cuenta" id="cc_codigo" class="form-control" placeholder="Ej: 11, 12, 9000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Año Fiscal <span class="text-danger">*</span></label>
                                <input type="number" name="anio_fiscal" id="cc_anio" class="form-control tabular-nums" value="<?= date('Y') ?>" required min="2020" max="2050">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Nombre del Centro de Costo <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="cc_nombre" class="form-control" placeholder="Ej: Depto. Informática, DIDECO, Administración" required>
                            </div>
                            <div class="col-12" id="cc_activo_wrap" style="display: none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activo" id="cc_activo" value="1" checked>
                                    <label class="form-check-label small fw-semibold text-dark" for="cc_activo">Centro de Costo Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light border-top py-2.5 px-4">
                        <button type="button" class="btn btn-ghost btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Guardar Centro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: ASIGNAR CUENTA A CENTRO DE COSTO                        -->
    <!-- ============================================================== -->
    <?php if ($tab === 'detalle_centro' && $centro_actual): ?>
    <div class="modal fade" id="modalAsignarCuenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form method="POST">
                    <input type="hidden" name="accion" value="asignar_cuenta">
                    <input type="hidden" name="centro_costo_id" value="<?= $centro_actual['id'] ?>">
                    
                    <div class="modal-header border-bottom py-3 px-4">
                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-link-45deg text-primary"></i>
                            <span>Vincular Cuenta al Centro de Costo</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Centro de Costo Destino</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($centro_actual['codigo_cuenta']) ?> - <?= htmlspecialchars($centro_actual['nombre']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Seleccionar Cuenta Maestra <span class="text-danger">*</span></label>
                            <select name="cuenta_maestra_id" class="form-select" required>
                                <option value="">-- Seleccionar Cuenta --</option>
                                <?php foreach ($catalogo_cuentas_activas as $cta): ?>
                                    <option value="<?= $cta['id'] ?>">
                                        [<?= $cta['tipo_cuenta'] === 'COMPLEMENTARIA' ? 'COMPLEMENTARIA' : 'PRESUPUESTARIA' ?>] <?= htmlspecialchars($cta['codigo']) ?> - <?= htmlspecialchars($cta['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Área de Gestión (AG)</label>
                            <select name="area_gestion_id" class="form-select">
                                <option value="">-- Sin Imputación de Área / Ninguna --</option>
                                <?php foreach ($catalogo_areas_activas as $ag): ?>
                                    <option value="<?= $ag['id'] ?>">
                                        <?= htmlspecialchars($ag['codigo']) ?> - <?= htmlspecialchars($ag['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Clasificación de destino (Gestión Interna, Servicios Comunitarios, etc.).</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light border-top py-2.5 px-4">
                        <button type="button" class="btn btn-ghost btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Vincular Cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- MODAL: CREAR / EDITAR CUENTA MAESTRA                           -->
    <!-- ============================================================== -->
    <div class="modal fade" id="modalCuenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_cuenta">
                    <input type="hidden" name="id" id="cta_id">
                    
                    <div class="modal-header border-bottom py-3 px-4">
                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalCuentaTitle">
                            <i class="bi bi-list-columns-reverse text-primary"></i>
                            <span>Nueva Cuenta Maestra</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Código de Cuenta <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" id="cta_codigo" class="form-control font-monospace" placeholder="Ej: 2152204001001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Tipo de Cuenta <span class="text-danger">*</span></label>
                                <select name="tipo_cuenta" id="cta_tipo" class="form-select fw-semibold" required>
                                    <option value="PRESUPUESTARIA">Presupuestaria (Municipal)</option>
                                    <option value="COMPLEMENTARIA">Complementaria (Fondos Ext.)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Nombre / Descripción de la Cuenta <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="cta_nombre" class="form-control" placeholder="Ej: Por propuesta pública, Canastas, etc." required>
                            </div>
                            <div class="col-12" id="cta_activo_wrap" style="display: none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activo" id="cta_activo" value="1" checked>
                                    <label class="form-check-label small fw-semibold text-dark" for="cta_activo">Cuenta Activa en Catálogo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light border-top py-2.5 px-4">
                        <button type="button" class="btn btn-ghost btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Guardar Cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: CREAR / EDITAR ÁREA DE GESTIÓN                          -->
    <!-- ============================================================== -->
    <div class="modal fade" id="modalArea" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_area">
                    <input type="hidden" name="id" id="area_id">
                    
                    <div class="modal-header border-bottom py-3 px-4">
                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalAreaTitle">
                            <i class="bi bi-diagram-3 text-primary"></i>
                            <span>Nueva Área de Gestión</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark">Código de Área <span class="text-danger">*</span></label>
                                <input type="text" name="codigo" id="area_codigo" class="form-control font-monospace" placeholder="Ej: AG 01, AG 02, S/I" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-dark">Nombre Descriptivo <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="area_nombre" class="form-control" placeholder="Ej: Gestión Interna, Servicios Comunitarios" required>
                            </div>
                            <div class="col-12" id="area_activo_wrap" style="display: none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="activo" id="area_activo" value="1" checked>
                                    <label class="form-check-label small fw-semibold text-dark" for="area_activo">Área de Gestión Activa</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light border-top py-2.5 px-4">
                        <button type="button" class="btn btn-ghost btn-sm px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Guardar Área</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts de interactividad y filtros en vivo -->
    <script>
    // 1. Modales de Centro de Costo
    function abrirModalCC() {
        document.getElementById('cc_id').value = '';
        document.getElementById('cc_codigo').value = '';
        document.getElementById('cc_nombre').value = '';
        document.getElementById('cc_anio').value = '<?= date('Y') ?>';
        document.getElementById('cc_activo_wrap').style.display = 'none';
        document.getElementById('modalCCTitle').innerHTML = '<i class="bi bi-building text-primary"></i> <span>Nuevo Centro de Costo</span>';
        new bootstrap.Modal(document.getElementById('modalCC')).show();
    }

    function editarCC(data) {
        document.getElementById('cc_id').value = data.id;
        document.getElementById('cc_codigo').value = data.codigo_cuenta;
        document.getElementById('cc_nombre').value = data.nombre;
        document.getElementById('cc_anio').value = data.anio_fiscal;
        document.getElementById('cc_activo').checked = (data.activo == 1);
        document.getElementById('cc_activo_wrap').style.display = 'block';
        document.getElementById('modalCCTitle').innerHTML = '<i class="bi bi-pencil text-primary"></i> <span>Editar Centro de Costo</span>';
        new bootstrap.Modal(document.getElementById('modalCC')).show();
    }

    // 2. Modales de Cuentas Maestras
    function abrirModalCuenta() {
        document.getElementById('cta_id').value = '';
        document.getElementById('cta_codigo').value = '';
        document.getElementById('cta_nombre').value = '';
        document.getElementById('cta_tipo').value = 'PRESUPUESTARIA';
        document.getElementById('cta_activo_wrap').style.display = 'none';
        document.getElementById('modalCuentaTitle').innerHTML = '<i class="bi bi-list-columns-reverse text-primary"></i> <span>Nueva Cuenta Maestra</span>';
        new bootstrap.Modal(document.getElementById('modalCuenta')).show();
    }

    function editarCuenta(data) {
        document.getElementById('cta_id').value = data.id;
        document.getElementById('cta_codigo').value = data.codigo;
        document.getElementById('cta_nombre').value = data.nombre;
        document.getElementById('cta_tipo').value = data.tipo_cuenta || 'PRESUPUESTARIA';
        document.getElementById('cta_activo').checked = (data.activo == 1);
        document.getElementById('cta_activo_wrap').style.display = 'block';
        document.getElementById('modalCuentaTitle').innerHTML = '<i class="bi bi-pencil text-primary"></i> <span>Editar Cuenta Maestra</span>';
        new bootstrap.Modal(document.getElementById('modalCuenta')).show();
    }

    // 3. Modales de Áreas de Gestión
    function abrirModalArea() {
        document.getElementById('area_id').value = '';
        document.getElementById('area_codigo').value = '';
        document.getElementById('area_nombre').value = '';
        document.getElementById('area_activo_wrap').style.display = 'none';
        document.getElementById('modalAreaTitle').innerHTML = '<i class="bi bi-diagram-3 text-primary"></i> <span>Nueva Área de Gestión</span>';
        new bootstrap.Modal(document.getElementById('modalArea')).show();
    }

    function editarArea(data) {
        document.getElementById('area_id').value = data.id;
        document.getElementById('area_codigo').value = data.codigo;
        document.getElementById('area_nombre').value = data.nombre;
        document.getElementById('area_activo').checked = (data.activo == 1);
        document.getElementById('area_activo_wrap').style.display = 'block';
        document.getElementById('modalAreaTitle').innerHTML = '<i class="bi bi-pencil text-primary"></i> <span>Editar Área de Gestión</span>';
        new bootstrap.Modal(document.getElementById('modalArea')).show();
    }

    // 4. Búsqueda en vivo de Centros de Costo
    const inputFiltroCentros = document.getElementById('filtroCentros');
    if (inputFiltroCentros) {
        inputFiltroCentros.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('#tablaCentros .item-fila').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // 5. Búsqueda y filtrado de Cuentas Maestras
    let filtroTipoActual = 'TODAS';
    function filtrarTipoCuenta(tipo, btn) {
        filtroTipoActual = tipo;
        if (btn) {
            btn.parentElement.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
        aplicarFiltrosCuentas();
    }

    const inputFiltroCuentas = document.getElementById('filtroCuentas');
    if (inputFiltroCuentas) {
        inputFiltroCuentas.addEventListener('input', aplicarFiltrosCuentas);
    }

    function aplicarFiltrosCuentas() {
        const query = (inputFiltroCuentas ? inputFiltroCuentas.value : '').toLowerCase().trim();
        document.querySelectorAll('#tablaCuentas .item-cuenta-fila').forEach(row => {
            const tipo = row.getAttribute('data-tipo');
            const text = row.textContent.toLowerCase();
            const coincideTipo = (filtroTipoActual === 'TODAS' || tipo === filtroTipoActual);
            const coincideTexto = text.includes(query);
            row.style.display = (coincideTipo && coincideTexto) ? '' : 'none';
        });
    }

    // 6. Búsqueda en vivo de Áreas de Gestión
    const inputFiltroAreas = document.getElementById('filtroAreas');
    if (inputFiltroAreas) {
        inputFiltroAreas.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('#tablaAreas .item-area-fila').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
