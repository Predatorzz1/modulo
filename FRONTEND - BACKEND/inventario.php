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
        $cantidad_inventario = $_POST['cantidad_inventario'];
        $id_producto = $_POST['id_producto'];
        $stmt = $conn->prepare("BEGIN MVCD_C_INVENTARIO(:cantidad_inventario,:id_producto); END;");
        $stmt->bindParam(':cantidad_inventario', $cantidad_inventario);
        $stmt->bindParam(':id_producto', $id_producto);
        $stmt->execute();
        echo "Registro insertado exitosamente.";
    } elseif ($action === 'listar') {
        $dsn = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$dbname)))";

        // Conexión a Oracle
        $conn = oci_connect($username, $password, $dsn);
        if (!$conn) {
            $e = oci_error();
            echo "Error de conexión: " . $e['message'];
            exit;
        }

        // Llamada al procedimiento almacenado
        $sql = "BEGIN MVCD_R_INVENTARIO(:p_cursor); END;";
        $stid = oci_parse($conn, $sql);

        // Crear cursor
        $cursor = oci_new_cursor($conn);

        // Vincular el cursor al procedimiento
        oci_bind_by_name($stid, ":p_cursor", $cursor, -1, OCI_B_CURSOR);

        // Ejecutar el procedimiento
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            echo "Error al ejecutar el procedimiento: " . $e['message'];
            exit;
        }

        // Ejecutar el cursor
        if (!oci_execute($cursor)) {
            $e = oci_error($cursor);
            echo "Error al ejecutar el cursor: " . $e['message'];
            exit;
        }
        // ID_INVENTARIO, CANTIDAD_INVENTARIO, ID_PRODUCTO

        // Mostrar resultados en una tabla HTML
        echo "<table id ='tableContainer' border='1'>";
        echo "<tr><th>ID INVENTARIO</th><th>CANTIDAD INVENTARIO</th><th>ID_PRODUCTO</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_INVENTARIO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CANTIDAD_INVENTARIO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_PRODUCTO']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Liberar recursos y cerrar conexión
        oci_free_statement($cursor);
        oci_free_statement($stid);
        oci_close($conn);
    } elseif ($action === 'borrar') {
        $id_inventario = $_POST['id_inventario'];
        $stmt = $conn->prepare("BEGIN MVCD_D_INVENTARIO(:id_inventario); END;");
        $stmt->bindParam(':id_inventario', $id_inventario);
        $stmt->execute();
        echo "Registro borrado exitosamente.";
        
    } elseif ($action === 'actualizar') {
        $id_inventario = $_POST['id_inventario'];
        $cantidad_inventario = $_POST['cantidad_inventario'];
        $id_producto = $_POST['id_producto'];
        $stmt = $conn->prepare("BEGIN MVCD_U_INVENTARIO(:id_inventario,:cantidad_inventario,:id_producto); END;");
        $stmt->bindParam(':id_inventario', $id_inventario);
        $stmt->bindParam(':cantidad_inventario', $cantidad_inventario);
        $stmt->bindParam(':id_producto', $id_producto);
        $stmt->execute();
        echo "Registro actualizado exitosamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Gestión de Inventario</h1>
    <div class="button-container">
        <button id = "insertar" onclick="showForm('insertar')">Insertar</button>
        <button id  = "listar" onclick="submitForm('listar')">Listar</button>
        <button id = "borrar" onclick="showForm('borrar')">Borrar</button>
        <button id = "actualizar" onclick="showForm('actualizar')">Actualizar</button>
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
                    <form method="POST" action="inventario.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>Cantidad de Inventario:</label>
                        <input type="number" name="cantidad_inventario" required>
                        <label>ID del Producto a añadir:</label>
                        <input type="number" name="id_producto" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            } else if (action === 'borrar') {
                html = `
                    <form method="POST" action="inventario.php">
                        <input type="hidden" name="action" value="borrar">
                        <label>ID del Inventario a borrar:</label>
                        <input type="number" name="id_inventario" required>
                        <button type="submit">Borrar</button>
                    </form>
                `;
            } else if (action === 'actualizar') {
                html = `
                    <form method="POST" action="inventario.php">
                        <input type="hidden" name="action" value="actualizar">
                        <label>ID del Inventario a actualizar:</label>
                        <input type="number" name="id_inventario" required>
                        <label>Nueva Cantidad de Inventario:</label>
                        <input type="number" name="cantidad_inventario" required>
                        <label>Nueva ID del producto:</label>
                        <input type="number" name="id_producto" required>
                        <button type="submit">Actualizar</button>
                    </form>
                `;
            }
            container.innerHTML = html;
            hideTable();
        }

        function submitForm(action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'inventario.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = action;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }
        function hideTable() {
            tableContainer.style.display = "none";
        }
    </script>
</body>
</html>