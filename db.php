<?php
// Fichier: db.php
$servername = "sql100.infinityfree.com"; // Votre hôte de base de données
$username = "if0_39960555"; // Votre nom d'utilisateur de base de données
$password = "5MTgEgC8Rg"; 
$dbname = "if0_39960555_biscuits_db"; // Votre nom de base de données

try {
    // Crée une nouvelle instance de PDO avec les informations du serveur en ligne
    $connexion = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configure le mode d'erreur pour lancer des exceptions en cas de problème
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Affiche une erreur si la connexion échoue
    die("Échec de la connexion à la base de données : " . $e->getMessage());
}
?>