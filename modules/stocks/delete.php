<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // En cas d'erreur de clé étrangère (article lié à une facture ou recette)
        header("Location: index.php?error=impossible_de_supprimer");
        exit();
    }
}

header("Location: index.php");
exit();