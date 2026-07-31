<?php
// flujos_helper.php - Motor central de transiciones de estado (V5.0 - Dynamic Workflow Engine)

/**
 * Obtiene las transiciones autorizadas para un expediente en su estado actual,
 * filtrando por límites de UTM configurados en la transición.
 */
function obtener_transiciones_disponibles($pdo, $expediente_id) {
    // 1. Obtener estado actual, tipo de compra y rango UTM del expediente
    $stmt = $pdo->prepare("
        SELECT e.tipo_compra_id, e.estado_actual, e.rango_utm_id, r.min_utm, r.max_utm 
        FROM expedientes e
        LEFT JOIN rangos_utm r ON e.rango_utm_id = r.id
        WHERE e.id = ?
    ");
    $stmt->execute([$expediente_id]);
    $exp = $stmt->fetch();
    if (!$exp) return [];

    $estado_actual = $exp['estado_actual'];
    $tipo_compra = $exp['tipo_compra_id'];
    
    // Si no tiene rango_utm asignado, asumimos min_utm = 0
    $exp_min_utm = ($exp['min_utm'] !== null) ? floatval($exp['min_utm']) : 0.0;

    // 2. Cargar todas las transiciones configuradas para este estado y tipo
    $stmtTrans = $pdo->prepare("
        SELECT * FROM flujos_definicion 
        WHERE tipo_compra_id = ? AND estado_actual = ?
    ");
    $stmtTrans->execute([$tipo_compra, $estado_actual]);
    $transiciones = $stmtTrans->fetchAll();

    // 3. Filtrar las transiciones por reglas de UTM si están definidas
    $disponibles = [];
    foreach ($transiciones as $t) {
        $check_min = true;
        $check_max = true;

        if ($t['monto_min_utm'] !== null) {
            $check_min = ($exp_min_utm >= floatval($t['monto_min_utm']));
        }
        if ($t['monto_max_utm'] !== null) {
            $check_max = ($exp_min_utm <= floatval($t['monto_max_utm']));
        }

        if ($check_min && $check_max) {
            $disponibles[] = $t;
        }
    }

    return $disponibles;
}

/**
 * Ejecuta una transición por su ID de definición de flujo.
 */
function ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $transicion_id, $comentario = '') {
    // 1. Buscar la transición
    $stmt = $pdo->prepare("SELECT * FROM flujos_definicion WHERE id = ?");
    $stmt->execute([$transicion_id]);
    $t = $stmt->fetch();
    if (!$t) throw new Exception("Transición no encontrada.");

    // 2. Obtener expediente
    $stmtExp = $pdo->prepare("SELECT estado_actual, codigo_interno FROM expedientes WHERE id = ?");
    $stmtExp->execute([$expediente_id]);
    $exp = $stmtExp->fetch();
    if (!$exp) throw new Exception("Expediente no encontrado.");

    if ($exp['estado_actual'] !== $t['estado_actual']) {
        throw new Exception("El expediente no está en el estado de origen de esta transición.");
    }

    // 3. Validar comentario si es obligatorio
    if ($t['requiere_comentario'] && empty(trim($comentario))) {
        throw new Exception("Esta acción requiere un comentario explicativo obligatorio.");
    }

    $estado_anterior = $exp['estado_actual'];
    $destino = $t['estado_destino'];

    // 4. Ejecutar el avance
    $pdo->prepare("UPDATE expedientes SET estado_actual = ? WHERE id = ?")->execute([$destino, $expediente_id]);

    // 5. Registrar en el historial
    $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$expediente_id, $usuario_id, $t['accion_codigo'], $estado_anterior, $destino, $comentario ?: $t['accion_label']]);

    // Liberar presupuesto si pasa a un estado de cancelación o corrección
    liberar_presupuesto_si_aplica($pdo, $expediente_id, $destino);

    return $destino;
}

/**
 * Avanza el expediente al siguiente estado (Mantenido para compatibilidad).
 */
function avanzar_flujo($pdo, $expediente_id, $usuario_id, $comentario = 'Aprobado y enviado a la siguiente etapa.') {
    $transiciones = obtener_transiciones_disponibles($pdo, $expediente_id);
    
    // Buscar la de tipo APROBAR
    foreach ($transiciones as $t) {
        if ($t['accion_codigo'] === 'APROBAR') {
            return ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $t['id'], $comentario);
        }
    }

    // Fallback si no hay transiciones dinámicas configuradas: lanzar excepción
    throw new Exception("No hay una transición de tipo 'APROBAR' configurada para el estado actual de este expediente.");
}

/**
 * Devuelve el expediente al usuario creador para correcciones (Mantenido para compatibilidad).
 */
function devolver_flujo($pdo, $expediente_id, $usuario_id, $motivo) {
    $transiciones = obtener_transiciones_disponibles($pdo, $expediente_id);
    
    // Buscar la de tipo DEVOLVER
    foreach ($transiciones as $t) {
        if ($t['accion_codigo'] === 'DEVOLVER') {
            return ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $t['id'], $motivo);
        }
    }

    // Fallback si no hay configuración dinámica de devolución:
    $stmtEst = $pdo->prepare("SELECT estado_actual FROM expedientes WHERE id = ?");
    $stmtEst->execute([$expediente_id]);
    $estado_actual = $stmtEst->fetchColumn();
    $pdo->prepare("UPDATE expedientes SET estado_actual = 'EN_CORRECCION' WHERE id = ?")->execute([$expediente_id]);
    $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'DEVOLVER', ?, 'EN_CORRECCION', ?)")
        ->execute([$expediente_id, $usuario_id, $estado_actual, "Devuelto para corrección: " . $motivo]);
    
    liberar_presupuesto_si_aplica($pdo, $expediente_id, 'EN_CORRECCION');
}

/**
 * Rechaza y cierra el ciclo de la solicitud (Mantenido para compatibilidad).
 */
function rechazar_flujo($pdo, $expediente_id, $usuario_id, $motivo) {
    $transiciones = obtener_transiciones_disponibles($pdo, $expediente_id);
    
    // Buscar la de tipo RECHAZAR
    foreach ($transiciones as $t) {
        if ($t['accion_codigo'] === 'RECHAZAR') {
            return ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $t['id'], $motivo);
        }
    }

    // Fallback si no hay configuración dinámica de rechazo:
    $stmtEst = $pdo->prepare("SELECT estado_actual FROM expedientes WHERE id = ?");
    $stmtEst->execute([$expediente_id]);
    $estado_actual = $stmtEst->fetchColumn();
    $pdo->prepare("UPDATE expedientes SET estado_actual = 'RECHAZADO', observacion_cierre = ? WHERE id = ?")->execute([$motivo, $expediente_id]);
    $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'RECHAZAR', ?, 'RECHAZADO', ?)")
        ->execute([$expediente_id, $usuario_id, $estado_actual, "Rechazado definitivamente: " . $motivo]);

    liberar_presupuesto_si_aplica($pdo, $expediente_id, 'RECHAZADO');
}

/**
 * Libera de forma segura los saldos comprometidos si el expediente ya había sido visado.
 */
function liberar_presupuesto_si_aplica($pdo, $expediente_id, $estado_destino) {
    // Funcionalidad de cruce de saldos deshabilitada - el control se maneja externamente
    return;
}
?>