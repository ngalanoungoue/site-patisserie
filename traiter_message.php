<?php
header('Content-Type: application/json');
include 'db.php';

$response = ["success" => false, "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $sujet = $_POST['sujet'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $response['message'] = "Tous les champs sont requis.";
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $connexion->prepare("INSERT INTO messages (nom, email, sujet, message, date_envoi) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$nom, $email, $sujet, $message]);
        
        $response['success'] = true;
        $response['message'] = "Votre message a été envoyé avec succès !";

    } catch(PDOException $e) {
        $response['message'] = "Erreur lors de l'enregistrement du message : " . $e->getMessage();
        error_log("Erreur lors de l'envoi du message: " . $e->getMessage());
    }
} else {
    $response['message'] = "Méthode de requête invalide.";
}

echo json_encode($response);
?>