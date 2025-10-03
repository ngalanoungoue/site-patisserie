<?php
session_start();
// 1. Vérification de l'authentification de l'administrateur
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas admin
    header("Location: admin_login.php");
    exit();
}

// Inclusion de la connexion à la base de données
include 'db.php';

// 2. Vérification et validation de l'ID client
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Erreur : ID client non valide ou manquant.";
    header("Location: admin_clients.php");
    exit();
}

$clientId = (int)$_GET['id'];

try {
    // IMPORTANT : Si vous avez des commandes liées à ce client (clé étrangère),
    // et que vous n'avez pas activé la suppression en cascade (ON DELETE CASCADE)
    // dans votre schéma SQL, cette suppression pourrait échouer
    // ou laisser des enregistrements orphelins.

    // Requête DELETE préparée pour supprimer le client
    $sql = "DELETE FROM clients WHERE id = :id";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':id', $clientId, PDO::PARAM_INT);
    $stmt->execute();

    // Vérification du nombre de lignes affectées
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "Le client (ID: {$clientId}) a été supprimé avec succès.";
    } else {
        // Cela peut arriver si l'ID était valide mais qu'aucun enregistrement n'a été trouvé
        $_SESSION['message'] = "Erreur : Aucun client trouvé avec l'ID {$clientId}.";
    }

} catch (PDOException $e) {
    // Gérer les erreurs de base de données (ex: violation de contrainte de clé étrangère)
    $_SESSION['message'] = "Erreur de base de données lors de la suppression du client : " . $e->getMessage();
}

// Redirection vers la liste des clients
header("Location: admin_clients.php");
exit();
?>
