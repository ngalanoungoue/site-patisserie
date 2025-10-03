<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $stmt = $connexion->prepare("DELETE FROM produits WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = "<div class='alert alert-success'>Produit supprimé avec succès.</div>";
    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Erreur lors de la suppression du produit: " . $e->getMessage() . "</div>";
    }
}
header("Location: admin_products.php");
exit();
?>