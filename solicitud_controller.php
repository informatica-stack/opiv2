<?php
// solicitud_controller.php
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD DE SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Variables de Sesión
$user_id = $_SESSION['user_id'];
$unidad_id = $_SESSION['user_unidad'];
$es_jefe = $_SESSION['es_jefe'] ?? 0;

// Variables para la Vista
$mensaje = '';
$tipo_mensaje = '';
$centro_costo = null;

// 2. FUNCIÓN DE NEGOCIO: Detectar Presupuesto Recursivo
function obtenerPresupuestoUnidad($pdo, $unidad_id) {
    $current = $unidad_id;
    while ($current) {
        // Buscamos la unidad actual
        $stmt = $pdo->prepare("SELECT id, padre_id, centro_costo_id, nombre FROM unidades WHERE id = ?");
        $stmt->execute([$current]);
        $u = $stmt->fetch();
        
        // Si tiene centro de costo propio, devolvemos ese
        if ($u && $u['centro_costo_id']) {
            $stmtCC = $pdo->prepare("SELECT * FROM centros_costo WHERE id = ?");
            $stmtCC->execute([$u['centro_costo_id']]);
            return $stmtCC->fetch();
        }
        
        // Si no, subimos al padre
        $current = $u['padre_id'] ?? null;
    }
    return null; // Llegamos a la raíz y nadie paga
}

// Cargar el Centro de Costo al iniciar
$centro_costo = obtenerPresupuestoUnidad($pdo, $unidad_id);


// 3. PROCESAMIENTO DEL FORMULARIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_solicitud'])) {
    try {
        if (!$centro_costo) {
            throw new Exception("Error Crítico: Su unidad no tiene una cuenta presupuestaria asignada.");
        }

        $pdo->beginTransaction();

        // A. Preparar Datos del Encabezado
        $motivo = trim($_POST['motivo']);
        $tipo = $_POST['tipo_compra'];
        $prioridad = $_POST['prioridad'];
        
        // B. Validación y Cálculo Backend (No confiar en el JS)
        $total_estimado = 0;
        $cantidades = $_POST['cant'] ?? [];
        $precios = $_POST['prec'] ?? [];
        $descripciones = $_POST['desc'] ?? [];
        $unidades_medida = $_POST['uni'] ?? [];

        if (empty($descripciones)) throw new Exception("Debe agregar al menos un ítem.");

        for ($i = 0; $i < count($cantidades); $i++) {
            $c = floatval($cantidades[$i]);
            $p = floatval($precios[$i]);
            $total_estimado += ($c * $p);
        }

        // C. Determinar Estado Inicial
        // Si es Jefe, salta 'EN_REVISION_JEFATURA' y va directo a 'EN_VALIDACION_PRESUPUESTARIA'
        $estado_inicial = ($es_jefe == 1) ? ESTADO_VAL_PRESUPUESTO : ESTADO_REV_JEFE;
        
        // Código Interno Temporal
        $codigo = "REQ-" . date('Y') . "-" . time(); 

        // D. Insertar Expediente
        $sqlExp = "INSERT INTO expedientes 
                   (codigo_interno, usuario_creador_id, unidad_origen_id, centro_costo_id, tipo_compra, prioridad, estado_actual, monto_estimado, motivo_compra)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sqlExp);
        $stmt->execute([
            $codigo, $user_id, $unidad_id, $centro_costo['id'], 
            $tipo, $prioridad, $estado_inicial, $total_estimado, $motivo
        ]);
        $exp_id = $pdo->lastInsertId();

        // E. Insertar Items
        $sqlItem = "INSERT INTO expedientes_items (expediente_id, descripcion, unidad_medida, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)";
        $stmtItem = $pdo->prepare($sqlItem);

        for ($i = 0; $i < count($descripciones); $i++) {
            if (!empty($descripciones[$i])) {
                $stmtItem->execute([
                    $exp_id,
                    strip_tags($descripciones[$i]), // Sanitizar
                    $unidades_medida[$i],
                    floatval($cantidades[$i]),
                    floatval($precios[$i])
                ]);
            }
        }

        // F. Guardar Historial
        $comentario = ($es_jefe == 1) 
            ? "Solicitud creada. Autovisada por Jefatura." 
            : "Solicitud creada. Enviada a revisión de jefatura.";
            
        $sqlHist = "INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_nuevo, comentario) VALUES (?, ?, 'CREAR', ?, ?)";
        $pdo->prepare($sqlHist)->execute([$exp_id, $user_id, $estado_inicial, $comentario]);

        $pdo->commit();
        
        // Redirección con éxito
        header("Location: usuario.php?msg=" . urlencode("Solicitud #$codigo creada correctamente.") . "&type=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error al procesar: " . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}
?>