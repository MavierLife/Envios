// Js/Sidebar.js
$(document).ready(function() {
    console.log("Sidebar.js cargado y listo.");

    const sidebar = $('#sidebar');
    const content = $('.content'); // El script del sidebar ajusta el margen de esta sección
    const body = $('body');
    const sidebarOverlay = $('#sidebarOverlay');
    const sidebarToggleDesktop = $('#sidebarToggleDesktop');
    const sidebarToggleMobile = $('#sidebarToggleMobile');

    // Leer variables CSS una vez
    const sidebarWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width').trim();
    const sidebarCollapsedWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--sidebar-collapsed-width').trim();
    const mobileSidebarVisibleWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim();
    const navbarHeightVal = getComputedStyle(document.documentElement).getPropertyValue('--navbar-height').trim();

    function updateLayout() {
        const windowWidth = window.innerWidth;

        if (windowWidth < 768) { // VISTA MÓVIL
            content.css('margin-left', '0');
            sidebar.css({
                'position': 'fixed',
                'top': '0',
                'height': '100%',
                'width': mobileSidebarVisibleWidthVal,
                'padding-top': navbarHeightVal
            });

            if (body.hasClass('sidebar-mobile-shown')) {
                sidebar.addClass('sidebar-mobile-visible').css('left', '0');
                sidebarOverlay.show();
            } else {
                sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
                sidebarOverlay.hide();
            }
        } else { // VISTA ESCRITORIO
            body.removeClass('sidebar-mobile-shown');
            sidebar.removeClass('sidebar-mobile-visible');
            sidebarOverlay.hide();

            sidebar.css({
                'position': 'relative',
                'left': '',
                'top': '',
                'height': '',
                'padding-top': ''
            });

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
        if (window.innerWidth >= 768) {
            sidebar.toggleClass('collapsed');
            updateLayout();
        }
    });

    // Event Listener para Toggle de MÓVIL
    $(document).on('click', '#sidebarToggleMobile', function(e) {
        e.preventDefault();
        if (window.innerWidth < 768) {
            body.toggleClass('sidebar-mobile-shown');
            if (body.hasClass('sidebar-mobile-shown')) {
                sidebar.addClass('sidebar-mobile-visible').css('left', '0');
                sidebarOverlay.fadeIn(200);
            } else {
                sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
                sidebarOverlay.fadeOut(200);
            }
        }
    });

    // Event Listener para Overlay (cerrar sidebar móvil)
    sidebarOverlay.on('click', function() {
        if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
            body.removeClass('sidebar-mobile-shown');
            sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
            $(this).fadeOut(200);
        }
    });

    // Ejecutar updateLayout en resize y al cargar para estado inicial correcto
    $(window).on('resize', updateLayout).trigger('resize');

    console.log("Script de la sidebar (Sidebar.js) completamente cargado y listeners adjuntados.");
});