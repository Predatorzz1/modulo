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
        $nombre_trabajador = $_POST['nombre_trabajador'];
        $apellido1_trabajador = $_POST['apellido1_trabajador'];
        $apellido2_trabajador = $_POST['apellido2_trabajador'];
        $telefono_trabajador = $_POST['telefono_trabajador'];
        $correo_trabajador = $_POST['correo_trabajador'];
        $id_region = $_POST['id_region'];
        $id_rol = $_POST['id_rol'];
        $stmt = $conn->prepare("BEGIN MVCD_C_TRABAJADOR(:nombre_trabajador,:apellido1_trabajador,:apellido2_trabajador,:telefono_trabajador,:correo_trabajador,:id_region,:id_rol); END;");
        $stmt->bindParam(':nombre_trabajador', $nombre_trabajador);
        $stmt->bindParam(':apellido1_trabajador', $apellido1_trabajador);
        $stmt->bindParam(':apellido2_trabajador', $apellido2_trabajador);
        $stmt->bindParam(':telefono_trabajador', $telefono_trabajador);
        $stmt->bindParam(':correo_trabajador', $correo_trabajador);
        $stmt->bindParam(':id_region', $id_region);
        $stmt->bindParam(':id_rol', $id_rol);
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
        $sql = "BEGIN MVCD_R_TRABAJADOR(:p_cursor); END;";
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

        // Mostrar resultados en una tabla HTML
        echo "<table id ='tableContainer' border='1'>";
        echo "<tr><th>ID_TRABAJADOR</th><th>NOMBRE_TRABAJADOR</th><th>APELLIDO1_TRABAJADOR</th><th>APELLIDO2_TRABAJADOR</th><th>TELEFONO_TRABAJADOR</th><th>CORREO</th><th>ID_ROL</th><th>ID_REGION</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NOMBRE_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['APELLIDO1_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['APELLIDO2_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TELEFONO_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CORREO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_ROL']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_REGION']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Liberar recursos y cerrar conexión
        oci_free_statement($cursor);
        oci_free_statement($stid);
        oci_close($conn);
    } elseif ($action === 'borrar') {
        $id_trabajador = $_POST['id_trabajador'];
        $stmt = $conn->prepare("BEGIN MVCD_D_TRABAJADOR(:id_trabajador); END;");
        $stmt->bindParam(':id_trabajador', $id_trabajador);
        $stmt->execute();
        echo "Registro borrado exitosamente.";

    } elseif ($action === 'actualizar') {
        $id_trabajador = $_POST['id_trabajador'];
        $nombre_trabajador = $_POST['nombre_trabajador'];
        $apellido1_trabajador = $_POST['apellido1_trabajador'];
        $apellido2_trabajador = $_POST['apellido2_trabajador'];
        $telefono_trabajador = $_POST['telefono_trabajador'];
        $correo_trabajador = $_POST['correo_trabajador'];
        $id_region = $_POST['id_region'];
        $id_rol = $_POST['id_rol'];
        $stmt = $conn->prepare("BEGIN MVCD_U_TRABAJADOR(:id_trabajador,:nombre_trabajador,:apellido1_trabajador,:apellido2_trabajador,:telefono_trabajador,:correo_trabajador,:id_rol,:id_region); END;");
        $stmt->bindParam(':id_trabajador', $id_trabajador);
        $stmt->bindParam(':nombre_trabajador', $nombre_trabajador);
        $stmt->bindParam(':apellido1_trabajador', $apellido1_trabajador);
        $stmt->bindParam(':apellido2_trabajador', $apellido2_trabajador);
        $stmt->bindParam(':telefono_trabajador', $telefono_trabajador);
        $stmt->bindParam(':correo_trabajador', $correo_trabajador);
        $stmt->bindParam(':id_region', $id_region);
        $stmt->bindParam(':id_rol', $id_rol);
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
    <title>Gestión de Trabajadores</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Gestión de Trabajadores</h1>
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
                    <form method="POST" action="trabajadores.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>Nombre del Trabajador:</label>
                        <input type="text" name="nombre_trabajador" required>
                        <label>Apellido 1 del Trabajador:</label>
                        <input type="text" name="apellido1_trabajador" required>
                        <label>Apellido 2 del Trabajador:</label>
                        <input type="text" name="apellido2_trabajador" required>
                        <label>Telefono del Trabajador:</label>
                        <input type="number" name="telefono_trabajador" required>
                        <label>Correo del Trabajador:</label>
                        <input type="text" name="correo_trabajador" required>
                        <label>ID Región del Trabajador:</label>
                        <input type="number" name="id_region" required>
                        <label>ID Rol del Trabajador:</label>
                        <input type="number" name="id_rol" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            } else if (action === 'borrar') {
                html = `
                    <form method="POST" action="trabajadores.php">
                        <input type="hidden" name="action" value="borrar">
                        <label>ID del Trabajador a borrar:</label>
                        <input type="number" name="id_trabajador" required>
                        <button type="submit">Borrar</button>
                    </form>
                `;
            } else if (action === 'actualizar') {
                html = `
                    <form method="POST" action="trabajadores.php">
                        <input type="hidden" name="action" value="actualizar">
                        <label>ID del Trabajador a actualizar:</label>
                        <input type="number" name="id_trabajador" required>
                        <label>Nuevo Nombre del Trabajador:</label>
                        <input type="text" name="nombre_trabajador" required>
                        <label>Nuevo Apellido 1 del Trabajador:</label>
                        <input type="text" name="apellido1_trabajador" required>
                        <label>Nuevo Apellido 2 del Trabajador:</label>
                        <input type="text" name="apellido2_trabajador" required>
                        <label>Nuevo Teléfono del Trabajador:</label>
                        <input type="number" name="telefono_trabajador" required>
                        <label>Nuevo Correo del Trabajador:</label>
                        <input type="text" name="correo_trabajador" required>
                        <label>Nuevo ID Región del Trabajador:</label>
                        <input type="number" name="id_region" required>
                        <label>Nuevo ID Rol del Trabajador:</label>
                        <input type="number" name="id_rol" required>
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
            form.action = 'trabajadores.php';

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