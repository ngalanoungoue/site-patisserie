<?php
$password_admin_clair = 'lesbiscuitsdemaman'; // Remplacez ceci par votre vrai mot de passe
$hashed_password = password_hash($password_admin_clair, PASSWORD_DEFAULT);
echo $hashed_password;
?>