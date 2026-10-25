<?php
session_start();

// Si l'utilisateur est déjà connecté, on le dirige vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header("Location: modules/dashboard/index.php");
    exit();
} 

// Sinon, on le redirige vers la page de connexion à la racine
header("Location: login_page.php");
exit();
?>