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
        $monto_pago_cliente = $_POST['monto_pago_cliente'];
        $id_trabajador = $_POST['id_trabajador'];
        $id_cliente = $_POST['id_cliente'];
        $stmt = $conn->prepare("BEGIN MVCD_C_PAGO_CLIENTE(:monto_pago_cliente,:id_trabajador,:id_cliente); END;");
        $stmt->bindParam(':monto_pago_cliente', $monto_pago_cliente);
        $stmt->bindParam(':id_trabajador', $id_trabajador);
        $stmt->bindParam(':id_cliente', $id_cliente);
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
        $sql = "BEGIN MVCD_R_PAGO_CLIENTE(:p_cursor); END;";
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

        //ID_PAGO_CLIENTE, MONTO_PAGO_CLIENTE, ID_TRABAJADOR, ID_CLIENTE
         
        // Mostrar resultados en una tabla HTML
        echo "<table id ='tableContainer' border='1'>";
        echo "<tr><th>ID PAGO CLIENTE</th><th>MONTO PAGO CLIENTE</th><th>ID TRABAJADOR</th><th>ID CLIENTE</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_PAGO_CLIENTE']) . "</td>";
            echo "<td>$" . htmlspecialchars($row['MONTO_PAGO_CLIENTE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_CLIENTE']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Liberar recursos y cerrar conexión
        oci_free_statement($cursor);
        oci_free_statement($stid);
        oci_close($conn);
        
    } elseif ($action === 'borrar') {
        $id_pago_cliente = $_POST['id_pago_cliente'];
        $stmt = $conn->prepare("BEGIN MVCD_D_PAGO_CLIENTE(:id_pago_cliente); END;");
        $stmt->bindParam(':id_pago_cliente', $id_pago_cliente);
        $stmt->execute();
        echo "Registro borrado exitosamente.";

    } elseif ($action === 'actualizar') {
        $id_pago_cliente = $_POST['id_pago_cliente'];
        $monto_pago_cliente = $_POST['monto_pago_cliente'];
        $id_trabajador = $_POST['id_trabajador'];
        $id_cliente = $_POST['id_cliente'];
        $stmt = $conn->prepare("BEGIN MVCD_U_PAGO_CLIENTE(:id_pago_cliente,:monto_pago_cliente,:id_trabajador,:id_cliente); END;");
        $stmt->bindParam(':id_pago_cliente', $id_pago_cliente);
        $stmt->bindParam(':monto_pago_cliente', $monto_pago_cliente);
        $stmt->bindParam(':id_trabajador', $id_trabajador);
        $stmt->bindParam(':id_cliente', $id_cliente);
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
    <title>Gestión del Pago Cliente</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Gestión del Pago Cliente</h1>
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
                    <form method="POST" action="pagocliente.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>Monto del Pago Cliente:</label>
                        <input type="number" name="monto_pago_cliente" required>
                        <label>ID Trabajador del Pago Cliente:</label>
                        <input type="number" name="id_trabajador" required>
                        <label>ID Cliente del Pago Cliente:</label>
                        <input type="number" name="id_cliente" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            } else if (action === 'borrar') {
                html = `
                    <form method="POST" action="pagocliente.php">
                        <input type="hidden" name="action" value="borrar">
                        <label>ID del Pago Cliente a borrar:</label>
                        <input type="number" name="id_pago_cliente" required>
                        <button type="submit">Borrar</button>
                    </form>
                `;
            } else if (action === 'actualizar') {
                html = `
                    <form method="POST" action="pagocliente.php">
                        <input type="hidden" name="action" value="actualizar">
                        <label>ID del Pago Cliente a actualizar:</label>
                        <input type="number" name="id_pago_cliente" required>
                        <label>Nuevo Monto del Pago Cliente:</label>
                        <input type="number" name="monto_pago_cliente" required>
                        <label>Nuevo ID Trabajador del Pago Cliente:</label>
                        <input type="number" name="id_trabajador" required>
                        <label>Nuevo ID Cliente del Pago Cliente:</label>
                        <input type="number" name="id_cliente" required>
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
            form.action = 'pagocliente.php';

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