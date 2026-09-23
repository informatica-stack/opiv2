<?php
// configuracion_sistema.php - Vista de Configuración del Sistema
require_once __DIR__ . '/configuracion_sistema_controller.php';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <?php 
    $titulo_pagina = "Configuración del Sistema";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 font-sans d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4">
        
        <!-- CABECERA PRINCIPAL -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary fs-3"></i>
                    Configuración Global del Sistema
                </h1>
                <p class="text-muted small mb-0">Gestión de parámetros operativos, límites de adjuntos y configuraciones generales.</p>
            </div>
            <div class="col-12 col-md-auto text-start text-md-end">
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
                </a>
            </div>
        </div>

        <!-- MENSAJES DE ALERTA -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje === 'error' ? 'danger' : 'success' ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $tipo_mensaje === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill' ?> fs-5"></i>
                <div class="fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="configuracion_sistema.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="row g-4">

                <!-- TARJETA 1: PARÁMETROS DE ARCHIVOS ADJUNTOS -->
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-light h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                            <div class="p-2 bg-primary-subtle text-primary rounded-3">
                                <i class="bi bi-paperclip fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Archivos Adjuntos</h5>
                                <p class="text-muted small mb-0">Establezca los límites de subida de documentación.</p>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary small">Tamaño Máximo por Archivo (MB) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="limite_peso_adjunto_mb" min="1" max="200" required class="form-control fw-bold text-primary" value="<?= htmlspecialchars($configs['limite_peso_adjunto_mb']) ?>">
                                    <span class="input-group-text bg-light fw-bold">MB</span>
                                </div>
                                <div class="form-text text-muted small mt-1">Límite máximo permitido para cada documento cargado en las solicitudes (por defecto: 10 MB).</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold text-secondary small">Extensiones Permitidas <span class="text-danger">*</span></label>
                                <textarea name="extensiones_permitidas" rows="3" required class="form-control font-monospace small"><?= htmlspecialchars($configs['extensiones_permitidas']) ?></textarea>
                                <div class="form-text text-muted small mt-1">Formatos separados por comas (Ejemplo: pdf, zip, rar, doc, docx, xls, xlsx, jpg, jpeg, png).</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TARJETA 2: VALORES ECONÓMICOS Y ESTADO DEL SISTEMA -->
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-light h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                            <div class="p-2 bg-success-subtle text-success rounded-3">
                                <i class="bi bi-cash-stack fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Valores Económicos y Estado</h5>
                                <p class="text-muted small mb-0">Parámetros financieros y modo de mantenimiento.</p>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary small">Valor UTM del Mes (CLP) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white fw-bold">$</span>
                                    <input type="number" name="valor_utm" min="1" step="0.01" required class="form-control fw-bold text-success" value="<?= htmlspecialchars($configs['valor_utm']) ?>">
                                </div>
                                <div class="form-text text-muted small mt-1">Utilizado para calcular la escala UTM según Art. 10 de Compras Públicas.</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold text-secondary small">Modo Mantenimiento del Sistema</label>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="swMantenimiento" name="modo_mantenimiento" value="1" <?= ($configs['modo_mantenimiento'] === '1') ? 'checked' : '' ?>>
                                        <label class="form-check-input-label fw-bold text-dark" for="swMantenimiento">
                                            Activar Modo Mantenimiento
                                        </label>
                                    </div>
                                    <p class="text-muted small mb-0" style="font-size: 11px;">Al activar esta opción, solo los usuarios administradores (SYSADMIN) podrán acceder al sistema. Los demás usuarios serán redirigidos a la pantalla de mantenimiento.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TARJETA 3: INTEGRACIÓN FIRMAGOB (MODO ATENDIDO / DESATENDIDO) -->
                <div class="col-12">
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="p-2 bg-indigo-subtle text-indigo rounded-3" style="background-color: #e0e7ff; color: #4338ca;">
                                    <i class="bi bi-shield-lock-fill fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Integración FirmaGob (Secretaría de Gobierno Digital)</h5>
                                    <p class="text-muted small mb-0">Seleccione el modo de operación de la firma electrónica avanzada (Atendida con OTP o Desatendida automática).</p>
                                </div>
                            </div>
                            <span class="badge <?= ($configs['firmagob_modo'] === 'DESATENDIDA') ? 'bg-success' : 'bg-primary' ?> px-3 py-2 fw-bold">
                                Modo Actual: <?= htmlspecialchars($configs['firmagob_modo']) ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Modo de Firma Digital <span class="text-danger">*</span></label>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="firmagob_modo" id="modoAtendida" value="ATENDIDA" <?= ($configs['firmagob_modo'] !== 'DESATENDIDA') ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-dark" for="modoAtendida">
                                                Firma Atendida (Requiere Código OTP)
                                            </label>
                                            <div class="text-muted small">El funcionario debe ingresar el código de 6 dígitos desde Google Authenticator o FreeOTP en cada firma.</div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="firmagob_modo" id="modoDesatendida" value="DESATENDIDA" <?= ($configs['firmagob_modo'] === 'DESATENDIDA') ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-success" for="modoDesatendida">
                                                Firma Desatendida (Automática / 1-Clic Sin OTP)
                                            </label>
                                            <div class="text-muted small">Firma directa e instantánea utilizando el certificado desatendido autorizado en la RA de FirmaGob.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small">Nombre de la Entidad (JWT Entity)</label>
                                        <input type="text" name="firmagob_entity" class="form-control" value="<?= htmlspecialchars($configs['firmagob_entity'] ?? 'Ilustre Municipalidad de Lebu') ?>" required>
                                        <div class="form-text small">Debe coincidir exactamente con el nombre institucional registrado en la RA de FirmaGob.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small">Propósito para Firma Atendida (JWT Purpose)</label>
                                        <input type="text" name="firmagob_purpose" class="form-control" value="<?= htmlspecialchars($configs['firmagob_purpose'] ?? 'Propósito General') ?>">
                                        <div class="form-text small">Para modo desatendido se asigna automáticamente "Desatendido".</div>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold text-secondary small">Ambiente de Operación</label>
                                        <select name="firmagob_ambiente" class="form-select">
                                            <option value="PRODUCCION" <?= ($configs['firmagob_ambiente'] !== 'CERTIFICACION') ? 'selected' : '' ?>>Producción (api.firma.digital.gob.cl)</option>
                                            <option value="CERTIFICACION" <?= ($configs['firmagob_ambiente'] === 'CERTIFICACION') ? 'selected' : '' ?>>Certificación / Sandbox (api.firma.cert.digital.gob.cl)</option>
                                        </select>
                                    </div>
                                </div>

                <!-- TARJETA 4: ACCESO DIRECTO AL DISEÑADOR DE PLANTILLA OPI -->
                <div class="col-12">
                    <div class="card shadow-sm border-primary-subtle bg-primary-subtle bg-opacity-10">
                        <div class="card-body p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-3 bg-primary text-white rounded-3 shadow-sm">
                                    <i class="bi bi-file-earmark-pdf-fill fs-3"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark">Diseñador y Calibrador de Plantilla OPI (PDF)</h5>
                                    <p class="text-muted small mb-0">Personalice los títulos oficiales, textos de cláusulas, pie legal y calibre la posición de las líneas base de firma electrónica con vista previa en vivo.</p>
                                </div>
                            </div>
                            <div>
                                <a href="diseno_opi.php" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2 text-nowrap">
                                    <i class="bi bi-pencil-square fs-5"></i>
                                    <span>Abrir Diseñador de OPI</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BOTÓN GUARDAR -->
            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary px-5 py-2.5 rounded-3 fw-bold shadow d-inline-flex align-items-center gap-2">
                    <i class="bi bi-floppy-fill fs-5"></i>
                    <span>Guardar Configuraciones</span>
                </button>
            </div>
        </form>

    </div>

    <!-- Bootstrap JS Bundle -->
    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
