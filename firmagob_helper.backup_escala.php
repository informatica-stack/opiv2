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
    function firmagob_generar_jwt($run, $entity = null, $purpose = null, $secret = null, $minutos_expiracion = 15, $decodificar_base64 = false) {
        $entity = $entity ?: FIRMAGOB_ENTITY;
        $purpose = $purpose ?: FIRMAGOB_PURPOSE;
        $secret = trim((string)($secret ?: FIRMAGOB_SECRET));
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

        // Clave HMAC: Por defecto FirmaGob utiliza la clave en string UTF-8 directo.
        if ($decodificar_base64) {
            $secret_key = base64_decode($secret, true) ?: $secret;
        } else {
            $secret_key = $secret;
        }

        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret_key, true);
        $signature_encoded = firmagob_base64url_encode($signature);

        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
}

if (!function_exists('firmagob_formatear_run')) {
    /**
     * Formatea un RUN chileno con puntos y guion (ej: 17.439.829-1)
     */
    function firmagob_formatear_run($run) {
        $run_limpio = preg_replace('/[^0-9kK]/', '', (string)$run);
        if (strlen($run_limpio) < 2) return (string)$run;
        $dv = strtoupper(substr($run_limpio, -1));
        $cuerpo = substr($run_limpio, 0, -1);
        return number_format((int)$cuerpo, 0, '', '.') . '-' . $dv;
    }
}

if (!function_exists('firmagob_generar_estampa_dinamica_base64')) {
    /**
     * Genera una estampa visual institucional de alta resolución (HD) en memoria RAM usando PHP GD.
     * Soporta fuentes TrueType con anti-aliasing, paletas cromáticas institucionales, iconos y metadatos configurables.
     *
     * @param string $nombre Nombre del firmante
     * @param string $run RUN del firmante
     * @param string $cargo Cargo o rol del firmante
     * @param string|null $fecha_hora Fecha y hora en formato string (opcional)
     * @param array $opciones_custom Opciones de personalización dinámicas (opcional)
     * @return string Imagen PNG codificada en Base64
     */
    function firmagob_generar_estampa_dinamica_base64($nombre, $run, $cargo = '', $fecha_hora = null, $opciones_custom = []) {
        if (!extension_loaded('gd')) {
            return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        }

        // Si el 4to argumento se pasó como array de opciones personalizadas
        if (is_array($fecha_hora)) {
            $opciones_custom = $fecha_hora;
            $fecha_hora = null;
        }

        // Cargar configuraciones del sistema si no están presentes
        global $config_sistema, $pdo;
        if (!isset($config_sistema) || empty($config_sistema)) {
            try {
                if (isset($pdo)) {
                    $stmtCfg = $pdo->query("SELECT clave, valor FROM configuraciones_sistema WHERE clave LIKE 'estampa_%'");
                    $cfg_db = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);
                    $config_sistema = array_merge($config_sistema ?? [], $cfg_db);
                }
            } catch (Exception $e) {}
        }

        $cfg = array_merge($config_sistema ?? [], $opciones_custom ?? []);

        // Parámetros de diseño
        $tema            = $cfg['estampa_tema'] ?? 'azul_institucional';
        $fuente_id       = $cfg['estampa_fuente'] ?? 'segoeui';
        $titulo          = $cfg['estampa_titulo_texto'] ?? 'FIRMADO ELECTRÓNICAMENTE (FEA)';
        $icono           = $cfg['estampa_icono'] ?? 'check';
        $mostrar_run     = ($cfg['estampa_mostrar_run'] ?? '1') === '1';
        $mostrar_cargo   = ($cfg['estampa_mostrar_cargo'] ?? '1') === '1';
        $mostrar_fecha   = ($cfg['estampa_mostrar_fecha'] ?? '1') === '1';
        $mostrar_entidad = ($cfg['estampa_mostrar_entidad'] ?? '1') === '1';
        $mostrar_ley     = ($cfg['estampa_mostrar_ley'] ?? '1') === '1';
        $entidad_nombre  = defined('FIRMAGOB_ENTITY') ? FIRMAGOB_ENTITY : 'Ilustre Municipalidad de Lebu';

        // Dimensiones en alta resolución 2x para nitidez vectorial (760x220 px reescalado a 380x110 pt)
        $width  = 760;
        $height = 220;
        $im = imagecreatetruecolor($width, $height);

        // Paletas cromáticas profesionales
        $paletas = [
            'azul_institucional' => [
                'bg'       => [255, 255, 255],
                'border'   => [37, 99, 235],
                'hdr_bg'   => [239, 246, 255],
                'hdr_text' => [29, 78, 216],
                'name'     => [15, 23, 42],
                'meta'     => [51, 65, 85],
                'muted'    => [100, 116, 139]
            ],
            'verde_validacion' => [
                'bg'       => [255, 255, 255],
                'border'   => [5, 150, 105],
                'hdr_bg'   => [236, 253, 245],
                'hdr_text' => [4, 120, 87],
                'name'     => [15, 23, 42],
                'meta'     => [51, 65, 85],
                'muted'    => [100, 116, 139]
            ],
            'monocromatico' => [
                'bg'       => [255, 255, 255],
                'border'   => [71, 85, 105],
                'hdr_bg'   => [241, 245, 249],
                'hdr_text' => [30, 41, 59],
                'name'     => [15, 23, 42],
                'meta'     => [51, 65, 85],
                'muted'    => [100, 116, 139]
            ],
            'barra_lateral' => [
                'bg'       => [255, 255, 255],
                'border'   => [203, 213, 225],
                'accent'   => [37, 99, 235],
                'hdr_bg'   => [255, 255, 255],
                'hdr_text' => [29, 78, 216],
                'name'     => [15, 23, 42],
                'meta'     => [51, 65, 85],
                'muted'    => [100, 116, 139]
            ]
        ];

        $paleta = $paletas[$tema] ?? $paletas['azul_institucional'];

        $c_bg      = imagecolorallocate($im, $paleta['bg'][0], $paleta['bg'][1], $paleta['bg'][2]);
        $c_border  = imagecolorallocate($im, $paleta['border'][0], $paleta['border'][1], $paleta['border'][2]);
        $c_hdr_bg  = imagecolorallocate($im, $paleta['hdr_bg'][0], $paleta['hdr_bg'][1], $paleta['hdr_bg'][2]);
        $c_hdr_txt = imagecolorallocate($im, $paleta['hdr_text'][0], $paleta['hdr_text'][1], $paleta['hdr_text'][2]);
        $c_name    = imagecolorallocate($im, $paleta['name'][0], $paleta['name'][1], $paleta['name'][2]);
        $c_meta    = imagecolorallocate($im, $paleta['meta'][0], $paleta['meta'][1], $paleta['meta'][2]);
        $c_muted   = imagecolorallocate($im, $paleta['muted'][0], $paleta['muted'][1], $paleta['muted'][2]);

        imagefilledrectangle($im, 0, 0, $width, $height, $c_bg);

        // Estructura del marco
        if ($tema === 'barra_lateral') {
            $c_accent = imagecolorallocate($im, $paleta['accent'][0], $paleta['accent'][1], $paleta['accent'][2]);
            imagefilledrectangle($im, 0, 0, 16, $height, $c_accent);
            imagerectangle($im, 0, 0, $width - 1, $height - 1, $c_border);
            imagerectangle($im, 1, 1, $width - 2, $height - 2, $c_border);
        } else {
            imagefilledrectangle($im, 3, 3, $width - 4, 46, $c_hdr_bg);
            imagerectangle($im, 0, 0, $width - 1, $height - 1, $c_border);
            imagerectangle($im, 1, 1, $width - 2, $height - 2, $c_border);
            imagerectangle($im, 2, 2, $width - 3, $height - 3, $c_border);
        }

        // Datos formateados
        $run_formateado = firmagob_formatear_run($run);
        if (empty($fecha_hora)) {
            $dt = new DateTime('now', new DateTimeZone('America/Santiago'));
            $fecha_hora = $dt->format('d/m/Y H:i:s') . ' CLT';
        }

        $nombre_limpio = mb_strtoupper(trim((string)$nombre), 'UTF-8');
        if (empty($nombre_limpio)) {
            $nombre_limpio = 'FUNCIONARIO AUTORIZADO';
        }
        if (mb_strlen($nombre_limpio, 'UTF-8') > 45) {
            $nombre_limpio = mb_substr($nombre_limpio, 0, 42, 'UTF-8') . '...';
        }

        $cargo_limpio = trim((string)$cargo);
        if (empty($cargo_limpio)) {
            $cargo_limpio = 'Funcionario Autorizado';
        }
        if (mb_strlen($cargo_limpio, 'UTF-8') > 45) {
            $cargo_limpio = mb_substr($cargo_limpio, 0, 42, 'UTF-8') . '...';
        }

        // Mapa de fuentes TrueType en Windows
        $f_map = [
            'segoeui' => ['C:/Windows/Fonts/segoeui.ttf', 'C:/Windows/Fonts/segoeuib.ttf'],
            'calibri' => ['C:/Windows/Fonts/calibri.ttf', 'C:/Windows/Fonts/calibrib.ttf'],
            'arial'   => ['C:/Windows/Fonts/arial.ttf',   'C:/Windows/Fonts/arialbd.ttf']
        ];
        $f_pair = $f_map[$fuente_id] ?? $f_map['segoeui'];
        $has_ttf = function_exists('imagettftext') && file_exists($f_pair[0]) && file_exists($f_pair[1]);

        $offset_x = ($tema === 'barra_lateral') ? 34 : 20;

        if ($has_ttf) {
            $f_reg  = $f_pair[0];
            $f_bold = $f_pair[1];

            // Renderizado de iconos vectoriales
            if ($icono === 'check') {
                $c_badge = imagecolorallocate($im, 16, 185, 129);
                imagefilledellipse($im, $offset_x + 10, 24, 22, 22, $c_badge);
                $c_chk = imagecolorallocate($im, 255, 255, 255);
                imagesetthickness($im, 3);
                imageline($im, $offset_x + 5, 24, $offset_x + 9, 29, $c_chk);
                imageline($im, $offset_x + 9, 29, $offset_x + 16, 18, $c_chk);
                imagesetthickness($im, 1);
                $title_x = $offset_x + 30;
            } elseif ($icono === 'candado') {
                $c_lock = $c_hdr_txt;
                imagesetthickness($im, 2);
                imagearc($im, $offset_x + 10, 18, 14, 14, 180, 360, $c_lock);
                imagefilledrectangle($im, $offset_x + 3, 18, $offset_x + 17, 29, $c_lock);
                imagesetthickness($im, 1);
                $title_x = $offset_x + 28;
            } elseif ($icono === 'escudo') {
                $c_shield = $c_hdr_txt;
                imagefilledrectangle($im, $offset_x + 2, 14, $offset_x + 18, 23, $c_shield);
                imagefilledarc($im, $offset_x + 10, 23, 16, 14, 0, 180, $c_shield, IMG_ARC_PIE);
                $title_x = $offset_x + 28;
            } else {
                $title_x = $offset_x;
            }

            // Título de la estampa
            imagettftext($im, 13, 0, $title_x, 32, $c_hdr_txt, $f_bold, mb_strtoupper($titulo, 'UTF-8'));

            // Nombre del firmante
            imagettftext($im, 16, 0, $offset_x, 82, $c_name, $f_bold, $nombre_limpio);

            // Línea 2: RUN / Cargo
            $parts_l2 = [];
            if ($mostrar_run) $parts_l2[] = "RUN: " . $run_formateado;
            if ($mostrar_cargo) $parts_l2[] = "Cargo: " . $cargo_limpio;
            if (!empty($parts_l2)) {
                imagettftext($im, 12.5, 0, $offset_x, 118, $c_meta, $f_reg, implode(' | ', $parts_l2));
            }

            // Línea 3: Fecha / Entidad
            $parts_l3 = [];
            if ($mostrar_fecha) $parts_l3[] = "Fecha: " . $fecha_hora;
            if ($mostrar_entidad) $parts_l3[] = "Entidad: " . $entidad_nombre;
            if (!empty($parts_l3)) {
                imagettftext($im, 11.5, 0, $offset_x, 154, $c_muted, $f_reg, implode(' | ', $parts_l3));
            }

            // Línea 4: Mención Legal
            if ($mostrar_ley) {
                imagettftext($im, 10.5, 0, $offset_x, 192, $c_muted, $f_reg, "Validez Legal: Ley N° 19.799 sobre Firma Electrónica");
            }

        } else {
            // Fallback transparente a fuentes bitmap si no hubiera TrueType
            imagestring($im, 3, 10, 4, mb_strtoupper($titulo, 'UTF-8'), $c_hdr_txt);
            imagestring($im, 2, 10, 28, 'Firmante: ' . $nombre_limpio, $c_name);
            imagestring($im, 2, 10, 46, 'RUN: ' . $run_formateado . ' | Cargo: ' . $cargo_limpio, $c_meta);
            imagestring($im, 2, 10, 64, 'Fecha: ' . $fecha_hora, $c_muted);
            imagestring($im, 2, 10, 80, 'Entidad: ' . $entidad_nombre, $c_muted);
            imagestring($im, 1, 10, 96, 'Validez Legal: Ley No 19.799 sobre Firma Electronica', $c_muted);
        }

        ob_start();
        imagepng($im);
        $raw_png = ob_get_clean();
        imagedestroy($im);

        return base64_encode($raw_png);
    }
}

if (!function_exists('firmagob_obtener_layout_xml')) {
    /**
     * Genera la configuración de layout XML de AgileSignerConfig para estampar la firma visible en el PDF.
     * @param string $etapa
     * @param string|null $imagen_base64 Imagen personalizada en Base64 (si es null se genera la estampa dinámica con metadatos)
     * @param string $nombre
     * @param string $run
     * @param string $cargo
     * @return string
     */
    function firmagob_obtener_layout_xml($etapa = 'JEFATURA', $imagen_base64 = null, $nombre = '', $run = '', $cargo = '') {
        // Si no se provee imagen fija, generar la estampa dinámica en memoria
        if (empty($imagen_base64)) {
            $imagen_base64 = firmagob_generar_estampa_dinamica_base64($nombre, $run, $cargo);
        }

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
               "<BASE64VALUE>{$imagen_base64}</BASE64VALUE>" .
               '</Visible>' .
               '</Signature>' .
               '</Application>' .
               '</AgileSignerConfig>';

        return $xml;
    }
}

if (!function_exists('firmagob_firmar_archivo')) {
    /**
     * Realiza la llamada HTTP POST a la API FirmaGob para firmar un documento PDF.
     *
     * @param string $ruta_pdf_entrada Ruta absoluta al archivo PDF que se va a firmar.
     * @param string $run_firmante RUN del firmante habilitado en la RA.
     * @param string $descripcion Descripción del documento.
     * @param string|null $otp Código OTP de 6 dígitos (requerido para Firma Atendida).
     * @param string $etapa Etapa de firma ('JEFATURA', 'PRESUPUESTO', 'ADMIN_MUNICIPAL', 'CDP_FINANZAS').
     * @param string|null $purpose Propósito específico si difiere del global.
     * @param string|null $nombre_firmante Nombre completo del firmante para la estampa.
     * @param string|null $cargo_firmante Cargo del firmante para la estampa.
     * @return array Metadatos y contenido binario firmado.
     * @throws Exception
     */
    function firmagob_firmar_archivo($ruta_pdf_entrada, $run_firmante, $descripcion, $otp = null, $etapa = 'JEFATURA', $purpose = null, $nombre_firmante = null, $cargo_firmante = null) {
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

        // MODO SIMULACIÓN LOCAL: Permite pruebas completas mientras se esperan credenciales
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

        // Resolver metadatos para la estampa si no vienen provistos
        if (empty($nombre_firmante)) {
            $nombre_firmante = $_SESSION['user_nombre'] ?? ($_SESSION['user_name'] ?? 'Funcionario Autorizado');
        }
        if (empty($cargo_firmante)) {
            if (!empty($_SESSION['user_cargo'])) {
                $cargo_firmante = $_SESSION['user_cargo'];
            } elseif (!empty($_SESSION['user_rol'])) {
                $cargo_firmante = match ($_SESSION['user_rol']) {
                    'ADMIN_MUNICIPAL' => 'Administrador Municipal',
                    'PRESUPUESTO'     => 'Control Presupuestario',
                    'JEFE_UNIDAD'     => 'Jefatura de Unidad',
                    'FINANZAS'        => 'Dirección de Finanzas',
                    'ADQUISICIONES'   => 'Encargado de Adquisiciones',
                    'SYSADMIN'        => 'Administrador del Sistema',
                    default           => $_SESSION['user_rol']
                };
            } else {
                $cargo_firmante = match (strtoupper($etapa)) {
                    'JEFATURA'        => 'Jefatura Unidad Solicitante',
                    'PRESUPUESTO'     => 'Control Presupuestario',
                    'ADMIN_MUNICIPAL', 'ADMINISTRADOR' => 'Administrador Municipal',
                    'CDP_FINANZAS', 'FINANZAS' => 'Dirección de Finanzas',
                    default           => 'Funcionario Autorizado'
                };
            }
        }

        // Generar JWT
        $jwt = firmagob_generar_jwt($run_firmante, FIRMAGOB_ENTITY, $purpose_usar, FIRMAGOB_SECRET);

        // Layout visual de estampa dinámica
        $layout_xml = firmagob_obtener_layout_xml($etapa, null, $nombre_firmante, $run_firmante, $cargo_firmante);

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
}

if (!function_exists('firmagob_obtener_firmante_activo')) {
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
}
