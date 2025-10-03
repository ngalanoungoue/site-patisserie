<?php
// Fichier: envoyer_message.php

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    if (empty($nom) || empty($email) || empty($message)) {
        header("Location: contact.html?status=error&message=Tous les champs sont requis.");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.html?status=error&message=L'adresse email n'est pas valide.");
        exit();
    }

    try {
        $stmt = $connexion->prepare("INSERT INTO messages (nom_expediteur, email_expediteur, message) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $email, $message]);

        header("Location: contact.html?status=success&message=Votre message a été envoyé avec succès !");
        exit();
    } catch (PDOException $e) {
        header("Location: contact.html?status=error&message=Une erreur est survenue lors de l'envoi de votre message.");
        exit();
    }
} else {
    header("Location: contact.html");
    exit();
}
?>