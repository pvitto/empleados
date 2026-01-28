<?php
session_start();
include('config.php');

// 1. Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 2. Obtener datos de la solicitud para verificar dueño y estado
    // Solo permitimos borrar si la cédula coincide con la sesión Y si el estado es 'Pendiente'
    $stmt = $db->prepare("SELECT * FROM solicitudes WHERE id = ?");
    $stmt->execute([$id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($solicitud) {
        // Obtenemos la cédula del usuario logueado
        $stmtUser = $db->prepare("SELECT cedula FROM usuarios WHERE id = ?");
        $stmtUser->execute([$_SESSION['user_id']]);
        $mi_usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

        // CONDICIÓN DE ORO: ¿Es mi solicitud? Y ¿Está pendiente?
        if ($solicitud['cedula'] == $mi_usuario['cedula'] && $solicitud['estado'] == 'Pendiente') {
            
            // 3. Borrar los archivos físicos del servidor (Limpieza)
            if (!empty($solicitud['archivo_soporte'])) {
                $archivos = explode(',', $solicitud['archivo_soporte']);
                foreach ($archivos as $archivo) {
                    $ruta = "uploads/" . trim($archivo);
                    if (file_exists($ruta)) {
                        unlink($ruta); // Borra el archivo
                    }
                }
            }

            // 4. Borrar el registro de la Base de Datos
            $del = $db->prepare("DELETE FROM solicitudes WHERE id = ?");
            $del->execute([$id]);

            echo "<script>alert('Solicitud eliminada correctamente.'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('No puedes eliminar esta solicitud (Ya fue gestionada o no es tuya).'); window.location.href='index.php';</script>";
        }
    } else {
        header("Location: index.php");
    }
} else {
    header("Location: index.php");
}
?>