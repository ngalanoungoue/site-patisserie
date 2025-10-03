<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';
$client = null;
$commandes = [];

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_clients.php");
    exit();
}

$client_id = $_GET['id'];

// Récupérer les informations du client
try {
    // Récupération du client
    $stmt = $connexion->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        header("Location: admin_clients.php");
        exit();
    }

    // Récupération des commandes du client avec les produits
    $stmt = $connexion->prepare("
        SELECT c.*,
               p.nom as produit_nom,
               p.description as produit_description,
               p.image_url as produit_image
        FROM commandes c
        JOIN commande_produits cp ON c.id = cp.commande_id
        JOIN produits p ON cp.produit_id = p.id
        WHERE c.client_id = ?
        ORDER BY c.date_commande DESC
    ");
    $stmt->execute([$client_id]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";

    // Requête alternative si la table commande_produits n'existe pas
    try {
        $stmt = $connexion->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_commande DESC");
        $stmt->execute([$client_id]);
        $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $commandes = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Client - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .client-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .order-card {
            border-left: 4px solid #dee2e6;
            margin-bottom: 15px;
        }
        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .order-status {
            display: inline-block;
            padding: 0.25em 0.4em;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-en-attente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-livree {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge {
            margin-right: 5px;
        }
        .product-info {
            margin-top: 10px;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
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
                            <a class="nav-link active" href="admin_clients.php">Clients</a>
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
            <h1 class="h2"><i class="bi bi-person me-2"></i> Détails Client</h1>
            <a href="admin_clients.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>

        <?php echo $message; ?>

        <!-- Informations client -->
        <div class="card client-header">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3><?php echo htmlspecialchars($client['nom']); ?></h3>
                        <p class="text-muted">Client depuis le <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="btn btn-outline-primary me-2">
                            <i class="bi bi-envelope me-1"></i> Email
                        </a>
                        <?php if (!empty($client['telephone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" class="btn btn-outline-success">
                            <i class="bi bi-telephone me-1"></i> Appeler
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-envelope me-2"></i> Email</h5>
                        <p><?php echo htmlspecialchars($client['email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-telephone me-2"></i> Téléphone</h5>
                        <p><?php echo htmlspecialchars($client['telephone'] ?? 'Non renseigné'); ?></p>
                    </div>
                </div>
                <?php if (!empty($client['adresse'])): ?>
                <div class="row">
                    <div class="col-12">
                        <h5><i class="bi bi-geo-alt me-2"></i> Adresse</h5>
                        <p><?php echo nl2br(htmlspecialchars($client['adresse'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Commandes du client -->
        <h3 class="mb-4"><i class="bi bi-cart me-2"></i> Commandes</h3>
        <?php if (empty($commandes)): ?>
            <div class="alert alert-info">Ce client n'a pas encore passé de commandes.</div>
        <?php else: ?>
            <?php foreach ($commandes as $commande): ?>
                <div class="card order-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Commande #<?php echo htmlspecialchars($commande['id']); ?></h5>
                                <div class="order-date">
                                    Passée le <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?>
                                </div>
                            </div>
                            <div>
                                <span class="order-status
                                    <?php echo ($commande['statut'] === 'Livrée') ? 'status-livree' : 'status-en-attente'; ?>">
                                    <?php echo htmlspecialchars($commande['statut']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if (isset($commande['produit_nom'])): ?>
                        <div class="product-info mt-3">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($commande['produit_image'])): ?>
                                <img src="<?php echo htmlspecialchars($commande['produit_image']); ?>"
                                     alt="<?php echo htmlspecialchars($commande['produit_nom']); ?>"
                                     class="product-image me-3">
                                <?php endif; ?>
                                <div>
                                    <h6><?php echo htmlspecialchars($commande['produit_nom']); ?></h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($commande['produit_description']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-2">
                            <div>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($commande['statut']); ?>
                                </span>
                            </div>
                            <div>
                                <strong>Total: <?php echo number_format($commande['total_prix'], 0, ',', ' '); ?> FCFA</strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
</body>
</html>
