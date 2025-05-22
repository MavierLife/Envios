// Js/ajax.js
$(document).ready(function() {
    console.log("ajax.js cargado y listo.");

    const contentSection = $('.content');
    const body = $('body'); // Necesario para cerrar el menú móvil
    const sidebar = $('#sidebar'); // Necesario para cerrar el menú móvil
    const sidebarOverlay = $('#sidebarOverlay'); // Necesario para cerrar el menú móvil

    // Función para cargar contenido
    function loadPage(pageUrl, pageTitle, pushState = true) { // pageUrl es el nombre base, ej: "dashboard"
        let requestUrl = pageUrl.endsWith('.php')
            ? pageUrl
            : pageUrl + '.php'; // requestUrl es "dashboard.php"

        $.ajax({
            url: requestUrl,
            type: 'GET',
            dataType: 'html', 
            beforeSend: function() {
                contentSection.html('<p style="text-align:center; padding:20px;">Cargando contenido...</p>');
            },
            success: function(response, status, xhr) {
                try {
                    const contentType = xhr.getResponseHeader("content-type") || "";
                    if (contentType.indexOf("application/json") > -1) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.redirect) {
                            window.location.href = jsonData.redirect;
                            return;
                        }
                        if (jsonData.error) {
                            contentSection.html('<p style="text-align:center; padding:20px; color:red;">' + jsonData.error + (jsonData.message ? ': ' + jsonData.message : '') + '</p>');
                            return;
                        }
                    }
                } catch (e) {
                    // No es JSON o error al parsear, asumimos que es HTML.
                }

                contentSection.html(response);
                if (pushState && history.pushState) {
                    // Siempre usar hash para la URL visible y el estado
                    history.pushState({ path: pageUrl }, pageTitle, '#' + pageUrl); 
                    document.title = pageTitle;
                }
                contentSection.scrollTop(0);

                const mobileSidebarVisibleWidthVal = getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim();
                if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
                    body.removeClass('sidebar-mobile-shown');
                    sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${mobileSidebarVisibleWidthVal} - 10px)`);
                    sidebarOverlay.fadeOut(200);
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401 || xhr.status === 403) {
                    try {
                        let responseData = JSON.parse(xhr.responseText);
                        if (responseData.redirect) {
                            window.location.href = responseData.redirect;
                            return;
                        } else if (responseData.message) {
                            contentSection.html('<p style="text-align:center; padding:20px; color:red;">' + responseData.message + '</p>');
                            return;
                        } else if (responseData.error) {
                             contentSection.html('<p style="text-align:center; padding:20px; color:red;">Error: ' + responseData.error + '</p>');
                            return;
                        }
                    } catch (e) {
                        if (xhr.status === 401) {
                            window.location.href = 'login.php?session_expired=true';
                            return;
                        }
                    }
                }
                contentSection.html('<p style="text-align:center; padding:20px; color:red;">Error al cargar la página: ' + error + ' (Estado: ' + xhr.status + ')</p>');
                console.error("AJAX Error:", status, error, xhr.responseText);
            }
        });
    }

    $(document).on('click', '.sidebar .nav-link', function(e) {
        e.preventDefault();
        const $this = $(this);
        let pageUrlWithHash = $this.attr('href'); // ej: "#dashboard"
        const pageTitle = $this.find('span').text() || $this.text() || "MiMarca";

        if (!pageUrlWithHash || pageUrlWithHash === '#') {
             // Si es solo # o vacío, no hacer nada, o cerrar menú móvil si está abierto
            if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
                body.removeClass('sidebar-mobile-shown');
                sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim()} - 10px)`);
                sidebarOverlay.fadeOut(200);
            }
            return;
        }
        
        let pageUrl = pageUrlWithHash.substring(1); // pageUrl es "dashboard"

        // Evitar recargar si el hash actual ya es el de la página solicitada
        if (window.location.hash === pageUrlWithHash) {
            // Opcional: cerrar el menú móvil si está abierto
            if (window.innerWidth < 768 && body.hasClass('sidebar-mobile-shown')) {
                body.removeClass('sidebar-mobile-shown');
                sidebar.removeClass('sidebar-mobile-visible').css('left', `calc(-1 * ${getComputedStyle(document.documentElement).getPropertyValue('--mobile-sidebar-visible-width').trim()} - 10px)`);
                sidebarOverlay.fadeOut(200);
            }
            return;
        }

        $('.sidebar .nav-link.active').removeClass('active');
        $this.addClass('active');

        loadPage(pageUrl, pageTitle); // pageUrl es "dashboard"
    });

    function loadInitialOrHashedContent() {
        let pageToLoad = 'dashboard'; 
        let pageTitle = 'Dashboard';
        let activeLinkSelector = '.sidebar .nav-link[href="#dashboard"]';

        if (window.location.hash && window.location.hash.length > 1) {
            const potentialPage = window.location.hash.substring(1); // ej: "dashboard"
            const $link = $('.sidebar .nav-link[href="#' + potentialPage + '"]');
            if ($link.length) {
                pageToLoad = potentialPage;
                pageTitle = $link.find('span').text() || $link.text();
                activeLinkSelector = $link; 
            } else {
                 console.warn("Página en hash '#" + potentialPage + "' no encontrada en el sidebar. Cargando dashboard por defecto.");
            }
        }
        // No más lógica de pathname para URLs limpias aquí

        const $initialLink = (typeof activeLinkSelector === 'string') ? $(activeLinkSelector) : activeLinkSelector;

        if ($initialLink.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $initialLink.addClass('active');
            loadPage(pageToLoad, pageTitle, false); // No hacer pushState en la carga inicial
        } else {
            contentSection.html('<p>Bienvenido. Selecciona una opción del menú o el dashboard no pudo ser cargado.</p>');
            console.warn("No se encontró el link inicial/dashboard para cargar contenido. Selector intentado:", activeLinkSelector);
        }
    }

    loadInitialOrHashedContent();

    $(window).on('popstate', function(event) {
        let pageUrlToLoad = 'dashboard'; // Default
        let pageTitle = "MiMarca";

        if (event.originalEvent.state && event.originalEvent.state.path) {
            pageUrlToLoad = event.originalEvent.state.path; // ej: "dashboard"
        } else if (window.location.hash && window.location.hash.length > 1) {
            pageUrlToLoad = window.location.hash.substring(1); // ej: "dashboard"
        }
        // No más lógica de pathname para URLs limpias aquí

        const $linkToActivate = $('.sidebar .nav-link[href="#' + pageUrlToLoad + '"]');
        if ($linkToActivate.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $linkToActivate.addClass('active');
            pageTitle = $linkToActivate.find('span').text() || $linkToActivate.text();
            loadPage(pageUrlToLoad, pageTitle, false); // No hacer pushState en popstate
        } else {
            const $dashboardLink = $('.sidebar .nav-link[href="#dashboard"]');
            if($dashboardLink.length){
                 $('.sidebar .nav-link.active').removeClass('active');
                $dashboardLink.addClass('active');
                loadPage('dashboard', 'Dashboard', false); 
            } else {
                console.warn("Popstate: No se encontró el link para '#" + pageUrlToLoad + "', ni el link de dashboard.");
                 contentSection.html('<p>Contenido no encontrado.</p>');
            }
        }
    });

    // Eliminado el segundo listener de popstate redundante que estaba aquí.

    console.log("Script de AJAX (ajax.js) completamente cargado y listeners adjuntados.");
});