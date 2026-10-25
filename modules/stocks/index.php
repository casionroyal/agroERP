<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

// Récupération des filtres de recherche
$type_filter = $_GET['type'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Construction de la requête SQL dynamique
$sql = "SELECT * FROM articles WHERE 1=1";
$params = [];

if ($type_filter !== 'all') {
    $sql .= " AND type = :type";
    $params['type'] = $type_filter;
}

if (!empty($search)) {
    $sql .= " AND (designation LIKE :search OR code LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY type ASC, designation ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <h2>📦 Gestion des Stocks & Articles</h2>
    <a href="add.php" class="btn" style="background: #27ae60;">+ Ajouter un Article</a>
</div>

<!-- Barre de Recherche et Filtres -->
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
        <input type="text" name="search" placeholder="Rechercher par nom ou code..." value="<?= htmlspecialchars($search) ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex: 1; min-width: 200px;">
        
        <select name="type" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>Tous les types</option>
            <option value="matiere_premiere" <?= $type_filter === 'matiere_premiere' ? 'selected' : '' ?>>Matières Premières</option>
            <option value="emballage" <?= $type_filter === 'emballage' ? 'selected' : '' ?>>Emballages</option>
            <option value="produit_fini" <?= $type_filter === 'produit_fini' ? 'selected' : '' ?>>Produits Finis</option>
        </select>
        
        <button type="submit" class="btn" style="background: #34495e;">Filtrer</button>
    </form>
</div>

<!-- Message d'erreur éventuel lors de la suppression -->
<?php if (isset($_GET['error']) && $_GET['error'] === 'impossible_de_supprimer'): ?>
    <div style="background: #fadbd8; color: #e74c3c; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
        ⚠️ Impossible de supprimer cet article car il est associé à des ventes, fiches techniques ou ordres de fabrication.
    </div>
<?php endif; ?>

<!-- Tableau des Articles -->
<div class="table-responsive card">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 12px;">Code</th>
                <th style="padding: 12px;">Désignation</th>
                <th style="padding: 12px;">Type</th>
                <th style="padding: 12px;">Stock Actuel</th>
                <th style="padding: 12px;">Prix Unitaire</th>
                <th style="padding: 12px;">Statut</th>
                <th style="padding: 12px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 20px;">Aucun article trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($articles as $art): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><strong><?= htmlspecialchars($art['code']) ?></strong></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($art['designation']) ?></td>
                        <td style="padding: 12px;">
                            <?php 
                                if($art['type'] == 'matiere_premiere') echo 'Matière Première';
                                elseif($art['type'] == 'emballage') echo 'Emballage';
                                else echo 'Produit Fini';
                            ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?= number_format($art['stock_actuel'], 2) ?></strong> <?= htmlspecialchars($art['unite'] ?? '') ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($art['type'] === 'produit_fini'): ?>
                                <?= number_format($art['prix_vente'] ?? 0, 2) ?> DA (Vente)
                            <?php else: ?>
                                <?= number_format($art['prix_achat'] ?? 0, 2) ?> DA (Achat)
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php 
                                $seuil_min = $art['stock_minimum'] ?? $art['stock_min'] ?? 0;
                                if ($art['stock_actuel'] <= $seuil_min): 
                            ?>
                                <span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 13px;">Stock Bas</span>
                            <?php else: ?>
                                <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 13px;">OK</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="edit.php?id=<?= $art['id'] ?>" class="btn" style="background: #f39c12; padding: 5px 10px; font-size: 13px;">✏️</a>
                            <a href="delete.php?id=<?= $art['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');" class="btn" style="background: #c0392b; padding: 5px 10px; font-size: 13px;">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>