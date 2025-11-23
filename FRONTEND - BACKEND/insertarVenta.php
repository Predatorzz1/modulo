<?php
// Configuración de la conexión a la base de datos (ajusta los datos de tu servidor)
$host = 'localhost';
$port = '1521';
$sid = 'XE';
$username = 'HR';
$password = '123';

try {
    // Crear conexión PDO con Oracle
    $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener los datos del formulario
    $productos = $_POST['productos']; // Array de productos
    $id_trabajador = $_POST['id_trabajador'];
    $id_cliente = $_POST['id_cliente'];
    // Validar que se ha ingresado al menos un producto
    if (empty($productos)) {
        throw new Exception("Debe agregar al menos un producto.");
    }


    // Inicio Proceso de inserción de la venta
    $stmt = $conn->prepare("BEGIN MVCD_C_CABECERA_VENTA(0,:id_trabajador,:id_cliente); END;");
    $stmt->bindParam(':id_trabajador', $id_trabajador);
    $stmt->bindParam(':id_cliente', $id_cliente);
    $stmt->execute();


    // Insertar los productos en la base de datos
    $sql = "BEGIN MVCD_C_CUERPO_VENTA(:cantidad,:id_producto); END;";
    $stmt = $conn->prepare($sql);

    // Iterar sobre los productos y hacer las inserciones
    foreach ($productos as $producto) {
        // Ejecutar la consulta preparada con los valores correspondientes
        $stmt->execute([
            ':cantidad' => $producto['cantidad'],
            ':id_producto' => $producto['id_producto'],
        ]);
    }

    $stmt = $conn->prepare("BEGIN MVCD_REEMPLAZO_TOTAL; END;");
    $stmt->execute();

    echo "<script>alert('Venta registrada correctamente. Redirigiendo a la página principal...');</script>";
    echo "<script>window.location.href = 'admin.html';</script>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
