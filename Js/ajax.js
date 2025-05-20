// Js/ajax.js
$(document).ready(function() {
    console.log("ajax.js cargado y listo.");

    const contentSection = $('.content');
    const body = $('body'); // Necesario para cerrar el menú móvil
    const sidebar = $('#sidebar'); // Necesario para cerrar el menú móvil
    const sidebarOverlay = $('#sidebarOverlay'); // Necesario para cerrar el menú móvil

    // Función para cargar contenido
    function loadPage(pageUrl, pageTitle, pushState = true) {
        // si viene "/produccion" añade ".php"
        let requestUrl = pageUrl.endsWith('.php')
            ? pageUrl
            : pageUrl + '.php';

        $.ajax({
            url: requestUrl,
            type: 'GET',
            dataType: 'html', // Esperamos HTML por defecto
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
                    history.pushState({ path: pageUrl }, pageTitle, pageUrl);
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
        const pageUrl = $this.attr('href');
        const pageTitle = $this.find('span').text() || $this.text() || "MiMarca";

        if (!pageUrl || pageUrl === '#' || (window.location.pathname + window.location.search + window.location.hash === pageUrl)) {
            if(pageUrl !== '#') return;
        }
        
        if (pageUrl.startsWith('#') && pageUrl.length > 1 && (window.location.pathname + window.location.search) === (window.location.pathname + window.location.search)) {
            // return; // Opcional: si no quieres que los hashes internos recarguen la página
        }

        $('.sidebar .nav-link.active').removeClass('active');
        $this.addClass('active');

        loadPage(pageUrl, pageTitle);
    });

    function loadInitialOrHashedContent() {
        let pageToLoad = 'dashboard'; // Usar 'dashboard' para que loadPage añada '.php'
        let pageTitle = 'Dashboard';
        let activeLinkSelector = '.sidebar .nav-link[href="dashboard"]'; // MODIFICADO AQUÍ

        let potentialPage = '';
        if (window.location.hash && window.location.hash.length > 1) {
            potentialPage = window.location.hash.substring(1);
        } else {
            const currentPath = window.location.pathname.split("/").pop();
            if (currentPath && currentPath !== 'index.php' && currentPath !== '' && !currentPath.includes("/") && currentPath !== 'Envios') { // Asumiendo que 'Envios' es parte de la ruta base
                potentialPage = currentPath;
            }
        }

        if (potentialPage) {
            // Intenta encontrar un enlace que coincida con el potentialPage (que ya no debería tener .php si viene de un hash como #dashboard)
            const $link = $('.sidebar .nav-link[href="' + potentialPage + '"]');
            if ($link.length) {
                pageToLoad = potentialPage; // pageToLoad será 'dashboard' o 'produccion', etc.
                pageTitle = $link.find('span').text() || $link.text();
                activeLinkSelector = $link; // $link es el objeto jQuery, no un string selector
            } else {
                 console.warn("Página en hash/path '" + potentialPage + "' no encontrada en el sidebar. Cargando dashboard por defecto.");
                 // Si no se encuentra, pageToLoad y activeLinkSelector se quedan con los valores por defecto para 'dashboard'
            }
        }
        
        const $initialLink = (typeof activeLinkSelector === 'string') ? $(activeLinkSelector) : activeLinkSelector;

        if ($initialLink.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $initialLink.addClass('active');
            
            // Determinar si se debe hacer pushState
            // pageToLoad aquí será el nombre base (ej. 'dashboard')
            // window.location.hash.substring(1) también será el nombre base (ej. 'dashboard')
            const currentHashPage = window.location.hash ? window.location.hash.substring(1) : '';
            const currentPathPage = window.location.pathname.split("/").pop();
            
            // Solo hacer pushState si la URL actual (sin #) no es la página a cargar o el hash no coincide
            let shouldPushState = true;
            if (currentPathPage === pageToLoad || (currentPathPage === 'index.php' && pageToLoad === 'dashboard')) {
                 if (!window.location.hash || currentHashPage === pageToLoad) {
                    shouldPushState = false;
                 }
            }
            if (currentHashPage === pageToLoad) {
                shouldPushState = false;
            }


            loadPage(pageToLoad, pageTitle, shouldPushState);
        } else {
            contentSection.html('<p>Bienvenido. Selecciona una opción del menú o el dashboard no pudo ser cargado.</p>');
            console.warn("No se encontró el link inicial/dashboard para cargar contenido. Selector intentado:", activeLinkSelector);
        }
    }

    loadInitialOrHashedContent();

    // Solo un manejador popstate es necesario. Este parece el más completo.
    $(window).on('popstate', function(event) {
        let pageUrlToLoad;
        let pageTitle = "MiMarca";

        if (event.originalEvent.state && event.originalEvent.state.path) {
            pageUrlToLoad = event.originalEvent.state.path; // ej: "dashboard"
        } else {
            if (window.location.hash && window.location.hash.length > 1) {
                 pageUrlToLoad = window.location.hash.substring(1); // ej: "dashboard"
            } else {
                 pageUrlToLoad = window.location.pathname.split("/").pop() || 'dashboard';
                 if (pageUrlToLoad === 'index.php' || pageUrlToLoad === '' || pageUrlToLoad === 'Envios') pageUrlToLoad = 'dashboard';
            }
        }

        const $linkToActivate = $('.sidebar .nav-link[href="' + pageUrlToLoad + '"]');
        if ($linkToActivate.length) {
            $('.sidebar .nav-link.active').removeClass('active');
            $linkToActivate.addClass('active');
            pageTitle = $linkToActivate.find('span').text() || $linkToActivate.text();
            loadPage(pageUrlToLoad, pageTitle, false); 
        } else {
            // Fallback al dashboard si el enlace no se encuentra
            const $dashboardLink = $('.sidebar .nav-link[href="dashboard"]'); // MODIFICADO AQUÍ
            if($dashboardLink.length){
                 $('.sidebar .nav-link.active').removeClass('active');
                $dashboardLink.addClass('active');
                loadPage('dashboard', 'Dashboard', false); // Usar 'dashboard' para que loadPage añada '.php'
            } else {
                console.warn("Popstate: No se encontró el link para:", pageUrlToLoad, "ni el link de dashboard.");
                 contentSection.html('<p>Contenido no encontrado.</p>');
            }
        }
    });

    // Eliminado el segundo listener de popstate redundante que estaba aquí.

    console.log("Script de AJAX (ajax.js) completamente cargado y listeners adjuntados.");
});