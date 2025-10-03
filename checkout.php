<?php
// checkout.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
const SHIPPING_FEES = 1000.0;
require_once __DIR__ . '/db.php';

try {
    // 1. Récupérer les données JSON
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides : ' . json_last_error_msg());
    }

    $clientData = $requestData['client'] ?? null;
    $panierData = $requestData['panier'] ?? null;

    if (!$clientData || !$panierData) {
        throw new Exception('Données manquantes : client ou panier non fourni.');
    }

    // 2. Valider les données client
    $nom = trim($clientData['nom'] ?? '');
    $email = trim($clientData['email'] ?? '');
    $telephone = preg_replace('/\s+/', '', trim($clientData['phone'] ?? ''));
    $city = trim($clientData['city'] ?? '');
    $address = trim($clientData['address'] ?? '');
    $comments = trim($clientData['comments'] ?? '');

    if (empty($nom) || empty($email) || empty($telephone) || empty($address)) {
        throw new Exception('Veuillez remplir tous les champs obligatoires.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide.');
    }

    // 3. Valider le panier
    $calculated_subtotal = 0.0;
    foreach ($panierData as $item) {
        if (!isset($item['produit_id']) || !isset($item['lot_id']) || !isset($item['prix']) || !isset($item['quantite'])) {
            throw new Exception('Données du panier incomplètes.');
        }
    }

    // 4. Calculer le total
    foreach ($panierData as &$item) {
        $item['produit_id'] = intval($item['produit_id']);
        $item['lot_id'] = intval($item['lot_id']);
        $item['option_id'] = isset($item['option_id']) ? intval($item['option_id']) : null;
        $item['quantite'] = intval($item['quantite']);
        $item['prix'] = floatval($item['prix']);
        $calculated_subtotal += $item['prix'] * $item['quantite'];
    }

    $total_final = $calculated_subtotal + SHIPPING_FEES;

    // 5. Transaction BD
    $connexion->beginTransaction();

    // 6. Vérifier ou créer le client
    $stmt_check_client = $connexion->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt_check_client->execute([$email]);
    $existing_client = $stmt_check_client->fetch(PDO::FETCH_ASSOC);

    if ($existing_client) {
        $client_id = $existing_client['id'];
        $stmt_update_client = $connexion->prepare("UPDATE clients SET nom = ?, telephone = ?, adresse = ? WHERE id = ?");
        $stmt_update_client->execute([$nom, $telephone, $address, $client_id]);
    } else {
        $stmt_insert_client = $connexion->prepare("INSERT INTO clients (nom, email, telephone, adresse) VALUES (?, ?, ?, ?)");
        $stmt_insert_client->execute([$nom, $email, $telephone, $address]);
        $client_id = $connexion->lastInsertId();
    }

    // 7. Créer la commande
    $stmt_commande = $connexion->prepare("INSERT INTO commandes (client_id, date_commande, total_prix, commentaires) VALUES (?, NOW(), ?, ?)");
    $stmt_commande->execute([$client_id, $total_final, $comments]);
    $commande_id = $connexion->lastInsertId();

    // 8. Ajouter les lignes de commande
    $stmt_lignes = $connexion->prepare("INSERT INTO lignes_commandes (commande_id, produit_id, lot_id, quantite_lot, sous_total, option_id) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($panierData as $item) {
        $sous_total = $item['prix'] * $item['quantite'];
        $stmt_lignes->execute([
            $commande_id,
            $item['produit_id'],
            $item['lot_id'],
            $item['quantite'], // Utilisez 'quantite_lot' si c'est le nom de la colonne pour la quantité commandée
            $sous_total,
            $item['option_id']
        ]);
    }

    $connexion->commit();

    // 9. Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Commande validée avec succès.',
        'commande_id' => $commande_id
    ]);

} catch (PDOException $e) {
    if (isset($connexion) && $connexion->inTransaction()) {
        $connexion->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données : ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    if (isset($connexion) && $connexion->inTransaction()) {
        $connexion->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
