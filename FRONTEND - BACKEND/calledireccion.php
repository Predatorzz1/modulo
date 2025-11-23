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
        $calle_direccion = $_POST['calle_direccion'];
        $nro_direccion = $_POST['nro_direccion'];
        $stmt = $conn->prepare("BEGIN MVCD_C_CALLE_DIRECCION(:calle_direccion,:nro_direccion); END;");
        $stmt->bindParam(':calle_direccion', $calle_direccion);
        $stmt->bindParam(':nro_direccion', $nro_direccion);
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
        $sql = "BEGIN MVCD_R_CALLE_DIRECCION(:p_cursor); END;";
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
        echo "<tr><th>ID_CALLE</th><th>CALLE DIRECCIÓN</th><th>NRO DIRECCION</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_CALLE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CALLE_DIRECCION']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NRO_DIRECCION']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Liberar recursos y cerrar conexión
        oci_free_statement($cursor);
        oci_free_statement($stid);
        oci_close($conn);
        

    } elseif ($action === 'borrar') {
        $id_calle = $_POST['id_calle'];
        $stmt = $conn->prepare("BEGIN MVCD_D_CALLE_DIRECCION(:id_calle); END;");
        $stmt->bindParam(':id_calle', $id_calle);
        $stmt->execute();
        echo "Registro borrado exitosamente.";
        
    } elseif ($action === 'actualizar') {
        $id_calle = $_POST['id_calle'];
        $calle_direccion = $_POST['calle_direccion'];
        $nro_direccion = $_POST['nro_direccion'];
        $stmt = $conn->prepare("BEGIN MVCD_U_CALLE_DIRECCION(:id_calle,:calle_direccion,:nro_direccion); END;");
        $stmt->bindParam(':id_calle', $id_calle);
        $stmt->bindParam(':calle_direccion', $calle_direccion);
        $stmt->bindParam(':nro_direccion', $nro_direccion);
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
    <title>Gestión de Dirección</title>
    <link rel="stylesheet" href="styles.css">
    </style>
</head>
<body>
    <h1>Gestión de Dirección</h1>
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
                    <form method="POST" action="calledireccion.php">
                        <input type="hidden" name="action" value="insertar">
                        <label>Dirección de Calle:</label>
                        <input type="text" name="calle_direccion" required>
                        <label>Número de Dirección:</label>
                        <input type="number" name="nro_direccion" required>
                        <button type="submit">Insertar</button>
                    </form>
                `;
            } else if (action === 'borrar') {
                html = `
                    <form method="POST" action="calledireccion.php">
                        <input type="hidden" name="action" value="borrar">
                        <label>ID de la Dirección a borrar:</label>
                        <input type="number" name="id_calle" required>
                        <button type="submit">Borrar</button>
                    </form>
                `;
            } else if (action === 'actualizar') {
                html = `
                    <form method="POST" action="calledireccion.php">
                        <input type="hidden" name="action" value="actualizar">
                        <label>ID de la Dirección a actualizar:</label>
                        <input type="number" name="id_calle" required>
                        <label>Nueva Dirección de Calle:</label>
                        <input type="text" name="calle_direccion" required>
                        <label>Nuevo Número de Dirección:</label>
                        <input type="number" name="nro_direccion" required>
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
            form.action = 'calledireccion.php';

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