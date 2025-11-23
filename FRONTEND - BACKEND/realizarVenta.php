<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Productos a una Cabecera</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // Función para agregar más filas de productos dinámicamente
        function agregarProducto() {
            const container = document.getElementById('productos-container');
            const productoHTML = `
                <div class="producto">
                    <label>ID Producto:</label>
                    <input type="number" name="productos[][id_producto]" required>
                    <br><br>
                    <label>Cantidad:</label>
                    <input type="number" name="productos[][cantidad]" required>
                    <br><br>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', productoHTML);
        }
    </script>
</head>
<body>
    <h1>Registrar Venta</h1>
    <form method="POST" action="insertarVenta.php">
        <h2>Trabajador y cliente</h2>
                <label>ID Trabajador:</label>
                <input type="number" name="id_trabajador" required>
                <label>ID Cliente:</label>
                <input type="number" name="id_cliente" required>
        <h3>Productos</h3>
        <h3>Productos</h3>
            <div id="productos-container">
                <div class="producto">
                    <label for="id_producto_0">ID Producto:</label>
                    <input type="number" name="productos[0][id_producto]" id="id_producto_0" required>
                    <br><br>
                    <label for="cantidad_0">Cantidad:</label>
                    <input type="number" name="productos[0][cantidad]" id="cantidad_0" required>
                    <br><br>
                </div>
            </div>
        <br>
        <button type="button" id="agregarProducto">Agregar otro producto</button>
        <br><br>
        <button type="submit">Registrar Venta</button>
        <br><br>
    </form>
    <a href="admin.html"><button id = "volver">Volver</button></a>
    <script>
        // JavaScript para agregar productos dinámicamente
        document.getElementById('agregarProducto').addEventListener('click', function() {
            var productosContainer = document.getElementById('productos-container');
            var productoCount = productosContainer.children.length; // Obtiene el número actual de productos

            // Clonar la primera sección de producto y actualizar los índices
            var newProducto = productosContainer.children[0].cloneNode(true);
            
            // Actualizar el índice en los nuevos campos
            newProducto.querySelector('[name="productos[0][id_producto]"]').setAttribute('name', 'productos[' + productoCount + '][id_producto]');
            newProducto.querySelector('[name="productos[0][cantidad]"]').setAttribute('name', 'productos[' + productoCount + '][cantidad]');
            
            // Limpiar los campos clonados para que estén vacíos
            newProducto.querySelector('[name="productos[' + productoCount + '][id_producto]"]').value = '';
            newProducto.querySelector('[name="productos[' + productoCount + '][cantidad]"]').value = '';
            
            // Agregar el nuevo producto al contenedor
            productosContainer.appendChild(newProducto);
        });
    </script>

</body>
</html>
