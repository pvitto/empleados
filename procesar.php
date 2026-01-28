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

    $hora_inicio_raw = $_POST['hora_inicio'];
    $hora_fin_raw    = $_POST['hora_fin'];
    $h_ini = (!empty($hora_inicio_raw)) ? date("H:i:s", strtotime($hora_inicio_raw)) : '00:00:00';
    $h_fin = (!empty($hora_fin_raw)) ? date("H:i:s", strtotime($hora_fin_raw)) : '00:00:00';

    // HORA EXACTA DE ENVÍO
    $ahora_envio = date("Y-m-d H:i:s");

    // --- PROCESAMIENTO MÚLTIPLE ---
    $nombres_guardados = [];
    $html_adjuntos_email = "";

    if (isset($_FILES['soporte']) && count($_FILES['soporte']['name']) > 0) {
        $total = count($_FILES['soporte']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['soporte']['error'][$i] == 0 && !empty($_FILES['soporte']['name'][$i])) {
                $nombre_original = $_FILES['soporte']['name'][$i];
                $ext = pathinfo($nombre_original, PATHINFO_EXTENSION);
                $nuevo_nombre = uniqid() . "__" . $nombre_original; // Separador __
                $ruta_destino = "uploads/" . $nuevo_nombre;
                
                if (move_uploaded_file($_FILES['soporte']['tmp_name'][$i], $ruta_destino)) {
                    $nombres_guardados[] = $nuevo_nombre;
                    $url_codificada = "https://agro-costa.com/empleados/uploads/" . rawurlencode($nuevo_nombre);
                    $html_adjuntos_email .= "<div style='margin-top:5px;'><a href='$url_codificada' style='color: #FFCD00; text-decoration:none;'>📎 $nombre_original</a></div>";
                }
            }
        }
    }
    $string_archivos_bd = implode(',', $nombres_guardados);
    if (empty($html_adjuntos_email)) { $html_adjuntos_email = "<span style='color:#777;'>Sin soportes adjuntos.</span>"; }

    try {
        // INCLUIMOS fecha_solicitud EN EL INSERT
        $sql = "INSERT INTO solicitudes (empleado, cedula, cargo, motivo, fecha_inicio, fecha_fin, hora_inicio, hora_fin, archivo_soporte, correo_jefe, notas, fecha_solicitud, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $empleado, $cedula, $cargo, $motivo, $fecha_inicio, $fecha_fin, $h_ini, $h_fin, 
            $string_archivos_bd, $correo_jefe, $notas, $ahora_envio
        ]);
        
        $id = $db->lastInsertId();
        $url = "https://agro-costa.com/empleados/gestionar.php?id=" . $id;

        // ENVÍO CORREO
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
                        <div style='background: #222; padding: 15px; border-radius: 10px; margin-top: 15px;'>
                            <strong style='color: #FFCD00;'>Soportes Adjuntos:</strong><br>
                            $html_adjuntos_email
                        </div>
                        <div style='text-align: center; margin-top: 40px;'>
                            <a href='$url' style='background-color: #FFCD00; color: #000; padding: 18px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; display: inline-block;'>GESTIONAR SOLICITUD</a>
                        </div>
                    </div>
                </div>
            </div>";
        
        $mail->send();
        header("Location: index.php?enviado=1");
    } catch (Exception $e) { die("Error: " . $e->getMessage()); }
}
?>