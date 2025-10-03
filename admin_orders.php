<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Récupération des commandes avec filtres
try {
    $query = "SELECT c.*, cl.nom AS client_nom, cl.email AS client_email
              FROM commandes c
              JOIN clients cl ON c.client_id = cl.id";

    $conditions = [];
    $params = [];

    // Filtre par statut
    if ($filter_status && $filter_status != 'all') {
        $conditions[] = "c.statut = :statut";
        $params[':statut'] = $filter_status;
    }

    // Recherche
    if ($search_term) {
        $conditions[] = "(cl.nom LIKE :search OR cl.email LIKE :search OR c.id LIKE :search)";
        $params[':search'] = "%$search_term%";
    }

    // Filtre par date
    if ($date_start) {
        $conditions[] = "c.date_commande >= :date_start";
        $params[':date_start'] = $date_start;
    }
    if ($date_end) {
        $conditions[] = "c.date_commande <= :date_end";
        $params[':date_end'] = $date_end;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY c.date_commande DESC";

    $stmt = $connexion->prepare($query);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul des statistiques
    $stmt_stats = $connexion->query("
        SELECT
            COUNT(*) as total_commandes,
            SUM(CASE WHEN statut = 'En attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN statut = 'Livrée' THEN 1 ELSE 0 END) as livrees,
            SUM(total_prix) as total_ca
        FROM commandes
    ");
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $commandes = [];
    $message = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";
    $stats = [
        'total_commandes' => 0,
        'en_attente' => 0,
        'livrees' => 0,
        'total_ca' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Commandes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        .order-card {
            transition: all 0.2s ease;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
            border-radius: 10px;
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
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: white;
            height: 100%;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
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
                            <a class="nav-link active" href="admin_orders.php">Commandes</a>
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
            <h1 class="h2"><i class="bi bi-cart-check me-2"></i> Gestion des Commandes</h1>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>

        <?php echo $message; ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card" style="background-color: #4e73df;">
                    <div class="card-body">
                        <div class="stats-value"><?php echo $stats['total_commandes']; ?></div>
                        <div class="stats-label">Total Commandes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" style="background-color: #1cc88a;">
                    <div class="card-body">
                        <div class="stats-value"><?php echo number_format($stats['total_ca'], 0, ',', ' '); ?> FCFA</div>
                        <div class="stats-label">Chiffre d'affaires</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" style="background-color: #36b9cc;">
                    <div class="card-body">
                        <div class="stats-value"><?php echo $stats['en_attente']; ?></div>
                        <div class="stats-label">En attente</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" style="background-color: #f6c23e;">
                    <div class="card-body">
                        <div class="stats-value"><?php echo $stats['livrees']; ?></div>
                        <div class="stats-label">Livrées</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card filter-section mb-4">
            <div class="card-body">
                <form method="GET" action="admin_orders.php" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Recherche</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="ID, nom ou email">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if ($search_term): ?>
                                <a href="admin_orders.php" class="btn btn-outline-danger">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="status">
                            <option value="">Tous</option>
                            <option value="En attente" <?php echo $filter_status == 'En attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="Livrée" <?php echo $filter_status == 'Livrée' ? 'selected' : ''; ?>>Livrée</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date début</label>
                        <input type="date" class="form-control" name="date_start" value="<?php echo $date_start; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date fin</label>
                        <input type="date" class="form-control" name="date_end" value="<?php echo $date_end; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des commandes -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Frais Livraison</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($commandes)): ?>
                        <?php foreach ($commandes as $commande): ?>
                            <tr class="order-card">
                                <td><?php echo htmlspecialchars($commande['id']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($commande['client_nom']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($commande['client_email']); ?></div>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                <td><?php echo number_format($commande['total_prix'], 0, ',', ' '); ?> FCFA</td>
                                <td>
                                    <span class="badge
                                        <?php echo $commande['statut'] === 'Livrée' ? 'status-livree' : 'status-en-attente'; ?>">
                                        <?php echo htmlspecialchars($commande['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($commande['frais_livraison'], 0, ',', ' '); ?> FCFA</td>
                                <td>
                                    <a href="admin_order_details.php?id=<?php echo $commande['id']; ?>" class="btn btn-sm btn-info" title="Voir détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Aucune commande trouvée avec ces critères.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-end fw-bold">
                            Total: <?php echo number_format(array_sum(array_column($commandes, 'total_prix')), 0, ',', ' '); ?> FCFA
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
</body>
</html>
