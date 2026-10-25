<?php
// Récupère le nom du fichier actuel (ex: index.php) et du dossier parent (ex: dashboard)
$script_name = $_SERVER['SCRIPT_NAME'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PESTOS | Gestion Production & Stock</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Barre de Navigation Mobile / Desktop -->
        <aside class="sidebar">
            
            <!-- Bloc Logo + Titre centré et empilé -->
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; margin-bottom: 20px;">
                <img src="/assets/images/logo_pestos.svg" alt="Logo PEST'OS" style="height: 70px; width: auto; object-fit: contain; margin-bottom: 8px;">
                <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: #ffffff;">PEST'OS</h2>
            </div>
                
            <nav class="nav-menu">
                <a href="/modules/dashboard/index.php" class="nav-link">📊 Dashboard</a>
                <a href="/modules/stocks/index.php" class="nav-link">📦 Stocks</a>
                <a href="/modules/production/index.php" class="nav-link">⚙️ Production</a>
                <a href="/modules/ventes/index.php" class="nav-link">🛒 Ventes</a>
                <a href="/modules/paiements/index.php" class="nav-link">💰 Créances</a>
                <a href="/logout.php" class="nav-link" style="color: #e74c3c;">🚪 Déconnexion</a>
            </nav>
        </aside>

        <!-- Début du contenu principal -->
        <main class="main-content">