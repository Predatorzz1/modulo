<?php
// Configuración de conexión a Oracle
$host = 'localhost';
$port = '1521';
$dbname = 'XE'; // Cambia según tu configuración
$username = 'HR';
$password = '123';
// Manejar la acción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'listar') {
        $dsn = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$dbname)))";

        // Conexión a Oracle
        $conn = oci_connect($username, $password, $dsn);
        if (!$conn) {
            $e = oci_error();
            echo "Error de conexión: " . $e['message'];
            exit;
        }

        // Llamada al procedimiento almacenado
        $sql = "BEGIN MVCD_REPORTE_VENTAS(:p_cursor); END;";
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
        echo "<tr><th>CANTIDAD DE PRODUCTO VENDIDO</th><th>NOMBRE DEL PRODUCTO</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['SUM(C.CANTIDAD_VENTA*C.ID_PRODUCTO)']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NOMBRE_PRODUCTO']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Liberar recursos y cerrar conexión
        oci_free_statement($cursor);
        oci_free_statement($stid);
        oci_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de ventas</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Reporte de ventas</h1>
    <div class="button-container">
        <button id  = "listar" onclick="submitForm('listar')">Listar</button>
        <a href="admin.html"><button id = "volver">Volver</button></a>
    </div>
    <div id="formContainer"></div>
    <div id="output"></div>

    <script>
        function submitForm(action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'func1.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = action;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>