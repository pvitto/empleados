<?php
include('config.php');

// CANDADO 1: Verificar si hay sesión iniciada
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, guardar la URL a la que quería ir y mandar al login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$id_solicitud = $_GET['id'] ?? null;

if (!$id_solicitud) {
    die("Error: Faltan datos.");
}

// Obtenemos los datos de la solicitud
$stmt = $db->prepare("SELECT * FROM solicitudes WHERE id = ?");
$stmt->execute([$id_solicitud]);
$solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitud) {
    die("Solicitud no encontrada.");
}

// CANDADO 2: Verificar Autorización
// Solo puede ver esto: El Jefe asignado, Un Admin, o el propio empleado (solo ver)
$soy_el_jefe = ($_SESSION['correo'] == $solicitud['correo_jefe']);
$soy_admin   = ($_SESSION['rol'] == 'admin');

if (!$soy_el_jefe && !$soy_admin) {
    // Si no es el jefe ni admin, bloqueo total (o redirescción)
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'>
            <h1>🚫 ACCESO DENEGADO</h1>
            <p>No tienes permisos para gestionar esta solicitud.</p>
            <p>Esta solicitud pertenece al jefe: <strong>{$solicitud['correo_jefe']}</strong></p>
            <a href='index.php'>Volver al inicio</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Solicitud | Agro-Costa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #F5F5F7; }
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
        .header-top { background: #111; color: #FFCD00; padding: 20px; text-align: center; border-bottom: 4px solid #FFCD00; }
        .label-dato { font-size: 0.8rem; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .valor-dato { font-size: 1.1rem; font-weight: 500; color: #111; margin-bottom: 15px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5">

<div class="container" style="max-width: 600px;">
    <div class="card card-custom">
        <div class="header-top">
            <h4 class="m-0 fw-bold">GESTIONAR SOLICITUD #<?php echo $solicitud['id']; ?></h4>
        </div>
        <div class="card-body p-4">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="label-dato">Empleado</div>
                    <div class="valor-dato"><?php echo $solicitud['empleado']; ?></div>
                </div>
                <div class="col-md-6">
                    <div class="label-dato">Cédula</div>
                    <div class="valor-dato"><?php echo $solicitud['cedula']; ?></div>
                </div>
            </div>

            <hr class="text-muted">

            <div class="mb-3">
                <div class="label-dato">Motivo</div>
                <div class="valor-dato"><?php echo $solicitud['motivo']; ?></div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="label-dato">Desde</div>
                    <div class="valor-dato"><?php echo $solicitud['fecha_inicio']; ?></div>
                </div>
                <div class="col-6">
                    <div class="label-dato">Hasta</div>
                    <div class="valor-dato"><?php echo $solicitud['fecha_fin']; ?></div>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="label-dato">Horario</div>
                <div class="valor-dato">
                    <?php 
                    echo ($solicitud['hora_inicio'] == '00:00:00' || empty($solicitud['hora_inicio'])) 
                        ? 'Día completo' 
                        : date('g:i A', strtotime($solicitud['hora_inicio'])) . " - " . date('g:i A', strtotime($solicitud['hora_fin'])); 
                    ?>
                </div>
            </div>

            <?php if($solicitud['archivo_soporte']): ?>
            <div class="mb-4 text-center">
                <a href="uploads/<?php echo $solicitud['archivo_soporte']; ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-paperclip"></i> Ver Soporte Adjunto
                </a>
            </div>
            <?php endif; ?>

            <form action="aprobar.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $solicitud['id']; ?>">
                
                <div class="mb-3">
                    <label class="fw-bold small mb-1">Observaciones (Opcional)</label>
                    <textarea name="observacion_jefe" class="form-control" rows="2" placeholder="Escribe un comentario..."></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="accion" value="Aprobado" class="btn btn-success fw-bold py-2">✅ APROBAR PERMISO</button>
                    <button type="submit" name="accion" value="Rechazado" class="btn btn-danger fw-bold py-2">❌ RECHAZAR PERMISO</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <a href="index.php" class="text-muted small text-decoration-none">Volver al panel</a>
            </div>

        </div>
    </div>
</div>

</body>
</html>