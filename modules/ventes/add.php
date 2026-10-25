<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id']);
    $produit_id = intval($_POST['produit_id']);
    $quantite = floatval($_POST['quantite']);
    $statut_paiement = $_POST['statut_paiement'];

    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;

    if ($produit_id > 0 && $quantite > 0) {
        try {
            // Récupération des informations sur le produit (sans forcer prix_revient)
            $stmt_art = $pdo->prepare("SELECT * FROM articles WHERE id = ? AND type = 'produit_fini'");
            $stmt_art->execute([$produit_id]);
            $produit = $stmt_art->fetch();

            if (!$produit) {
                $error = "Produit introuvable.";
            } elseif ($produit['stock_actuel'] < $quantite) {
                $error = "Stock insuffisant pour " . $produit['designation'] . " (Disponible : " . $produit['stock_actuel'] . ")";
            } else {
                $prix_unitaire = floatval($produit['prix_vente']);
                // Vérification si prix_revient existe dans les données récupérées, sinon 0
                $prix_revient = isset($produit['prix_revient']) ? floatval($produit['prix_revient']) : 0;
                $marge_unitaire = $prix_unitaire - $prix_revient;
                
                $montant_total = $quantite * $prix_unitaire;
                $facture_num = 'FAC-' . date('Y') . '-' . time();

                $pdo->beginTransaction();

                // 1. Enregistrer la vente principale
                $stmt_v = $pdo->prepare("INSERT INTO ventes (facture_num, client_id, user_id, montant_total, statut_paiement, date_vente) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt_v->execute([$facture_num, $client_id ?: null, $user_id, $montant_total, $statut_paiement]);
                $vente_id = $pdo->lastInsertId();

                // 2. Enregistrer le détail dans vente_details
                $stmt_d = $pdo->prepare("INSERT INTO vente_details (vente_id, produit_fini_id, quantite, prix_unitaire, marge_unitaire) VALUES (?, ?, ?, ?, ?)");
                $stmt_d->execute([$vente_id, $produit_id, $quantite, $prix_unitaire, $marge_unitaire]);

                // 3. Déduire du stock
                $stmt_up = $pdo->prepare("UPDATE articles SET stock_actuel = stock_actuel - ? WHERE id = ?");
                $stmt_up->execute([$quantite, $produit_id]);

                $pdo->commit();

                header("Location: index.php");
                exit();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Erreur lors de la vente : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez sélectionner un produit et une quantité valide.";
    }
}

// Récupération des clients et produits finis
$clients = $pdo->query("SELECT id, nom_entreprise FROM clients ORDER BY nom_entreprise ASC")->fetchAll();
$produits = $pdo->query("SELECT * FROM articles WHERE type = 'produit_fini' ORDER BY designation ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>➕ Enregistrer une Vente</h2>
    <a href="index.php" class="btn" style="background: #7f8c8d;">← Annuler</a>
</div>

<?php if ($error): ?>
    <div style="background: #fadbd8; color: #e74c3c; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Client</label>
            <select name="client_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">-- Client de Passage / Comptoir --</option>
                <?php foreach ($clients as $cl): ?>
                    <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nom_entreprise']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Produit Fini à vendre *</label>
            <select name="produit_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">-- Sélectionner Produit Fini --</option>
                <?php foreach ($produits as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['designation']) ?> (Dispo: <?= floatval($p['stock_actuel']) ?> | Prix: <?= number_format($p['prix_vente'], 2) ?> DA)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Quantité Vendue *</label>
                <input type="number" step="0.01" name="quantite" required placeholder="ex: 10" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; margin-bottom: 5px; font-weight: bold;">Statut Paiement</label>
                <select name="statut_paiement" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="paye">Payé (Comptant)</option>
                    <option value="non_paye">Non Payé (Crédit)</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn" style="width: 100%; padding: 12px; background: #27ae60;">Valider et Enregistrer la Vente</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>