<?php
// Obtiene el listado de usuarios desde el archivo
$users = include('usuarios.php');

// Recoge los datos enviados desde el formulario
$username = $_POST['username'];
$password = $_POST['password'];

foreach ($users as $user) {
    if ($user['username'] === $username && $password === $user['password']) {
        // Inicio de sesión exitoso
        header('Location: admin.html');
        exit;
    }
}

// Si las credenciales no coinciden, regresa al login
echo "Usuario o contraseña incorrectos.";
echo '<br><a href="login.html">Volver a intentarlo</a>';
?>
