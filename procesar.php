<?php
// Forzar zona horaria
date_default_timezone_set('America/Bogota');

include('config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CAPTURA DE DATOS
    $empleado     = $_POST['empleado'];
    $cedula       = $_POST['cedula']; 
    $correo_jefe  = $_POST['correo_jefe'];
    $motivo       = $_POST['motivo'];
    $cargo        = $_POST['cargo']; 
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin    = $_POST['fecha_fin'];
    $notas        = $_POST['notas'] ?? '';

    // --- CORRECCIÓN DE FORMATO DE HORA PARA MYSQL ---
    $hora_inicio_raw = $_POST['hora_inicio'];
    $hora_fin_raw    = $_POST['hora_fin'];

    // Convertimos "8:10 AM" a "08:10:00" (formato 24h que acepta la BD)
    $h_ini = (!empty($hora_inicio_raw)) ? date("H:i:s", strtotime($hora_inicio_raw)) : '00:00:00';
    $h_fin = (!empty($hora_fin_raw)) ? date("H:i:s", strtotime($hora_fin_raw)) : '00:00:00';
    // ------------------------------------------------

    // PROCESAMIENTO DE ARCHIVO SOPORTE
    $nombre_archivo = null;
    $link_soporte_html = "";
    if (isset($_FILES['soporte']) && $_FILES['soporte']['error'] == 0) {
        $ext = pathinfo($_FILES['soporte']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = time() . "_soporte." . $ext;
        move_uploaded_file($_FILES['soporte']['tmp_name'], "uploads/" . $nombre_archivo);
        $link_soporte_html = "<p style='color: #ffffff;'><strong>Soporte adjunto:</strong> <a href='https://agro-costa.com/empleados/uploads/$nombre_archivo' style='color: #FFCD00;'>Ver Documento</a></p>";
    }

    try {
        $sql = "INSERT INTO solicitudes (empleado, cedula, cargo, motivo, fecha_inicio, fecha_fin, hora_inicio, hora_fin, archivo_soporte, correo_jefe, notas, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $empleado, 
            $cedula, 
            $cargo, 
            $motivo, 
            $fecha_inicio, 
            $fecha_fin, 
            $h_ini, // Ya convertido a 24h
            $h_fin, // Ya convertido a 24hf
            $nombre_archivo, 
            $correo_jefe, 
            $notas
        ]);
        
        $id = $db->lastInsertId();
        $url = "https://agro-costa.com/empleados/gestionar.php?id=" . $id;

        // CONFIGURACIÓN DE ENVÍO DE CORREO (ESTILO CATERPILLAR)
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.zoho.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'permisos-agrocosta@zohomail.com'; 
        $mail->Password   = 'Bm7Y7i90q0tr'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465; 

        $mail->setFrom('permisos-agrocosta@zohomail.com', 'Agro-Costa RRHH');
        $mail->addAddress($correo_jefe);
        $mail->addCC('rrosado@agro-costa.com');
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "NUEVA SOLICITUD: $empleado ($motivo)";
        
        $mail->Body = "
            <div style='background-color: #f4f4f4; padding: 20px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #111111; border: 2px solid #FFCD00; border-radius: 20px; overflow: hidden;'>
                    <div style='background-color: #FFCD00; color: #000; padding: 25px; text-align: center;'>
                        <h2 style='margin: 0; text-transform: uppercase; font-weight: 800;'>AGRO-COSTA: Nueva Solicitud</h2>
                    </div>
                    <div style='padding: 30px; color: #ffffff; line-height: 1.6;'>
                        <p style='font-size: 16px;'>El empleado <strong>$empleado</strong> ha solicitado un permiso:</p>
                        <hr style='border: 0; border-top: 1px solid #333;'>
                        <p><strong>Cargo:</strong> $cargo</p>
                        <p><strong>Motivo:</strong> $motivo</p>
                        <p><strong>Fechas:</strong> Del $fecha_inicio al $fecha_fin</p>
                        <p><strong>Horario:</strong> " . ($hora_inicio_raw ?: "Día completo") . " a " . ($hora_fin_raw ?: "Día completo") . "</p>
                        <p><strong>Notas:</strong> <em>" . ($notas ?: 'Sin notas') . "</em></p>
                        $link_soporte_html
                        <div style='text-align: center; margin-top: 40px;'>
                            <a href='$url' style='background-color: #FFCD00; color: #000; padding: 18px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; display: inline-block;'>GESTIONAR SOLICITUD</a>
                        </div>
                    </div>
                </div>
            </div>";
        
        $mail->send();
        header("Location: index.php?enviado=1");
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}