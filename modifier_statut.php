<?php
// Fichier: modifier_statut.php

header('Content-Type: application/json');

// Inclure le fichier de connexion à la base de données
include 'db.php'; 

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée.';
    echo json_encode($response);
    exit;
}

// 1. Récupération et validation des données POST
$commande_id = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
// Utiliser FILTER_SANITIZE_FULL_SPECIAL_CHARS pour un nettoyage moderne
$nouveau_statut = filter_input(INPUT_POST, 'nouveau_statut', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// 2. Nettoyage et vérification de la validité
if (!$commande_id || empty($nouveau_statut)) {
    $response['message'] = 'Données de commande ou de statut manquantes/invalides.';
    echo json_encode($response);
    exit;
}

$nouveau_statut = trim($nouveau_statut);

// Liste blanche des statuts autorisés (sécurité)
$statuts_valides = ['En attente', 'Livrée', 'Annulée'];
if (!in_array($nouveau_statut, $statuts_valides)) {
    $response['message'] = 'Statut non autorisé.';
    echo json_encode($response);
    exit;
}

try {
    if (!isset($connexion)) {
        throw new Exception("Erreur de connexion à la base de données (variable \$connexion non définie).");
    }

    // 3. Préparation de la requête de mise à jour (incluant la date de modification)
    $stmt = $connexion->prepare("
        UPDATE commandes 
        SET statut = ?, date_modification = NOW() 
        WHERE id = ?
    ");
    
    // 4. Exécution de la requête
    $success = $stmt->execute([$nouveau_statut, $commande_id]);

    if ($success) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = "Le statut de la commande #{$commande_id} a été mis à jour à '{$nouveau_statut}'.";
        } else {
            $response['message'] = "Commande #{$commande_id} non trouvée ou statut déjà défini.";
        }
    } else {
        $response['message'] = "Échec de l'exécution de la requête de mise à jour.";
    }

} catch (Exception $e) {
    $response['message'] = 'Erreur serveur: ' . $e->getMessage();
}

echo json_encode($response);
?>