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
        $rut = $_POST['rut'];
        $tipo_cuenta_bancaria = $_POST['tipo_cuenta_bancaria'];
        $nro_cuenta_bancaria = $_POST['nro_cuenta_bancaria'];
        $nombre_banco = $_POST['nombre_banco'];
        $id_trabajador = $_POST['id_trabajador'];
        $stmt = $conn->prepare("BEGIN MVCD_C_CUENTA_BANCARIA(:rut,:tipo_cuenta_bancaria,:nro_cuenta_bancaria,:nombre_banco,:id_trabajador); END;");
        $stmt->bindParam(':rut', $rut);
        $stmt->bindParam(':tipo_cuenta_bancaria', $tipo_cuenta_bancaria);
        $stmt->bindParam(':nro_cuenta_bancaria', $nro_cuenta_bancaria);
        $stmt->bindParam(':nombre_banco', $nombre_banco);
        $stmt->bindParam(':id_trabajador', $id_trabajador);
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
        $sql = "BEGIN  MVCD_R_CUENTA_BANCARIA(:p_cursor); END;";
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
        // ID_CUENTA_BANCARIA, RUT, TIPO_CUENTA_BANCARIA, NRO_CUENTA_BANCARIA, NOMBRE_BANCO, ID_TRABAJADOR

        // Mostrar resultados en una tabla HTML
        echo "<table id ='tableContainer' border='1'>";
        echo "<tr><th>ID CUENTA BANCARIA</th><th>RUT</th><th>TIPO CUENTA BANCARIA</th><th>NRO CUENTA BANCARIA</th><th>NOMBRE BANCO</th><th>ID TRABAJADOR</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_CUENTA_BANCARIA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['RUT']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TIPO_CUENTA_BANCARIA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NRO_CUENTA_BANCARIA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NOMBRE_BANCO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_TRABAJADOR']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        // Liberar recursos y cerrar conexión
        oci_free_statement($cursor);
        oci_free_statement($stid);
        oci_close($conn);
    } elseif ($action === 'borrar') {
        $id_cuenta_bancaria = $_POST['id_cuenta_bancaria'];
        $stmt = $conn->prepare("BEGIN MVCD_D_CUENTA_BANCARIA(:id_cuenta_bancaria); END;");
        $stmt->bindParam(':id_cuenta_bancaria', $id_cuenta_bancaria);
        $stmt->execute();
        echo "Registro borrado exitosamente.";

    } elseif ($action === 'actualizar') {
        $id_cuenta_bancaria = $_POST['id_cuenta_bancaria'];
        $tipo_cuenta_bancaria = $_POST['tipo_cuenta_bancaria'];
        $nro_cuenta_bancaria = $_POST['nro_cuenta_bancaria'];
        $nombre_banco = $_POST['nombre_banco'];
        $id_trabajador = $_POST['id_trabajador'];
        $rut = $_POST['rut'];
        $stmt = $conn->prepare("BEGIN MVCD_U_CUENTA_BANCARIA(:id_cuenta_bancaria,:rut,:tipo_cuenta_bancaria,:nro_cuenta_bancaria,:nombre_banco,:id_trabajador); END;");
        $stmt->bindParam(':id_cuenta_bancaria', $id_cuenta_bancaria);
        $stmt->bindParam(':rut', $rut);
        $stmt->bindParam(':tipo_cuenta_bancaria', $tipo_cuenta_bancaria);
        $stmt->bindParam(':nro_cuenta_bancaria', $nro_cuenta_bancaria);
        $stmt->bindParam(':nombre_banco', $nombre_banco);
        $stmt->bindParam(':id_trabajador', $id_trabajador);
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
    <title>Gestión de Cuenta Bancaria</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Gestión de Cuenta Bancaria</h1>
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
                    <form method="POST" action="cuentabancaria.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>RUT:</label>
                        <input type="number" name="rut" required>
                        <label>Tipo de Cuenta Bancaria:</label>
                        <input type="text" name="tipo_cuenta_bancaria" required>
                        <label>Nombre del Banco:</label>
                        <input type="text" name="nombre_banco" required>
                        <label>Nro de la Cuenta Bancaria:</label>
                        <input type="number" name="nro_cuenta_bancaria" required>
                        <label>ID del Trabajador:</label>
                        <input type="number" name="id_trabajador" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            } else if (action === 'borrar') {
                html = `
                    <form method="POST" action="cuentabancaria.php">
                        <input type="hidden" name="action" value="borrar">
                        <label>ID de la Cuenta Bancaria a borrar:</label>
                        <input type="number" name="id_cuenta_bancaria" required>
                        <button type="submit">Borrar</button>
                    </form>
                `;
            } else if (action === 'actualizar') {
                html = `
                    <form method="POST" action="cuentabancaria.php">
                        <input type="hidden" name="action" value="actualizar">
                        <label>ID de la Cuenta Bancaria a actualizar:</label>
                        <input type="number" name="id_cuenta_bancaria" required>
                        <label>RUT:</label>
                        <input type="number" name="rut" required>
                        <label>Nuevo Tipo de Cuenta Bancaria:</label>
                        <input type="text" name="tipo_cuenta_bancaria" required>
                        <label>Nuevo Nombre del Banco:</label>
                        <input type="text" name="nombre_banco" required>
                        <label>Nuevo Nro de la Cuenta Bancaria:</label>
                        <input type="number" name="nro_cuenta_bancaria" required>
                        <label>Nuevo ID del Trabajador:</label>
                        <input type="number" name="id_trabajador" required>
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
            form.action = 'cuentabancaria.php';

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