<?php
if (!function_exists('firmagob_base64url_encode')) {
    function firmagob_base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('firmagob_limpiar_run')) {
    function firmagob_limpiar_run($run) {
        $run_limpio = preg_replace('/[^0-9kK]/', '', (string)$run);
        // Si incluye dígito verificador, remover el último caracter
        if (strlen($run_limpio) > 1 && strpos((string)$run, '-') !== false) {
            $run_limpio = substr($run_limpio, 0, -1);
        }
        // Si aún tiene letras (ej K), limpiar
        $run_limpio = preg_replace('/[^0-9]/', '', $run_limpio);
        return $run_limpio;
    }
}

if (!function_exists('firmagob_generar_jwt')) {
    function firmagob_generar_jwt($run, $entity = null, $purpose = null, $secret = null, $minutos_expiracion = 15) {
        $entity = $entity ?: FIRMAGOB_ENTITY;
        $purpose = $purpose ?: FIRMAGOB_PURPOSE;
        $secret = $secret ?: FIRMAGOB_SECRET;
        $run_limpio = firmagob_limpiar_run($run);

        // Fecha en hora chilena formato ISODate YYYY-MM-DDTHH:MM:SS
        $fecha_exp = new DateTime('now', new DateTimeZone('America/Santiago'));
        $fecha_exp->modify("+{$minutos_expiracion} minutes");
        $expiration = $fecha_exp->format('Y-m-d\TH:i:s');

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'entity'     => $entity,
            'run'        => (string)$run_limpio,
            'expiration' => $expiration,
            'purpose'    => $purpose
        ];

        $header_encoded = firmagob_base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payload_encoded = firmagob_base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
        $signature_encoded = firmagob_base64url_encode($signature);

        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
}

/**
 * Genera la configuración de layout XML de AgileSignerConfig para estampar la firma visible en el PDF.
 */
function firmagob_obtener_layout_xml($etapa = 'JEFATURA') {
    // Coordenadas según la etapa en la OPI
    switch (strtoupper($etapa)) {
        case 'JEFATURA':
            // Cuadrante inferior izquierdo
            $llx = 40;  $lly = 50;  $urx = 210; $ury = 130;
            break;
        case 'PRESUPUESTO':
            // Cuadrante inferior central
            $llx = 220; $lly = 50;  $urx = 390; $ury = 130;
            break;
        case 'ADMIN_MUNICIPAL':
        case 'ADMINISTRADOR':
            // Cuadrante inferior derecho
            $llx = 400; $lly = 50;  $urx = 570; $ury = 130;
            break;
        case 'CDP_FINANZAS':
        case 'FINANZAS':
        default:
            // Posición estándar para CDP u otros documentos
            $llx = 350; $lly = 60;  $urx = 550; $ury = 140;
            break;
    }

    $xml = '<AgileSignerConfig>' .
           '<Application id="THIS-CONFIG">' .
           '<pdfPassword/>' .
           '<Signature>' .
           '<Visible active="true" layer2="false" label="true" pos="1">' .
           "<llx>{$llx}</llx>" .
           "<lly>{$lly}</lly>" .
           "<urx>{$urx}</urx>" .
           "<ury>{$ury}</ury>" .
           '<page>LAST</page>' .
           '<image>BASE64</image>' .
           '<BASE64VALUE></BASE64VALUE>' .
           '</Visible>' .
           '</Signature>' .
           '</Application>' .
           '</AgileSignerConfig>';

    return $xml;
}

/**
 * Realiza la llamada HTTP POST a la API FirmaGob para firmar un documento PDF.
 *
 * @param string $ruta_pdf_entrada Ruta absoluta al archivo PDF que se va a firmar.
 * @param string $run_firmante RUN del firmante habilitado en la RA.
 * @param string $descripcion Descripción del documento.
 * @param string|null $otp Código OTP de 6 dígitos (requerido para Firma Atendida).
 * @param string $etapa Etapa de firma ('JEFATURA', 'PRESUPUESTO', 'ADMIN_MUNICIPAL', 'CDP_FINANZAS').
 * @param string|null $purpose Propósito específico si difiere del global.
 * @return array Metadatos y contenido binario firmado.
 * @throws Exception
 */
function firmagob_firmar_archivo($ruta_pdf_entrada, $run_firmante, $descripcion, $otp = null, $etapa = 'JEFATURA', $purpose = null) {
    if (!file_exists($ruta_pdf_entrada)) {
        throw new Exception("El archivo PDF a firmar no existe en la ruta: $ruta_pdf_entrada");
    }

    $filesize = filesize($ruta_pdf_entrada);
    if ($filesize > 5 * 1024 * 1024) {
        throw new Exception("El archivo excede el tamaño máximo permitido por FirmaGob (5 MB). Tamaño actual: " . round($filesize / 1024 / 1024, 2) . " MB");
    }

    $pdf_content = file_get_contents($ruta_pdf_entrada);
    $pdf_base64 = base64_encode($pdf_content);
    $checksum_sha256 = hash('sha256', $pdf_content);

    // MODO SIMULACIÓN LOCAL: Permite pruebas completas mientras se esperan credenciales de Cerfrías
    if (defined('FIRMAGOB_AMBIENTE') && FIRMAGOB_AMBIENTE === 'SIMULADO') {
        return [
            'success'           => true,
            'content_binary'    => $pdf_content,
            'id_solicitud'      => 'SIM_' . strtoupper(uniqid()),
            'checksum_original' => $checksum_sha256,
            'checksum_signed'   => hash('sha256', $pdf_content . microtime()),
            'otp_expired'       => false
        ];
    }

    if (empty(FIRMAGOB_API_TOKEN_KEY) || empty(FIRMAGOB_SECRET)) {
        throw new Exception("FirmaGob no configurado: Por favor configure las variables de entorno FIRMAGOB_API_TOKEN_KEY y FIRMAGOB_SECRET en Dokploy.");
    }

    $purpose_usar = $purpose ?: (
        (FIRMAGOB_MODO === 'DESATENDIDA') ? 'Desatendido' : FIRMAGOB_PURPOSE
    );

    // Generar JWT
    $jwt = firmagob_generar_jwt($run_firmante, FIRMAGOB_ENTITY, $purpose_usar, FIRMAGOB_SECRET);

    // Layout visual de estampa
    $layout_xml = firmagob_obtener_layout_xml($etapa);

    $payload = [
        'token'         => $jwt,
        'api_token_key' => FIRMAGOB_API_TOKEN_KEY,
        'files'         => [
            [
                'content-type' => 'application/pdf',
                'content'      => $pdf_base64,
                'description'  => $descripcion,
                'checksum'     => $checksum_sha256,
                'layout'       => $layout_xml
            ]
        ]
    ];

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    // Si es firma atendida y se proveyó OTP, agregar header
    if (!empty($otp) && FIRMAGOB_MODO !== 'DESATENDIDA') {
        $headers[] = 'OTP: ' . trim($otp);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, FIRMAGOB_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("Error de comunicación de red con FirmaGob: $curl_error");
    }

    $data = json_decode($response_body, true);

    if ($http_code === 200 && isset($data['files'][0])) {
        $file_res = $data['files'][0];
        
        if (isset($file_res['status']) && $file_res['status'] !== 'OK') {
            throw new Exception("FirmaGob rechazó el documento: " . ($file_res['status'] ?? 'Error desconocido'));
        }

        $signed_binary = base64_decode($file_res['content']);
        if (!$signed_binary) {
            throw new Exception("La respuesta de FirmaGob no contiene un archivo firmado válido.");
        }

        return [
            'success'           => true,
            'content_binary'    => $signed_binary,
            'id_solicitud'      => $data['idSolicitud'] ?? null,
            'checksum_original' => $file_res['checksum_original'] ?? $checksum_sha256,
            'checksum_signed'   => $file_res['checksum_signed'] ?? hash('sha256', $signed_binary),
            'otp_expired'       => $data['metadata']['otpExpired'] ?? false
        ];
    }

    // Manejo de Errores específicos según Manual FirmaGob v18
    $msg_error = "Error al conectar con FirmaGob (HTTP $http_code)";
    if (isset($data['error'])) {
        $msg_error = $data['error'];
    } elseif (isset($data['message'])) {
        $msg_error = $data['message'];
    }

    if ($http_code === 400) {
        throw new Exception("FirmaGob (400 - Solicitud inválida): $msg_error");
    } elseif ($http_code === 404) {
        throw new Exception("FirmaGob (404 - Certificado o datos no encontrados): $msg_error. Verifique que el RUN ($run_firmante) y la entidad ('" . FIRMAGOB_ENTITY . "') coincidan exactamente con la RA.");
    } elseif ($http_code === 412) {
        throw new Exception("FirmaGob (412 - Verificación OTP fallida): $msg_error. Por favor revise el código OTP de su aplicación e intente nuevamente.");
    } elseif ($http_code === 429) {
        throw new Exception("FirmaGob (429 - Límite de intentos excedido): Ha excedido el número máximo de intentos de OTP (5 intentos). Su acceso está bloqueado temporalmente por seguridad. Intente más tarde.");
    } elseif (in_array($http_code, [500, 502, 503, 504])) {
        throw new Exception("FirmaGob ($http_code - Servidor no disponible): El servicio de Gobierno Digital está temporalmente no disponible o en mantención. Intente nuevamente en unos minutos o use la opción de subida manual.");
    } else {
        throw new Exception("FirmaGob ($http_code): $msg_error");
    }
}

/**
 * Obtiene los datos del firmante en ejercicio considerando subrogancias activas si aplica.
 */
function firmagob_obtener_firmante_activo($pdo, $rol_codigo, $usuario_sesion_id = null) {
    $hoy = date('Y-m-d');

    // 1. Caso Administrador Municipal (Soporta subrogancia formal)
    if ($rol_codigo === 'ADMIN_MUNICIPAL') {
        $stmtTit = $pdo->prepare("SELECT u.id, u.nombre_completo, u.rut, u.cargo FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'ADMIN_MUNICIPAL' AND u.activo = 1 LIMIT 1");
        $stmtTit->execute();
        $titular = $stmtTit->fetch();

        if ($titular) {
            $stmtSub = $pdo->prepare("SELECT u.id, u.nombre_completo, u.rut, u.cargo FROM subrogancias s JOIN usuarios u ON s.usuario_subrogante_id = u.id WHERE s.usuario_titular_id = ? AND s.activo = 1 AND ? BETWEEN s.fecha_inicio AND s.fecha_fin LIMIT 1");
            $stmtSub->execute([$titular['id'], $hoy]);
            $subrogante = $stmtSub->fetch();

            if ($subrogante) {
                return [
                    'id'              => $subrogante['id'],
                    'nombre'          => $subrogante['nombre_completo'],
                    'nombre_completo' => $subrogante['nombre_completo'],
                    'rut'             => $subrogante['rut'],
                    'cargo'           => $subrogante['cargo'] ?: 'ADMINISTRADOR MUNICIPAL (S)',
                    'es_subrogante'   => true
                ];
            }

            return [
                'id'              => $titular['id'],
                'nombre'          => $titular['nombre_completo'],
                'nombre_completo' => $titular['nombre_completo'],
                'rut'             => $titular['rut'],
                'cargo'           => $titular['cargo'] ?: 'ADMINISTRADOR MUNICIPAL',
                'es_subrogante'   => false
            ];
        }
    }

    // 2. Otros roles (Usuario en sesión)
    if ($usuario_sesion_id) {
        $stmtU = $pdo->prepare("SELECT id, nombre_completo, rut, cargo FROM usuarios WHERE id = ?");
        $stmtU->execute([$usuario_sesion_id]);
        $usr = $stmtU->fetch();
        if ($usr) {
            return [
                'id'              => $usr['id'],
                'nombre'          => $usr['nombre_completo'],
                'nombre_completo' => $usr['nombre_completo'],
                'rut'             => $usr['rut'],
                'cargo'           => $usr['cargo'] ?: $rol_codigo,
                'es_subrogante'   => false
            ];
        }
    }

    return null;
}
