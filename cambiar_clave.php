<?php
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $actual = $_POST['clave_actual'];
    $nueva = $_POST['clave_nueva'];
    $confirmar = $_POST['clave_confirmar'];

    if ($nueva !== $confirmar) {
        echo "<script>alert('Las contraseñas nuevas no coinciden.'); window.location.href='index.php';</script>";
        exit();
    }

    $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar la contraseña actual
    if (password_verify($actual, $user['password'])) {
        // Encriptar la nueva
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $upd = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $upd->execute([$hash, $_SESSION['user_id']]);
        echo "<script>alert('Contraseña actualizada correctamente.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('La contraseña actual es incorrecta.'); window.location.href='index.php';</script>";
    }
} else {
    header("Location: index.php");
}
?>