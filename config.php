<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$host = 'localhost';
$db_name = 'agrocosta_empleados'; // Nombre exacto de tu nueva BD
$user = 'agrocosta_rrhhagr';                  // Cambia por tu usuario de producción
$pass = 'BvNZGAY*^9ac-N*';                      // Cambia por tu clave de producción

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>