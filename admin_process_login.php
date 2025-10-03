<?php
session_start();

$email_admin = 'ngalanoungoue@gmail.com';
// Votre mot de passe haché
$hashed_password_admin = '$2y$10$.FI9miQ1gtUdtw.aYxPf3uPR9kEEb5F7D21XaCki6E/Bc9ARAfEPm'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Vérification sécurisée du mot de passe
    if ($email === $email_admin && password_verify($password, $hashed_password_admin)) {
        // Authentification réussie
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_email'] = $email;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Authentification échouée
        $_SESSION['login_error'] = "Email ou mot de passe incorrect.";
        header("Location: admin_login.php");
        exit();
    }
} else {
    header("Location: admin_login.php");
    exit();
}