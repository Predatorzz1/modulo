<?php
// Configuración de conexión a Oracle
$host = 'localhost';
$port = '1521';
$dbname = 'XE'; // Cambia según tu configuración
$username = 'HR';
$password = '123';

try {
    $dsn = "oci:dbname=//$host:$port/$dbname;charset=UTF8";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Manejar la acción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'insertar') {
        $id_promocion = $_POST['id_promocion'];
        $id_cabecera_venta = $_POST['id_cabecera_venta'];
        $stmt = $conn->prepare("BEGIN MVCD_APLICAR_PROMOCION(:id_promocion,:id_cabecera_venta); END;");
        $stmt->bindParam(':id_promocion', $id_promocion);
        $stmt->bindParam(':id_cabecera_venta', $id_cabecera_venta);
        $stmt->execute();
        echo "Registro insertado exitosamente.";
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicar Promoción</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Aplicar Promoción</h1>
    <div class="button-container">
        <button id = "insertar" onclick="showForm('insertar')">Insertar</button>
        <a href="admin.html"><button id = "volver">Volver</button></a>
    </div>
    <div id="formContainer"></div>
    <div id="output"></div>

    <script>
        function showForm(action) {
            const container = document.getElementById('formContainer');
            let html = '';
            if (action === 'insertar') {
                html = `
                    <form method="POST" action="aplicarpromocion.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>ID_PROMOCION:</label>
                        <input type="number" name="id_promocion" required>
                        <label>ID_CABECERA_VENTA:</label>
                        <input type="number" name="id_cabecera_venta" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            }
            container.innerHTML = html;
        }
    </script>
</body>
</html>