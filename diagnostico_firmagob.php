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
        $rut_probar = trim($_POST['rut_probar'] ?? $rut_probar);
        $otp_probar = trim($_POST['otp_probar'] ?? '');
        $purpose_probar = trim($_POST['purpose_probar'] ?? FIRMAGOB_PURPOSE);
        $entity_probar = trim($_POST['entity_probar'] ?? FIRMAGOB_ENTITY);

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
            $pdf->Cell(190, 10, 'RUN: ' . $rut_probar, 0, 1, 'C');
            $pdf->Output('F', $test_pdf_path);

            // 2. Generar JWT y payload
            $jwt = firmagob_generar_jwt($rut_probar, $entity_probar, $purpose_probar, FIRMAGOB_SECRET);
            $pdf_content = file_get_contents($test_pdf_path);
            $pdf_base64 = base64_encode($pdf_content);
            $checksum = hash('sha256', $pdf_content);
            $layout_xml = firmagob_obtener_layout_xml('JEFATURA');

            $payload = [
                'token'         => $jwt,
                'api_token_key' => FIRMAGOB_API_TOKEN_KEY,
                'files'         => [
                    [
                        'content-type' => 'application/pdf',
                        'content'      => $pdf_base64,
                        'description'  => 'Documento de Prueba Diagnostico',
                        'checksum'     => $checksum,
                        'layout'       => $layout_xml
                    ]
                ]
            ];

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            if (!empty($otp_probar)) {
                $headers[] = 'OTP: ' . $otp_probar;
            }

            $raw_request = [
                'url'     => FIRMAGOB_API_URL,
                'headers' => $headers,
                'jwt_payload_decoded' => json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true),
                'api_token_key' => FIRMAGOB_API_TOKEN_KEY ? (substr(FIRMAGOB_API_TOKEN_KEY, 0, 8) . '...' . substr(FIRMAGOB_API_TOKEN_KEY, -4)) : 'VACÍO'
            ];

            // 3. Ejecutar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FIRMAGOB_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
                'http_code' => $http_code,
                'curl_error' => $curl_err ?: 'Ninguno',
                'body' => json_decode($response_body, true) ?: $response_body
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

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">RUT Titular del Certificado:</label>
                            <input type="text" name="rut_probar" class="form-control font-monospace" value="<?= htmlspecialchars($rut_probar) ?>" required>
                            <div class="form-text small">RUT con guión o solo números.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Código OTP de tu Teléfono:</label>
                            <input type="text" name="otp_probar" class="form-control font-monospace text-center fs-5 tracking-widest" placeholder="123456" maxlength="6" autocomplete="off" required>
                            <div class="form-text small">Código de 6 dígitos de Google Authenticator / FreeOTP.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Entidad (JWT Entity):</label>
                            <input type="text" name="entity_probar" class="form-control" value="<?= htmlspecialchars(FIRMAGOB_ENTITY) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Propósito (JWT Purpose):</label>
                            <input type="text" name="purpose_probar" class="form-control" value="<?= htmlspecialchars(FIRMAGOB_PURPOSE) ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-play-circle-fill"></i>
                        Ejecutar Test de Conexión y Firma en Vivo
                    </button>
                </form>

                <?php if ($raw_request || $raw_response): ?>
                    <div class="mt-4">
                        <h6 class="fw-bold text-uppercase text-secondary small mb-2">Detalles Técnicos de la Petición</h6>
                        
                        <div class="card bg-dark text-light p-3 mb-3 font-monospace small" style="max-height: 250px; overflow-y: auto;">
                            <strong class="text-warning">=== PAYLOAD JWT GENERADO ===</strong><br>
                            <?= htmlspecialchars(json_encode($raw_request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>
                        </div>

                        <div class="card bg-dark text-light p-3 font-monospace small" style="max-height: 250px; overflow-y: auto;">
                            <strong class="text-info">=== RESPUESTA RAW RECIBIDA DE FIRMAGOB ===</strong><br>
                            <?= htmlspecialchars(json_encode($raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>