<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';
$demo_otp = $_SESSION['demo_otp'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $new_password = trim($_POST['new_password']);

    if (!empty($code) && !empty($new_password)) {
        // Vérification en BDD du code OTP et de la date d'expiration
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$_SESSION['reset_user_id'], $code]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Mise à jour du mot de passe et nettoyage des tokens
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update->execute([$hashed, $user['id']]);

            unset($_SESSION['reset_user_id'], $_SESSION['demo_otp']);

            $success = "Mot de passe réinitialisé avec succès ! Redirection...";
            header("refresh:2;url=index.php");
        } else {
            $error = "Code invalide ou expiré.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie du Code OTP | PEST'OS</title>
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #2c3e50; font-family: Arial, sans-serif; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 380px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .card h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; color: #7f8c8d; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 1rem; }
        .btn { width: 100%; padding: 12px; background: #27ae60; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .alert-info { background: #e8f4f8; color: #2980b9; padding: 12px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; border: 1px solid #3498db; }
        .alert-danger { background: #fadbd8; color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Validation du code</h2>

        <?php if ($demo_otp): ?>
            <div class="alert-info">
                <b>Code OTP généré :</b> <span style="font-size:1.2rem; font-weight:bold; letter-spacing:2px;"><?= htmlspecialchars($demo_otp) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Code de sécurité (6 chiffres)</label>
                <input type="text" name="code" required placeholder="ex: 123456" value="<?= htmlspecialchars($demo_otp ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Réinitialiser le mot de passe</button>
        </form>
    </div>
</body>
</html>