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

  <link rel="stylesheet" href="Css/bootstrap.css"> <link rel="stylesheet" href="Css/icomoon/styles.css"> <style>
    :root {
      --sidebar-width: 260px;
      --sidebar-collapsed-width: 60px;
      --sidebar-bg: #263238;
      --icon-color: #c9cccd;
      --sidebar-padding-vertical: 1rem; /* Padding general para el contenido de la nav de la sidebar */
      --sidebar-item-gap: .5rem;
      --navbar-height: 45px;
      --mobile-sidebar-visible-width: 85%;
    }
    html, body {
      height: 100%; margin: 0; padding: 0; overflow-x: hidden; font-family: sans-serif;
    }
    body { padding-top: var(--navbar-height); } /* Espacio para la navbar fija */

    .wrapper { display: flex; flex-direction: column; min-height: calc(100vh - var(--navbar-height)); }

    /* --- Navbar --- */
    .navbar-fixed-top {
      flex: 0 0 auto; min-height: var(--navbar-height) !important; height: var(--navbar-height) !important;
      background-color: #263238; border-bottom: none; z-index: 1031; /* Navbar encima del overlay */
    }
    .navbar-fixed-top .navbar-brand,
    .navbar-fixed-top .nav > li > a {
        color: #fff; padding-top: 0 !important; padding-bottom: 0 !important;
        line-height: var(--navbar-height) !important; /* Centrar verticalmente */
    }
    .navbar-fixed-top .navbar-brand {
        height: var(--navbar-height) !important; font-size: 16px;
        margin-left: 5px !important; padding-left: 10px; padding-right: 10px;
    }
    .navbar-fixed-top .nav > li > a i { line-height: var(--navbar-height); } /* Alinear iconos en navbar */

    /* --- Main Layout --- */
    .main { display: flex; flex: 1; overflow: hidden; }

    /* --- Sidebar --- */
    .sidebar {
      width: var(--sidebar-width); background: var(--sidebar-bg);
      display: flex; flex-direction: column;
      transition: width .3s, left .3s ease-out;
      overflow-x: hidden; position: relative; flex-shrink: 0; z-index: 1000; /* z-index base */
    }
    .sidebar.collapsed { width: var(--sidebar-collapsed-width); } /* Solo para escritorio */

    .sidebar .nav { /* Contenedor de los items de menú en la sidebar */
      flex: 1; margin: 0; padding: 0; list-style: none; overflow-y: auto;
      padding-top: var(--sidebar-padding-vertical); /* Padding superior para el inicio de los items */
    }
    .sidebar .nav-item { margin-bottom: var(--sidebar-item-gap); }
    .sidebar .nav-link {
      display: flex; align-items: center; padding: 0.8rem 1rem; /* Padding de cada item */
      color: var(--icon-color); text-decoration: none; white-space: nowrap;
    }
    .sidebar .nav-link i {
      font-size: 1.2em; color: var(--icon-color); width: 20px; text-align: center; flex-shrink: 0;
      margin-right: 15px; /* Espacio entre icono y texto */
    }
    .sidebar .nav-link span {
      margin-left: 0; /* El margen ya está en el icono */
      display: inline-block; opacity: 1;
      transition: opacity 0.2s ease-out, width 0.2s ease-out;
      overflow: hidden; font-size: 0.9em; /* Texto un poco más pequeño */
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #1e2a33; color: #fff; }

    /* Comportamiento del texto en sidebar colapsada (escritorio) */
    .sidebar.collapsed .nav-link span { opacity: 0; width: 0; pointer-events: none; }
    .sidebar.collapsed .nav-link { justify-content: center; /* Centra el icono */ }
    .sidebar.collapsed .nav-link i { margin-right: 0; }

    /* --- Contenido Principal --- */
    .content {
      flex: 1; padding: 20px; background: #f5f5f5; overflow-y: auto;
      transition: margin-left .3s;
    }

    /* --- Overlay para Sidebar Móvil --- */
    .sidebar-overlay {
        display: none; position: fixed; top: 0; left: 0;
        width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
        z-index: 1030; /* Debajo de la sidebar móvil, encima del contenido */
    }
    body.sidebar-mobile-shown .sidebar-overlay { display: block; }

    /* --- Botones de Toggle --- */
    #sidebarToggleDesktop { /* Botón para ESCRITORIO */
        color: white; font-size: 1.2em; padding: 0 10px;
        margin-left: 10px; background: transparent; border: none;
        line-height: var(--navbar-height); /* Centrar verticalmente */
        float:left; /* Para que esté al lado de la marca */
    }
    #sidebarToggleDesktop:hover { background-color: rgba(255,255,255,0.1); }

    #sidebarToggleMobile { /* Botón para MÓVIL */
        color: white; font-size: 1.3em; padding: 0 15px;
        display: none; /* Se controla por media query */
        line-height: var(--navbar-height); /* Centrar verticalmente */
        background: transparent; border: none; /* Asegurar que es un botón limpio */
    }
     #sidebarToggleMobile:hover, #sidebarToggleMobile:focus { background-color: rgba(255,255,255,0.1); color:white; }


    /* === MEDIA QUERIES === */
    @media (min-width: 768px) { /* ESCRITORIO */
      .sidebar { /* Estado por defecto en escritorio */
          position: relative !important; /* Volver a ser parte del flujo normal */
          left: auto !important; /* Limpiar estilos de móvil */
          top: auto !important;
          height: auto !important;
          padding-top: 0 !important; /* El padding lo maneja .sidebar .nav para sus items */
          /* El width lo maneja .collapsed o el valor por defecto de .sidebar */
          /* z-index se puede reducir si no es necesario que esté encima de otros elementos en escritorio */
          z-index: 1000 !important;
      }
      /* Márgenes del contenido según estado de la sidebar en escritorio */
      .sidebar:not(.collapsed) + .content { margin-left: var(--sidebar-width); }
      .sidebar.collapsed + .content { margin-left: var(--sidebar-collapsed-width); }

      /* Visibilidad de botones de toggle */
      #sidebarToggleDesktop { display: block !important; }
      #sidebarToggleMobile { display: none !important; }

      /* Mostrar texto en items de navbar derecha en escritorio */
      .navbar-fixed-top .navbar-right > li > a > span { display: inline-block; }
    }

    @media (max-width: 767px) { /* MÓVIL */
      .sidebar { /* Estilos base para la sidebar en móvil, inicialmente oculta */
        position: fixed !important;
        left: calc(-1 * var(--mobile-sidebar-visible-width) - 10px) !important; /* Oculta fuera de pantalla */
        top: 0 !important; /* Desde el tope de la pantalla */
        height: 100% !important; /* Altura completa */
        width: var(--mobile-sidebar-visible-width) !important; /* Ancho que tendrá cuando sea visible */
        z-index: 1035 !important; /* Encima del overlay y navbar */
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        padding-top: var(--navbar-height) !important; /* Espacio para la barra superior DENTRO de la sidebar */
      }
      .sidebar.sidebar-mobile-visible { /* Clase para MOSTRAR la sidebar en móvil */
        left: 0 !important;
      }
      .sidebar .nav { /* En móvil, el padding superior de los items de la lista */
          padding-top: 15px; /* Espacio adicional después de la barra superior conceptual */
      }

      /* Estilos de los links cuando la sidebar móvil está visible */
      .sidebar.sidebar-mobile-visible .nav-link span { opacity: 1; width: auto; pointer-events: auto; }
      .sidebar.sidebar-mobile-visible .nav-link { justify-content: flex-start; }
      .sidebar.sidebar-mobile-visible .nav-link i { font-size: 1.1em; margin-right: 10px;}

      /* Contenido en móvil */
      .content { margin-left: 0 !important; padding: 15px; }

      /* Navbar en Móvil */
      .navbar-fixed-top .container-fluid { /* Alinear brand y toggle */
          display: flex; justify-content: space-between; align-items: center;
          padding-left: 5px; padding-right: 5px; /* Reducir padding del container-fluid */
      }
       .navbar-fixed-top .navbar-brand { flex-grow: 1; /* Para que la marca ocupe el espacio y empuje el toggle */ }

      /* Visibilidad de botones de toggle */
      #sidebarToggleDesktop { display: none !important; }
      #sidebarToggleMobile { display: block !important; } /* Mostrar botón de móvil */

      /* Ocultar items de navbar derecha en móvil */
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
          <li><a href="#"><i class="icon-user"></i><span><?php echo $_SESSION['user_name']; ?></span></a></li>
          <li><a href="login.php?logout=true"><i class="icon-switch2"></i> <span>Cerrar sesión</span></a></li>
        </ul>
      </div>
    </nav>

    <div class="main">
      <nav id="sidebar" class="sidebar collapsed">
        <ul class="nav">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="icon-home4"></i><span>Dashboard</span></a></li>
          <li class="nav-item"><a class="nav-link" href="ventas.php"><i class="icon-stack"></i><span>Ventas</span></a></li>
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
        <h1>DashBoard - HelenSystem</h1>
        <p>Contenido principal de la aplicación.</p>
        <div style="height:1200px; background: #ddd; padding:10px;">Contenido largo para probar scroll...</div>
      </section>
    </div>
  </div>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

  <script>
  // Esperar a que jQuery esté disponible
  if (typeof jQuery === 'undefined') {
    console.error("jQuery no se ha cargado. El script de la sidebar no funcionará.");
  } else {
    console.log("jQuery cargado. Versión:", jQuery.fn.jquery); // Verificar versión de jQuery
    $(document).ready(function() {
      console.log("Document ready. Adjuntando eventos de la sidebar...");

      const sidebar = $('#sidebar');
      const content = $('.content');
      const body = $('body');
      const sidebarOverlay = $('#sidebarOverlay');
      const sidebarToggleDesktop = $('#sidebarToggleDesktop');
      const sidebarToggleMobile = $('#sidebarToggleMobile');

      // Leer variables CSS una vez (si es posible, o leerlas dentro de updateLayout si cambian dinámicamente)
      const sidebarWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width').trim();
      const sidebarCollapsedWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--sidebar-collapsed-width').trim();
      const mobileSidebarVisibleWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim();
      const navbarHeightVal = getComputedStyle(document.documentElement).getPropertyValue('--navbar-height').trim();

      function updateLayout() {
        const windowWidth = window.innerWidth;
        // console.log("updateLayout - windowWidth:", windowWidth); // Descomentar para depurar resize

        if (windowWidth < 768) { // VISTA MÓVIL
          // Ocultar botón de escritorio, mostrar botón de móvil (manejado por CSS)
          content.css('margin-left', '0'); // Contenido siempre ocupa todo el ancho

          // Aplicar estilos 'fixed' a la sidebar para que esté lista para off-canvas
          sidebar.css({
              'position': 'fixed',
              'top': '0', // Desde el borde superior de la ventana
              'height': '100%', // Altura completa de la ventana
              'width': mobileSidebarVisibleWidthVal, // Ancho que tendrá cuando sea visible
              'padding-top': navbarHeightVal // Espacio para la navbar superior DENTRO de la sidebar
          });

          // Si el body tiene la clase 'sidebar-mobile-shown', la sidebar debe estar visible
          if (body.hasClass('sidebar-mobile-shown')) {
            sidebar.addClass('sidebar-mobile-visible').css('left', '0');
            sidebarOverlay.show(); // Asegurar que el overlay esté visible
          } else {
            sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
            sidebarOverlay.hide(); // Asegurar que el overlay esté oculto
          }
        } else { // VISTA ESCRITORIO
          // Quitar clases y estilos de móvil
          body.removeClass('sidebar-mobile-shown');
          sidebar.removeClass('sidebar-mobile-visible');
          sidebarOverlay.hide();

          sidebar.css({ // Restaurar estilos de escritorio
            'position': 'relative',
            'left': '', // Limpiar 'left' de móvil
            'top': '',
            'height': '',
            'padding-top': '' // El padding-top de los items de la nav lo maneja .sidebar .nav
          });

          // Aplicar width y margin-left del content según estado colapsado
          if (sidebar.hasClass('collapsed')) {
            content.css('margin-left', sidebarCollapsedWidthVal);
            sidebar.css('width', sidebarCollapsedWidthVal);
          } else {
            content.css('margin-left', sidebarWidthVal);
            sidebar.css('width', sidebarWidthVal);
          }
        }
      }

      // Event Listener para Toggle de ESCRITORIO
      sidebarToggleDesktop.on('click', function(e) {
        e.preventDefault();
        // console.log("Click en sidebarToggleDesktop"); // Descomentar para depurar
        if (window.innerWidth >= 768) {
          sidebar.toggleClass('collapsed');
          updateLayout(); // Re-aplicar estilos de escritorio
        }
      });

      // Event Listener para Toggle de MÓVIL (usando delegación para robustez)
      // console.log("Verificando #sidebarToggleMobile ANTES de adjuntar evento. Existe:", $('#sidebarToggleMobile').length); // DEBUG
      $(document).on('click', '#sidebarToggleMobile', function(e) {
          e.preventDefault();
          // alert("¡Botón móvil clickeado! (Desde delegado)"); // Descomentar para prueba de clic muy básica
          console.log("Click en #sidebarToggleMobile (delegado). Ancho ventana:", window.innerWidth);

          if (window.innerWidth < 768) {
              console.log("Ejecutando toggle para móvil (delegado)");
              body.toggleClass('sidebar-mobile-shown');

              if (body.hasClass('sidebar-mobile-shown')) {
                  console.log("Mostrando sidebar móvil y overlay (delegado)");
                  sidebar.addClass('sidebar-mobile-visible').css('left', '0');
                  sidebarOverlay.fadeIn(200);
              } else {
                  console.log("Ocultando sidebar móvil y overlay (delegado)");
                  sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
                  sidebarOverlay.fadeOut(200);
              }
          } else {
              console.warn("Clic en #sidebarToggleMobile detectado, PERO el ancho es de ESCRITORIO. Esto no debería pasar si el CSS oculta el botón correctamente.");
          }
      });

      // Event Listener para Overlay (cerrar sidebar móvil)
      sidebarOverlay.on('click', function() {
        // console.log("Click en Overlay"); // Descomentar para depurar
        if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
          // console.log("Cerrando sidebar móvil desde overlay"); // Descomentar para depurar
          body.removeClass('sidebar-mobile-shown');
          sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
          $(this).fadeOut(200);
        }
      });

      // Ejecutar updateLayout en resize y al cargar para estado inicial correcto
      $(window).on('resize', updateLayout).trigger('resize');

      console.log("Script de la sidebar completamente cargado y listeners adjuntados.");
    });
  }
  </script>
</body>
</html>