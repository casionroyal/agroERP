<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';

// Détection dynamique de la table des utilisateurs
$user_table = 'users';
try {
    $pdo->query("SELECT 1 FROM utilisateurs LIMIT 1");
    $user_table = 'utilisateurs';
} catch (Exception $e) {
    // Si 'utilisateurs' n'existe pas, on utilise 'users'
}

// Récupération de la structure de la table utilisateur pour identifier la bonne colonne de nom
$user_name_col = 'id'; // fallback de sécurité
try {
    $columns = $pdo->query("DESCRIBE {$user_table}")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['nom', 'username', 'full_name', 'nom_utilisateur', 'login'] as $possible_col) {
        if (in_array($possible_col, $columns)) {
            $user_name_col = $possible_col;
            break;
        }
    }
} catch (Exception $e) {
    // En cas d'erreur de lecture de la structure
}

// Requête principale adaptative
$sql = "
    SELECT v.*, c.nom_entreprise, u.{$user_name_col} AS vendeur
    FROM ventes v
    LEFT JOIN clients c ON v.client_id = c.id
    LEFT JOIN {$user_table} u ON v.user_id = u.id
    ORDER BY v.date_vente DESC
";
$ventes = $pdo->query($sql)->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>🛒 Historique des Ventes</h2>
    <div>
        <a href="creances.php" class="btn" style="background: #e67e22; margin-right: 10px;">💳 Créances Clients</a>
        <a href="add.php" class="btn" style="background: #27ae60;">➕ Enregistrer une Vente</a>
    </div>
</div>

<div class="card">
    <!-- Début de l'enveloppe avec défilement horizontal mobile -->
    <div style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; min-width: 700px; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 12px;">N° Facture</th>
                    <th style="padding: 12px;">Date</th>
                    <th style="padding: 12px;">Client</th>
                    <th style="padding: 12px;">Vendeur</th>
                    <th style="padding: 12px; text-align: right;">Montant Total</th>
                    <th style="padding: 12px; text-align: center;">Statut</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">Aucune vente enregistrée pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ventes as $v): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; font-weight: bold;"><?= htmlspecialchars($v['facture_num'] ?? 'N/A') ?></td>
                            <td style="padding: 12px;"><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($v['nom_entreprise'] ?? 'Client de Passage') ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($v['vendeur'] ?? 'N/A') ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: bold;"><?= number_format($v['montant_total'], 2) ?> DA</td>
                            <td style="padding: 12px; text-align: center;">
                                <?php if ($v['statut_paiement'] === 'paye'): ?>
                                    <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 13px;">Payé</span>
                                <?php else: ?>
                                    <span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 13px;">Non Payé</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="facture.php?id=<?= $v['id'] ?>" class="btn" style="background: #2980b9; padding: 5px 10px; font-size: 13px;">📄 Facture</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Fin du défilement horizontal -->
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>