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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MiMarca</title>

  <link rel="stylesheet" href="Css/bootstrap.css">
  <link rel="stylesheet" href="Css/icomoon/styles.css">
  <style>
    :root {
      --sidebar-width: 260px;
      --sidebar-collapsed-width: 60px;
      --sidebar-bg: #263238;
      --icon-color: #c9cccd;
      --sidebar-padding-vertical: 1rem;
      --sidebar-item-gap: .5rem;
      --navbar-height: 45px;
      --mobile-sidebar-visible-width: 85%;
    }
    html, body {
      height: 100%; margin: 0; padding: 0; overflow-x: hidden; font-family: sans-serif;
    }
    body { padding-top: var(--navbar-height); }

    .wrapper { display: flex; flex-direction: column; min-height: calc(100vh - var(--navbar-height)); }

    .navbar-fixed-top {
      flex: 0 0 auto; min-height: var(--navbar-height) !important; height: var(--navbar-height) !important;
      background-color: #263238; border-bottom: none; z-index: 1031;
    }
    .navbar-fixed-top .navbar-brand,
    .navbar-fixed-top .nav > li > a {
        color: #fff; padding-top: 0 !important; padding-bottom: 0 !important;
        line-height: var(--navbar-height) !important;
    }
    .navbar-fixed-top .navbar-brand {
        height: var(--navbar-height) !important; font-size: 16px;
        margin-left: 5px !important; padding-left: 10px; padding-right: 10px;
    }
    .navbar-fixed-top .nav > li > a i { line-height: var(--navbar-height); }

    .main { display: flex; flex: 1; overflow: hidden; }

    .sidebar {
      width: var(--sidebar-width); background: var(--sidebar-bg);
      display: flex; flex-direction: column;
      transition: width .3s, left .3s ease-out;
      overflow-x: hidden; position: relative; flex-shrink: 0; z-index: 1000;
    }
    .sidebar.collapsed { width: var(--sidebar-collapsed-width); }

    .sidebar .nav {
      flex: 1; margin: 0; padding: 0; list-style: none; overflow-y: auto;
      padding-top: var(--sidebar-padding-vertical);
    }
    .sidebar .nav-item { margin-bottom: var(--sidebar-item-gap); }
    .sidebar .nav-link {
      display: flex; align-items: center; padding: 0.8rem 1rem;
      color: var(--icon-color); text-decoration: none; white-space: nowrap;
      cursor: pointer;
    }
    .sidebar .nav-link i {
      font-size: 1.2em; color: var(--icon-color); width: 20px; text-align: center; flex-shrink: 0;
      margin-right: 15px;
    }
    .sidebar .nav-link span {
      margin-left: 0;
      display: inline-block; opacity: 1;
      transition: opacity 0.2s ease-out, width 0.2s ease-out;
      overflow: hidden; font-size: 0.9em;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #1e2a33; color: #fff; }

    .sidebar.collapsed .nav-link span { opacity: 0; width: 0; pointer-events: none; }
    .sidebar.collapsed .nav-link { justify-content: center; }
    .sidebar.collapsed .nav-link i { margin-right: 0; }

    .content {
      flex: 1; padding: 20px; background: #f5f5f5; overflow-y: auto;
      transition: margin-left .3s;
    }

    .sidebar-overlay {
        display: none; position: fixed; top: 0; left: 0;
        width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
        z-index: 1030;
    }
    body.sidebar-mobile-shown .sidebar-overlay { display: block; }

    #sidebarToggleDesktop {
        color: white; font-size: 1.2em; padding: 0 10px;
        margin-left: 10px; background: transparent; border: none;
        line-height: var(--navbar-height);
        float:left;
    }
    #sidebarToggleDesktop:hover { background-color: rgba(255,255,255,0.1); }

    #sidebarToggleMobile {
        color: white; font-size: 1.3em; padding: 0 15px;
        display: none;
        line-height: var(--navbar-height);
        background: transparent; border: none;
    }
     #sidebarToggleMobile:hover, #sidebarToggleMobile:focus { background-color: rgba(255,255,255,0.1); color:white; }

    @media (min-width: 768px) {
      .sidebar {
          position: relative !important; left: auto !important; top: auto !important;
          height: auto !important; padding-top: 0 !important; z-index: 1000 !important;
      }
      .sidebar:not(.collapsed) + .content { margin-left: var(--sidebar-width); }
      .sidebar.collapsed + .content { margin-left: var(--sidebar-collapsed-width); }
      #sidebarToggleDesktop { display: block !important; }
      #sidebarToggleMobile { display: none !important; }
      .navbar-fixed-top .navbar-right > li > a > span { display: inline-block; }
    }

    @media (max-width: 767px) {
      .sidebar {
        position: fixed !important;
        left: calc(-1 * var(--mobile-sidebar-visible-width) - 10px) !important;
        top: 0 !important; height: 100% !important;
        width: var(--mobile-sidebar-visible-width) !important;
        z-index: 1035 !important; box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        padding-top: var(--navbar-height) !important;
      }
      .sidebar.sidebar-mobile-visible { left: 0 !important; }
      .sidebar .nav { padding-top: 15px; }
      .sidebar.sidebar-mobile-visible .nav-link span { opacity: 1; width: auto; pointer-events: auto; }
      .sidebar.sidebar-mobile-visible .nav-link { justify-content: flex-start; }
      .sidebar.sidebar-mobile-visible .nav-link i { font-size: 1.1em; margin-right: 10px;}
      .content { margin-left: 0 !important; padding: 15px; }
      .navbar-fixed-top .container-fluid {
          display: flex; justify-content: space-between; align-items: center;
          padding-left: 5px; padding-right: 5px;
      }
       .navbar-fixed-top .navbar-brand { flex-grow: 1; }
      #sidebarToggleDesktop { display: none !important; }
      #sidebarToggleMobile { display: block !important; }
      .navbar-fixed-top .nav.navbar-nav.navbar-right { display: none; }
    }
  </style>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="wrapper">
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <button type="button" id="sidebarToggleDesktop">
            <i class="icon-paragraph-justify3"></i>
        </button>
        <a class="navbar-brand" href="#">MiMarca</a>

        <button type="button" id="sidebarToggleMobile">
            <i class="icon-menu"></i>
        </button>

        <ul class="nav navbar-nav navbar-right">
          <li><a href="#"><i class="icon-user"></i><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></a></li>
          <li><a href="login.php?logout=true"><i class="icon-switch2"></i> <span>Cerrar sesión</span></a></li>
        </ul>
      </div>
    </nav>

    <div class="main">
      <nav id="sidebar" class="sidebar collapsed"> <ul class="nav">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="icon-home4"></i><span>Dashboard</span></a></li>
          <li class="nav-item"><a class="nav-link" href="produccion.php"><i class="icon-stack"></i><span>Produccion</span></a></li>
          <li class="nav-item"><a class="nav-link" href="compras.php"><i class="icon-cart5"></i><span>COMPRAS</span></a></li>
          <li class="nav-item"><a class="nav-link" href="facturacion.php"><i class="icon-file-text2"></i><span>FACTURACION</span></a></li>
          <li class="nav-item"><a class="nav-link" href="creditos.php"><i class="icon-credit-card"></i><span>CREDITOS</span></a></li>
          <li class="nav-item"><a class="nav-link" href="inventario.php"><i class="icon-archive"></i><span>INVENTARIO</span></a></li>
          <li class="nav-item"><a class="nav-link" href="clientes.php"><i class="icon-users"></i><span>CLIENTES</span></a></li>
          <li class="nav-item"><a class="nav-link" href="configuracion.php"><i class="icon-cogs"></i><span>Configuración</span></a></li>
          <li class="nav-item"><a class="nav-link" href="ayuda.php"><i class="icon-question3"></i><span>AYUDA</span></a></li>
        </ul>
      </nav>

      <section class="content">
        </section>
    </div>
  </div>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

  <script src="Js/Sidebar.js"></script>
  <script src="Js/ajax.js"></script>

</body>
</html>