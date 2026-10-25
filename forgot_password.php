<?php
session_start();
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // 1. Chercher l'utilisateur avec cet e-mail
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Générer le code OTP à 6 chiffres
            $otp = random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // 3. Enregistrer en BDD
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$otp, $expires, $user['id']]);

            // 4. Passer les informations en session pour la simulation d'affichage
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['demo_otp'] = $otp;

            header("Location: verify_code.php");
            exit();
        } else {
            $error = "Aucun compte associé à cet e-mail.";
        }
    } else {
        $error = "Veuillez entrer votre adresse e-mail.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié | PEST'OS</title>
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #2c3e50; font-family: Arial, sans-serif; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 380px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .card h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; color: #7f8c8d; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 1rem; }
        .btn { width: 100%; padding: 12px; background: #27ae60; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .alert-danger { background: #fadbd8; color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Mot de passe oublié</h2>
        <?php if (!empty($error)): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Adresse E-mail</label>
                <input type="email" name="email" required placeholder="ex: Votre.mail@gmail.com">
            </div>
            <button type="submit" class="btn">Générer le code OTP</button>
            <p style="text-align: center; margin-top: 15px;">
                <a href="index.php" style="color: #7f8c8d; text-decoration: none; font-size: 0.85rem;">← Retour à la connexion</a>
            </p>
        </form>
    </div>
</body>
</html>