<?php
session_start();
// 1) Validación de sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_acceso'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>MiMarca</title>

  <!-- Bootstrap e IcoMoon -->
  <link rel="stylesheet" href="bootstrap.css">
  <link rel="stylesheet" href="icomoon/styles.css">

  <style>
    :root {
      --sidebar-width: 260px;
      --sidebar-collapsed-width: 60px;
      --sidebar-bg: #263238;
      --icon-color: #c9cccd;
      --sidebar-padding-vertical: 1rem;
      --sidebar-item-gap: .5rem;
    }
    html, body {
      height: 100%;
      margin: 0; padding: 0;
    }
    body {
      padding-top: 50px; /* espacio para navbar fija */
    }
    /* Wrapper: navbar + main */
    .wrapper {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    /* Navbar fija arriba */
    .navbar-fixed-top {
      flex: 0 0 auto;
    }
    /* Main: sidebar + contenido */
    .main {
      display: flex;
      flex: 1;
      overflow: hidden;
    }

    /* Sidebar siempre controlado por JS (no :hover) */
    .sidebar {
      flex: 0 0 var(--sidebar-width);
      width: var(--sidebar-width);
      background: var(--sidebar-bg);
      display: flex;
      flex-direction: column;
      transition: width .3s;
      overflow-x: hidden;
    }
    .sidebar.collapsed {
      flex: 0 0 var(--sidebar-collapsed-width);
      width: var(--sidebar-collapsed-width);
    }
    .sidebar .nav {
      flex: 1;
      margin: 0;
      padding: 0;
      list-style: none;
      overflow-y: auto;
      padding-top: var(--sidebar-padding-vertical);
    }
    .sidebar .nav-item {
      margin-bottom: var(--sidebar-item-gap);
    }
    .sidebar .nav-link {
      display: flex;
      align-items: center;
      padding: var(--sidebar-padding-vertical) 1rem;
      color: var(--icon-color);
      text-decoration: none;
      white-space: nowrap;
    }
    .sidebar .nav-link i {
      font-size: 1.4em;
      color: var(--icon-color);
    }
    .sidebar .nav-link span {
      margin-left: 1rem;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: #1e2a33;
      color: #fff;
    }
    .sidebar.collapsed .nav-link span {
      display: none; /* sólo icono */
    }
    .sidebar.collapsed .nav-link {
      justify-content: center;
    }

    /* Contenido */
    .content {
      flex: 1;
      padding: 20px;
      background: #f5f5f5;
      overflow-y: auto;
    }
  </style>
</head>
<body>

  <div class="wrapper">
    <!-- Navbar -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <!-- Botón que alterna .collapsed en la sidebar -->
          <button id="sidebarToggle" class="btn btn-link text-white">
            <i class="icon-paragraph-justify3"></i>
          </button>
          <a class="navbar-brand text-white" href="#">MiMarca</a>
        </div>
        <ul class="nav navbar-nav navbar-right">
          <li>
            <a class="text-white" href="#"><i class="icon-user"></i>
              <?php echo $_SESSION['user_name']; ?>
            </a>
          </li>
          <li>
            <a class="text-white" href="login.php?logout=true">
              <i class="icon-switch2"></i> Cerrar sesión
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Sidebar y contenido -->
    <div class="main">
      <nav id="sidebar" class="sidebar collapsed">
        <ul class="nav">
          <!-- Rutas de ejemplo: apunta a páginas reales -->
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="icon-home4"></i><span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="ventas.php">
              <i class="icon-stack"></i><span>Ventas</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="configuracion.php">
              <i class="icon-cogs"></i><span>Configuración</span>
            </a>
          </li>
          <!-- …más ítems… -->
        </ul>
      </nav>

      <section class="content">
        <!-- Aquí carga tu contenido real (p.ej include($pathView)) -->
      </section>
    </div>
  </div>

  <!-- JS -->
  <script src="jquery.min.js"></script>
  <script src="bootstrap.min.js"></script>
  <script>
    // Toggle de colapsado sólo con JS
    document.getElementById('sidebarToggle').addEventListener('click', function(){
      document.getElementById('sidebar').classList.toggle('collapsed');
    });
  </script>
</body>
</html>
