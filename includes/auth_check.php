<?php
// 1. Configurer la durée de session STRICTEMENT AVANT session_start()
ini_set('session.gc_maxlifetime', 2400);

// 2. Démarrer la session unique
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /agro_ERP/login_page.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';

// 4. Vérification de l'utilisateur en BDD
$stmt = $pdo->prepare("SELECT username, role, is_active FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['is_active'] == 0) {
    session_unset();
    session_destroy();
    header("Location: /agro_ERP/login_page.php?error=disabled");
    exit();
}

// 5. Timeout d'inactivité (40 minutes)
$timeout_duration = 2400;
if (isset($_SESSION['last_action'])) {
    if ((time() - $_SESSION['last_action']) >= $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: /agro_ERP/login_page.php?error=timeout");
        exit();
    }
}
$_SESSION['last_action'] = time();

// 6. Suivi IP & Monitoring des sessions actives
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ip_array[0]);
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

try {
    $stmt_active = $pdo->prepare("INSERT INTO active_users (username, ip_address, last_activity) 
                                   VALUES (:username, :ip, NOW()) 
                                   ON DUPLICATE KEY UPDATE ip_address = :ip_update, last_activity = NOW()");
    $stmt_active->execute([
        'username'  => $user['username'],
        'ip'        => $ip,
        'ip_update' => $ip
    ]);

    $pdo->query("DELETE FROM active_users WHERE last_activity < NOW() - INTERVAL 40 MINUTE");
} catch (Exception $e) {
    // Ignore silencieusement en production
}
?>