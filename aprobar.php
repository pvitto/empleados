<?php
date_default_timezone_set('America/Bogota');
include('config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_SESSION['user_id'])) { die("Acceso denegado."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $accion = $_POST['accion']; 
    $obs = $_POST['observacion_jefe'];

    $stmt = $db->prepare("SELECT s.*, u.correo as correo_user FROM solicitudes s JOIN usuarios u ON s.cedula = u.cedula WHERE s.id = ?");
    $stmt->execute([$id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sol) { die("Error: Solicitud no encontrada."); }

    // Validación de seguridad (Dueño o Admin)
    $soy_el_jefe = ($_SESSION['correo'] == $sol['correo_jefe']);
    $soy_admin   = ($_SESSION['rol'] == 'admin');

    if (!$soy_el_jefe && !$soy_admin) {
        die("SEGURIDAD: No tienes permiso para gestionar esta solicitud.");
    }

    // DATOS DE AUDITORÍA
    $ip_cliente = $_SERVER['REMOTE_ADDR'];
    $dispositivo = $_SERVER['HTTP_USER_AGENT'];
    $ahora = date("Y-m-d H:i:s");
    $quien_gestiona = $_SESSION['nombre']; // Nombre del Jefe/Admin que aprobó
    // -------------------

    $upd = $db->prepare("UPDATE solicitudes SET estado = ?, observacion_jefe = ?, ip_aprobacion = ?, info_dispositivo = ?, fecha_gestion = ?, usuario_gestor = ? WHERE id = ?");
    $upd->execute([$accion, $obs, $ip_cliente, $dispositivo, $ahora, $quien_gestiona, $id]);

    // ENVÍO DE CORREO
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.zoho.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'permisos-agrocosta@zohomail.com'; 
        $mail->Password   = 'Bm7Y7i90q0tr'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465; 

        $mail->setFrom('permisos-agrocosta@zohomail.com', 'Agro-Costa RRHH');
        $mail->addAddress($sol['correo_user']); 
        $mail->addCC('rrosado@agro-costa.com'); 

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "RESPUESTA PERMISO: {$sol['motivo']} ($accion)";
        
        $accent = ($accion == 'Aprobado') ? '#34C759' : '#FF3B30';
        
        $mail->Body = "
            <div style='background-color: #F5F5F7; padding: 40px; font-family: sans-serif; color: #111;'>
                <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 24px; overflow: hidden; border: 1px solid #d2d2d7;'>
                    <div style='background-color: #111111; padding: 30px; text-align: center; border-bottom: 5px solid #FFCD00;'>
                        <h2 style='margin: 0; color: #FFCD00; font-size: 18px; text-transform: uppercase;'>AGRO-COSTA RRHH</h2>
                    </div>
                    <div style='padding: 40px;'>
                        <p>Hola <strong>{$sol['empleado']}</strong>,</p>
                        <div style='background-color: #F5F5F7; padding: 25px; border-radius: 20px; border-left: 6px solid $accent;'>
                            <p><strong>ESTADO:</strong> <span style='color: $accent; font-weight:bold;'>$accion</span></p>
                            <p><strong>OBSERVACIONES:</strong><br><em>" . ($obs ?: 'Ninguna') . "</em></p>
                        </div>
                    </div>
                </div>
            </div>";
        
        $mail->send();
    } catch (Exception $e) { }

    echo "<script>alert('Solicitud procesada.'); window.location.href='index.php';</script>";
}
?>