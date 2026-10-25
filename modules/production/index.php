<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$message = '';
$error = '';

// Traitement du lancement d'une production
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_lancer_production'])) {
    $produit_fini_id = intval($_POST['produit_fini_id']);
    $quantite_produite = floatval($_POST['quantite_produite']);

    if ($produit_fini_id > 0 && $quantite_produite > 0) {
        try {
            // 1. Récupérer la recette du produit fini
            $stmt = $pdo->prepare("SELECT composant_id, quantite_necessaire FROM recettes WHERE produit_fini_id = ?");
            $stmt->execute([$produit_fini_id]);
            $recette = $stmt->fetchAll();

            if (empty($recette)) {
                $error = "Impossible de lancer la production : Aucune recette n'est définie pour ce produit fini !";
            } else {
                // 2. Vérifier que le stock de chaque composant est suffisant
                $stock_ok = true;
                $composant_manquant = "";

                foreach ($recette as $item) {
                    $qte_totale_requise = $item['quantite_necessaire'] * $quantite_produite;
                    
                    $stmt_st = $pdo->prepare("SELECT designation, stock_actuel FROM articles WHERE id = ?");
                    $stmt_st->execute([$item['composant_id']]);
                    $art = $stmt_st->fetch();

                    if ($art['stock_actuel'] < $qte_totale_requise) {
                        $stock_ok = false;
                        $composant_manquant = $art['designation'] . " (Stock dispo: " . $art['stock_actuel'] . " / Requis: " . $qte_totale_requise . ")";
                        break;
                    }
                }

                if (!$stock_ok) {
                    $error = "Stock insuffisant pour le composant : " . $composant_manquant;
                } else {
                    // 3. Exécuter la production dans une transaction SQL
                    $pdo->beginTransaction();

                    $cout_total = 0;

                    // Déduire les composants du stock et calculer le coût total de production
                    foreach ($recette as $item) {
                        $qte_totale_requise = $item['quantite_necessaire'] * $quantite_produite;
                        
                        // Obtenir le prix d'achat du composant pour le coût de revient
                        $stmt_price = $pdo->prepare("SELECT prix_achat FROM articles WHERE id = ?");
                        $stmt_price->execute([$item['composant_id']]);
                        $prix_unitaire = $stmt_price->fetchColumn();

                        $cout_total += ($qte_totale_requise * $prix_unitaire);

                        // Déstocker le composant
                        $stmt_deduct = $pdo->prepare("UPDATE articles SET stock_actuel = stock_actuel - ? WHERE id = ?");
                        $stmt_deduct->execute([$qte_totale_requise, $item['composant_id']]);
                    }

                    // Augmenter le stock du produit fini
                    $stmt_add_pf = $pdo->prepare("UPDATE articles SET stock_actuel = stock_actuel + ? WHERE id = ?");
                    $stmt_add_pf->execute([$quantite_produite, $produit_fini_id]);

                    // Enregistrer l'ordre de fabrication
                    $stmt_of = $pdo->prepare("INSERT INTO ordres_fabrication (produit_fini_id, quantite_produite, cout_total, date_production) VALUES (?, ?, ?, NOW())");
                    $stmt_of->execute([$produit_fini_id, $quantite_produite, $cout_total]);

                    $pdo->commit();
                    $message = "Production lancée avec succès ! Le stock des matières premières a été déduit et le produit fini a été crédité.";
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Erreur lors du traitement de la production : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez sélectionner un produit et entrer une quantité valide.";
    }
}

// Récupération des Produits Finis ayant au moins 1 composant dans leur recette
$sql_pf_recettes = "SELECT DISTINCT pf.id, pf.designation, pf.code, pf.unite 
                    FROM articles pf 
                    JOIN recettes r ON pf.id = r.produit_fini_id 
                    WHERE pf.type = 'produit_fini' 
                    ORDER BY pf.designation ASC";
$produits_disponibles = $pdo->query($sql_pf_recettes)->fetchAll();

// Historique des productions (Jointure SQL nettoyée)
$sql_historique = "SELECT of.id, pf.designation as produit, of.quantite_produite, pf.unite, of.cout_total, of.date_production 
                   FROM ordres_fabrication of 
                   JOIN articles pf ON of.produit_fini_id = pf.id 
                   ORDER BY of.date_production DESC";
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>⚙️ Ordres de Fabrication (Production)</h2>
    <a href="recettes.php" class="btn" style="background: #e67e22;">📖 Gérer les Fiches Techniques</a>
</div>

<?php if ($message): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Formulaire Lancement de Production -->
<div class="card" style="margin-bottom: 25px;">
    <h3>🚀 Lancer un Lot de Production</h3>
    <form method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; margin-top: 15px;">
        <input type="hidden" name="action_lancer_production" value="1">
        
        <div style="flex: 2; min-width: 250px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Produit Fini à fabriquer</label>
            <select name="produit_fini_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">-- Sélectionner Produit Fini --</option>
                <?php foreach ($produits_disponibles as $pf): ?>
                    <option value="<?= $pf['id'] ?>"><?= htmlspecialchars($pf['designation']) ?> (<?= htmlspecialchars($pf['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 150px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Quantité à Produire</label>
            <input type="number" step="0.01" name="quantite_produite" placeholder="ex: 100" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <button type="submit" class="btn" style="padding: 9px 20px; background: #27ae60;">Valider la Production</button>
    </form>
</div>

<!-- Historique des Fabrications -->
<div class="table-responsive">
    <h3>📋 Historique des Ordres de Fabrication</h3>
    <table style="margin-top: 10px;">
        <thead>
            <tr>
                <th>N° OF</th>
                <th>Produit Fini</th>
                <th>Quantité Produite</th>
                <th>Coût Total Production</th>
                <th>Coût Unitaire Reviens</th>
                <th>Date / Heure</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($historique)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #7f8c8d;">Aucune fabrication enregistrée pour le moment.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($historique as $of): ?>
                    <tr>
                        <td><strong>OF-<?= str_pad($of['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= htmlspecialchars($of['produit']) ?></td>
                        <td><strong><?= floatval($of['quantite_produite']) ?></strong> <?= htmlspecialchars($of['unite']) ?></td>
                        <td><?= number_format($of['cout_total'], 2) ?> DA</td>
                        <td><strong><?= number_format($of['cout_total'] / $of['quantite_produite'], 2) ?> DA</strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($of['date_production'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>