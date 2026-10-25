<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $designation = trim($_POST['designation']);
    $type = $_POST['type'];
    $unite = trim($_POST['unite']);
    $stock_actuel = floatval($_POST['stock_actuel']);
    $stock_min = floatval($_POST['stock_min']); // Seuil d'alerte réglable
    $prix_achat = floatval($_POST['prix_achat']);
    $prix_vente = floatval($_POST['prix_vente']);

    if (!empty($code) && !empty($designation)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO articles (code, designation, type, unite, stock_actuel, stock_min, prix_achat, prix_vente) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $designation, $type, $unite, $stock_actuel, $stock_min, $prix_achat, $prix_vente]);
            
            header("Location: index.php?success=1");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur : Le code article existe déjà ou une donnée est invalide.";
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>➕ Ajouter un Nouvel Article</h2>
    <a href="index.php" class="btn" style="background: #7f8c8d;">← Retour au stock</a>
</div>

<?php if ($error): ?>
    <div style="background: #fadbd8; color: #e74c3c; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 650px; margin: 0 auto;">
    <form method="POST">
        <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Code Article *</label>
                <input type="text" name="code" required placeholder="ex: MP-003, EMB-02, PF-002" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Type d'article *</label>
                <select name="type" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="matiere_premiere">Matière Première</option>
                    <option value="emballage">Emballage</option>
                    <option value="produit_fini">Produit Fini</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Désignation *</label>
            <input type="text" name="designation" required placeholder="ex: Arôme Vanille 1L ou Bouteille verre 50cl" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Unité de mesure</label>
                <input type="text" name="unite" value="Kg" required placeholder="Kg, L, Pce..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Stock Initial</label>
                <input type="number" step="0.01" name="stock_actuel" value="0.00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold; color: #e67e22;">🔔 Seuil Alerte (Stock Min)</label>
                <input type="number" step="0.01" name="stock_min" value="10.00" style="width: 100%; padding: 8px; border: 1px solid #e67e22; border-radius: 4px; font-weight: bold;" title="Dès que le stock descend sous cette valeur, l'alerte rouge sera activée.">
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Prix d'Achat Unitaire (DA)</label>
                <input type="number" step="0.01" name="prix_achat" value="0.00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Prix de Vente HT (DA)</label>
                <input type="number" step="0.01" name="prix_vente" value="0.00" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>

        <button type="submit" class="btn" style="width: 100%; padding: 12px; font-size: 1rem;">Enregistrer l'Article</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>