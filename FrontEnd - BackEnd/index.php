<?php
session_start();

// ==========================================
// 1. CONFIGURACIÓN Y CONEXIÓN ORACLE
// ==========================================
define('DB_HOST', '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=xe)))');
define('DB_USER', 'user_developer');
define('DB_PASS', '123456'); // <--- ¡VERIFICA TU CONTRASEÑA!

function getDB() {
    $conn = @oci_connect(DB_USER, DB_PASS, DB_HOST, 'AL32UTF8');
    if (!$conn) {
        $e = oci_error();
        die("<div class='alert alert-danger'><h4>Error de Conexión Oracle</h4><p>" . htmlentities($e['message']) . "</p></div>");
    }
    return $conn;
}

// HELPER PARA EJECUTAR CURSORES RÁPIDAMENTE
function executeCursor($conn, $sql, $params = []) {
    $stid = oci_parse($conn, $sql);
    $cursor = oci_new_cursor($conn);
    
    // Bind de parámetros normales
    foreach ($params as $key => $val) {
        oci_bind_by_name($stid, $key, $params[$key]);
    }
    
    // Bind del cursor de salida
    oci_bind_by_name($stid, ":cursor", $cursor, -1, OCI_B_CURSOR);
    
    oci_execute($stid);
    oci_execute($cursor); // Ejecutamos el cursor para poder leerlo
    return $cursor; // Retornamos el cursor listo para fetch
}

// ==========================================
// 2. LÓGICA DE NEGOCIO (Controlador)
// ==========================================
$action = $_GET['action'] ?? 'dashboard';
$error = '';
$success = '';

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = strtoupper($_POST['username']); 
    $pass = $_POST['password'];
    
    $conn = getDB();
    // USANDO PROCEDURE MVCD_R_LOGIN
    $sql = "BEGIN MVCD_R_LOGIN(:u, :p, :cursor); END;";
    $stid = executeCursor($conn, $sql, [":u" => $user, ":p" => $pass]);
    
    if ($row = oci_fetch_assoc($stid)) {
        $_SESSION['user'] = $row['NOMBRE_USUARIO'];
        $_SESSION['role'] = $row['ROL'];
        $_SESSION['id_user'] = $row['ID_USUARIO'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// GUARDAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $conn = getDB();
    $sql = "BEGIN MVCD_C_PRODUCTO(:n, :p, :s, :c); END;";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":n", $_POST['nombre']);
    oci_bind_by_name($stid, ":p", $_POST['precio']);
    oci_bind_by_name($stid, ":s", $_POST['stock']);
    oci_bind_by_name($stid, ":c", $_POST['categoria']);
    
    if(@oci_execute($stid)) { $success = "Producto guardado."; } 
    else { $e = oci_error($stid); $error = "Error: " . $e['message']; }
}

// ACTUALIZAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $conn = getDB();
    $sql = "BEGIN MVCD_U_PRODUCTO(:id,:n,:p,:s,:c); END;";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":n", $_POST['edit_nombre']);
    oci_bind_by_name($stid, ":p", $_POST['edit_precio']);
    oci_bind_by_name($stid, ":s", $_POST['edit_stock']);
    oci_bind_by_name($stid, ":c", $_POST['edit_categoria']);
    oci_bind_by_name($stid, ":id", $_POST['edit_id']);
    
    if(@oci_execute($stid)) { $success = "Producto actualizado."; } 
    else { $e = oci_error($stid); $error = "Error: " . $e['message']; }
}

// BORRAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $conn = getDB();
    $sql = "BEGIN MVCD_D_PRODUCTO(:id); END;";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $_POST['id_producto']);
    
    if(@oci_execute($stid)) { $success = "Producto eliminado."; } 
    else { 
        $e = oci_error($stid); 
        $error = ($e['code'] == 2292) ? "No se puede eliminar: tiene ventas." : "Error: " . $e['message']; 
    }
}

// REALIZAR VENTA (POS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
    $conn = getDB();
    $cart = json_decode($_POST['cart_data'], true);
    $total = $_POST['total_amount'];
    $userId = $_SESSION['id_user'];

    if (!empty($cart)) {
        // Usando Función para obtener ID
        $sqlCab = "BEGIN MVCD_C_VENTA_CABECERA(:usr_id, :total, :id_venta); END;";
        $stid = oci_parse($conn, $sqlCab);
        oci_bind_by_name($stid, ":total", $total);
        oci_bind_by_name($stid, ":usr_id", $userId); 
        oci_bind_by_name($stid, ":id_venta", $idVenta, 32);
        
        if (@oci_execute($stid)) {
            $sqlDet = "BEGIN MVCD_C_VENTA_CUERPO(:idv, :idp, :qty, :pu, :sub); END;"; 
            $stidDet = oci_parse($conn, $sqlDet);
            
            // Inicializar vars para bind
            $prod_id = 0; $prod_qty = 0; $prod_price = 0; $prod_sub = 0;
            oci_bind_by_name($stidDet, ":idv", $idVenta, 32);
            oci_bind_by_name($stidDet, ":idp", $prod_id, 32);
            oci_bind_by_name($stidDet, ":qty", $prod_qty, 32);
            oci_bind_by_name($stidDet, ":pu", $prod_price, 32); 
            oci_bind_by_name($stidDet, ":sub", $prod_sub, 32);

            $errorDetalle = false;
            foreach ($cart as $item) {
                $prod_id = $item['id'];
                $prod_qty = $item['qty'];
                $prod_price = $item['price'];
                $prod_sub = $item['price'] * $item['qty'];
                if (!@oci_execute($stidDet)) { $errorDetalle = true; break; }
            }
            if (!$errorDetalle) $success = "Venta #$idVenta registrada con éxito.";
            else $error = "Error al guardar detalles de venta.";
        } else {
            $e = oci_error($stid); $error = "Error venta: " . $e['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Ventas Oracle</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
</head>
<body class="hold-transition <?php echo !isset($_SESSION['user']) ? 'login-page' : 'sidebar-mini'; ?>">

<?php if (!isset($_SESSION['user'])): ?>
    <div class="login-box">
        <div class="login-logo"><a href="#"><b>Admin</b>LTE Oracle</a></div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Inicia sesión</p>
                <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <form action="" method="post">
                    <div class="input-group mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Usuario" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary btn-block">Ingresar</button>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li></ul>
            <ul class="navbar-nav ml-auto"><li class="nav-item"><a class="nav-link text-danger" href="?logout=true"><i class="fas fa-sign-out-alt"></i> Salir</a></li></ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="#" class="brand-link"><span class="brand-text font-weight-light pl-3"><b>Admin</b>LTE</span></a>
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="info"><a href="#" class="d-block"><?= $_SESSION['user'] ?> <small>(<?= $_SESSION['role'] ?>)</small></a></div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item"><a href="?action=dashboard" class="nav-link <?= $action=='dashboard'?'active':'' ?>"><i class="nav-icon fas fa-tachometer-alt"></i> <p>Dashboard</p></a></li>
                        <li class="nav-item"><a href="?action=pos" class="nav-link <?= $action=='pos'?'active':'' ?>"><i class="nav-icon fas fa-shopping-cart"></i> <p>Punto de Venta</p></a></li>
                        <?php if($_SESSION['role'] == 'ADMIN'): ?>
                        <li class="nav-item"><a href="?action=products" class="nav-link <?= $action=='products'?'active':'' ?>"><i class="nav-icon fas fa-box"></i> <p>Productos</p></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1><?= ucfirst(str_replace('_', ' ', $action)) ?></h1></div></div></div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?php if($success): ?><div class="alert alert-success alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $success ?></div><?php endif; ?>
                    <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $error ?></div><?php endif; ?>

                    <?php 
                    // --- VISTA DASHBOARD ---
                    if ($action == 'dashboard'): 
                        $conn = getDB();
                        // 1. CANT VENTAS
                        $r1 = oci_fetch_assoc(executeCursor($conn, "BEGIN MVCD_R_DASH_CANT_VENTAS(:cursor); END;"));
                        // 2. TOTAL INGRESOS
                        $r2 = oci_fetch_assoc(executeCursor($conn, "BEGIN MVCD_R_DASH_TOTAL_INGRESOS(:cursor); END;"));
                        // 3. STOCK BAJO
                        $r3 = oci_fetch_assoc(executeCursor($conn, "BEGIN MVCD_R_DASH_STOCK_BAJO(:cursor); END;"));
                        
                        // 4. CHART (TOP 5)
                        $stidChart = executeCursor($conn, "BEGIN MVCD_R_DASH_CHART(:cursor); END;");
                        $labels = []; $data = [];
                        while($row = oci_fetch_assoc($stidChart)) { $labels[] = $row['NOMBRE_PRODUCTO']; $data[] = $row['TOTAL_VENDIDO']; }
                    ?>
                        <div class="row">
                            <div class="col-lg-4 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $r1['CANT'] ?></h3><p>Ventas Realizadas</p></div><div class="icon"><i class="fas fa-shopping-cart"></i></div></div></div>
                            <div class="col-lg-4 col-6"><div class="small-box bg-success"><div class="inner"><h3>$<?= number_format($r2['TOTAL'],0) ?></h3><p>Ingresos Totales</p></div><div class="icon"><i class="fas fa-chart-bar"></i></div></div></div>
                            <div class="col-lg-4 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= $r3['BAJOS'] ?></h3><p>Stock Bajo</p></div><div class="icon"><i class="fas fa-exclamation-triangle"></i></div></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><div class="card card-primary"><div class="card-header"><h3 class="card-title">Top 5 Productos</h3></div><div class="card-body"><canvas id="salesChart" style="height:250px;"></canvas></div></div></div>
                            <div class="col-md-6"><div class="card"><div class="card-header border-transparent"><h3 class="card-title">Últimas Ventas</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table m-0"><thead><tr><th>ID</th><th>Usuario</th><th>Total</th><th>Acción</th></tr></thead><tbody>
                            <?php 
                            // 5. ULTIMAS VENTAS
                            $stid = executeCursor($conn, "BEGIN MVCD_R_DASH_LAST_SALES(:cursor); END;");
                            while($row = oci_fetch_assoc($stid)): ?>
                            <tr><td><a href="#">OR<?= $row['ID_VENTA'] ?></a></td><td><?= $row['NOMBRE_USUARIO'] ?></td><td><span class="badge badge-success">$<?= number_format($row['TOTAL_VENTA'],0) ?></span></td><td><a href="?action=sale_detail&id=<?= $row['ID_VENTA'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</a></td></tr>
                            <?php endwhile; ?>
                            </tbody></table></div></div></div></div>
                        </div>
                        <script>
                            var ctx = document.getElementById('salesChart').getContext('2d');
                            var chart = new Chart(ctx, { type: 'doughnut', data: { labels: <?= json_encode($labels) ?>, datasets: [{ data: <?= json_encode($data) ?>, backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc'] }] }, options: { maintainAspectRatio: false, responsive: true } });
                        </script>

                    <?php 
                    // --- VISTA DETALLE ---
                    elseif ($action == 'sale_detail'):
                        $conn = getDB(); $idVenta = $_GET['id'];
                        // 6. CABECERA DETALLE
                        $stid = executeCursor($conn, "BEGIN MVCD_R_DETALLE_CAB(:id, :cursor); END;", [":id" => $idVenta]);
                        $cabecera = oci_fetch_assoc($stid);
                        // 7. CUERPO DETALLE
                        $stid2 = executeCursor($conn, "BEGIN MVCD_R_DETALLE_BODY(:id, :cursor); END;", [":id" => $idVenta]);
                    ?>
                        <div class="invoice p-3 mb-3">
                            <div class="row"><div class="col-12"><h4><i class="fas fa-globe"></i> Sistema Ventas<small class="float-right">Fecha: <?= $cabecera['FECHA_VENTA'] ?></small></h4></div></div>
                            <div class="row invoice-info"><div class="col-sm-4 invoice-col">Vendedor<address><strong><?= $cabecera['NOMBRE_USUARIO'] ?></strong></address></div><div class="col-sm-4 invoice-col"><b>Venta #OR<?= $cabecera['ID_VENTA'] ?></b></div></div>
                            <div class="row"><div class="col-12 table-responsive"><table class="table table-striped"><thead><tr><th>Producto</th><th>Cant</th><th>P. Unit</th><th>Subtotal</th></tr></thead><tbody>
                            <?php while($det = oci_fetch_assoc($stid2)): ?><tr><td><?= $det['NOMBRE_PRODUCTO'] ?></td><td><?= $det['CANTIDAD'] ?></td><td>$<?= number_format($det['PRECIO_UNITARIO'],0) ?></td><td>$<?= number_format($det['SUBTOTAL'],0) ?></td></tr><?php endwhile; ?>
                            </tbody></table></div></div>
                            <div class="row"><div class="col-6"></div><div class="col-6"><div class="table-responsive"><table class="table"><tr><th style="width:50%">Total:</th><td>$<?= number_format($cabecera['TOTAL_VENTA'],0) ?></td></tr></table></div></div></div>
                            <div class="row no-print"><div class="col-12"><a href="?action=dashboard" class="btn btn-default"><i class="fas fa-arrow-left"></i> Volver</a><button onclick="window.print()" class="btn btn-primary float-right"><i class="fas fa-print"></i> Imprimir</button></div></div>
                        </div>

                    <?php 
                    // --- VISTA PRODUCTOS ---
                    elseif ($action == 'products' && $_SESSION['role'] == 'ADMIN'): 
                        $conn = getDB();
                        $cats = [];
                        // 8. LISTAR CATEGORIAS
                        $sCat = executeCursor($conn, "BEGIN MVCD_R_CATEGORIAS(:cursor); END;");
                        while($r = oci_fetch_assoc($sCat)) $cats[] = $r;
                    ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card card-primary">
                                    <div class="card-header"><h3 class="card-title">Nuevo Producto</h3></div>
                                    <form method="post">
                                        <div class="card-body">
                                            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                                            <div class="form-group"><label>Precio</label><input type="number" name="precio" class="form-control" required></div>
                                            <div class="form-group"><label>Stock</label><input type="number" name="stock" class="form-control" required></div>
                                            <div class="form-group"><label>Categoría</label>
                                                <select name="categoria" class="form-control"><?php foreach($cats as $c): ?><option value="<?= $c['ID_CATEGORIA'] ?>"><?= $c['NOMBRE_CATEGORIA'] ?></option><?php endforeach; ?></select>
                                            </div>
                                        </div>
                                        <div class="card-footer"><button type="submit" name="save_product" class="btn btn-primary">Guardar</button></div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header"><h3 class="card-title">Inventario</h3></div>
                                    <div class="card-body p-0">
                                        <table class="table table-striped">
                                            <thead><tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Acción</th></tr></thead>
                                            <tbody>
                                                <?php
                                                // 9. LISTAR INVENTARIO
                                                $stid = executeCursor($conn, "BEGIN MVCD_R_INVENTORY(:cursor); END;");
                                                while($row = oci_fetch_assoc($stid)):
                                                ?>
                                                <tr>
                                                    <td><?= $row['ID_PRODUCTO'] ?></td>
                                                    <td><?= $row['NOMBRE_PRODUCTO'] ?></td>
                                                    <td>$<?= number_format($row['PRECIO_PRODUCTO'],0) ?></td>
                                                    <td><span class="badge badge-<?= $row['STOCK_PRODUCTO']<10?'danger':'success' ?>"><?= $row['STOCK_PRODUCTO'] ?></span></td>
                                                    <td>
                                                        <button type="button" class="btn btn-xs btn-warning btn-edit" 
                                                                data-id="<?= $row['ID_PRODUCTO'] ?>"
                                                                data-nombre="<?= $row['NOMBRE_PRODUCTO'] ?>"
                                                                data-precio="<?= $row['PRECIO_PRODUCTO'] ?>"
                                                                data-stock="<?= $row['STOCK_PRODUCTO'] ?>"
                                                                data-categoria="<?= $row['ID_CATEGORIA'] ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="post" onsubmit="return confirm('¿Borrar?');" style="display:inline;">
                                                            <input type="hidden" name="id_producto" value="<?= $row['ID_PRODUCTO'] ?>">
                                                            <button type="submit" name="delete_product" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="modal-editar">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning">
                                        <h4 class="modal-title">Editar Producto</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="edit_id" id="edit_id">
                                            <div class="form-group"><label>Nombre</label><input type="text" name="edit_nombre" id="edit_nombre" class="form-control" required></div>
                                            <div class="form-group"><label>Precio</label><input type="number" name="edit_precio" id="edit_precio" class="form-control" required></div>
                                            <div class="form-group"><label>Stock</label><input type="number" name="edit_stock" id="edit_stock" class="form-control" required></div>
                                            <div class="form-group"><label>Categoría</label>
                                                <select name="edit_categoria" id="edit_categoria" class="form-control">
                                                    <?php foreach($cats as $c): ?><option value="<?= $c['ID_CATEGORIA'] ?>"><?= $c['NOMBRE_CATEGORIA'] ?></option><?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer justify-content-between">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" name="update_product" class="btn btn-warning">Guardar Cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                $('.btn-edit').on('click', function() {
                                    $('#edit_id').val($(this).data('id'));
                                    $('#edit_nombre').val($(this).data('nombre'));
                                    $('#edit_precio').val($(this).data('precio'));
                                    $('#edit_stock').val($(this).data('stock'));
                                    $('#edit_categoria').val($(this).data('categoria'));
                                    $('#modal-editar').modal('show');
                                });
                            });
                        </script>

                    <?php 
                    // --- VISTA POS ---
                    elseif ($action == 'pos'): 
                        $conn = getDB();
                        $prods = [];
                        // 10. LISTAR PRODUCTOS POS
                        $sp = executeCursor($conn, "BEGIN MVCD_R_POS_PRODUCTS(:cursor); END;");
                        while($r = oci_fetch_assoc($sp)) $prods[] = $r;
                    ?>
                        <div class="row">
                            <div class="col-md-7">
                                <div class="card card-solid"><div class="card-body pb-0"><div class="row d-flex align-items-stretch">
                                    <?php foreach($prods as $p): ?>
                                    <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch" onclick="addToCart(<?= $p['ID_PRODUCTO'] ?>, '<?= $p['NOMBRE_PRODUCTO'] ?>', <?= $p['PRECIO_PRODUCTO'] ?>)" style="cursor:pointer">
                                        <div class="card bg-light w-100 hover-shadow"><div class="card-header text-muted border-bottom-0">Stock: <?= $p['STOCK_PRODUCTO'] ?></div>
                                        <div class="card-body pt-0"><div class="row"><div class="col-12 text-center"><h2 class="lead"><b><?= $p['NOMBRE_PRODUCTO'] ?></b></h2><p class="text-success font-weight-bold text-lg">$<?= number_format($p['PRECIO_PRODUCTO'],0) ?></p></div></div></div></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div></div></div>
                            </div>
                            <div class="col-md-5">
                                <div class="card card-warning">
                                    <div class="card-header"><h3 class="card-title">Orden Actual</h3></div>
                                    <div class="card-body p-0 table-responsive" style="height: 300px;">
                                        <table class="table table-head-fixed text-nowrap" id="cartTable"><thead><tr><th>Producto</th><th>Cant</th><th>Subtotal</th></tr></thead><tbody></tbody></table>
                                    </div>
                                    <div class="card-footer">
                                        <h3 class="float-right">Total: $<span id="cartTotal">0</span></h3>
                                        <form method="post" id="saleForm">
                                            <input type="hidden" name="cart_data" id="cartData">
                                            <input type="hidden" name="total_amount" id="totalAmount">
                                            <button type="button" onclick="submitSale()" class="btn btn-success btn-lg btn-block">Confirmar Venta</button>
                                            <button type="submit" name="process_sale" id="btnRealSubmit" style="display:none;"></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            let cart = [];
                            function addToCart(id, name, price) {
                                let item = cart.find(i => i.id === id);
                                if(item) item.qty++; else cart.push({id, name, price, qty: 1});
                                renderCart();
                            }
                            function renderCart() {
                                let tbody = document.querySelector("#cartTable tbody");
                                tbody.innerHTML = "";
                                let total = 0;
                                cart.forEach(i => {
                                    let sub = i.price * i.qty; total += sub;
                                    tbody.innerHTML += `<tr><td>${i.name}</td><td>${i.qty}</td><td>$${sub}</td></tr>`;
                                });
                                document.getElementById("cartTotal").innerText = total;
                                document.getElementById("totalAmount").value = total;
                                document.getElementById("cartData").value = JSON.stringify(cart);
                            }
                            function submitSale() {
                                if(cart.length === 0) return alert("Carrito vacío");
                                if(confirm("¿Procesar venta?")) document.getElementById("btnRealSubmit").click();
                            }
                        </script>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <footer class="main-footer"><div class="float-right d-none d-sm-block"><b>Version</b> 1.2</div><strong>Copyright &copy; 2024 AdminLTE Oracle.</strong></footer>
    </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>