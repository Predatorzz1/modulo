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
        $cabecera = $_POST['cabecera'];
        // Llamada al procedimiento almacenado
        if (empty($cabecera)){
            $cabecera = -1;
        }
        $sql = "BEGIN MVCD_REPORTE_PAGO_VENTAS(:p_cursor,:cabecera); END;";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":cabecera", $cabecera);
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
        if ($cabecera == -1){
            echo "<tr><th>Reporte de ventas de todas las cabeceras </th><th>Fecha</th><th>Total</th><th>Trabajador</th><th>Cliente</th><th>Cantidad</th><th>Producto</th><th>Precio</th></tr>";
        }else{
            echo "<tr><th>Reporte de ventas de la cabecera $cabecera </th><th>Fecha</th><th>Total</th><th>Trabajador</th><th>Cliente</th><th>Cantidad</th><th>Producto</th><th>Precio</th></tr>";
        }
        while ($row = oci_fetch_assoc($cursor)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CABECERA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['FECHA_VENTA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TOTAL_VENTA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_TRABAJADOR']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ID_CLIENTE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CANTIDAD_VENTA']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NOMBRE_PRODUCTO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Precio']) . "</td>";
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
    <title>REPORTE DE VENTAS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>REPORTE DE VENTAS</h1>
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
                    <form method="POST" action="func4.php">
                        <input type="hidden" name="action" value="listar">
                        <label>Cabecera para listar:</label>
                        <input type="number" name="cabecera">
                        <button type="submit">Filtrar</button>
                    </form>
                `;
            }
            container.innerHTML = html;
        }
    </script>
</body>
</html>