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
 * Avanza el expediente al siguiente estado de progresión positiva.
 */
function avanzar_flujo($pdo, $expediente_id, $usuario_id, $comentario = 'Aprobado y enviado a la siguiente etapa.', $accion_codigo = null) {
    $transiciones = obtener_transiciones_disponibles($pdo, $expediente_id);
    
    // 1. Si se especificó un código de acción exacto, buscarlo
    if ($accion_codigo) {
        foreach ($transiciones as $t) {
            if ($t['accion_codigo'] === $accion_codigo) {
                return ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $t['id'], $comentario);
            }
        }
    }

    // 2. Buscar transición estándar APROBAR
    foreach ($transiciones as $t) {
        if ($t['accion_codigo'] === 'APROBAR') {
            return ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $t['id'], $comentario);
        }
    }

    // 3. Fallback: buscar la primera acción de avance (no destructiva / no retroceso)
    foreach ($transiciones as $t) {
        if (!in_array($t['accion_codigo'], ['DEVOLVER', 'RECHAZAR', 'ANULAR'])) {
            return ejecutar_transicion_por_id($pdo, $expediente_id, $usuario_id, $t['id'], $comentario);
        }
    }

    // Fallback si no hay transiciones dinámicas configuradas: lanzar excepción
    throw new Exception("No hay una transición de avance configurada para el estado actual de este expediente.");
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

/**
 * =====================================================================
 * GESTIÓN DE MULTI-AUTORIZACIÓN PARALELA INTER-CENTROS DE COSTO
 * =====================================================================
 */

/**
 * Obtiene la unidad municipal responsable/titular de un Centro de Costo.
 */
function obtener_unidad_responsable_cc($pdo, $centro_costo_id) {
    if (!$centro_costo_id) return 1;
    $stmt = $pdo->prepare("SELECT id FROM unidades WHERE centro_costo_id = ? ORDER BY padre_id ASC LIMIT 1");
    $stmt->execute([$centro_costo_id]);
    $uid = $stmt->fetchColumn();
    return $uid ? (int)$uid : 1; // Fallback a unidad principal si no está asignada
}

/**
 * Genera o actualiza los registros de autorización requeridos (Unidad de Origen y CCs Externos).
 * Retorna true si todas las autorizaciones requeridas están aprobadas (ej: creador es jefe sin CCs externos).
 */
function generar_autorizaciones_cc_expediente($pdo, $expediente_id, $unidad_origen_id, $centro_costo_origen_id, $items, $es_jefe_creador = false, $creador_id = null) {
    // 1. Agrupar montos totales por centro de costo a partir de los ítems
    $montos_por_cc = [];
    if (!empty($items)) {
        foreach ($items as $it) {
            $pa_id = $it['presupuesto_asignado_id'] ?? ($it['cuenta_id'] ?? null);
            $cant = floatval($it['cantidad'] ?? ($it['cant'] ?? 1));
            $prec = floatval($it['precio_unitario'] ?? ($it['prec'] ?? 0));
            $total_linea = $cant * $prec;
            
            $cc_id = $centro_costo_origen_id;
            if ($pa_id) {
                $stmtPA = $pdo->prepare("SELECT centro_costo_id FROM presupuestos_asignados WHERE id = ?");
                $stmtPA->execute([$pa_id]);
                $found_cc = $stmtPA->fetchColumn();
                if ($found_cc) $cc_id = (int)$found_cc;
            }
            if (!isset($montos_por_cc[$cc_id])) $montos_por_cc[$cc_id] = 0;
            $montos_por_cc[$cc_id] += $total_linea;
        }
    }

    // 2. Limpiar autorizaciones previas para re-evaluación limpia
    $pdo->prepare("DELETE FROM expedientes_autorizaciones_cc WHERE expediente_id = ?")->execute([$expediente_id]);

    $stmtIns = $pdo->prepare("
        INSERT INTO expedientes_autorizaciones_cc 
        (expediente_id, tipo_autorizacion, centro_costo_id, unidad_responsable_id, monto_imputado, estado, visado_por_id, fecha_visacion, comentario) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // 3. Autorización de la Unidad de Origen (Jefatura Requirente)
    $monto_origen = $montos_por_cc[$centro_costo_origen_id] ?? 0;
    $estado_origen = $es_jefe_creador ? 'APROBADO' : 'PENDIENTE';
    $visado_por = $es_jefe_creador ? $creador_id : null;
    $fecha_visacion = $es_jefe_creador ? date('Y-m-d H:i:s') : null;
    $comentario_origen = $es_jefe_creador ? 'Aprobado automáticamente al crear la solicitud como Jefatura de Unidad.' : null;

    $stmtIns->execute([
        $expediente_id,
        'UNIDAD_ORIGEN',
        $centro_costo_origen_id,
        $unidad_origen_id,
        $monto_origen,
        $estado_origen,
        $visado_por,
        $fecha_visacion,
        $comentario_origen
    ]);

    // 4. Autorizaciones de Centros de Costos Externos
    $tiene_externos_pendientes = false;
    foreach ($montos_por_cc as $cc_id => $monto) {
        if ($cc_id != $centro_costo_origen_id) {
            $unidad_ext = obtener_unidad_responsable_cc($pdo, $cc_id);
            $stmtIns->execute([
                $expediente_id,
                'CENTRO_COSTO_EXTERNO',
                $cc_id,
                $unidad_ext,
                $monto,
                'PENDIENTE',
                null,
                null,
                null
            ]);
            $tiene_externos_pendientes = true;
        }
    }

    return ($es_jefe_creador && !$tiene_externos_pendientes);
}

/**
 * Obtiene todas las autorizaciones inter-CC configuradas para un expediente.
 */
function obtener_autorizaciones_expediente($pdo, $expediente_id) {
    $stmt = $pdo->prepare("
        SELECT 
            ac.*,
            COALESCE(cc.codigo_cuenta, '') as cc_codigo,
            COALESCE(cc.nombre, 'Centro de Costos') as cc_nombre,
            COALESCE(un.nombre, 'Unidad Responsable') as unidad_nombre,
            u.nombre_completo as visador_nombre,
            u.cargo as visador_cargo
        FROM expedientes_autorizaciones_cc ac
        LEFT JOIN centros_costo cc ON ac.centro_costo_id = cc.id
        LEFT JOIN unidades un ON ac.unidad_responsable_id = un.id
        LEFT JOIN usuarios u ON ac.visado_por_id = u.id
        WHERE ac.expediente_id = ?
        ORDER BY ac.tipo_autorizacion DESC, ac.id ASC
    ");
    $stmt->execute([$expediente_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Procesa la acción de una Jefatura (Requirente o Cedente de Fondos) en el esquema paralelo.
 */
function procesar_autorizacion_paralela_jefatura($pdo, $expediente_id, $usuario_id, $unidad_usuario_id, $rol_usuario, $accion, $comentario = '') {
    $stmtExp = $pdo->prepare("SELECT e.*, un.nombre as unidad_origen_nombre FROM expedientes e JOIN unidades un ON e.unidad_origen_id = un.id WHERE e.id = ?");
    $stmtExp->execute([$expediente_id]);
    $exp = $stmtExp->fetch();
    if (!$exp) throw new Exception("Expediente no encontrado.");

    $autorizaciones = obtener_autorizaciones_expediente($pdo, $expediente_id);
    
    // Identificar qué autorizaciones le corresponden visar a este usuario
    $matching_ids = [];
    $es_admin = in_array($rol_usuario, ['ADMIN_MUNICIPAL', 'SYSADMIN']);

    foreach ($autorizaciones as $aut) {
        if ($aut['estado'] === 'PENDIENTE') {
            if ($es_admin || $aut['unidad_responsable_id'] == $unidad_usuario_id) {
                $matching_ids[] = $aut['id'];
            }
        }
    }

    if (empty($matching_ids) && !$es_admin) {
        throw new Exception("No tiene autorizaciones pendientes asignadas para este expediente.");
    }

    // A. DEVOLVER A CORRECCIÓN
    if ($accion === 'devolver') {
        $pdo->prepare("UPDATE expedientes_autorizaciones_cc SET estado = 'DEVUELTO', visado_por_id = ?, fecha_visacion = NOW(), comentario = ? WHERE id IN (" . implode(',', $matching_ids) . ")")
            ->execute([$usuario_id, $comentario]);
        
        devolver_flujo($pdo, $expediente_id, $usuario_id, $comentario);
        return 'DEVUELTO';
    }

    // B. RECHAZAR DEFINITIVAMENTE
    if ($accion === 'rechazar') {
        $pdo->prepare("UPDATE expedientes_autorizaciones_cc SET estado = 'RECHAZADO', visado_por_id = ?, fecha_visacion = NOW(), comentario = ? WHERE id IN (" . implode(',', $matching_ids) . ")")
            ->execute([$usuario_id, $comentario]);
        
        rechazar_flujo($pdo, $expediente_id, $usuario_id, $comentario);
        return 'RECHAZADO';
    }

    // C. APROBAR AUTORIZACIÓN
    if ($accion === 'aprobar' || $accion === 'visar') {
        $pdo->prepare("UPDATE expedientes_autorizaciones_cc SET estado = 'APROBADO', visado_por_id = ?, fecha_visacion = NOW(), comentario = ? WHERE id IN (" . implode(',', $matching_ids) . ")")
            ->execute([$usuario_id, $comentario ?: 'Autorización de fondos visada correctamente.']);

        // Registrar en historial el V°B° específico
        foreach ($matching_ids as $mid) {
            $rowAut = null;
            foreach ($autorizaciones as $a) { if ($a['id'] == $mid) { $rowAut = $a; break; } }
            $tipo_label = ($rowAut && $rowAut['tipo_autorizacion'] === 'UNIDAD_ORIGEN') ? 'V°B° Jefatura Requirente' : "Autorización Fondos CC ({$rowAut['cc_nombre']})";
            $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'AUTORIZAR_CC', 'EN_REVISION_JEFATURA', 'EN_REVISION_JEFATURA', ?)")
                ->execute([$expediente_id, $usuario_id, "$tipo_label aprobada. " . ($comentario ? "Observación: $comentario" : '')]);
        }

        // Verificar si TODAS las autorizaciones del expediente están APROBADAS
        $stmtCheckAll = $pdo->prepare("SELECT COUNT(*) FROM expedientes_autorizaciones_cc WHERE expediente_id = ? AND estado != 'APROBADO'");
        $stmtCheckAll->execute([$expediente_id]);
        $pendientes_restantes = (int)$stmtCheckAll->fetchColumn();

        if ($pendientes_restantes === 0) {
            // Todas las autorizaciones paralelas están listas -> Avanzar a Presupuesto
            avanzar_flujo($pdo, $expediente_id, $usuario_id, "Todas las autorizaciones de Jefatura y Centros de Costos completadas. Enviado a Control Presupuestario.");
            return 'COMPLETADO_AVANZADO';
        } else {
            // Aún quedan otras jefaturas pendientes
            return 'PARCIAL_PENDIENTE';
        }
    }

    throw new Exception("Acción no reconocida.");
}
?>