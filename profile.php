<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

// Récupération de l'utilisateur connecté via session
$username_session = $_SESSION['username'] ?? $_SESSION['user'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Le nouveau mot de passe et la confirmation ne correspondent pas.";
    } elseif (strlen($new_password) < 4) {
        $error = "Le nouveau mot de passe doit contenir au moins 4 caractères.";
    } else {
        // Recherche de l'utilisateur en BDD
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR id = ?");
        $stmt->execute([$username_session, $_SESSION['user_id'] ?? 1]);
        $user = $stmt->fetch();

        if ($user) {
            $pass_db = $user['password'] ?? '';
            $mot_de_passe_db = $user['mot_de_passe'] ?? '';

            // Comparaison souple (Hash OU texte brut)
            $password_correct = false;

            if (!empty($pass_db) && password_verify($old_password, $pass_db)) {
                $password_correct = true;
            } elseif ($old_password === $pass_db || $old_password === $mot_de_passe_db) {
                $password_correct = true;
            }

            if ($password_correct) {
                // Génération du nouveau hash sécurisé
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt_up = $pdo->prepare("UPDATE users SET password = ?, mot_de_passe = ? WHERE id = ?");
                if ($stmt_up->execute([$new_hash, $new_password, $user['id']])) {
                    $success = "Succès ! Votre mot de passe a été modifié.";
                } else {
                    $error = "Erreur lors de la mise à jour en BDD.";
                }
            } else {
                $error = "L'ancien mot de passe saisi est incorrect.";
            }
        } else {
            $error = "Utilisateur introuvable.";
        }
    }
}

// Données utilisateur pour affichage
$stmt_info = $pdo->prepare("SELECT username, role FROM users WHERE username = ? OR id = ?");
$stmt_info->execute([$username_session, $_SESSION['user_id'] ?? 1]);
$current_user_info = $stmt_info->fetch();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>👤 Mon Profil & Sécurité</h2>
</div>

<?php if ($success): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fadbd8; color: #e74c3c; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <div style="margin-bottom: 20px; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h3 style="margin: 0; color: #2c3e50;"><?= htmlspecialchars($current_user_info['username'] ?? 'admin') ?></h3>
        <span style="background: #34495e; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; text-transform: uppercase;">
            Rôle : <?= htmlspecialchars($current_user_info['role'] ?? 'admin') ?>
        </span>
    </div>

    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Ancien Mot de Passe</label>
            <input type="password" name="old_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Nouveau Mot de Passe</label>
            <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Confirmer le Nouveau Mot de Passe</label>
            <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <button type="submit" class="btn" style="width: 100%; padding: 12px; background: #27ae60;">Mettre à jour le Mot de Passe</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>