<?php
session_start();

// 1. Vider toutes les variables de session
$_SESSION = array();

// 2. Détruire le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Détruire la session
session_destroy();

// 4. Rediriger vers la page de connexion
header("Location: login_page.php");
exit();
?>