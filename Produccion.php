<?php
session_start();
if (!isset($_SESSION['user_id']) /* || check specific roles if needed */) {
    // Optional: Send an error message or redirect
    // For AJAX, it's better to send an HTML error message
    echo '<p style="color:red; text-align:center;">Acceso no autorizado.</p>';
    exit;
}
// Rest of your page content for this specific PHP file
?>
<h1>Contenido de la Página X</h1>
<p>...</p>