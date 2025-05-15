<?php
session_start();
require_once 'Config/Database.php';

$message = '';

// If user is already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php?loggedout=true'); // Redirect to show a logged out message or just login page
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $message = 'Por favor, ingrese el usuario y la contraseña.';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        $username = $_POST['username'];
        $password = $_POST['password'];

        // Query to fetch user from tlbregistrodeempleados table
        // The table name comes from helensystem_data.sql
        $query = "SELECT UUIDEmpleado, CodigoEMP, Nombres, Apellidos, ClaveAcceso, ModuloAcceso 
                  FROM tblregistrodeempleados 
                  WHERE CodigoEMP = :username LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Verify password using password_verify since ClaveAcceso is a bcrypt hash
            // This information is from your request and confirmed by helensystem_data.sql structure for ClaveAcceso
            if (password_verify($password, $row['ClaveAcceso'])) {
                $_SESSION['user_id'] = $row['UUIDEmpleado'];
                $_SESSION['user_name'] = $row['Nombres'] . ' ' . $row['Apellidos'];
                $_SESSION['user_code'] = $row['CodigoEMP'];
                $_SESSION['user_acceso'] = $row['ModuloAcceso']; // Storing access level from db

                // Regenerate session ID for security
                session_regenerate_id(true);

                header('Location: index.php');
                exit;
            } else {
                $message = 'La contraseña ingresada es incorrecta.';
            }
        } else {
            $message = 'El usuario ingresado no existe.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="Css/bootstrap.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .form-control:focus {
            border-color: #263238;
            box-shadow: 0 0 0 0.2rem rgba(38,50,56,.25);
        }
        .btn-primary {
            background-color: #263238;
            border-color: #263238;
        }
        .btn-primary:hover {
            background-color: #1e2a33;
            border-color: #1e2a33;
        }
        .alert-danger {
            margin-top: 15px;
        }
        .alert-success {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Envios de cargas- Iniciar Sesión</h2>
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['loggedout'])): ?>
            <div class="alert alert-success" role="alert">
                Has cerrado sesión exitosamente.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['session_expired'])): ?>
            <div class="alert alert-warning" role="alert">
                Tu sesión ha expirado. Por favor, inicia sesión nuevamente.
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Usuario (Código EMP)</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
        </form>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>