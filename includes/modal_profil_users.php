<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id_session = $_SESSION['user_id'] ?? null;
$role_session = strtolower($_SESSION['role'] ?? '');

$msg_pass = '';
$msg_user = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_modal'])) {
    
    // ACTION 1 : Changement de son propre mot de passe
    if ($_POST['action_modal'] === 'change_my_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!empty($old_pass) && !empty($new_pass) && $new_pass === $confirm_pass) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id_session]);
            $current_hash = $stmt->fetchColumn();

            if ($current_hash && password_verify($old_pass, $current_hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt_up = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_up->execute([$new_hash, $user_id_session]);
                $msg_pass = "<div style='color:green; margin-bottom:10px;'>✅ Mot de passe mis à jour avec succès !</div>";
            } else {
                $msg_pass = "<div style='color:red; margin-bottom:10px;'>❌ Ancien mot de passe incorrect.</div>";
            }
        } else {
            $msg_pass = "<div style='color:red; margin-bottom:10px;'>❌ Les nouveaux mots de passe ne correspondent pas.</div>";
        }
    }

    // ACTION 2 : Création d'un utilisateur (Réservé Admin)
    if ($_POST['action_modal'] === 'create_user' && $role_session === 'admin') {
        $username = trim($_POST['new_username'] ?? '');
        $email    = trim($_POST['new_user_email'] ?? ''); // Récupération de l'e-mail
        $password = $_POST['new_user_password'] ?? '';
        
        // On s'assure que le rôle est soit 'admin' soit 'operateur'
        $raw_role = strtolower(trim($_POST['new_role'] ?? 'operateur'));
        $role = ($raw_role === 'admin') ? 'admin' : 'operateur';

        if (!empty($username) && !empty($email) && !empty($password)) {
            try {
                // Vérification si l'identifiant ou l'e-mail existe déjà
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                
                if ($check->fetchColumn() > 0) {
                    $msg_user = "<div style='color:red; margin-bottom:10px;'>❌ Le nom d'utilisateur ou l'adresse e-mail existe déjà.</div>";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertion en BDD avec la colonne email
                    $stmt_add = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt_add->execute([$username, $email, $hash, $role]);
                    
                    $msg_user = "<div style='color:green; margin-bottom:10px;'>✅ Utilisateur créé avec succès !</div>";
                }
            } catch (PDOException $e) {
                $msg_user = "<div style='color:red; margin-bottom:10px;'>❌ Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $msg_user = "<div style='color:red; margin-bottom:10px;'>❌ Veuillez remplir tous les champs (Nom, E-mail et Mot de passe).</div>";
        }
    }

    // ACTION 3 : Réinitialisation du mot de passe
    if ($_POST['action_modal'] === 'reset_user_pass' && $role_session === 'admin') {
        $target_id = intval($_POST['target_user_id'] ?? 0);
        $target_pass = $_POST['target_password'] ?? '';

        if ($target_id > 0 && !empty($target_pass)) {
            $hash = password_hash($target_pass, PASSWORD_DEFAULT);
            $stmt_reset = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_reset->execute([$hash, $target_id]);
            $msg_user = "<div style='color:green; margin-bottom:10px;'>✅ Mot de passe modifié pour l'utilisateur ID #$target_id.</div>";
        }
    }

    // ACTION 4 : Suppression d'un utilisateur (Réservé Admin)
    if ($_POST['action_modal'] === 'delete_user' && $role_session === 'admin') {
        $target_id = intval($_POST['target_user_id'] ?? 0);

        // Sécurité : empêcher de supprimer son propre compte connecté
        if ($target_id === intval($user_id_session)) {
            $msg_user = "<div style='color:red; margin-bottom:10px;'>❌ Vous ne pouvez pas supprimer votre propre compte actuellement connecté.</div>";
        } elseif ($target_id > 0) {
            try {
                // 1. Récupération du nom de l'utilisateur avant suppression
                $stmt_get = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt_get->execute([$target_id]);
                $target_name = $stmt_get->fetchColumn();

                if ($target_name) {
                    // 2. Suppression de l'utilisateur
                    $stmt_del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt_del->execute([$target_id]);

                    $msg_user = "<div style='color:green; margin-bottom:10px;'>🗑️ Utilisateur <strong>" . htmlspecialchars($target_name) . "</strong> supprimé avec succès.</div>";
                } else {
                    $msg_user = "<div style='color:red; margin-bottom:10px;'>❌ Utilisateur introuvable.</div>";
                }
            } catch (PDOException $e) {
                $msg_user = "<div style='color:red; margin-bottom:10px;'>❌ Erreur lors de la suppression : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// Récupération de la liste des utilisateurs si Admin
$all_users = [];
if ($role_session === 'admin') {
    try {
        // Sélection de la colonne email
        $stmt_users = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
        $all_users = $stmt_users->fetchAll();
    } catch (Exception $e) {}
}
?>

<!-- MODAL HTML -->
<div id="userModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; width:90%; max-width:800px; padding:25px; box-shadow:0 4px 15px rgba(0,0,0,0.2); position:relative; max-height:85vh; overflow-y:auto;">
        
        <button onclick="closeUserModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:20px; cursor:pointer;">✖</button>
        
        <h3>⚙️ Profil & Gestion des Accès</h3>

        <!-- Onglets -->
        <div style="display:flex; gap:10px; border-bottom:2px solid #ddd; margin-bottom:20px;">
            <button onclick="switchTab('tab_pass')" id="btn_tab_pass" style="padding:10px 15px; border:none; background:none; border-bottom:3px solid #2980b9; font-weight:bold; cursor:pointer;">🔑 Mon Mot de Passe</button>
            <?php if ($role_session === 'admin'): ?>
                <button onclick="switchTab('tab_users')" id="btn_tab_users" style="padding:10px 15px; border:none; background:none; border-bottom:3px solid transparent; font-weight:bold; cursor:pointer;">👥 Gestion Utilisateurs (Admin)</button>
            <?php endif; ?>
        </div>

        <!-- ONGLET 1 : Changement de son propre mot de passe -->
        <div id="tab_pass">
            <?= $msg_pass ?>
            <form method="POST">
                <input type="hidden" name="action_modal" value="change_my_password">
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-weight:bold;">Ancien mot de passe</label>
                    <input type="password" name="old_password" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-weight:bold;">Nouveau mot de passe</label>
                    <input type="password" name="new_password" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold;">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <button type="submit" class="btn" style="background:#2980b9; color:#fff; border:none; padding:10px 15px; border-radius:4px; cursor:pointer;">Mettre à jour mon mot de passe</button>
            </form>
        </div>

        <!-- ONGLET 2 : Admin - Gestion des Utilisateurs -->
        <?php if ($role_session === 'admin'): ?>
        <div id="tab_users" style="display:none;">
            <?= $msg_user ?>
            
            <h4 style="margin-top:0;">➕ Créer un nouvel utilisateur</h4>
            <form method="POST" style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px;">
                <input type="hidden" name="action_modal" value="create_user">
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
                    <input type="text" name="new_username" placeholder="Nom d'utilisateur" required style="flex:1; min-width:140px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    
                    <!-- CHAMP E-MAIL AJOUTÉ -->
                    <input type="email" name="new_user_email" placeholder="Adresse E-mail" required style="flex:1; min-width:180px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    
                    <input type="password" name="new_user_password" placeholder="Mot de passe" required style="flex:1; min-width:140px; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    
                    <select name="new_role" style="padding:8px; border:1px solid #ccc; border-radius:4px;">
                        <option value="operateur">Utilisateur / Opérateur</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <button type="submit" class="btn" style="background:#27ae60; color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">+ Ajouter l'utilisateur</button>
            </form>

            <h4>📋 Liste des Comptes</h4>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#eee; text-align:left;">
                        <th style="padding:8px;">ID</th>
                        <th style="padding:8px;">Nom</th>
                        <th style="padding:8px;">E-mail</th>
                        <th style="padding:8px;">Rôle</th>
                        <th style="padding:8px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_users)): ?>
                        <?php foreach ($all_users as $u): ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td style="padding:8px;"><?= $u['id'] ?></td>
                                <td style="padding:8px;"><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                <td style="padding:8px; font-size:13px; color:#555;"><?= htmlspecialchars($u['email'] ?? 'Non renseigné') ?></td>
                                <td style="padding:8px;"><span class="badge badge-success"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td style="padding:8px; text-align:right;">
                                    <div style="display:inline-flex; gap:5px; align-items:center;">
                                        <!-- Formulaire Réinitialisation Pass -->
                                        <form method="POST" style="display:flex; gap:5px; margin:0;">
                                            <input type="hidden" name="action_modal" value="reset_user_pass">
                                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                            <input type="password" name="target_password" placeholder="Nouveau Pass" required style="width:100px; padding:4px; font-size:12px; border:1px solid #ccc; border-radius:3px;">
                                            <button type="submit" style="background:#e67e22; color:#fff; border:none; padding:5px 10px; border-radius:3px; cursor:pointer; font-size:12px; font-weight:bold;">Modifier</button>
                                        </form>

                                        <!-- Formulaire Suppression -->
                                        <?php if ($u['id'] != $user_id_session): ?>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <input type="hidden" name="action_modal" value="delete_user">
                                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" style="background:#e74c3c; color:#fff; border:none; padding:5px 10px; border-radius:3px; cursor:pointer; font-size:12px; font-weight:bold;">🗑️ Supprimer</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="padding:8px; text-align:center; color:#7f8c8d;">Aucun utilisateur trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function openUserModal() {
    document.getElementById('userModal').style.display = 'flex';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

function switchTab(tabId) {
    document.getElementById('tab_pass').style.display = 'none';
    var tabUsers = document.getElementById('tab_users');
    if (tabUsers) tabUsers.style.display = 'none';

    document.getElementById(tabId).style.display = 'block';

    document.getElementById('btn_tab_pass').style.borderBottomColor = 'transparent';
    var btnUsers = document.getElementById('btn_tab_users');
    if (btnUsers) btnUsers.style.borderBottomColor = 'transparent';

    if (tabId === 'tab_pass') {
        document.getElementById('btn_tab_pass').style.borderBottomColor = '#2980b9';
    } else if (btnUsers) {
        btnUsers.style.borderBottomColor = '#2980b9';
    }
}

<?php if (!empty($msg_pass) || !empty($msg_user)): ?>
    openUserModal();
    <?php if (!empty($msg_user)): ?>
        switchTab('tab_users');
    <?php endif; ?>
<?php endif; ?>
</script>