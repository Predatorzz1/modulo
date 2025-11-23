<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .section {
            border: 2px solid black;
            padding: 20px;
            width: 750px;
            text-align: center;
        }
        .section h2 {
            margin-top: 0;
        }
        .button-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .button-grid button {
            padding: 10px;
            font-size: 14px;
            cursor: pointer;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover{
            background-color: #000000;
            color: white;
        }
    </style>
</head>
<body>
    <div class="section">
        <h2>Invitado</h2>
        <div class="button-grid">
            <button onclick="func1Cliente()">Productos más vendidos</button>
        </div>
    </div>
    <script src = "links.js"></script>
</body>
</html>
