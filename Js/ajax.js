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
            dataType: 'html', // Esperamos HTML por defecto
            beforeSend: function() {
                contentSection.html('<p style="text-align:center; padding:20px;">Cargando contenido...</p>');
            },
            success: function(response, status, xhr) {
                // Verificar si la respuesta es JSON (en caso de que el servidor decida enviar JSON en éxito)
                // Normalmente, para la carga de páginas, se espera HTML.
                // Esta verificación es más para endpoints API, pero no hace daño tenerla.
                try {
                    const contentType = xhr.getResponseHeader("content-type") || "";
                    if (contentType.indexOf("application/json") > -1) {
                        // Si es JSON y tiene un mensaje de error o redirección inesperado aquí, lo manejamos.
                        let jsonData = JSON.parse(response);
                        if (jsonData.redirect) {
                            window.location.href = jsonData.redirect;
                            return;
                        }
                        if (jsonData.error) {
                            contentSection.html('<p style="text-align:center; padding:20px; color:red;">' + jsonData.error + (jsonData.message ? ': ' + jsonData.message : '') + '</p>');
                            return;
                        }
                        // Si es JSON pero no es un error/redirect, probablemente no es lo que esperamos para cargar una página.
                        // Continuamos como si fuera HTML, pero esto podría ser un punto a revisar según la lógica del backend.
                    }
                } catch (e) {
                    // No es JSON o error al parsear, asumimos que es HTML.
                }

                contentSection.html(response); // Carga el HTML en la sección de contenido
                if (pushState && history.pushState) {
                    history.pushState({ path: pageUrl }, pageTitle, pageUrl);
                }
                document.title = pageTitle || "MiMarca"; // Actualiza el título de la página
                contentSection.scrollTop(0); // Scroll al inicio del área de contenido

                // Si estamos en móvil y la sidebar está visible, la ocultamos
                const mobileSidebarVisibleWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim();
                if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
                    body.removeClass('sidebar-mobile-shown');
                    sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
                    sidebarOverlay.fadeOut(200);
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401 || xhr.status === 403) { // 401 No Autorizado, 403 Prohibido
                    try {
                        let responseData = JSON.parse(xhr.responseText); // El backend PHP envía JSON en estos casos
                        if (responseData.redirect) {
                            window.location.href = responseData.redirect; // Redirige la ventana principal
                            return;
                        } else if (responseData.message) {
                            // Muestra el mensaje de error en la sección de contenido si no hay redirección
                            contentSection.html('<p style="text-align:center; padding:20px; color:red;">' + responseData.message + '</p>');
                            return;
                        } else if (responseData.error) {
                             contentSection.html('<p style="text-align:center; padding:20px; color:red;">Error: ' + responseData.error + '</p>');
                            return;
                        }
                    } catch (e) {
                        // Si la respuesta no es JSON o no tiene 'redirect', redirige por defecto para 401
                        if (xhr.status === 401) {
                            window.location.href = 'login.php?session_expired=true';
                            return;
                        }
                    }
                }
                // Para otros errores o si el parseo falló y no es 401
                contentSection.html('<p style="text-align:center; padding:20px; color:red;">Error al cargar la página: ' + error + ' (Estado: ' + xhr.status + ')</p>');
                console.error("AJAX Error:", status, error, xhr.responseText);
            }
        });
    }

    // Event Listener para los links de la sidebar
    $(document).on('click', '.sidebar .nav-link', function(e) { // Usar 'on' para elementos dinámicos si el sidebar se recarga
        e.preventDefault();
        const $this = $(this);
        const pageUrl = $this.attr('href');
        const pageTitle = $this.find('span').text() || $this.text() || "MiMarca"; // Intenta obtener el título

        // Evitar recargar la misma página o cargar href="#"
        if (!pageUrl || pageUrl === '#' || (window.location.pathname + window.location.search + window.location.hash === pageUrl)) {
            // Si es exactamente la misma URL actual (incluyendo hash), no hacer nada
            if(pageUrl !== '#') return;
        }
        
        // Opcional: si la URL es solo un hash para la página actual, manejarlo o ignorarlo
        if (pageUrl.startsWith('#') && pageUrl.length > 1 && (window.location.pathname + window.location.search) === (window.location.pathname + window.location.search)) {
            // console.log("Manejando solo hash:", pageUrl);
            // Podrías querer hacer scroll a un elemento o alguna otra acción sin recargar contenido
            // Por ahora, lo tratamos como un enlace normal, o puedes optar por no hacer nada:
            // return;
        }


        $('.sidebar .nav-link.active').removeClass('active');
        $this.addClass('active');

        loadPage(pageUrl, pageTitle);
    });

    // Cargar contenido inicial o basado en la URL (path o hash)
    function loadInitialOrHashedContent() {
        let pageToLoad = 'dashboard.php'; // Página por defecto
        let pageTitle = 'Dashboard';
        let activeLinkSelector = '.sidebar .nav-link[href="dashboard.php"]';

        let potentialPage = '';
        // Primero intentar desde el hash si existe y es válido (no solo "#")
        if (window.location.hash && window.location.hash.length > 1) { // Asegura que el hash no sea solo '#'
            potentialPage = window.location.hash.substring(1); // Quita el '#'
        } else {
            // Si no hay hash, intentar desde el path
            const currentPath = window.location.pathname.split("/").pop();
            if (currentPath && currentPath !== 'index.php' && currentPath !== '' && currentPath !== 'Envios') { // Evita 'index.php' o el nombre del directorio
                potentialPage = currentPath;
            }
        }

        if (potentialPage) {
            const $link = $('.sidebar .nav-link[href="' + potentialPage + '"]');
            if ($link.length) {
                pageToLoad = potentialPage;
                pageTitle = $link.find('span').text() || $link.text();
                activeLinkSelector = $link;
            } else {
                 // Si el hash/path no coincide con un link, se usará el default (dashboard)
                 // o puedes decidir no cargar nada específico.
                 console.warn("Página en hash/path '" + potentialPage + "' no encontrada en el sidebar. Cargando dashboard por defecto.");
            }
        }
        // ... resto de la función loadInitialOrHashedContent
        const $initialLink = $(activeLinkSelector);
        if ($initialLink.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $initialLink.addClass('active');
            const currentCleanUrl = window.location.pathname.split("/").pop();
            const shouldPushState = !(currentCleanUrl === pageToLoad || (currentCleanUrl === 'index.php' && pageToLoad === 'dashboard.php') || window.location.hash.substring(1) === pageToLoad);
            loadPage(pageToLoad, pageTitle, shouldPushState);
        } else {
            contentSection.html('<p>Bienvenido. Selecciona una opción del menú o el dashboard no pudo ser cargado.</p>');
            console.warn("No se encontró el link inicial/dashboard para cargar contenido.");
        }
    }

    loadInitialOrHashedContent(); // Llama a la función

    // Manejar botones de atrás/adelante del navegador
    $(window).on('popstate', function(event) {
        let pageUrlToLoad;
        let pageTitle = "MiMarca";

        if (event.originalEvent.state && event.originalEvent.state.path) {
            pageUrlToLoad = event.originalEvent.state.path;
        } else {
            // Si no hay estado, podría ser el estado inicial o un hash
            if (window.location.hash && window.location.hash.length > 1) {
                 pageUrlToLoad = window.location.hash.substring(1);
            } else {
                 pageUrlToLoad = window.location.pathname.split("/").pop() || 'dashboard.php';
                 if (pageUrlToLoad === 'index.php' || pageUrlToLoad === '') pageUrlToLoad = 'dashboard.php';
            }
        }

        const $linkToActivate = $('.sidebar .nav-link[href="' + pageUrlToLoad + '"]');
        if ($linkToActivate.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $linkToActivate.addClass('active');
            pageTitle = $linkToActivate.find('span').text() || $linkToActivate.text();
            loadPage(pageUrlToLoad, pageTitle, false); // false para no pushear state, ya está en el historial
        } else {
            // Si no se encuentra el link, cargar dashboard como fallback seguro
            const $dashboardLink = $('.sidebar .nav-link[href="dashboard.php"]');
            if($dashboardLink.length){
                 $('.sidebar .nav-link.active').removeClass('active');
                $dashboardLink.addClass('active');
                loadPage('dashboard.php', 'Dashboard', false);
            } else {
                console.warn("Popstate: No se encontró el link para:", pageUrlToLoad, "ni el link de dashboard.");
                 contentSection.html('<p>Contenido no encontrado.</p>');
            }
        }
    });

    console.log("Script de AJAX (ajax.js) completamente cargado y listeners adjuntados.");
});