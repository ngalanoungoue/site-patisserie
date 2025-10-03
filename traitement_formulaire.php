<?php
// Inclure le fichier de connexion à la base de données
require_once 'db.php';

// Vérifier si la requête est bien de type POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $nom = htmlspecialchars(trim($_POST['nom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // Vous pouvez ajouter ici la validation des champs (email, etc.)
    
    // Préparer et exécuter la requête d'insertion
    try {
        $sql = "INSERT INTO messages (nom, email, message, date_envoi) VALUES (:nom, :email, :message, NOW())";
        $stmt = $conn->prepare($sql);
        
        // Lier les paramètres
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':message', $message);
        
        // Exécuter la requête
        $stmt->execute();
        
        // Réponse pour le client (JSON)
        echo json_encode(['success' => true, 'message' => 'Votre message a été envoyé avec succès !']);
        
    } catch(PDOException $e) {
        // En cas d'erreur de la base de données
        echo json_encode(['success' => false, 'message' => 'Erreur de la base de données : ' . $e->getMessage()]);
    }
} else {
    // Si la requête n'est pas de type POST
    echo json_encode(['success' => false, 'message' => 'Méthode de requête invalide.']);
}
?>