<?php
include('config.php');
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Buscamos por nombre de usuario O por correo
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? OR correo = ?");
    $stmt->execute([$usuario, $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // CAMBIO IMPORTANTE: Usamos password_verify para validar la encriptación
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol']     = $user['rol'];
        $_SESSION['nombre']  = $user['nombre_completo'];
        $_SESSION['correo']  = $user['correo'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Credenciales incorrectas.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgroCosta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #F7931E 0%, #FFCD00 100%);
            height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            width: 100%; max-width: 380px; padding: 2.5rem;
            border-radius: 12px; background: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border-bottom: 5px solid #000;
        }
        .logo-container { text-align: center; margin-bottom: 20px; }
        .logo-container img { max-width: 180px; height: auto; }
        .btn-dark-cat {
            background-color: #000; border: none; color: #FFCD00;
            font-weight: bold; padding: 10px; transition: 0.3s;
        }
        .btn-dark-cat:hover { background-color: #333; color: #fff; }
        .form-control:focus { border-color: #F7931E; box-shadow: 0 0 0 0.25rem rgba(247, 147, 30, 0.2); }
        .link-olvido { color: #555; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .link-olvido:hover { color: #000; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="logo.png" alt="AgroCosta">
        </div>
        <h6 class="text-center mb-4 fw-bold text-dark">ACCESO A EMPLEADOS</h6>
        <?php if($error): ?>
            <div class="alert alert-danger py-1 small text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="Nombre de usuario" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <div class="text-end mt-2">
                    <a href="recuperar.php" class="link-olvido">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            <button type="submit" class="btn btn-dark-cat w-100">INGRESAR</button>
        </form>
    </div>
</body>
</html>