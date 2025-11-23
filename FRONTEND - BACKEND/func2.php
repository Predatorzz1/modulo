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
        $fecha = date('d-M-Y', strtotime($_POST['fecha']));
        // Llamada al procedimiento almacenado
        $sql = "BEGIN MVCD_REPORTE_PAGO_CLIENTE(:fecha,:p_cursor); END;";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":fecha", $fecha);
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
        echo "<tr><th>DINERO TOTAL INGRESADO DESDE LA FECHA $fecha</th></tr>";
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['SUM(MONTO_PAGO_CLIENTE)']) . "</td>";
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
    <title>REPORTE DE PAGOS CLIENTE</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>REPORTE DE PAGOS CLIENTE</h1>
    <div class="button-container">
        <button id  = "listar" onclick="showForm('filtrar')">Filtrar</button>
        <a href="admin.html"><button id = "volver">Volver</button></a>
    </div>
    <div id="formContainer"></div>
    <div id="output"></div>

    <script>
        function showForm(action) {
            const container = document.getElementById('formContainer');
            let html = '';
            if (action === 'filtrar') {
                html = `
                    <form method="POST" action="func2.php">
                        <input type="hidden" name="action" value="listar">
                        <label>Fecha desde la que se quiere filtrar:</label>
                        <input type="date" name="fecha" required>
                        <button type="submit">Filtrar</button>
                    </form>
                `;
            }
            container.innerHTML = html;
        }
    </script>
</body>
</html>