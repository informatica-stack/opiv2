<?php
// diagnostico_firmagob.php - Herramienta de Diagnóstico en Vivo para FirmaGob
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/firmagob_helper.php';

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $rol = $_SESSION['user_rol'] ?? '';
    if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
        die("Acceso Denegado. Solo administradores pueden usar la herramienta de diagnóstico.");
    }
}

$resultado_test = null;
$error_test = null;
$raw_request = null;
$raw_response = null;
$rut_probar = $_SESSION['user_rut'] ?? '17439829-1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ejecutar_prueba'])) {
    $token_csrf = $_POST['csrf_token'] ?? '';
    if (!$is_cli && (empty($token_csrf) || $token_csrf !== ($_SESSION['csrf_token'] ?? ''))) {
        $error_test = "Error CSRF: Token inválido. Recargue la página.";
    } else {
        $modo_probar = strtoupper(trim($_POST['modo_probar'] ?? FIRMAGOB_MODO));
        $rut_probar = trim($_POST['rut_probar'] ?? $rut_probar);
        $otp_probar = trim($_POST['otp_probar'] ?? '');
        $purpose_probar = ($modo_probar === 'DESATENDIDA') ? 'Desatendido' : trim($_POST['purpose_probar'] ?? FIRMAGOB_PURPOSE);
        $entity_probar = trim($_POST['entity_probar'] ?? FIRMAGOB_ENTITY);
        $url_probar = trim($_POST['url_probar'] ?? FIRMAGOB_API_URL);
        $token_key_probar = trim($_POST['token_key_probar'] ?? FIRMAGOB_API_TOKEN_KEY);
        $secret_probar = trim($_POST['secret_probar'] ?? FIRMAGOB_SECRET);

        try {
            // 1. Crear un PDF mínimo de prueba
            $test_dir = __DIR__ . '/uploads/test';
            if (!file_exists($test_dir)) @mkdir($test_dir, 0777, true);
            $test_pdf_path = $test_dir . '/test_' . time() . '.pdf';

            require_once __DIR__ . '/fpdf.php';
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(190, 10, 'Prueba de Firma Digital FirmaGob - Municipalidad de Lebu', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(190, 10, 'Fecha: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Cell(190, 10, 'RUN: ' . $rut_probar . ' (Modo: ' . $modo_probar . ')', 0, 1, 'C');
            $pdf->Output('F', $test_pdf_path);

            // 2. Generar JWT y payload
            $jwt = firmagob_generar_jwt($rut_probar, $entity_probar, $purpose_probar, $secret_probar);
            $pdf_content = file_get_contents($test_pdf_path);
            $pdf_base64 = base64_encode($pdf_content);
            $checksum = hash('sha256', $pdf_content);
            $incluir_layout = isset($_POST['incluir_layout']) ? ($_POST['incluir_layout'] === '1') : false;
            $layout_xml = $incluir_layout ? firmagob_obtener_layout_xml('JEFATURA') : null;

            $file_item = [
                'content-type' => 'application/pdf',
                'content'      => $pdf_base64,
                'description'  => 'Documento de Prueba Diagnostico',
                'checksum'     => $checksum
            ];
            if ($layout_xml !== null) {
                $file_item['layout'] = $layout_xml;
            }

            $payload = [
                'token'         => $jwt,
                'api_token_key' => $token_key_probar,
                'files'         => [ $file_item ]
            ];

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            if (!empty($otp_probar) && $modo_probar !== 'DESATENDIDA') {
                $headers[] = 'OTP: ' . $otp_probar;
            }

            $json_body_enviado = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Para visualización limpia: versión truncando el base64 del PDF para que sea legible
            $payload_preview = $payload;
            if (isset($payload_preview['files'][0]['content'])) {
                $len = strlen($payload_preview['files'][0]['content']);
                $payload_preview['files'][0]['content'] = substr($payload_preview['files'][0]['content'], 0, 45) . "... [Base64 de $len caracteres]";
            }
            $json_body_preview = json_encode($payload_preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $jwt_parts = explode('.', $jwt);
            $jwt_header_decoded = json_decode(base64_decode(strtr($jwt_parts[0] ?? '', '-_', '+/')), true);
            $jwt_payload_decoded = json_decode(base64_decode(strtr($jwt_parts[1] ?? '', '-_', '+/')), true);

            $raw_request = [
                'metodo'               => 'POST',
                'url'                  => $url_probar,
                'headers'              => $headers,
                'json_body_completo'   => $json_body_preview,
                'jwt_token_string'     => $jwt,
                'jwt_header_decoded'   => $jwt_header_decoded,
                'jwt_payload_decoded'  => $jwt_payload_decoded
            ];

            // 3. Ejecutar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_probar);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body_enviado);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response_body = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);

            @unlink($test_pdf_path);

            $raw_response = [
                'http_code'  => $http_code,
                'curl_error' => $curl_err ?: 'Ninguno',
                'raw_body'   => json_decode($response_body, true) ?: $response_body
            ];

            if ($http_code === 200) {
                $resultado_test = "¡ÉXITO TOTAL! FirmaGob respondió con HTTP 200 y el documento fue firmado correctamente.";
            } else {
                $error_test = "FirmaGob respondió con HTTP $http_code. Ver detalles técnicos abajo.";
            }

        } catch (Exception $e) {
            $error_test = "Excepción: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <?php 
    $titulo_pagina = "Diagnóstico en Vivo FirmaGob";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container my-4" style="max-width: 900px;">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check fs-4"></i>
                    <h5 class="fw-bold mb-0">Consola de Diagnóstico FirmaGob v2</h5>
                </div>
                <span class="badge bg-white text-primary fw-bold">Ambiente: <?= htmlspecialchars(FIRMAGOB_AMBIENTE) ?></span>
            </div>
            <div class="card-body p-4">

                <h6 class="fw-bold text-uppercase text-secondary small mb-3">1. Estado de Variables Cargadas en el Servidor</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Variable</th>
                                <th>Valor Detectado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-monospace fw-bold">FIRMAGOB_ENTITY</td>
                                <td><?= htmlspecialchars(FIRMAGOB_ENTITY) ?></td>
                                <td><span class="badge bg-success">OK</span></td>
                            </tr>
                            <tr>
                                <td class="font-monospace fw-bold">FIRMAGOB_PURPOSE</td>
                                <td><?= htmlspecialchars(FIRMAGOB_PURPOSE) ?></td>
                                <td><span class="badge bg-success">OK</span></td>
                            </tr>
                            <tr>
                                <td class="font-monospace fw-bold">FIRMAGOB_API_URL</td>
                                <td class="small font-monospace"><?= htmlspecialchars(FIRMAGOB_API_URL) ?></td>
                                <td><span class="badge bg-success">OK</span></td>
                            </tr>
                            <tr>
                                <td class="font-monospace fw-bold">FIRMAGOB_API_TOKEN_KEY</td>
                                <td class="font-monospace">
                                    <?php if (!empty(FIRMAGOB_API_TOKEN_KEY)): ?>
                                        <?= htmlspecialchars(substr(FIRMAGOB_API_TOKEN_KEY, 0, 8)) ?>...<?= htmlspecialchars(substr(FIRMAGOB_API_TOKEN_KEY, -4)) ?>
                                        <span class="text-muted small">(<?= strlen(FIRMAGOB_API_TOKEN_KEY) ?> caracteres)</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">VACÍO (No configurado)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !empty(FIRMAGOB_API_TOKEN_KEY) ? '<span class="badge bg-success">Presente</span>' : '<span class="badge bg-danger">Faltante</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-monospace fw-bold">FIRMAGOB_SECRET</td>
                                <td class="font-monospace">
                                    <?php if (!empty(FIRMAGOB_SECRET)): ?>
                                        ••••••••••••••••
                                        <span class="text-muted small">(<?= strlen(FIRMAGOB_SECRET) ?> caracteres)</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold">VACÍO (No configurado)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !empty(FIRMAGOB_SECRET) ? '<span class="badge bg-success">Presente</span>' : '<span class="badge bg-danger">Faltante</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-monospace fw-bold">Archivo .env Local</td>
                                <td><?= file_exists(__DIR__ . '/.env') ? '<span class="text-success fw-bold">Detectado en raíz</span>' : '<span class="text-muted">No usado (Leyendo de Dokploy)</span>' ?></td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-uppercase text-secondary small mb-3">2. Ejecutar Prueba de Firma Directa con FirmaGob</h6>

                <?php if ($resultado_test): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-check-circle-fill fs-3 shrink-0"></i>
                        <div>
                            <h6 class="fw-bold mb-0">¡Firma Completada con Éxito!</h6>
                            <span><?= htmlspecialchars($resultado_test) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_test): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                        <i class="bi bi-exclamation-octagon-fill fs-3 shrink-0 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Resultado de la Prueba: Error</h6>
                            <span><?= htmlspecialchars($error_test) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="ejecutar_prueba" value="1">

                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <label class="form-label fw-bold text-secondary small mb-2">Modo de Firma a Probar:</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_probar" id="diagModoAtendida" value="ATENDIDA" <?= (($_POST['modo_probar'] ?? FIRMAGOB_MODO) !== 'DESATENDIDA') ? 'checked' : '' ?> onchange="toggleOtpInput(this.value)">
                                <label class="form-check-label fw-bold text-dark" for="diagModoAtendida">
                                    Firma Atendida (Con OTP)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_probar" id="diagModoDesatendida" value="DESATENDIDA" <?= (($_POST['modo_probar'] ?? FIRMAGOB_MODO) === 'DESATENDIDA') ? 'checked' : '' ?> onchange="toggleOtpInput(this.value)">
                                <label class="form-check-label fw-bold text-success" for="diagModoDesatendida">
                                    Firma Desatendida (Sin OTP, Propósito: "Desatendido")
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">RUT Titular del Certificado:</label>
                            <input type="text" name="rut_probar" class="form-control font-monospace" value="<?= htmlspecialchars($rut_probar) ?>" required>
                            <div class="form-text small">RUT con guión o solo números.</div>
                        </div>
                        <div class="col-md-6" id="contOtp">
                            <label class="form-label fw-bold small">Código OTP de tu Teléfono:</label>
                            <input type="text" name="otp_probar" id="diagOtpInp" class="form-control font-monospace text-center fs-5 tracking-widest" placeholder="123456" maxlength="6" autocomplete="off">
                            <div class="form-text small">Obligatorio solo en modo Atendido.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Entidad (JWT Entity):</label>
                            <input type="text" name="entity_probar" class="form-control" value="<?= htmlspecialchars($_POST['entity_probar'] ?? FIRMAGOB_ENTITY) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Propósito (JWT Purpose):</label>
                            <input type="text" name="purpose_probar" class="form-control" value="<?= htmlspecialchars($_POST['purpose_probar'] ?? FIRMAGOB_PURPOSE) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">URL del Endpoint FirmaGob:</label>
                        <select name="url_probar" class="form-select font-monospace small mb-3">
                            <option value="https://api.firma.digital.gob.cl/firma/v2/files/tickets" <?= (($_POST['url_probar'] ?? FIRMAGOB_API_URL) === 'https://api.firma.digital.gob.cl/firma/v2/files/tickets') ? 'selected' : '' ?>>Producción: https://api.firma.digital.gob.cl/firma/v2/files/tickets</option>
                            <option value="https://api.firma.cert.digital.gob.cl/firma/v2/files/tickets" <?= (($_POST['url_probar'] ?? FIRMAGOB_API_URL) === 'https://api.firma.cert.digital.gob.cl/firma/v2/files/tickets') ? 'selected' : '' ?>>Certificación/QA: https://api.firma.cert.digital.gob.cl/firma/v2/files/tickets</option>
                        </select>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="incluir_layout" id="chkLayout" value="1" <?= (!empty($_POST['incluir_layout'])) ? 'checked' : '' ?>>
                            <label class="form-check-label small text-muted" for="chkLayout">
                                Incluir parámetro <code class="fw-bold">layout</code> (estampa visual en PDF). <em>(Recomendado desactivar para firma digital pura).</em>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-play-circle-fill"></i>
                        Ejecutar Test de Conexión y Firma en Vivo
                    </button>
                </form>

                <?php if ($raw_request || $raw_response): ?>
                    <div class="mt-4">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-terminal-fill me-1 text-primary"></i> Volcado Técnico Real (Raw Wire Traffic)
                        </h5>
                        
                        <!-- 1. Petición HTTP Enviada -->
                        <div class="card bg-dark text-light p-3 mb-3 font-monospace small shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-1">
                                <span class="text-warning fw-bold">1. PETICIÓN HTTP REAL ENVIADA A FIRMAGOB (JSON BODY)</span>
                                <span class="badge bg-secondary"><?= htmlspecialchars($raw_request['metodo'] ?? 'POST') ?> <?= htmlspecialchars($raw_request['url'] ?? '') ?></span>
                            </div>
                            <div class="mb-2 text-info">
                                <strong>Headers:</strong><br>
                                <?php foreach (($raw_request['headers'] ?? []) as $h): ?>
                                    &nbsp;&nbsp;<?= htmlspecialchars($h) ?><br>
                                <?php endforeach; ?>
                            </div>
                            <strong class="text-white">Body JSON Transmitido por cURL:</strong>
                            <pre class="text-success-emphasis bg-black p-2 rounded mt-1 mb-0" style="white-space: pre-wrap; word-break: break-all; max-height: 250px; overflow-y: auto;"><?= htmlspecialchars($raw_request['json_body_completo'] ?? '') ?></pre>
                        </div>

                        <!-- 2. Inspección del JWT -->
                        <div class="card bg-dark text-light p-3 mb-3 font-monospace small shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-1">
                                <span class="text-info fw-bold">2. DETALLE DECODIFICADO DEL TOKEN JWT (CLAIMS)</span>
                                <span class="badge bg-primary">Algoritmo HS256</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <strong class="text-secondary">Header:</strong>
                                    <pre class="text-light bg-black p-2 rounded mt-1 mb-0"><?= htmlspecialchars(json_encode($raw_request['jwt_header_decoded'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                </div>
                                <div class="col-md-8">
                                    <strong class="text-secondary">Payload (Claims):</strong>
                                    <pre class="text-light bg-black p-2 rounded mt-1 mb-0"><?= htmlspecialchars(json_encode($raw_request['jwt_payload_decoded'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Respuesta HTTP Recibida -->
                        <div class="card bg-dark text-light p-3 font-monospace small shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom border-secondary pb-1">
                                <span class="text-warning fw-bold">3. RESPUESTA HTTP REAL RECIBIDA DE FIRMAGOB</span>
                                <span class="badge <?= ($raw_response['http_code'] === 200) ? 'bg-success' : 'bg-danger' ?>">
                                    HTTP <?= htmlspecialchars($raw_response['http_code'] ?? '0') ?>
                                </span>
                            </div>
                            <strong class="text-white">Body JSON de Respuesta:</strong>
                            <pre class="text-warning bg-black p-2 rounded mt-1 mb-0" style="white-space: pre-wrap; word-break: break-all; max-height: 250px; overflow-y: auto;"><?= htmlspecialchars(json_encode($raw_response['raw_body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
    <script>
    function toggleOtpInput(modo) {
        const cont = document.getElementById('contOtp');
        const inp = document.getElementById('diagOtpInp');
        if (modo === 'DESATENDIDA') {
            if (cont) cont.classList.add('opacity-50');
            if (inp) {
                inp.disabled = true;
                inp.value = '';
                inp.placeholder = 'No requerido en modo desatendido';
            }
        } else {
            if (cont) cont.classList.remove('opacity-50');
            if (inp) {
                inp.disabled = false;
                inp.placeholder = '123456';
            }
        }
    }
    // Inicializar estado según selección actual
    const checkedModo = document.querySelector('input[name="modo_probar"]:checked');
    if (checkedModo) toggleOtpInput(checkedModo.value);
    </script>
</body>
</html>