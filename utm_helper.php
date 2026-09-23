<?php
// utm_helper.php - Servicio de Sincronización y Cálculo de UTM (Mindicador.cl)

/**
 * Obtiene el valor vigente de la UTM consultando la API pública de mindicador.cl
 * y sincronizándolo en la base de datos si ha cambiado de mes o si aún no existe.
 * 
 * En caso de fallo de red o caída de la API externa, utiliza el valor respaldado en la BD
 * garantizando cero interrupciones de servicio.
 *
 * @param PDO $pdo Instancia de base de datos
 * @param bool $forzar Si es true, fuerza la consulta a la API ignorando el caché mensual
 * @return float Valor de la UTM en CLP
 */
function sincronizar_valor_utm($pdo, $forzar = false) {
    $mes_actual = date('Y-m');
    $valor_utm = 0.0;
    $mes_guardado = '';

    // 1. Consultar valores actuales en configuraciones_sistema
    try {
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuraciones_sistema WHERE clave IN ('valor_utm', 'valor_utm_mes', 'valor_utm_actualizado_el')");
        $stmt->execute();
        $cfgs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cfgs[$row['clave']] = $row['valor'];
        }

        if (!empty($cfgs['valor_utm'])) {
            $valor_utm = floatval($cfgs['valor_utm']);
        }
        if (!empty($cfgs['valor_utm_mes'])) {
            $mes_guardado = trim($cfgs['valor_utm_mes']);
        }
    } catch (Exception $e) {
        error_log("utm_helper: Error al leer configuraciones_sistema: " . $e->getMessage());
    }

    // 2. Si ya está sincronizado para el mes actual y no se fuerza, retornar el valor en BD
    if (!$forzar && $valor_utm > 0 && $mes_guardado === $mes_actual) {
        return $valor_utm;
    }

    // 3. Consultar la API pública de mindicador.cl
    $nuevo_valor = null;
    $fecha_indicador = null;

    try {
        $url = 'https://mindicador.cl/api/utm';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout defensivo de 3 segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OPIv2-Municipalidad/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && !empty($response)) {
            $data = json_decode($response, true);
            if (isset($data['serie']) && is_array($data['serie']) && count($data['serie']) > 0) {
                // El primer elemento corresponde al valor más reciente
                $nuevo_valor = floatval($data['serie'][0]['valor'] ?? 0);
                $fecha_indicador = $data['serie'][0]['fecha'] ?? date('Y-m-d');
            }
        } else {
            error_log("utm_helper: No se pudo contactar mindicador.cl (HTTP $http_code). Detalle: $curl_error");
        }
    } catch (Exception $e) {
        error_log("utm_helper: Excepción al consultar mindicador.cl: " . $e->getMessage());
    }

    // 4. Si se obtuvo un valor válido desde la API, actualizar en BD
    if ($nuevo_valor !== null && $nuevo_valor > 0) {
        try {
            $stmtUpsert = $pdo->prepare("
                INSERT INTO configuraciones_sistema (clave, valor) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");
            
            $stmtUpsert->execute(['valor_utm', (string)$nuevo_valor]);
            $stmtUpsert->execute(['valor_utm_mes', $mes_actual]);
            $stmtUpsert->execute(['valor_utm_actualizado_el', date('Y-m-d H:i:s')]);
            $stmtUpsert->execute(['valor_utm_fuente', 'https://mindicador.cl/api/utm']);

            return $nuevo_valor;
        } catch (Exception $e) {
            error_log("utm_helper: Error al actualizar valor_utm en BD: " . $e->getMessage());
            return $nuevo_valor; // Retornar el valor obtenido aunque falle el guardado
        }
    }

    // 5. Fallback: Si la API falló, usar valor previo o valor por defecto histórico (66000)
    return ($valor_utm > 0) ? $valor_utm : 66000.0;
}

/**
 * Determina automáticamente el rango UTM correspondiente a un monto neto en CLP
 *
 * @param PDO $pdo
 * @param float $monto_neto
 * @param float $valor_utm
 * @return array ['rango_id' => int, 'rango_nombre' => string, 'monto_utm' => float]
 */
function calcular_rango_utm_automatico($pdo, $monto_neto, $valor_utm) {
    $valor_utm = max(1, floatval($valor_utm));
    $monto_utm = round(floatval($monto_neto) / $valor_utm, 2);

    $stmt = $pdo->query("SELECT id, nombre, min_utm, max_utm FROM rangos_utm WHERE activo = 1 ORDER BY min_utm ASC");
    $rangos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rango_seleccionado = null;
    foreach ($rangos as $r) {
        $min = floatval($r['min_utm']);
        $max = ($r['max_utm'] !== null) ? floatval($r['max_utm']) : 999999999.0;

        if ($monto_utm >= $min && $monto_utm <= $max) {
            $rango_seleccionado = $r;
            break;
        }
    }

    if (!$rango_seleccionado && count($rangos) > 0) {
        // Si excede el máximo, asignar el último rango
        $rango_seleccionado = end($rangos);
    }

    return [
        'rango_id' => $rango_seleccionado ? (int)$rango_seleccionado['id'] : null,
        'rango_nombre' => $rango_seleccionado ? $rango_seleccionado['nombre'] : 'Sin Rango',
        'monto_utm' => $monto_utm
    ];
}
