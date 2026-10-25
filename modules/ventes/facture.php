<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Détection de la table et colonne utilisateur
$user_table = 'users';
try {
    $pdo->query("SELECT 1 FROM utilisateurs LIMIT 1");
    $user_table = 'utilisateurs';
} catch (Exception $e) {}

$user_name_col = 'id';
try {
    $columns = $pdo->query("DESCRIBE {$user_table}")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['nom', 'username', 'full_name', 'nom_utilisateur', 'login'] as $possible_col) {
        if (in_array($possible_col, $columns)) {
            $user_name_col = $possible_col;
            break;
        }
    }
} catch (Exception $e) {}

$stmt = $pdo->prepare("
    SELECT v.*, c.nom_entreprise, c.telephone, c.adresse, u.{$user_name_col} AS vendeur
    FROM ventes v
    LEFT JOIN clients c ON v.client_id = c.id
    LEFT JOIN {$user_table} u ON v.user_id = u.id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$vente = $stmt->fetch();

if (!$vente) {
    echo "<div class='card'>Facture introuvable.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$stmt_d = $pdo->prepare("
    SELECT vd.*, a.designation 
    FROM vente_details vd 
    JOIN articles a ON vd.produit_fini_id = a.id 
    WHERE vd.vente_id = ?
");
$stmt_d->execute([$id]);
$details = $stmt_d->fetchAll();
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between;">
    <a href="index.php" class="btn" style="background: #7f8c8d;">← Retour aux ventes</a>
    <button onclick="window.print()" class="btn" style="background: #2980b9;">🖨️ Imprimer la Facture</button>
</div>

<div class="card" style="padding: 40px; background: white;" id="print-area">
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; align-items: center;">
        <!-- En-tête avec Logo SVG -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="/assets/images/logo_pestos.svg" alt="Logo PEST'OS" style="height: 45px; width: auto; object-fit: contain;">
            <div>
                <h2 style="margin: 0; font-size: 24px; font-weight: bold; color: #2c3e50;">PEST'OS</h2>
                <p style="margin: 2px 0 0 0; color: #7f8c8d; font-size: 14px;">Pesto & sauces artisanales</p>
            </div>
        </div>
        <div style="text-align: right;">
            <h3 style="margin: 0;">FACTURE N° : <?= htmlspecialchars($vente['facture_num'] ?? '') ?></h3>
            <p style="margin: 5px 0 0 0;"><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></p>
        </div>
    </div>

    <div style="margin: 30px 0; display: flex; justify-content: space-between;">
        <div>
            <strong>Client :</strong><br>
            <?= htmlspecialchars($vente['nom_entreprise'] ?? 'Client de Passage / Comptoir') ?><br>
            <?= htmlspecialchars($vente['adresse'] ?? '') ?><br>
            <?= htmlspecialchars($vente['telephone'] ?? '') ?>
        </div>
        <div style="text-align: right;">
            <strong>Vendeur :</strong> <?= htmlspecialchars($vente['vendeur'] ?? 'Système') ?><br>
            <strong>Statut :</strong> <?= $vente['statut_paiement'] === 'paye' ? '<span style="color:green;">Payé</span>' : '<span style="color:red;">Non Payé</span>' ?>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #f2f2f2; border-bottom: 2px solid #ccc;">
                <th style="padding: 10px; text-align: left;">Désignation</th>
                <th style="padding: 10px; text-align: center;">Quantité</th>
                <th style="padding: 10px; text-align: right;">Prix Unitaire</th>
                <th style="padding: 10px; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $d): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= htmlspecialchars($d['designation']) ?></td>
                    <td style="padding: 10px; text-align: center;"><?= floatval($d['quantite']) ?></td>
                    <td style="padding: 10px; text-align: right;"><?= number_format($d['prix_unitaire'], 2) ?> DA</td>
                    <td style="padding: 10px; text-align: right;"><?= number_format($d['quantite'] * $d['prix_unitaire'], 2) ?> DA</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 18px;">
        <strong>Total à Payer : <?= number_format($vente['montant_total'], 2) ?> DA</strong>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>