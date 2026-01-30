<?php
// FORZAR HORA COLOMBIA
date_default_timezone_set('America/Bogota');

// Incluir configuración y PHPMailer
require 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Conexión con PDO y zona horaria
try {
    $db->exec("SET time_zone = '-05:00';");
} catch (Exception $e) { die("Error DB"); }

$ahora = date('Y-m-d H:i:s');
$hoy   = date('Y-m-d');
$hora_actual = date('H:i:s');

echo "<h2>Ejecutando Recordatorios... ($ahora)</h2>";

// BUSCAMOS SOLICITUDES APROBADAS Y PENDIENTES DE RECORDATORIO
// Solo traemos las que sean de hoy o futuro cercano
$sql = "SELECT * FROM solicitudes 
        WHERE estado = 'Aprobado' 
        AND recordatorio_enviado = 0 
        AND fecha_inicio >= ?";

$stmt = $db->prepare($sql);
$stmt->execute([$hoy]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$enviados = 0;

foreach ($solicitudes as $sol) {
    $enviar = false;
    $tipo   = "";
    
    // CASO 1: DÍA COMPLETO (Avisar a las 7:00 AM del mismo día)
    // Se detecta si hora_inicio es 00:00:00 o vacío
    if ($sol['hora_inicio'] == '00:00:00' || empty($sol['hora_inicio'])) {
        // Si la fecha es HOY y ya son las 7am o más
        if ($sol['fecha_inicio'] == $hoy && date('H') >= 7) {
            $enviar = true;
            $tipo   = "Día Completo (Aviso 7:00 AM)";
        }
    } 
    // CASO 2: POR HORAS (Avisar 1 hora antes)
    else {
        // Solo si es HOY
        if ($sol['fecha_inicio'] == $hoy) {
            $inicio_permiso = strtotime($sol['fecha_inicio'] . ' ' . $sol['hora_inicio']);
            $tiempo_ahora   = strtotime($ahora);
            $diferencia_minutos = ($inicio_permiso - $tiempo_ahora) / 60;

            // Si falta entre 0 y 65 minutos para que empiece (damos margen de 5 min por el Cron)
            if ($diferencia_minutos > 0 && $diferencia_minutos <= 65) {
                $enviar = true;
                $tipo   = "Por Horas (Falta 1 hora)";
            }
        }
    }

    // SI CUMPLE CONDICIONES, ENVIAMOS CORREO
    if ($enviar) {
        enviarRecordatorio($sol, $tipo);
        
        // MARCAR COMO ENVIADO EN BD
        $upd = $db->prepare("UPDATE solicitudes SET recordatorio_enviado = 1 WHERE id = ?");
        $upd->execute([$sol['id']]);
        
        $enviados++;
        echo "✅ Recordatorio enviado para ID {$sol['id']} ($tipo)<br>";
    }
}

echo "<hr>Total enviados: $enviados";


// FUNCIÓN DE ENVÍO
function enviarRecordatorio($sol, $tipo_aviso) {
    $mail = new PHPMailer(true);
    try {
        // Configuración SMTP (Igual a procesar.php)
        $mail->isSMTP();
        $mail->Host       = 'smtp.zoho.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'permisos-agrocosta@zohomail.com'; 
        $mail->Password   = 'Bm7Y7i90q0tr'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465; 

        $mail->setFrom('permisos-agrocosta@zohomail.com', 'Agro-Costa Alertas');
        
        // DESTINATARIOS: Jefe y rrosado
        $mail->addAddress($sol['correo_jefe']); 
        $mail->addAddress('rrosado@agro-costa.com'); 

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "🔔 RECORDATORIO: Permiso de {$sol['empleado']} (Hoy)";
        
        // Definir horario texto
        $horario_txt = ($sol['hora_inicio'] == '00:00:00' || empty($sol['hora_inicio'])) 
                       ? "Todo el día" 
                       : date("g:i A", strtotime($sol['hora_inicio'])) . " a " . date("g:i A", strtotime($sol['hora_fin']));

        $mail->Body = "
            <div style='background-color: #f8f9fa; padding: 20px; font-family: sans-serif;'>
                <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border: 1px solid #FFCD00; border-radius: 10px; overflow: hidden;'>
                    <div style='background-color: #FFCD00; padding: 15px; text-align: center;'>
                        <strong style='font-size: 18px;'>🔔 RECORDATORIO AUTOMÁTICO</strong>
                    </div>
                    <div style='padding: 20px;'>
                        <p style='color: #555;'>Este es un recordatorio de que el siguiente permiso inicia pronto o está vigente hoy:</p>
                        <ul style='line-height: 1.8;'>
                            <li><strong>Empleado:</strong> {$sol['empleado']}</li>
                            <li><strong>Motivo:</strong> {$sol['motivo']}</li>
                            <li><strong>Horario:</strong> $horario_txt</li>
                        </ul>
                        <p style='font-size: 12px; color: #999; margin-top: 20px; text-align: center;'>
                            Este correo es solo informativo. No requiere respuesta.
                        </p>
                    </div>
                </div>
            </div>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        echo "Error Mailer: " . $e->getMessage();
    }
}
?>