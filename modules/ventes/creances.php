<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$message = '';

// Traitement du règlement d'une facture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regler') {
    $vente_id = intval($_POST['vente_id']);
    
    if ($vente_id > 0) {
        $stmt = $pdo->prepare("UPDATE ventes SET statut_paiement = 'paye' WHERE id = ?");
        if ($stmt->execute([$vente_id])) {
            $message = "Le règlement a été enregistré avec succès !";
        } else {
            $message = "Erreur lors du règlement.";
        }
    }
}

// Récupération des factures non payées (à crédit)
$stmt = $pdo->query("
    SELECT v.*, c.nom_entreprise, c.telephone
    FROM ventes v
    LEFT JOIN clients c ON v.client_id = c.id
    WHERE v.statut_paiement = 'non_paye'
    ORDER BY v.date_vente DESC
");
$creances = $stmt->fetchAll();

// Calcul du montant total des créances
$total_creances = 0;
foreach ($creances as $c) {
    $total_creances += floatval($c['montant_total']);
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>💳 Suivi des Créances Clients</h2>
    <a href="index.php" class="btn" style="background: #7f8c8d;">← Historique Ventes</a>
</div>

<?php if ($message): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Cartes d'information -->
<div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
    <div class="card" style="flex: 1; min-width: 200px; border-left: 5px solid #e74c3c;">
        <h4 style="margin: 0; color: #7f8c8d;">Total Créances Non Payées</h4>
        <h2 style="margin: 10px 0 0 0; color: #c0392b;"><?= number_format($total_creances, 2) ?> DA</h2>
    </div>
    <div class="card" style="flex: 1; min-width: 200px; border-left: 5px solid #3498db;">
        <h4 style="margin: 0; color: #7f8c8d;">Factures en Attente</h4>
        <h2 style="margin: 10px 0 0 0; color: #2980b9;"><?= count($creances) ?></h2>
    </div>
</div>

<!-- Tableau des créances -->
<div class="card">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 12px;">N° Facture</th>
                <th style="padding: 12px;">Date</th>
                <th style="padding: 12px;">Client</th>
                <th style="padding: 12px;">Téléphone</th>
                <th style="padding: 12px; text-align: right;">Montant Total</th>
                <th style="padding: 12px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($creances)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #27ae60;">
                        🎉 Aucune créance en attente ! Tous les paiements sont réglés.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($creances as $c): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; font-weight: bold;"><?= htmlspecialchars($c['facture_num'] ?? 'N/A') ?></td>
                        <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($c['date_vente'])) ?></td>
                        <td style="padding: 12px; font-weight: bold;"><?= htmlspecialchars($c['nom_entreprise'] ?? 'Client de Passage') ?></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($c['telephone'] ?? '-') ?></td>
                        <td style="padding: 12px; text-align: right; font-weight: bold; color: #c0392b;"><?= number_format($c['montant_total'], 2) ?> DA</td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="facture.php?id=<?= $c['id'] ?>" class="btn" style="background: #2980b9; padding: 5px 10px; font-size: 13px;">📄 Facture</a>
                            
                            <form method="POST" onsubmit="return confirm('Confirmer l\'encaissement de cette facture ?');" style="display:inline-block; margin-left: 5px;">
                                <input type="hidden" name="action" value="regler">
                                <input type="hidden" name="vente_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn" style="background: #27ae60; padding: 5px 10px; font-size: 13px;">✅ Encaisser</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>