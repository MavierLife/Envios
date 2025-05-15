// Js/ajax.js
$(document).ready(function() {
    console.log("ajax.js cargado y listo.");

    const contentSection = $('.content');
    const body = $('body'); // Necesario para cerrar el menú móvil
    const sidebar = $('#sidebar'); // Necesario para cerrar el menú móvil
    const sidebarOverlay = $('#sidebarOverlay'); // Necesario para cerrar el menú móvil

    // Función para cargar contenido
    function loadPage(pageUrl, pageTitle, pushState = true) {
        $.ajax({
            url: pageUrl,
            type: 'GET',
            dataType: 'html',
            beforeSend: function() {
                contentSection.html('<p style="text-align:center; padding:20px;">Cargando contenido...</p>');
            },
            success: function(response) {
                contentSection.html(response);
                if (pushState && history.pushState) {
                    history.pushState({ path: pageUrl }, pageTitle, pageUrl);
                }
                contentSection.scrollTop(0); // Scroll to top of content area

                // Si estamos en móvil y la sidebar está visible, la ocultamos
                // Re-leemos la variable CSS aquí por si acaso, aunque debería ser constante
                const mobileSidebarVisibleWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim();
                if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
                    body.removeClass('sidebar-mobile-shown');
                    sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
                    sidebarOverlay.fadeOut(200);
                }
            },
            error: function(xhr, status, error) {
                contentSection.html('<p style="text-align:center; padding:20px; color:red;">Error al cargar la página: ' + error + '</p>');
                console.error("AJAX Error:", status, error, xhr);
            }
        });
    }

    // Event Listener para los links de la sidebar
    $('.sidebar .nav-link').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const pageUrl = $this.attr('href');
        const pageTitle = $this.find('span').text();

        if (!pageUrl || pageUrl === '#' || pageUrl === window.location.href.split('#')[0] + '#' + pageUrl.replace(/^#/, '')) {
            // Evitar recargar la misma página o cargar href="#"
            // O si la URL ya coincide (considerando un posible hash)
             if (pageUrl === window.location.pathname + window.location.search + window.location.hash && pageUrl !== '#') {
                // Si es exactamente la misma URL actual, no hacer nada
                return;
            }
        }

        $('.sidebar .nav-link.active').removeClass('active');
        $this.addClass('active');

        loadPage(pageUrl, pageTitle);
    });

    // Cargar contenido inicial o basado en la URL
    function loadInitialOrHashedContent() {
        let pageToLoad = 'dashboard.php'; // Página por defecto
        let activeLinkSelector = '.sidebar .nav-link[href="dashboard.php"]'; // Link por defecto

        // Intentar cargar desde el hash si existe
        if (window.location.hash && window.location.hash !== '#') {
            const hashPage = window.location.hash.substring(1);
            const hashedLink = $('.sidebar .nav-link[href="' + hashPage + '"]');
            if (hashedLink.length) {
                pageToLoad = hashPage;
                activeLinkSelector = hashedLink;
            }
        } else {
             // Intentar cargar desde el path si no es index.php y existe el link
            const currentPath = window.location.pathname.split("/").pop();
            if (currentPath && currentPath !== 'index.php' && currentPath !== '') {
                 const pathLink = $('.sidebar .nav-link[href="' + currentPath + '"]');
                 if(pathLink.length) {
                    pageToLoad = currentPath;
                    activeLinkSelector = pathLink;
                 }
            }
        }


        const $initialLink = $(activeLinkSelector);
        if ($initialLink.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $initialLink.addClass('active');
            loadPage($initialLink.attr('href'), $initialLink.find('span').text(), false); // false para no pushear el state si es la carga inicial y la URL ya es correcta
        } else {
            // Fallback si el link inicial no se encuentra, cargar dashboard
            const $dashboardLink = $('.sidebar .nav-link[href="dashboard.php"]');
            if($dashboardLink.length){
                $('.sidebar .nav-link.active').removeClass('active');
                $dashboardLink.addClass('active');
                loadPage('dashboard.php', 'Dashboard', false);
            } else {
                 contentSection.html('<p>Bienvenido. Selecciona una opción del menú.</p>');
            }
        }
    }

    loadInitialOrHashedContent();

    // Manejar botones de atrás/adelante del navegador
    $(window).on('popstate', function(event) {
        let pageUrl;
        if (event.originalEvent.state && event.originalEvent.state.path) {
            pageUrl = event.originalEvent.state.path;
        } else {
            // Si no hay estado, podría ser el estado inicial o un hash
            if (window.location.hash && window.location.hash !== '#') {
                 pageUrl = window.location.hash.substring(1);
            } else {
                 pageUrl = window.location.pathname.split("/").pop() || 'dashboard.php';
                 if (pageUrl === 'index.php' || pageUrl === '') pageUrl = 'dashboard.php';
            }
        }

        const linkToClick = $('.sidebar .nav-link[href="' + pageUrl + '"]');
        if (linkToClick.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            linkToClick.addClass('active');
            loadPage(pageUrl, linkToClick.find('span').text(), false); // No pushear state, ya está en el historial
        } else {
            // Si no se encuentra el link, cargar dashboard como fallback
            const $dashboardLink = $('.sidebar .nav-link[href="dashboard.php"]');
            if($dashboardLink.length){
                 $('.sidebar .nav-link.active').removeClass('active');
                $dashboardLink.addClass('active');
                loadPage('dashboard.php', 'Dashboard', false);
            }
        }
    });

    console.log("Script de AJAX (ajax.js) completamente cargado y listeners adjuntados.");
});