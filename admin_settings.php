<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';
$settings = [];

// Récupérer les paramètres actuels
try {
    $stmt = $connexion->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Transformer en tableau associatif pour un accès plus facile
    $settings_array = [];
    foreach ($settings as $setting) {
        $settings_array[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mettre à jour chaque paramètre
        foreach ($_POST as $key => $value) {
            // Vérifier que la clé existe dans la base de données
            $stmt = $connexion->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $message = "<div class='alert alert-success'>Paramètres mis à jour avec succès !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour : " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        .settings-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .settings-section {
            margin-bottom: 30px;
        }
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="admin_dashboard.php">Tableau de Bord Admin</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_orders.php">Commandes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_products.php">Produits</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_clients.php">Clients</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="btn btn-danger" href="admin_logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2"><i class="bi bi-gear me-2"></i> Paramètres</h1>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>

        <?php echo $message; ?>

        <div class="card settings-card">
            <div class="card-body">
                <form method="POST">
                    <!-- Section Générale -->
                    <div class="settings-section">
                        <h3 class="mb-4"><i class="bi bi-globe me-2"></i> Paramètres généraux</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="site_name" class="form-label">Nom du site</label>
                                <input type="text" class="form-control" id="site_name" name="site_name"
                                       value="<?php echo htmlspecialchars($settings_array['site_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="site_email" class="form-label">Email du site</label>
                                <input type="email" class="form-control" id="site_email" name="site_email"
                                       value="<?php echo htmlspecialchars($settings_array['site_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="currency" class="form-label">Devise</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="FCFA" <?php echo ($settings_array['currency'] ?? '') === 'FCFA' ? 'selected' : ''; ?>>FCFA</option>
                                    <option value="EUR" <?php echo ($settings_array['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                    <option value="USD" <?php echo ($settings_array['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Commandes -->
                    <div class="settings-section">
                        <h3 class="mb-4"><i class="bi bi-cart me-2"></i> Paramètres des commandes</h3>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_orders" name="enable_orders"
                                           <?php echo isset($settings_array['enable_orders']) && $settings_array['enable_orders'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_orders">Accepter les commandes</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="order_prefix" class="form-label">Préfixe des commandes</label>
                                <input type="text" class="form-control" id="order_prefix" name="order_prefix"
                                       value="<?php echo htmlspecialchars($settings_array['order_prefix'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Section Paiements -->
                    <div class="settings-section">
                        <h3 class="mb-4"><i class="bi bi-credit-card me-2"></i> Paramètres de paiement</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="payment_methods" class="form-label">Méthodes de paiement</label>
                                <select class="form-select" id="payment_methods" name="payment_methods" multiple>
                                    <option value="cash" <?php echo isset($settings_array['payment_methods']) && strpos($settings_array['payment_methods'], 'cash') !== false ? 'selected' : ''; ?>>Paiement à la livraison</option>
                                    <option value="mobile" <?php echo isset($settings_array['payment_methods']) && strpos($settings_array['payment_methods'], 'mobile') !== false ? 'selected' : ''; ?>>Paiement mobile</option>
                                    <option value="bank" <?php echo isset($settings_array['payment_methods']) && strpos($settings_array['payment_methods'], 'bank') !== false ? 'selected' : ''; ?>>Virement bancaire</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
</body>
</html>
