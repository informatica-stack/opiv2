<?php
// mantenedor_cuentas.php
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Solo Presupuesto o Admin o SYSADMIN
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'PRESUPUESTO' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Configuración exclusiva de Finanzas.");
}

$mensaje = '';
$tipo_mensaje = '';

// --- LÓGICA POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // A. GUARDAR (CREAR / EDITAR)
        if ($_POST['accion'] === 'guardar') {
            $id = $_POST['id'] ?? null;
            $cod = trim($_POST['codigo']);
            $nom = trim($_POST['nombre']);

            if ($id) {
                // Editar
                $stmt = $pdo->prepare("UPDATE cuentas_maestras SET codigo=?, nombre=? WHERE id=?");
                $stmt->execute([$cod, $nom, $id]);
                $mensaje = "Cuenta maestra actualizada.";
            } else {
                // Crear
                // Validar duplicado de código
                $check = $pdo->prepare("SELECT id FROM cuentas_maestras WHERE codigo = ?");
                $check->execute([$cod]);
                if ($check->fetch()) throw new Exception("El código de cuenta '$cod' ya existe.");

                $stmt = $pdo->prepare("INSERT INTO cuentas_maestras (codigo, nombre) VALUES (?, ?)");
                $stmt->execute([$cod, $nom]);
                $mensaje = "Nueva cuenta creada exitosamente.";
            }
            $tipo_mensaje = "success";
        }

        // B. ELIMINAR
        if ($_POST['accion'] === 'eliminar') {
            $id = $_POST['id'];

            // 1. Validar uso en Presupuestos Asignados
            $check1 = $pdo->prepare("SELECT COUNT(*) FROM presupuestos_asignados WHERE cuenta_maestra_id = ?");
            $check1->execute([$id]);
            if ($check1->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar: Esta cuenta ya ha sido distribuida a Centros de Costo.");
            }

            // 2. Proceder a borrar
            $pdo->prepare("DELETE FROM cuentas_maestras WHERE id = ?")->execute([$id]);
            $mensaje = "Cuenta maestra eliminada del catálogo.";
            $tipo_mensaje = "success";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// CONSULTA LISTADO
$cuentas = $pdo->query("SELECT * FROM cuentas_maestras ORDER BY codigo ASC")->fetchAll();

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan de Cuentas Global - Gestión OPI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light text-slate-800 pb-20 font-sans">

    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Inclusión de Bootstrap 5 JS Bundle después de nav.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <div class="container mt-4 px-3 px-md-4">

        <!-- CABECERA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-list-columns-reverse text-primary"></i>
                    Catálogo Global de Cuentas
                </h1>
                <p class="text-muted small mb-0">Definición del plan de cuentas municipal.</p>
            </div>
            
            <div class="col-12 col-md-auto text-start text-md-end">
                <div class="d-flex gap-2">
                    <a href="centros_de_costo.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Ir a Presupuestos
                    </a>
                    <button onclick="abrirModal()" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm d-flex align-items-center gap-1.5">
                        <i class="bi bi-plus-lg"></i> Nueva Cuenta Maestra
                    </button>
                </div>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if($mensaje): ?>
            <?php 
            $alertClass = ($tipo_mensaje === 'error') ? 'danger' : 'success';
            $iconClass = ($tipo_mensaje === 'error') ? 'exclamation-triangle-fill' : 'check-circle-fill';
            ?>
            <div class="alert alert-<?= $alertClass ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $iconClass ?> shrink-0"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- LISTADO DE CUENTAS -->
        <div class="card shadow-sm border-light">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Plan de Cuentas Municipal</h6>
            </div>
            <div class="table-responsive rounded-bottom">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small" style="font-size: 10px;">
                        <tr>
                            <th class="p-3" style="width: 180px;">Código Cuenta</th>
                            <th class="p-3">Nombre Descriptivo</th>
                            <th class="p-3 text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php foreach($cuentas as $c): ?>
                        <tr>
                            <td class="p-3">
                                <span class="badge bg-light text-secondary font-monospace border px-2.5 py-1.5 fs-6">
                                    <?= htmlspecialchars($c['codigo']) ?>
                                </span>
                            </td>
                            <td class="p-3 fw-semibold text-dark small">
                                <?= htmlspecialchars($c['nombre']) ?>
                            </td>
                            <td class="p-3 text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1.5">
                                    <button onclick='abrirModal(<?= json_encode($c) ?>)' class="btn btn-outline-primary btn-sm px-2" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    
                                    <form method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta cuenta del catálogo? Si ya ha sido asignada a algún centro de costo, la operación fallará.')" class="d-inline mb-0">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($cuentas)): ?>
                            <tr>
                                <td colspan="3" class="p-4 text-center text-muted italic">No hay cuentas maestras definidas en el catálogo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: GESTIÓN DE CUENTA -->
    <div class="modal fade" id="modalCta" tabindex="-1" aria-labelledby="modalCtaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-2xl border-light">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="modalTitle">Nueva Cuenta Maestra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="modal-body p-4">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="id" id="formId">
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Código Presupuestario</label>
                                <input name="codigo" id="formCodigo" class="form-control font-mono" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Nombre de la Cuenta</label>
                                <input name="nombre" id="formNombre" class="form-control bg-white" required>
                            </div>
                            

                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Guardar Cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS LOGIC -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.abrirModal = function(data = null) {
                const modalEl = document.getElementById('modalCta');
                const bootstrapModal = new bootstrap.Modal(modalEl);

                if (data) {
                    document.getElementById('modalTitle').innerText = "Editar Cuenta Maestra";
                    document.getElementById('formId').value = data.id;
                    document.getElementById('formCodigo').value = data.codigo;
                    document.getElementById('formNombre').value = data.nombre;
                } else {
                    document.getElementById('modalTitle').innerText = "Nueva Cuenta Maestra";
                    document.getElementById('formId').value = "";
                    document.getElementById('formCodigo').value = "";
                    document.getElementById('formNombre').value = "";
                }
                
                bootstrapModal.show();
            };
        });
    </script>
</body>
</html>