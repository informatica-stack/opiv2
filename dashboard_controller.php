<?php
// dashboard_controller.php - Controlador del Dashboard Presupuestario y de Administración (V1.0)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$rol = $_SESSION['user_rol'] ?? '';
// Acceso permitido para Control Presupuestario, Administrador Municipal y SysAdmin
if (!in_array($rol, ['PRESUPUESTO', 'ADMIN_MUNICIPAL', 'SYSADMIN'])) {
    die("Acceso Denegado. Módulo exclusivo de Control Presupuestario y Administración.");
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Usuario';

// 2. PARÁMETROS DE FILTRADO
$anio_actual_defecto = date('Y');
$f_anio = $_GET['f_anio'] ?? $anio_actual_defecto;
$f_cc = $_GET['f_cc'] ?? '';
$f_tipo = $_GET['f_tipo'] ?? '';
$f_macro = $_GET['f_macro'] ?? 'TODOS';
$f_desde = trim($_GET['f_desde'] ?? '');
$f_hasta = trim($_GET['f_hasta'] ?? '');
$f_q = trim($_GET['f_q'] ?? '');

// 3. CATÁLOGOS PARA FILTROS
try {
    $centros_costo = $pdo->query("SELECT id, codigo_cuenta, nombre FROM centros_costo WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_compra = $pdo->query("SELECT id, codigo, nombre FROM tipos_compra WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $estados_tramite = $pdo->query("SELECT codigo, nombre FROM estados_tramite ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Años disponibles en la base de datos
    $anios_disponibles = $pdo->query("SELECT DISTINCT YEAR(created_at) as anio FROM expedientes WHERE created_at IS NOT NULL ORDER BY anio DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array((int)$anio_actual_defecto, $anios_disponibles)) {
        array_unshift($anios_disponibles, (int)$anio_actual_defecto);
    }
} catch (Exception $e) {
    die("Error al cargar catálogos: " . $e->getMessage());
}

// 4. CONSTRUCCIÓN DE CLAUSULAS WHERE BASE
$where_kpi = [];
$params_kpi = [];

if ($f_anio !== 'TODOS' && is_numeric($f_anio)) {
    $where_kpi[] = "YEAR(e.created_at) = :anio";
    $params_kpi[':anio'] = (int)$f_anio;
}
if ($f_cc) {
    $where_kpi[] = "e.centro_costo_id = :cc";
    $params_kpi[':cc'] = $f_cc;
}
if ($f_tipo) {
    $where_kpi[] = "e.tipo_compra_id = :tipo";
    $params_kpi[':tipo'] = $f_tipo;
}
if ($f_desde) {
    $where_kpi[] = "DATE(e.created_at) >= :desde";
    $params_kpi[':desde'] = $f_desde;
}
if ($f_hasta) {
    $where_kpi[] = "DATE(e.created_at) <= :hasta";
    $params_kpi[':hasta'] = $f_hasta;
}

$where_kpi_sql = !empty($where_kpi) ? "WHERE " . implode(" AND ", $where_kpi) : "";

// 5. CÁLCULO DE KPIS Y TOTALES GLOBALES
try {
    // KPI 1: Totales y Montos (Excluyendo Rechazados para monto comprometido real)
    $stmtTotales = $pdo->prepare("
        SELECT 
            COUNT(*) as total_expedientes,
            COALESCE(SUM(CASE WHEN e.estado_actual != 'RECHAZADO' THEN e.monto_estimado ELSE 0 END), 0) as monto_total_comprometido,
            COALESCE(SUM(CASE WHEN e.estado_actual = 'FINALIZADO' THEN e.monto_estimado ELSE 0 END), 0) as monto_finalizado,
            COALESCE(SUM(CASE WHEN e.estado_actual NOT IN ('FINALIZADO', 'RECHAZADO') THEN e.monto_estimado ELSE 0 END), 0) as monto_en_curso,
            COUNT(CASE WHEN e.estado_actual = 'FINALIZADO' THEN 1 END) as count_finalizados,
            COUNT(CASE WHEN e.estado_actual NOT IN ('FINALIZADO', 'RECHAZADO') THEN 1 END) as count_en_curso,
            COUNT(CASE WHEN e.estado_actual = 'RECHAZADO' THEN 1 END) as count_rechazados,
            COUNT(CASE WHEN e.estado_actual = 'EN_CORRECCION' THEN 1 END) as count_en_correccion
        FROM expedientes e
        $where_kpi_sql
    ");
    $stmtTotales->execute($params_kpi);
    $kpis = $stmtTotales->fetch(PDO::FETCH_ASSOC);

    $kpi_total_expedientes = (int)($kpis['total_expedientes'] ?? 0);
    $kpi_monto_comprometido = (float)($kpis['monto_total_comprometido'] ?? 0);
    $kpi_monto_finalizado = (float)($kpis['monto_finalizado'] ?? 0);
    $kpi_monto_en_curso = (float)($kpis['monto_en_curso'] ?? 0);
    $kpi_count_finalizados = (int)($kpis['count_finalizados'] ?? 0);
    $kpi_count_en_curso = (int)($kpis['count_en_curso'] ?? 0);
    $kpi_count_rechazados = (int)($kpis['count_rechazados'] ?? 0);
    $kpi_count_en_correccion = (int)($kpis['count_en_correccion'] ?? 0);

    // Estimación Desglose Neto e IVA
    $kpi_monto_neto_est = round($kpi_monto_comprometido / 1.19);
    $kpi_monto_iva_est = $kpi_monto_comprometido - $kpi_monto_neto_est;

    // KPI Urgentes Presupuesto (Acciones Inmediatas requeridas)
    $stmtUrgPresupuesto = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN e.estado_actual = 'EN_VALIDACION_PRESUPUESTARIA' THEN 1 END) as urg_inicial,
            COUNT(CASE WHEN e.estado_actual IN ('EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL') THEN 1 END) as urg_final
        FROM expedientes e
    ");
    $stmtUrgPresupuesto->execute();
    $urg_presupuesto = $stmtUrgPresupuesto->fetch(PDO::FETCH_ASSOC);
    $kpi_urg_inicial = (int)($urg_presupuesto['urg_inicial'] ?? 0);
    $kpi_urg_final = (int)($urg_presupuesto['urg_final'] ?? 0);
    $kpi_urg_total = $kpi_urg_inicial + $kpi_urg_final;

} catch (Exception $e) {
    die("Error al calcular KPIs: " . $e->getMessage());
}

// 6. DATOS PARA GRÁFICOS ANALÍTICOS (Chart.js)
try {
    // GRÁFICO 1: Macro-Fases del Flujo (Pipeline OPI)
    $stmtPipeline = $pdo->prepare("
        SELECT e.estado_actual, COUNT(*) as cantidad, COALESCE(SUM(e.monto_estimado), 0) as monto
        FROM expedientes e
        $where_kpi_sql
        GROUP BY e.estado_actual
    ");
    $stmtPipeline->execute($params_kpi);
    $raw_estados = $stmtPipeline->fetchAll(PDO::FETCH_ASSOC);

    $pipeline_fases = [
        'Borrador / Corrección' => ['count' => 0, 'monto' => 0, 'color' => '#6c757d'],
        'Revisión Jefatura' => ['count' => 0, 'monto' => 0, 'color' => '#0d6efd'],
        'VB Presupuesto Inicial' => ['count' => 0, 'monto' => 0, 'color' => '#ffc107'],
        'Gestión Adquisiciones' => ['count' => 0, 'monto' => 0, 'color' => '#17a2b8'],
        'VB Presupuesto Final / CDP' => ['count' => 0, 'monto' => 0, 'color' => '#fd7e14'],
        'Firmas Electrónicas OPI' => ['count' => 0, 'monto' => 0, 'color' => '#6f42c1'],
        'Emisión / Aceptación OC' => ['count' => 0, 'monto' => 0, 'color' => '#20c997'],
        'Finalizado' => ['count' => 0, 'monto' => 0, 'color' => '#198754'],
        'Rechazado' => ['count' => 0, 'monto' => 0, 'color' => '#dc3545']
    ];

    foreach ($raw_estados as $row) {
        $est = $row['estado_actual'];
        $c = (int)$row['cantidad'];
        $m = (float)$row['monto'];

        if (in_array($est, ['BORRADOR', 'EN_CORRECCION'])) {
            $pipeline_fases['Borrador / Corrección']['count'] += $c;
            $pipeline_fases['Borrador / Corrección']['monto'] += $m;
        } elseif (in_array($est, ['EN_REVISION_JEFATURA'])) {
            $pipeline_fases['Revisión Jefatura']['count'] += $c;
            $pipeline_fases['Revisión Jefatura']['monto'] += $m;
        } elseif (in_array($est, ['EN_VALIDACION_PRESUPUESTARIA'])) {
            $pipeline_fases['VB Presupuesto Inicial']['count'] += $c;
            $pipeline_fases['VB Presupuesto Inicial']['monto'] += $m;
        } elseif (in_array($est, ['EN_COTIZACION_ADQUISICIONES', 'ESPERANDO_CUADRO_COMPARATIVO', 'ESPERANDO_ADJUDICACION_ADQUISICIONES', 'ESPERANDO_SUBIDA_DOCS_COMPRA', 'EN_ADQUISICIONES'])) {
            $pipeline_fases['Gestión Adquisiciones']['count'] += $c;
            $pipeline_fases['Gestión Adquisiciones']['monto'] += $m;
        } elseif (in_array($est, ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL'])) {
            $pipeline_fases['VB Presupuesto Final / CDP']['count'] += $c;
            $pipeline_fases['VB Presupuesto Final / CDP']['monto'] += $m;
        } elseif (in_array($est, ['EN_FIRMA_JEFATURA', 'EN_FIRMA_FINANZAS', 'EN_FIRMA_ADMINISTRADOR', 'EN_FIRMA_ALCALDE'])) {
            $pipeline_fases['Firmas Electrónicas OPI']['count'] += $c;
            $pipeline_fases['Firmas Electrónicas OPI']['monto'] += $m;
        } elseif (in_array($est, ['ESPERANDO_ORDEN_COMPRA_ACEPTADA', 'ESPERANDO_ACEPTACION_OC', 'OC_ENVIADA'])) {
            $pipeline_fases['Emisión / Aceptación OC']['count'] += $c;
            $pipeline_fases['Emisión / Aceptación OC']['monto'] += $m;
        } elseif ($est === 'FINALIZADO') {
            $pipeline_fases['Finalizado']['count'] += $c;
            $pipeline_fases['Finalizado']['monto'] += $m;
        } elseif ($est === 'RECHAZADO') {
            $pipeline_fases['Rechazado']['count'] += $c;
            $pipeline_fases['Rechazado']['monto'] += $m;
        } else {
            $pipeline_fases['Borrador / Corrección']['count'] += $c;
            $pipeline_fases['Borrador / Corrección']['monto'] += $m;
        }
    }

    // GRÁFICO 2: Top Gasto por Centro de Costos
    $stmtCCGasto = $pdo->prepare("
        SELECT 
            COALESCE(cc.codigo_cuenta, 'S/C') as codigo,
            COALESCE(cc.nombre, 'Sin Centro de Costo') as nombre,
            COUNT(e.id) as total_ops,
            COALESCE(SUM(CASE WHEN e.estado_actual != 'RECHAZADO' THEN e.monto_estimado ELSE 0 END), 0) as total_monto
        FROM expedientes e
        LEFT JOIN centros_costo cc ON e.centro_costo_id = cc.id
        $where_kpi_sql
        GROUP BY cc.id, cc.codigo_cuenta, cc.nombre
        ORDER BY total_monto DESC
        LIMIT 7
    ");
    $stmtCCGasto->execute($params_kpi);
    $cc_gasto_list = $stmtCCGasto->fetchAll(PDO::FETCH_ASSOC);

    // GRÁFICO 3: Distribución por Tipo de Compra
    $stmtTipoCompraGasto = $pdo->prepare("
        SELECT 
            tc.codigo,
            tc.nombre,
            COUNT(e.id) as cantidad,
            COALESCE(SUM(CASE WHEN e.estado_actual != 'RECHAZADO' THEN e.monto_estimado ELSE 0 END), 0) as monto
        FROM expedientes e
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        $where_kpi_sql
        GROUP BY tc.id, tc.codigo, tc.nombre
        ORDER BY monto DESC
    ");
    $stmtTipoCompraGasto->execute($params_kpi);
    $tipos_compra_gasto = $stmtTipoCompraGasto->fetchAll(PDO::FETCH_ASSOC);

    // GRÁFICO 4: Evolución Mensual del Gasto e Ingresos
    $anio_timeline = ($f_anio !== 'TODOS' && is_numeric($f_anio)) ? (int)$f_anio : (int)$anio_actual_defecto;
    $params_timeline = [':anio_tl' => $anio_timeline];
    $where_tl_extra = [];
    if ($f_cc) { $where_tl_extra[] = "e.centro_costo_id = :cc_tl"; $params_timeline[':cc_tl'] = $f_cc; }
    if ($f_tipo) { $where_tl_extra[] = "e.tipo_compra_id = :tipo_tl"; $params_timeline[':tipo_tl'] = $f_tipo; }
    $where_tl_str = !empty($where_tl_extra) ? " AND " . implode(" AND ", $where_tl_extra) : "";

    $stmtEvolucion = $pdo->prepare("
        SELECT 
            MONTH(e.created_at) as mes,
            COUNT(*) as cantidad,
            COALESCE(SUM(CASE WHEN e.estado_actual != 'RECHAZADO' THEN e.monto_estimado ELSE 0 END), 0) as monto
        FROM expedientes e
        WHERE YEAR(e.created_at) = :anio_tl $where_tl_str
        GROUP BY MONTH(e.created_at)
        ORDER BY mes ASC
    ");
    $stmtEvolucion->execute($params_timeline);
    $meses_data_raw = $stmtEvolucion->fetchAll(PDO::FETCH_ASSOC);

    $meses_labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $meses_montos = array_fill(0, 12, 0);
    $meses_cantidades = array_fill(0, 12, 0);

    foreach ($meses_data_raw as $m) {
        $idx = (int)$m['mes'] - 1;
        if ($idx >= 0 && $idx < 12) {
            $meses_montos[$idx] = (float)$m['monto'];
            $meses_cantidades[$idx] = (int)$m['cantidad'];
        }
    }

} catch (Exception $e) {
    die("Error al calcular estadísticas gráficas: " . $e->getMessage());
}

// 7. TABLA MAESTRA DE EXPEDIENTES (Búsqueda y Listado con Filtros)
$where_master = [];
$params_master = [];

if ($f_anio !== 'TODOS' && is_numeric($f_anio)) {
    $where_master[] = "YEAR(e.created_at) = :m_anio";
    $params_master[':m_anio'] = (int)$f_anio;
}
if ($f_cc) {
    $where_master[] = "e.centro_costo_id = :m_cc";
    $params_master[':m_cc'] = $f_cc;
}
if ($f_tipo) {
    $where_master[] = "e.tipo_compra_id = :m_tipo";
    $params_master[':m_tipo'] = $f_tipo;
}
if ($f_desde) {
    $where_master[] = "DATE(e.created_at) >= :m_desde";
    $params_master[':m_desde'] = $f_desde;
}
if ($f_hasta) {
    $where_master[] = "DATE(e.created_at) <= :m_hasta";
    $params_master[':m_hasta'] = $f_hasta;
}
if ($f_q) {
    $where_master[] = "(e.codigo_interno LIKE :m_q OR e.titulo_compra LIKE :m_q OR e.motivo_compra LIKE :m_q OR p.razon_social LIKE :m_q OR p.rut LIKE :m_q OR u.nombre_completo LIKE :m_q)";
    $params_master[':m_q'] = "%$f_q%";
}

// Macro-Filtros de Estado
if ($f_macro === 'PENDIENTES_PRESUPUESTO') {
    $where_master[] = "e.estado_actual IN ('EN_VALIDACION_PRESUPUESTARIA', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')";
} elseif ($f_macro === 'EN_CURSO') {
    $where_master[] = "e.estado_actual NOT IN ('FINALIZADO', 'RECHAZADO')";
} elseif ($f_macro === 'FINALIZADOS') {
    $where_master[] = "e.estado_actual = 'FINALIZADO'";
} elseif ($f_macro === 'RECHAZADOS_DEVUELTOS') {
    $where_master[] = "e.estado_actual IN ('RECHAZADO', 'EN_CORRECCION')";
}

$where_master_sql = !empty($where_master) ? "WHERE " . implode(" AND ", $where_master) : "";

try {
    $stmtMaster = $pdo->prepare("
        SELECT 
            e.id,
            e.codigo_interno,
            e.titulo_compra,
            e.motivo_compra,
            e.monto_estimado,
            e.tipo_impuesto,
            e.estado_actual,
            e.created_at,
            e.updated_at,
            tc.nombre as tipo_compra_nombre,
            tc.codigo as tipo_compra_codigo,
            cc.codigo_cuenta as cc_codigo,
            cc.nombre as cc_nombre,
            un.nombre as unidad_nombre,
            u.nombre_completo as usuario_nombre,
            p.rut as prov_rut,
            p.razon_social as prov_nombre,
            et.nombre as estado_nombre,
            DATEDIFF(NOW(), COALESCE(e.updated_at, e.created_at)) as dias_en_estado
        FROM expedientes e
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        LEFT JOIN centros_costo cc ON e.centro_costo_id = cc.id
        LEFT JOIN unidades un ON e.unidad_origen_id = un.id
        LEFT JOIN usuarios u ON e.usuario_creador_id = u.id
        LEFT JOIN proveedores p ON e.proveedor_adjudicado_id = p.id
        LEFT JOIN estados_tramite et ON e.estado_actual = et.codigo
        $where_master_sql
        ORDER BY e.created_at DESC
        LIMIT 150
    ");
    $stmtMaster->execute($params_master);
    $expedientes_lista = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error al consultar lista de expedientes: " . $e->getMessage());
}
?>
