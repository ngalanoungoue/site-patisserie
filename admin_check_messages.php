<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

try {
    $stmt = $connexion->prepare("SELECT COUNT(*) FROM messages WHERE statut = 'non lu'");
    $stmt->execute();
    $unread_count = $stmt->fetchColumn();

    header('Content-Type: application/json');
    echo json_encode(['unread_count' => $unread_count]);
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => 'Erreur de base de données']);
}
?>
