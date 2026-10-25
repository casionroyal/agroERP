<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

// Récupération de l'historique de tous les paiements (Ventes réglées)
$stmt = $pdo->query("
    SELECT v.id AS vente_id, v.facture_num, v.montant_total, v.date_vente, c.nom_entreprise
    FROM ventes v
    LEFT JOIN clients c ON v.client_id = c.id
    WHERE v.statut_paiement = 'paye'
    ORDER BY v.date_vente DESC
");
$paiements = $stmt->fetchAll();

// Calcul du total encaisse
$total_encaisse = 0;
foreach ($paiements as $p) {
    $total_encaisse += floatval($p['montant_total']);
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <h2>💰 Journal des Paiements & Encaissements</h2>
    <a href="../ventes/creances.php" class="btn" style="background: #e67e22;">💳 Voir les Créances En Attente</a>
</div>

<!-- Carte récapitulative (Adaptée au responsive mobile) -->
<div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
    <div class="card" style="flex: 1; min-width: 250px; border-left: 5px solid #27ae60;">
        <h4 style="margin: 0; color: #7f8c8d;">Total des Recettes Encaissées</h4>
        <h2 style="margin: 10px 0 0 0; color: #27ae60;"><?= number_format($total_encaisse, 2) ?> DA</h2>
    </div>
    <div class="card" style="flex: 1; min-width: 250px; border-left: 5px solid #3498db;">
        <h4 style="margin: 0; color: #7f8c8d;">Nombre de Transactions Payées</h4>
        <h2 style="margin: 10px 0 0 0; color: #2980b9;"><?= count($paiements) ?></h2>
    </div>
</div>

<!-- Tableau du journal des paiements -->
<div class="card">
    <!-- Conteneur pour le défilement horizontal sur mobile -->
    <div style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; min-width: 650px; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 12px;">N° Facture</th>
                    <th style="padding: 12px;">Date Règlement</th>
                    <th style="padding: 12px;">Client</th>
                    <th style="padding: 12px; text-align: right;">Montant Encaissé</th>
                    <th style="padding: 12px; text-align: center;">Statut</th>
                    <th style="padding: 12px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paiements)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #7f8c8d;">
                            Aucun paiement n'a encore été enregistré.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paiements as $p): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; font-weight: bold;"><?= htmlspecialchars($p['facture_num'] ?? 'N/A') ?></td>
                            <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($p['date_vente'])) ?></td>
                            <td style="padding: 12px; font-weight: bold;"><?= htmlspecialchars($p['nom_entreprise'] ?? 'Client de Passage') ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: bold; color: #27ae60;"><?= number_format($p['montant_total'], 2) ?> DA</td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 13px;">Réglé</span>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="../ventes/facture.php?id=<?= $p['vente_id'] ?>" class="btn" style="background: #2980b9; padding: 5px 10px; font-size: 13px;">📄 Reçu / Facture</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>