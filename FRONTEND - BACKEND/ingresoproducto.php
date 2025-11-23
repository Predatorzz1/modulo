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
        $id_proveedor = $_POST['id_proveedor'];
        $id_producto = $_POST['id_producto'];
        $stmt = $conn->prepare("BEGIN MVCD_INGRESO_PRODUCTO(:id_proveedor,:id_producto); END;");
        $stmt->bindParam(':id_proveedor', $id_proveedor);
        $stmt->bindParam(':id_producto', $id_producto);
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
    <title>Ingreso producto</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Ingreso producto</h1>
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
                    <form method="POST" action="ingresoproducto.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>ID_PROVEEDOR:</label>
                        <input type="number" name="id_proveedor" required>
                        <label>ID_PRODUCTO:</label>
                        <input type="number" name="id_producto" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            }
            container.innerHTML = html;
        }
    </script>
</body>
</html>