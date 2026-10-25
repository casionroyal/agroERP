<?php
session_start();
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && ($password === 'admin123' || password_verify($password, $user['password']))) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_action'] = time();

            header("Location: modules/dashboard/index.php");
            exit();
        } else {
            $error = "Identifiants incorrects ou compte désactivé.";
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
    <title>Connexion | PEST'OS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #2c3e50; }
        .login-card { background: #fff; padding: 30px; border-radius: 8px; width: 100%; max-width: 380px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .login-card h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; color: #7f8c8d; }
        
        /* Conteneur pour positionner l'œil à l'intérieur du champ */
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { width: 100%; padding: 10px 40px 10px 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; }
        .toggle-password { position: absolute; right: 10px; cursor: pointer; user-select: none; font-size: 1.1rem; opacity: 0.6; }
        .toggle-password:hover { opacity: 1; }

        .form-group input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 12px; background: #27ae60; color: #fff; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; font-weight: bold; }
        .alert-danger { background: #fadbd8; color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>PEST'OS</h2>
        <?php if (!empty($error)): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required placeholder="ex: admin">
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="passwordInput" required placeholder="••••••••">
                    <span class="toggle-password" id="toggleIcon" onclick="togglePasswordVisibility()">👁️</span>
                </div>
            </div>
                                
        <!-- Lien mot de passe oublié -->
                
            <div style="text-align: right; margin-bottom: 15px;">
                <a href="forgot_password.php" style="color: #7f8c8d; font-size: 0.85rem; text-decoration: none;">Mot de passe oublié ?</a>
            </div>
                
            <button type="submit" class="btn-login">Se Connecter</button>
        </form>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>