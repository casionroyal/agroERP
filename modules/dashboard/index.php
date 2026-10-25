<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

// 1. Alertes de stock
$stmt_alert = $pdo->query("SELECT COUNT(*) FROM articles WHERE stock_actuel <= stock_min");
$alertes_stock = $stmt_alert->fetchColumn();

// 2. Total des créances en attente (si la table ventes existe)
$total_creances = 0;
try {
    $stmt_creances = $pdo->query("SELECT SUM(reste_a_payer) FROM ventes WHERE reste_a_payer > 0");
    $total_creances = $stmt_creances->fetchColumn() ?: 0;
} catch (Exception $e) {}

// 3. Récupération des 5 dernières ventes
$dernieres_ventes = [];
try {
    $stmt_ventes = $pdo->query("
        SELECT v.*, c.nom as client_nom 
        FROM ventes v 
        LEFT JOIN clients c ON v.client_id = c.id 
        ORDER BY v.date_vente DESC LIMIT 5
    ");
    $dernieres_ventes = $stmt_ventes->fetchAll();
} catch (Exception $e) {}
?>

<!-- En-tête -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>📊 Tableau de Bord Principal</h2>
    <button onclick="openUserModal()" class="btn" style="background: #34495e; color: #fff; border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer;">
        ⚙️ Gestion Profil & Accès
    </button>
</div>

<!-- Cartes d'indicateurs (KPI) -->
<div class="grid-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card">
        <h4>Utilisateur Connecté</h4>
        <div class="value"><?= htmlspecialchars($_SESSION['username']); ?></div>
        <span class="badge badge-success">Rôle: <?= htmlspecialchars($_SESSION['role']); ?></span>
    </div>
    
    <div class="card">
        <h4>Alerte Stock</h4>
        <div class="value"><?= $alertes_stock ?> Articles</div>
        <?php if ($alertes_stock > 0): ?>
            <span class="badge badge-danger">Réappro. Requis</span>
        <?php else: ?>
            <span class="badge badge-success">Stock OK</span>
        <?php endif; ?>
    </div>

    <div class="card">
        <h4>Créances Clients</h4>
        <div class="value"><?= number_format($total_creances, 2, ',', ' ') ?> DA</div>
        <span class="badge <?= $total_creances > 0 ? 'badge-danger' : 'badge-success' ?>">
            <?= $total_creances > 0 ? 'En Attente' : 'Aucune Dette' ?>
        </span>
    </div>
</div>

<!-- Section 2 : Raccourcis Rapides -->

<div style="display:flex; gap:15px; margin-bottom:20px;">
    <a href="../ventes/add.php" class="btn" style="background:#27ae60; color:#fff; text-decoration:none; padding:10px 15px; border-radius:5px; font-weight:bold;">
        ➕ Nouvelle Vente
    </a>
    
    <a href="../production/recettes.php" class="btn" style="background:#e67e22; color:#fff; text-decoration:none; padding:10px 15px; border-radius:5px; font-weight:bold;">
        ⚙️ Lancer une Production
    </a>
    
    <a href="../stocks/index.php" class="btn" style="background:#2980b9; color:#fff; text-decoration:none; padding:10px 15px; border-radius:5px; font-weight:bold;">
        📦 Gérer le Stock
    </a>
</div>

<!-- Section 3 : Dernières Transactions -->
<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 15px;">🛒 Dernières Ventes Réalisées</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Total (DA)</th>
                    <th>Reste à Payer</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($dernieres_ventes)): ?>
                    <?php foreach ($dernieres_ventes as $vente): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></td>
                            <td><?= htmlspecialchars($vente['client_nom'] ?? 'Client Comptoir') ?></td>
                            <td><strong><?= number_format($vente['total_ttc'], 2, ',', ' ') ?> DA</strong></td>
                            <td><?= number_format($vente['reste_a_payer'], 2, ',', ' ') ?> DA</td>
                            <td>
                                <?php if ($vente['reste_a_payer'] <= 0): ?>
                                    <span class="badge badge-success">Payé</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Crédit</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #7f8c8d;">Aucune vente enregistrée pour le moment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
require_once __DIR__ . '/../../includes/modal_profil_users.php'; 
require_once __DIR__ . '/../../includes/footer.php'; 
?>