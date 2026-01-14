<?php
include('config.php');
if (!isset($_GET['id'])) { die("ID faltante"); }
$id = $_GET['id'];

$stmt = $db->prepare("SELECT * FROM solicitudes WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) { die("Solicitud no encontrada."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Gestión AgroCosta</title>
</head>
<body class="bg-light py-5">
    <div class="container card shadow" style="max-width: 500px; border-top: 5px solid #FFCD00;">
        <div class="card-body text-center">
            <h4>Solicitud de <?php echo $s['empleado']; ?></h4>
            <p class="text-muted"><?php echo $s['motivo']; ?></p>
            <hr>
            <?php if ($s['estado'] == 'Pendiente'): ?>
                <form action="aprobar.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <textarea name="observacion_jefe" class="form-control mb-3" placeholder="Comentarios..."></textarea>
                    <div class="d-flex gap-2">
                        <button name="accion" value="Aprobado" class="btn btn-success w-100">APROBAR</button>
                        <button name="accion" value="Rechazado" class="btn btn-danger w-100">RECHAZAR</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-secondary">Esta solicitud ya es: <strong><?php echo $s['estado']; ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>