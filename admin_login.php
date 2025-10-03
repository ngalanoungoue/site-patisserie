<?php
session_start();
// Vérifier si l'utilisateur est déjà connecté en tant qu'administrateur
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        body {
            background-image: url('path/to/your/image.jpg'); /* Change this to your image path */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            filter: blur(3px; /* Add blur effect */
        }
        .login-container {
            max-width: 400px;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background-color: rgba(255, 255, 255, 0.9); /* Add transparency to the background */
            position: relative; /* Position relative for proper stacking */
            z-index: 1; /* Ensure it is above the blurred background */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="login-container text-center">
                    <h2 class="mb-4"><i class="fas fa-lock me-2"></i>Accès Administrateur</h2>
                    
                    <?php if (isset($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="admin_process_login.php" method="POST">
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>