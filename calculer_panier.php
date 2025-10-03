// Fichier: calculer_panier.php
<?php
header('Content-Type: application/json');

// Récupère le corps de la requête AJAX
$data = file_get_contents('php://input');
$panier = json_decode($data, true);

// Pour l'instant, on se contente de vérifier que les données ont été reçues
if (is_array($panier)) {
    // Décommenter le bloc ci-dessous pour activer le calcul du total
    $nouveauTotal = 0;
    foreach ($panier as $item) {
        $nouveauTotal += $item['prixUnitaire'] * $item['quantite'];
    }

    // On renvoie une réponse JSON avec le total
    echo json_encode(['success' => true, 'total' => $nouveauTotal, 'message' => 'Données du panier reçues avec succès !']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la réception des données.']);
}
?>