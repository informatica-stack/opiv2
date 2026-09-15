<?php
// cuentas_centros_costo_controller.php - Controlador Centralizado de Presupuesto, Cuentas y Áreas (V1.0)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y CONTROL DE SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$rol_actual = $_SESSION['user_rol'] ?? '';
if ($rol_actual !== 'PRESUPUESTO' && $rol_actual !== 'ADMIN_MUNICIPAL' && $rol_actual !== 'SYSADMIN') {
    die("Acceso Denegado. Módulo exclusivo de Control Presupuestario y Administración.");
}

// 2. MIGRACIÓN PREVENTIVA DE COLUMNA tipo_cuenta (Auto-adaptable)
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM cuentas_maestras LIKE 'tipo_cuenta'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE cuentas_maestras ADD COLUMN tipo_cuenta ENUM('PRESUPUESTARIA', 'COMPLEMENTARIA') NOT NULL DEFAULT 'PRESUPUESTARIA' AFTER nombre");
    }
} catch (Exception $e) {
    // Ignorar si ya existe o no hay permisos DDL en runtime
}

$mensaje = '';
$tipo_mensaje = '';
$tab = $_GET['tab'] ?? 'centros'; // 'centros', 'detalle_centro', 'cuentas', 'areas'
$centro_id = isset($_GET['centro_id']) ? (int)$_GET['centro_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : null);

if ($tab === 'detalle' || isset($_GET['id'])) {
    if ($tab === 'centros' && isset($_GET['id'])) {
        $tab = 'detalle_centro';
        $centro_id = (int)$_GET['id'];
    }
}

// 3. PROCESAMIENTO DE ACCIONES (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        $pdo->beginTransaction();

        // -------------------------------------------------------------
        // A. ACCIONES DE CENTROS DE COSTO
        // -------------------------------------------------------------
        if ($accion === 'guardar_cc') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $codigo = trim($_POST['codigo_cuenta'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $anio = !empty($_POST['anio_fiscal']) ? (int)$_POST['anio_fiscal'] : (int)date('Y');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if (empty($codigo) || empty($nombre)) {
                throw new Exception("El código y el nombre del Centro de Costo son obligatorios.");
            }

            if ($id) {
                $stmt = $pdo->prepare("UPDATE centros_costo SET codigo_cuenta = ?, nombre = ?, anio_fiscal = ?, activo = ? WHERE id = ?");
                $stmt->execute([$codigo, $nombre, $anio, $activo, $id]);
                $mensaje = "Centro de Costo \"$nombre\" actualizado exitosamente.";
            } else {
                $stmtCheck = $pdo->prepare("SELECT id FROM centros_costo WHERE codigo_cuenta = ? AND anio_fiscal = ?");
                $stmtCheck->execute([$codigo, $anio]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Ya existe un Centro de Costo con el código '$codigo' para el año fiscal $anio.");
                }

                $stmt = $pdo->prepare("INSERT INTO centros_costo (codigo_cuenta, nombre, anio_fiscal, activo) VALUES (?, ?, ?, 1)");
                $stmt->execute([$codigo, $nombre, $anio]);
                $mensaje = "Nuevo Centro de Costo \"$nombre\" creado exitosamente.";
            }
            $tipo_mensaje = 'success';
            $tab = 'centros';
        }

        elseif ($accion === 'toggle_cc') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE centros_costo SET activo = 1 - activo WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Estado del Centro de Costo actualizado.";
            $tipo_mensaje = 'success';
            $tab = 'centros';
        }

        elseif ($accion === 'eliminar_cc') {
            $id = (int)$_POST['id'];
            
            // Verificar si tiene asignaciones
            $checkAsig = $pdo->prepare("SELECT COUNT(*) FROM presupuestos_asignados WHERE centro_costo_id = ?");
            $checkAsig->execute([$id]);
            if ($checkAsig->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar: El Centro de Costo tiene cuentas presupuestarias asignadas. Desactívelo en su lugar.");
            }

            // Verificar si está en unidades
            $checkUnid = $pdo->prepare("SELECT COUNT(*) FROM unidades WHERE centro_costo_id = ?");
            $checkUnid->execute([$id]);
            if ($checkUnid->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar: Existen Unidades municipales vinculadas a este Centro de Costo.");
            }

            $pdo->prepare("DELETE FROM centros_costo WHERE id = ?")->execute([$id]);
            $mensaje = "Centro de Costo eliminado permanentemente.";
            $tipo_mensaje = 'success';
            $tab = 'centros';
        }

        // -------------------------------------------------------------
        // B. ASIGNACIONES (VINCULACIÓN CC + CUENTA + ÁREA DE GESTIÓN)
        // -------------------------------------------------------------
        elseif ($accion === 'asignar_cuenta') {
            $cc_id = (int)$_POST['centro_costo_id'];
            $maestra_id = (int)$_POST['cuenta_maestra_id'];
            $ag_id = !empty($_POST['area_gestion_id']) ? (int)$_POST['area_gestion_id'] : null;

            if (!$cc_id || !$maestra_id) {
                throw new Exception("Debe seleccionar un Centro de Costo y una Cuenta Maestra.");
            }

            // Validar si ya existe
            $check = $pdo->prepare("SELECT id FROM presupuestos_asignados WHERE centro_costo_id = ? AND cuenta_maestra_id = ? AND area_gestion_id <=> ?");
            $check->execute([$cc_id, $maestra_id, $ag_id]);
            if ($check->fetch()) {
                throw new Exception("Esta cuenta ya se encuentra asignada a este Centro de Costo con la misma Área de Gestión.");
            }

            $stmt = $pdo->prepare("INSERT INTO presupuestos_asignados (centro_costo_id, cuenta_maestra_id, area_gestion_id) VALUES (?, ?, ?)");
            $stmt->execute([$cc_id, $maestra_id, $ag_id]);

            $mensaje = "Cuenta vinculada correctamente al Centro de Costo.";
            $tipo_mensaje = 'success';
            $tab = 'detalle_centro';
            $centro_id = $cc_id;
        }

        elseif ($accion === 'eliminar_asignacion') {
            $asignacion_id = (int)$_POST['asignacion_id'];
            $cc_id = (int)$_POST['centro_costo_id'];

            // Validar si tiene items en solicitudes de compras
            $checkItems = $pdo->prepare("SELECT COUNT(*) FROM expedientes_items WHERE presupuesto_asignado_id = ?");
            $checkItems->execute([$asignacion_id]);
            $countItems = $checkItems->fetchColumn();

            if ($countItems > 0) {
                throw new Exception("No se puede desvincular: Existen $countItems requerimiento(s) de compra vinculados a esta asignación presupuestaria.");
            }

            $pdo->prepare("DELETE FROM presupuestos_asignados WHERE id = ?")->execute([$asignacion_id]);
            $mensaje = "Asignación presupuestaria eliminada correctamente.";
            $tipo_mensaje = 'success';
            $tab = 'detalle_centro';
            $centro_id = $cc_id;
        }

        // -------------------------------------------------------------
        // C. ACCIONES DE CUENTAS MAESTRAS
        // -------------------------------------------------------------
        elseif ($accion === 'guardar_cuenta') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $codigo = trim($_POST['codigo'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $tipo_cuenta = in_array($_POST['tipo_cuenta'] ?? '', ['PRESUPUESTARIA', 'COMPLEMENTARIA']) ? $_POST['tipo_cuenta'] : 'PRESUPUESTARIA';
            $presupuesto_global = !empty($_POST['presupuesto_global_total']) ? floatval($_POST['presupuesto_global_total']) : 0.0;
            $activo = isset($_POST['activo']) ? 1 : 0;

            if (empty($codigo) || empty($nombre)) {
                throw new Exception("El código y la descripción de la cuenta son obligatorios.");
            }

            if ($id) {
                // Validar duplicado de código en otros registros
                $stmtCheck = $pdo->prepare("SELECT id FROM cuentas_maestras WHERE codigo = ? AND id != ?");
                $stmtCheck->execute([$codigo, $id]);
                if ($stmtCheck->fetch()) throw new Exception("Ya existe otra cuenta con el código '$codigo'.");

                $stmt = $pdo->prepare("UPDATE cuentas_maestras SET codigo = ?, nombre = ?, tipo_cuenta = ?, presupuesto_global_total = ?, activo = ? WHERE id = ?");
                $stmt->execute([$codigo, $nombre, $tipo_cuenta, $presupuesto_global, $activo, $id]);
                $mensaje = "Cuenta \"$codigo - $nombre\" actualizada exitosamente.";
            } else {
                $stmtCheck = $pdo->prepare("SELECT id FROM cuentas_maestras WHERE codigo = ?");
                $stmtCheck->execute([$codigo]);
                if ($stmtCheck->fetch()) throw new Exception("El código de cuenta '$codigo' ya está registrado.");

                $stmt = $pdo->prepare("INSERT INTO cuentas_maestras (codigo, nombre, tipo_cuenta, presupuesto_global_total, activo) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$codigo, $nombre, $tipo_cuenta, $presupuesto_global]);
                $mensaje = "Cuenta \"$codigo - $nombre\" registrada exitosamente.";
            }
            $tipo_mensaje = 'success';
            $tab = 'cuentas';
        }

        elseif ($accion === 'toggle_cuenta') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE cuentas_maestras SET activo = 1 - activo WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Estado de la cuenta actualizado.";
            $tipo_mensaje = 'success';
            $tab = 'cuentas';
        }

        elseif ($accion === 'eliminar_cuenta') {
            $id = (int)$_POST['id'];

            $checkAsig = $pdo->prepare("SELECT COUNT(*) FROM presupuestos_asignados WHERE cuenta_maestra_id = ?");
            $checkAsig->execute([$id]);
            if ($checkAsig->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar: Esta cuenta está distribuida a Centros de Costo. Desactívela en su lugar.");
            }

            $pdo->prepare("DELETE FROM cuentas_maestras WHERE id = ?")->execute([$id]);
            $mensaje = "Cuenta maestra eliminada del catálogo.";
            $tipo_mensaje = 'success';
            $tab = 'cuentas';
        }

        // -------------------------------------------------------------
        // D. ACCIONES DE ÁREAS DE GESTIÓN
        // -------------------------------------------------------------
        elseif ($accion === 'guardar_area') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $codigo = trim($_POST['codigo'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if (empty($codigo) || empty($nombre)) {
                throw new Exception("El código y nombre del Área de Gestión son obligatorios.");
            }

            if ($id) {
                $stmtCheck = $pdo->prepare("SELECT id FROM areas_gestion WHERE codigo = ? AND id != ?");
                $stmtCheck->execute([$codigo, $id]);
                if ($stmtCheck->fetch()) throw new Exception("Ya existe otra Área de Gestión con el código '$codigo'.");

                $stmt = $pdo->prepare("UPDATE areas_gestion SET codigo = ?, nombre = ?, activo = ? WHERE id = ?");
                $stmt->execute([$codigo, $nombre, $activo, $id]);
                $mensaje = "Área de Gestión \"$codigo - $nombre\" actualizada exitosamente.";
            } else {
                $stmtCheck = $pdo->prepare("SELECT id FROM areas_gestion WHERE codigo = ?");
                $stmtCheck->execute([$codigo]);
                if ($stmtCheck->fetch()) throw new Exception("El código de Área '$codigo' ya está registrado.");

                $stmt = $pdo->prepare("INSERT INTO areas_gestion (codigo, nombre, activo) VALUES (?, ?, 1)");
                $stmt->execute([$codigo, $nombre]);
                $mensaje = "Área de Gestión \"$codigo - $nombre\" creada exitosamente.";
            }
            $tipo_mensaje = 'success';
            $tab = 'areas';
        }

        elseif ($accion === 'toggle_area') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE areas_gestion SET activo = 1 - activo WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = "Estado del Área de Gestión actualizado.";
            $tipo_mensaje = 'success';
            $tab = 'areas';
        }

        elseif ($accion === 'eliminar_area') {
            $id = (int)$_POST['id'];

            $checkAsig = $pdo->prepare("SELECT COUNT(*) FROM presupuestos_asignados WHERE area_gestion_id = ?");
            $checkAsig->execute([$id]);
            if ($checkAsig->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar: Esta Área de Gestión tiene asignaciones presupuestarias vinculadas.");
            }

            $pdo->prepare("DELETE FROM areas_gestion WHERE id = ?")->execute([$id]);
            $mensaje = "Área de Gestión eliminada exitosamente.";
            $tipo_mensaje = 'success';
            $tab = 'areas';
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// 4. CONSULTAS DE DATOS Y MÉTRICAS GLOBALES
$kpi_centros = $pdo->query("SELECT COUNT(*) FROM centros_costo WHERE activo = 1")->fetchColumn();
$kpi_cuentas_presup = $pdo->query("SELECT COUNT(*) FROM cuentas_maestras WHERE activo = 1 AND (tipo_cuenta = 'PRESUPUESTARIA' OR tipo_cuenta IS NULL)")->fetchColumn();
$kpi_cuentas_complem = $pdo->query("SELECT COUNT(*) FROM cuentas_maestras WHERE activo = 1 AND tipo_cuenta = 'COMPLEMENTARIA'")->fetchColumn();
$kpi_areas = $pdo->query("SELECT COUNT(*) FROM areas_gestion WHERE activo = 1")->fetchColumn();
$kpi_asignaciones = $pdo->query("SELECT COUNT(*) FROM presupuestos_asignados")->fetchColumn();

// Listado de Centros de Costo con métricas
$centros = $pdo->query("
    SELECT cc.*, 
           COUNT(DISTINCT pa.id) as total_cuentas_asig,
           (SELECT COUNT(DISTINCT ei.id) 
            FROM presupuestos_asignados pa2 
            JOIN expedientes_items ei ON ei.presupuesto_asignado_id = pa2.id 
            WHERE pa2.centro_costo_id = cc.id) as total_items_compra
    FROM centros_costo cc
    LEFT JOIN presupuestos_asignados pa ON pa.centro_costo_id = cc.id
    GROUP BY cc.id
    ORDER BY cc.anio_fiscal DESC, cc.nombre ASC
")->fetchAll();

// Listado de Cuentas Maestras con métricas
$cuentas = $pdo->query("
    SELECT cm.*,
           COALESCE(cm.tipo_cuenta, 'PRESUPUESTARIA') as tipo_cuenta,
           COUNT(DISTINCT pa.centro_costo_id) as total_centros_asig,
           (SELECT COUNT(DISTINCT ei.id)
            FROM presupuestos_asignados pa2
            JOIN expedientes_items ei ON ei.presupuesto_asignado_id = pa2.id
            WHERE pa2.cuenta_maestra_id = cm.id) as total_items_compra
    FROM cuentas_maestras cm
    LEFT JOIN presupuestos_asignados pa ON pa.cuenta_maestra_id = cm.id
    GROUP BY cm.id
    ORDER BY cm.codigo ASC
")->fetchAll();

// Listado de Áreas de Gestión con métricas
$areas = $pdo->query("
    SELECT ag.*,
           COUNT(DISTINCT pa.id) as total_asignaciones,
           (SELECT COUNT(DISTINCT ei.id)
            FROM presupuestos_asignados pa2
            JOIN expedientes_items ei ON ei.presupuesto_asignado_id = pa2.id
            WHERE pa2.area_gestion_id = ag.id) as total_items_compra
    FROM areas_gestion ag
    LEFT JOIN presupuestos_asignados pa ON pa.area_gestion_id = ag.id
    GROUP BY ag.id
    ORDER BY ag.codigo ASC
")->fetchAll();

// Detalle de un Centro de Costo Específico si aplica
$centro_actual = null;
$asignaciones_centro = [];
if ($tab === 'detalle_centro' && $centro_id) {
    $stmtCC = $pdo->prepare("SELECT * FROM centros_costo WHERE id = ?");
    $stmtCC->execute([$centro_id]);
    $centro_actual = $stmtCC->fetch();

    if ($centro_actual) {
        $stmtAsig = $pdo->prepare("
            SELECT pa.id as asignacion_id, pa.centro_costo_id, pa.cuenta_maestra_id, pa.area_gestion_id,
                   cm.codigo as cuenta_codigo, cm.nombre as cuenta_nombre, COALESCE(cm.tipo_cuenta, 'PRESUPUESTARIA') as cuenta_tipo,
                   COALESCE(ag.codigo, 'S/I') as ag_codigo, COALESCE(ag.nombre, 'Sin Imputación') as ag_nombre,
                   (SELECT COUNT(*) FROM expedientes_items ei WHERE ei.presupuesto_asignado_id = pa.id) as items_usados
            FROM presupuestos_asignados pa
            JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
            LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
            WHERE pa.centro_costo_id = ?
            ORDER BY cm.codigo ASC, ag.codigo ASC
        ");
        $stmtAsig->execute([$centro_id]);
        $asignaciones_centro = $stmtAsig->fetchAll();
    }
}

// Cuentas y Áreas activas para los modales de asignación
$catalogo_cuentas_activas = $pdo->query("SELECT id, codigo, nombre, COALESCE(tipo_cuenta, 'PRESUPUESTARIA') as tipo_cuenta FROM cuentas_maestras WHERE activo = 1 ORDER BY codigo ASC")->fetchAll();
$catalogo_areas_activas = $pdo->query("SELECT id, codigo, nombre FROM areas_gestion WHERE activo = 1 ORDER BY codigo ASC")->fetchAll();

function money($v) {
    if ($v === null || $v === '') return '$ 0';
    return '$ ' . number_format((float)$v, 0, ',', '.');
}
