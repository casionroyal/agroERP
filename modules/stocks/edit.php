<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    echo "<div class='card'>Article introuvable.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Détection de la colonne pour le stock minimum (stock_min ou stock_minimum)
$stock_min_col = array_key_exists('stock_min', $article) ? 'stock_min' : 'stock_minimum';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation = trim($_POST['designation'] ?? '');
    $stock_actuel = floatval($_POST['stock_actuel'] ?? 0);
    $stock_min = floatval($_POST['stock_minimum'] ?? 0); // Récupère le champ du formulaire
    $prix_achat = floatval($_POST['prix_achat'] ?? 0);
    $prix_vente = floatval($_POST['prix_vente'] ?? 0);
    $unite = trim($_POST['unite'] ?? '');

    if (!empty($designation)) {
        try {
            // Mise à jour explicite selon le nom de colonne détecté dans la BD
            $sql = "UPDATE articles 
                    SET designation = :designation, 
                        stock_actuel = :stock_actuel, 
                        {$stock_min_col} = :stock_min, 
                        prix_achat = :prix_achat, 
                        prix_vente = :prix_vente, 
                        unite = :unite
                    WHERE id = :id";
            
            $stmt_up = $pdo->prepare($sql);
            $stmt_up->execute([
                'designation'  => $designation,
                'stock_actuel' => $stock_actuel,
                'stock_min'    => $stock_min,
                'prix_achat'   => $prix_achat,
                'prix_vente'   => $prix_vente,
                'unite'        => $unite,
                'id'           => $id
            ]);

            $success = "Article mis à jour avec succès !";
            // Recharger l'article
            $stmt->execute([$id]);
            $article = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    } else {
        $error = "La désignation ne peut pas être vide.";
    }
}
?> <!-- FERMETURE OBLIGATOIRE DE LA BALISE PHP ICI -->

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>✏️ Modifier l'Article : <?= htmlspecialchars($article['designation'] ?? '') ?></h2>
    <a href="index.php" class="btn" style="background: #7f8c8d;">← Annuler</a>
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

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Code Article</label>
            <input type="text" value="<?= htmlspecialchars($article['code'] ?? '') ?>" disabled style="width: 100%; padding: 8px; background: #eee; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Désignation *</label>
            <input type="text" name="designation" value="<?= htmlspecialchars($article['designation'] ?? '') ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Stock Actuel</label>
                <input type="number" step="0.01" name="stock_actuel" value="<?= floatval($article['stock_actuel'] ?? 0) ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Stock Minimum (Alerte)</label>
                <input type="number" step="0.01" name="stock_minimum" value="<?= floatval($article[$stock_min_col] ?? 0) ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Unité</label>
                <input type="text" name="unite" value="<?= htmlspecialchars($article['unite'] ?? 'Kg') ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Prix d'Achat (DA)</label>
                <input type="number" step="0.01" name="prix_achat" value="<?= floatval($article['prix_achat'] ?? 0) ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Prix de Vente (DA)</label>
                <input type="number" step="0.01" name="prix_vente" value="<?= floatval($article['prix_vente'] ?? 0) ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <button type="submit" class="btn" style="width: 100%; padding: 12px; background: #2980b9;">Enregistrer les Modifications</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>