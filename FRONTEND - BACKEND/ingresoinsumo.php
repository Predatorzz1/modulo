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
        $id_insumo = $_POST['id_insumo'];
        $stmt = $conn->prepare("BEGIN MVCD_INGRESO_INSUMO(:id_proveedor,:id_insumo); END;");
        $stmt->bindParam(':id_proveedor', $id_proveedor);
        $stmt->bindParam(':id_insumo', $id_insumo);
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
    <title>Ingreso insumo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Ingreso insumo</h1>
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
                    <form method="POST" action="ingresoinsumo.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>ID_PROVEEDOR:</label>
                        <input type="number" name="id_proveedor" required>
                        <label>ID_INSUMO:</label>
                        <input type="number" name="id_insumo" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            }
            container.innerHTML = html;
        }
    </script>
</body>
</html>