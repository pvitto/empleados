<?php
include('config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $accion = $_POST['accion']; 
    $obs = $_POST['observacion_jefe'];

    // Obtenemos la solicitud y cruzamos con usuarios mediante la Cédula para obtener el correo
    $stmt = $db->prepare("SELECT s.*, u.correo FROM solicitudes s 
                          JOIN usuarios u ON s.cedula = u.cedula 
                          WHERE s.id = ?");
    $stmt->execute([$id]);
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sol) { die("Error: Solicitud no encontrada."); }

    // Actualizamos la base de datos
    $upd = $db->prepare("UPDATE solicitudes SET estado = ?, observacion_jefe = ? WHERE id = ?");
    $upd->execute([$accion, $obs, $id]);

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
        $mail->addAddress($sol['correo']); 
        $mail->addCC('rrosado@agro-costa.com'); 

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "RESPUESTA PERMISO: {$sol['motivo']} ($accion)";
        
        $accent = ($accion == 'Aprobado') ? '#34C759' : '#FF3B30';
        
        $mail->Body = "
            <div style='background-color: #F5F5F7; padding: 40px; font-family: sans-serif; color: #111;'>
                <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 24px; overflow: hidden; border: 1px solid #d2d2d7; box-shadow: 0 10px 40px rgba(0,0,0,0.06);'>
                    <div style='background-color: #111111; padding: 30px; text-align: center; border-bottom: 5px solid #FFCD00;'>
                        <h2 style='margin: 0; color: #FFCD00; font-size: 18px; text-transform: uppercase;'>AGRO-COSTA RRHH</h2>
                    </div>
                    <div style='padding: 40px;'>
                        <p style='font-size: 16px; margin-bottom: 20px;'>Hola <strong>{$sol['empleado']}</strong>,</p>
                        
                        <div style='background-color: #F5F5F7; padding: 25px; border-radius: 20px; border-left: 6px solid $accent;'>
                            <p style='margin: 0 0 10px 0;'><strong>TIPO DE PERMISO:</strong> <span style='color: #111;'>{$sol['motivo']}</span></p>
                            
                            <p style='margin: 0;'><strong>ESTADO FINAL:</strong> <span style='color: $accent; font-weight:bold;'>$accion</span></p>
                            
                            <hr style='border:0; border-top:1px solid #d2d2d7; margin:15px 0;'>
                            
                            <p style='margin: 0;'><strong>COMENTARIOS DEL JEFE:</strong><br>
                            <span style='color: #424245; font-style: italic;'>\"" . ($obs ?: 'Sin observaciones adicionales.') . "\"</span></p>
                        </div>

                        <p style='text-align: center; color: #86868b; font-size: 11px; margin-top: 40px;'>
                            Agro-Costa S.A.S - Expertos en Maquinaria Pesada
                        </p>
                    </div>
                </div>
            </div>";
        
        $mail->send();
    } catch (Exception $e) { }

    echo "<script>alert('Solicitud procesada: $accion.'); window.location.href='index.php';</script>";
}