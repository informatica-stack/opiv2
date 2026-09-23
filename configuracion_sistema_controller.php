<?php
// configuracion_sistema_controller.php - Controlador de Parámetros Globales y Rangos UTM
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utm_helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    die("Acceso Denegado. Módulo exclusivo para Administración del Sistema.");
}

// Asegurar existencia de la tabla configuraciones_sistema
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `configuraciones_sistema` (
        `clave` varchar(50) NOT NULL,
        `valor` text DEFAULT NULL,
        PRIMARY KEY (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    error_log("Error al verificar/crear tabla configuraciones_sistema: " . $e->getMessage());
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar_parametros';

    // 1. ACCIÓN: SINCRONIZAR UTM DESDE MINDICADOR.CL
    if ($accion === 'sincronizar_utm') {
        try {
            $nuevo_valor = sincronizar_valor_utm($pdo, true);
            if ($nuevo_valor > 0) {
                $mensaje = "UTM sincronizada exitosamente desde Mindicador.cl: $" . number_format($nuevo_valor, 0, ',', '.') . " CLP para el mes " . date('Y-m') . ".";
                $tipo_mensaje = "success";
            } else {
                throw new Exception("No se pudo obtener el valor desde Mindicador.cl. Se mantuvo el valor anterior.");
            }
        } catch (Exception $e) {
            $mensaje = "Error al sincronizar UTM: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // 2. ACCIÓN: GUARDAR O EDITAR RANGO UTM (Art. 10)
    elseif ($accion === 'guardar_rango') {
        try {
            $rango_id = !empty($_POST['rango_id']) ? intval($_POST['rango_id']) : 0;
            $nombre = trim($_POST['rango_nombre'] ?? '');
            $min_utm = floatval($_POST['rango_min_utm'] ?? 0);
            $max_raw = trim($_POST['rango_max_utm'] ?? '');
            $max_utm = ($max_raw !== '' && is_numeric($max_raw)) ? floatval($max_raw) : null;
            $regla = trim($_POST['rango_regla'] ?? '');
            $activo = isset($_POST['rango_activo']) ? 1 : 0;

            if (empty($nombre)) {
                throw new Exception("El nombre del rango es obligatorio.");
            }
            if ($min_utm < 0) {
                throw new Exception("El valor mínimo en UTM no puede ser negativo.");
            }
            if ($max_utm !== null && $max_utm <= $min_utm) {
                throw new Exception("El valor máximo en UTM debe ser estrictamente mayor al valor mínimo.");
            }

            if ($rango_id > 0) {
                $stmtUpd = $pdo->prepare("UPDATE rangos_utm SET nombre = ?, min_utm = ?, max_utm = ?, regla_cotizaciones = ?, activo = ? WHERE id = ?");
                $stmtUpd->execute([$nombre, $min_utm, $max_utm, $regla, $activo, $rango_id]);
                $mensaje = "Rango de compra '$nombre' actualizado exitosamente.";
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO rangos_utm (nombre, min_utm, max_utm, regla_cotizaciones, activo) VALUES (?, ?, ?, ?, ?)");
                $stmtIns->execute([$nombre, $min_utm, $max_utm, $regla, $activo]);
                $mensaje = "Nuevo rango de compra '$nombre' registrado exitosamente.";
            }
            $tipo_mensaje = "success";

        } catch (Exception $e) {
            $mensaje = "Error al guardar el rango UTM: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // 3. ACCIÓN: ALTERNAR ESTADO (ACTIVO/INACTIVO) DE UN RANGO
    elseif ($accion === 'toggle_rango') {
        try {
            $rango_id = intval($_POST['rango_id'] ?? 0);
            if ($rango_id <= 0) throw new Exception("ID de rango inválido.");

            $stmtToggle = $pdo->prepare("UPDATE rangos_utm SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmtToggle->execute([$rango_id]);
            $mensaje = "Estado del rango actualizado exitosamente.";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al cambiar estado del rango: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // 4. ACCIÓN: ELIMINAR RANGO PERMANENTEMENTE
    elseif ($accion === 'eliminar_rango') {
        try {
            $rango_id = intval($_POST['rango_id'] ?? 0);
            if ($rango_id <= 0) throw new Exception("ID de rango inválido.");

            // Desvincular de expedientes históricos si estuviese asignado
            $stmtUnlink = $pdo->prepare("UPDATE expedientes SET rango_utm_id = NULL WHERE rango_utm_id = ?");
            $stmtUnlink->execute([$rango_id]);

            // Eliminar de rangos_utm
            $stmtDel = $pdo->prepare("DELETE FROM rangos_utm WHERE id = ?");
            $stmtDel->execute([$rango_id]);

            $mensaje = "Rango de compra eliminado permanentemente de la base de datos.";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al eliminar el rango: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // 5. ACCIÓN POR DEFECTO: GUARDAR PARÁMETROS GLOBALES DEL SISTEMA
    else {
        try {
            $limite_peso = max(1, min(200, intval($_POST['limite_peso_adjunto_mb'] ?? 10)));
            $valor_utm = max(1, floatval($_POST['valor_utm'] ?? 66000));
            $modo_mant = ($_POST['modo_mantenimiento'] ?? '0') === '1' ? '1' : '0';
            $ext_input = trim($_POST['extensiones_permitidas'] ?? 'pdf,zip,rar,doc,docx,xls,xlsx,jpg,jpeg,png');

            // Limpiar lista de extensiones (quitar puntos, espacios y convertir a minúsculas)
            $ext_array = array_filter(array_map(function($item) {
                return strtolower(ltrim(trim($item), '.'));
            }, explode(',', $ext_input)));
            $ext_clean = implode(',', array_unique($ext_array));

            $modo_firmagob = (strtoupper(trim($_POST['firmagob_modo'] ?? 'ATENDIDA')) === 'DESATENDIDA') ? 'DESATENDIDA' : 'ATENDIDA';
            $entity_firmagob = trim($_POST['firmagob_entity'] ?? 'Ilustre Municipalidad de Lebu');
            $purpose_firmagob = trim($_POST['firmagob_purpose'] ?? 'Propósito General');
            $ambiente_firmagob = (strtoupper(trim($_POST['firmagob_ambiente'] ?? 'PRODUCCION')) === 'CERTIFICACION') ? 'CERTIFICACION' : 'PRODUCCION';
            $api_url_firmagob = ($ambiente_firmagob === 'CERTIFICACION') 
                ? 'https://api.firma.cert.digital.gob.cl/firma/v2/files/tickets' 
                : 'https://api.firma.digital.gob.cl/firma/v2/files/tickets';

            $params = [
                'limite_peso_adjunto_mb' => (string)$limite_peso,
                'valor_utm'              => (string)$valor_utm,
                'modo_mantenimiento'     => $modo_mant,
                'extensiones_permitidas' => $ext_clean,
                'firmagob_modo'          => $modo_firmagob,
                'firmagob_entity'        => $entity_firmagob,
                'firmagob_purpose'       => $purpose_firmagob,
                'firmagob_ambiente'      => $ambiente_firmagob,
                'firmagob_api_url'       => $api_url_firmagob
            ];

            $stmtSave = $pdo->prepare("INSERT INTO configuraciones_sistema (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($params as $k => $v) {
                $stmtSave->execute([$k, $v]);
            }

            $mensaje = "Configuraciones del sistema y parámetros actualizados exitosamente.";
            $tipo_mensaje = "success";

        } catch (Exception $e) {
            $mensaje = "Error al guardar las configuraciones: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Cargar configuraciones actuales
$configs = [
    'limite_peso_adjunto_mb'    => '10',
    'valor_utm'                 => '66000',
    'valor_utm_mes'             => date('Y-m'),
    'valor_utm_actualizado_el'  => '',
    'valor_utm_fuente'          => 'https://mindicador.cl/api/utm',
    'modo_mantenimiento'        => '0',
    'extensiones_permitidas'    => 'pdf,zip,rar,doc,docx,xls,xlsx,jpg,jpeg,png',
    'firmagob_modo'             => 'ATENDIDA',
    'firmagob_entity'           => 'Ilustre Municipalidad de Lebu',
    'firmagob_purpose'          => 'Propósito General',
    'firmagob_ambiente'         => 'PRODUCCION',
    'firmagob_api_url'          => 'https://api.firma.digital.gob.cl/firma/v2/files/tickets'
];

try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuraciones_sistema");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['valor'] !== null && $row['valor'] !== '') {
            $configs[$row['clave']] = $row['valor'];
        }
    }
} catch (Exception $e) {
    // Usar valores predeterminados en caso de error
}

// Cargar listado de Rangos UTM
try {
    $stmtRangos = $pdo->query("SELECT * FROM rangos_utm ORDER BY min_utm ASC");
    $listado_rangos_utm = $stmtRangos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listado_rangos_utm = [];
}
