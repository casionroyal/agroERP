<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$message = '';
$error = '';

// Traitement de l'ajout d'un composant dans une recette
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_composant'])) {
    $produit_fini_id = intval($_POST['produit_fini_id']);
    $composant_id = intval($_POST['composant_id']);
    $quantite = floatval($_POST['quantite_necessaire']);

    if ($produit_fini_id > 0 && $composant_id > 0 && $quantite > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO recettes (produit_fini_id, composant_id, quantite_necessaire) VALUES (?, ?, ?)");
            $stmt->execute([$produit_fini_id, $composant_id, $quantite]);
            $message = "Composant ajouté avec succès à la recette !";
        } catch (PDOException $e) {
            $error = "Erreur : Ce composant existe déjà dans la recette de ce produit.";
        }
    } else {
        $error = "Veuillez remplir correctement tous les champs.";
    }
}

// Récupération de la liste des Produits Finis
$produits_finis = $pdo->query("SELECT * FROM articles WHERE type = 'produit_fini' ORDER BY designation ASC")->fetchAll();

// Récupération des Matières Premières et Emballages
$composants = $pdo->query("SELECT * FROM articles WHERE type IN ('matiere_premiere', 'emballage') ORDER BY type ASC, designation ASC")->fetchAll();

// Récupération de toutes les recettes existantes
$sql_recettes = "SELECT r.id, pf.designation as produit, c.designation as composant, c.type as type_composant, r.quantite_necessaire, c.unite, c.prix_achat 
                 FROM recettes r
                 JOIN articles pf ON r.produit_fini_id = pf.id
                 JOIN articles c ON r.composant_id = c.id
                 ORDER BY pf.designation ASC";
$recettes_list = $pdo->query($sql_recettes)->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>📖 Fiches Techniques & Recettes (BOM)</h2>
    <a href="index.php" class="btn" style="background: #34495e;">⚙️ Lancer une Production</a>
</div>

<?php if ($message): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Formulaire d'ajout dans la recette -->
<div class="card" style="margin-bottom: 25px;">
    <h3>➕ Ajouter un Composant à un Produit Fini</h3>
    <form method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; margin-top: 15px;">
        <input type="hidden" name="action_add_composant" value="1">
        
        <div style="flex: 1; min-width: 200px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Produit Fini (Fabriqué)</label>
            <select name="produit_fini_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">-- Sélectionner Produit Fini --</option>
                <?php foreach ($produits_finis as $pf): ?>
                    <option value="<?= $pf['id'] ?>"><?= htmlspecialchars($pf['designation']) ?> (<?= htmlspecialchars($pf['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Composant (MP / Emballage)</label>
            <select name="composant_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">-- Sélectionner Composant --</option>
                <?php foreach ($composants as $comp): ?>
                    <option value="<?= $comp['id'] ?>">
                        [<?= $comp['type'] === 'matiere_premiere' ? 'MP' : 'EMB' ?>] <?= htmlspecialchars($comp['designation']) ?> (Prix: <?= number_format($comp['prix_achat'], 2) ?> DA)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 150px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Qté pour 1 Unité PF</label>
            <input type="number" step="0.0001" name="quantite_necessaire" placeholder="ex: 0.200" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <button type="submit" class="btn" style="padding: 9px 20px;">Lier au Produit</button>
    </form>
</div>

<!-- Liste des Recettes -->
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Produit Fini</th>
                <th>Composant Requis</th>
                <th>Type</th>
                <th>Quantité Nécessaire</th>
                <th>Coût Estimé / Unité PF</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recettes_list)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #7f8c8d;">Aucune recette enregistrée pour le moment.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recettes_list as $rec): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($rec['produit']) ?></strong></td>
                        <td><?= htmlspecialchars($rec['composant']) ?></td>
                        <td>
                            <span class="badge <?= $rec['type_composant'] === 'matiere_premiere' ? 'badge-success' : 'badge-danger' ?>">
                                <?= $rec['type_composant'] === 'matiere_premiere' ? 'Matière Première' : 'Emballage' ?>
                            </span>
                        </td>
                        <td><strong><?= floatval($rec['quantite_necessaire']) ?></strong> <?= htmlspecialchars($rec['unite']) ?></td>
                        <td><?= number_format($rec['quantite_necessaire'] * $rec['prix_achat'], 2) ?> DA</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>