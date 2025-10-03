<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = null;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_creation';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Récupération des clients avec filtres
try {
    $query = "SELECT * FROM clients";

    // Ajout des conditions de recherche
    if ($search_term) {
        $query .= " WHERE nom LIKE :search OR email LIKE :search OR telephone LIKE :search";
    }

    // Ajout du tri
    $valid_sort_columns = ['id', 'nom', 'email', 'date_creation', 'total_commandes'];
    if (in_array($sort_by, $valid_sort_columns)) {
        $query .= " ORDER BY $sort_by " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
    } else {
        $query .= " ORDER BY date_creation DESC";
    }

    $stmt = $connexion->prepare($query);

    if ($search_term) {
        $stmt->bindValue(':search', "%$search_term%");
    }

    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les statistiques
    $stmt_stats = $connexion->query("
        SELECT
            COUNT(*) as total_clients,
            COUNT(DISTINCT CASE WHEN date_creation >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN id END) as new_clients,
            SUM(
                (SELECT COUNT(*)
                 FROM commandes
                 WHERE client_id = clients.id)
            ) as total_orders
        FROM clients
    ");
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $clients = [];
    $message = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";
    $stats = [
        'total_clients' => 0,
        'new_clients' => 0,
        'total_orders' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Clients - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        .client-card {
            transition: all 0.2s ease;
            border-left: 4px solid #dee2e6;
        }
        .client-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stats-card {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
        }
        .stats-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .search-box {
            max-width: 300px;
        }
        .client-actions {
            display: flex;
            gap: 5px;
        }
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            color: #0d6efd;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            <h1 class="h2"><i class="bi bi-people me-2"></i> Gestion des Clients</h1>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>

        <?php
        // Affichage des messages
        if (isset($_SESSION['message'])) {
            echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['message']) . "</div>";
            unset($_SESSION['message']);
        }
        if (isset($message)) echo $message;
        ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="stats-value"><?php echo $stats['total_clients']; ?></div>
                        <div class="stats-label">Total Clients</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="stats-value"><?php echo $stats['new_clients']; ?></div>
                        <div class="stats-label">Nouveaux ce mois</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="stats-value"><?php echo $stats['total_orders']; ?></div>
                        <div class="stats-label">Total Commandes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="card filter-section mb-4">
            <div class="card-body">
                <form method="GET" action="admin_clients.php" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Recherche</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Nom, email ou téléphone">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if ($search_term): ?>
                                <a href="admin_clients.php" class="btn btn-outline-danger">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des clients -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="sortable" onclick="changeSort('id')">
                            ID <?php echo $sort_by === 'id' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?>
                        </th>
                        <th class="sortable" onclick="changeSort('nom')">
                            Nom <?php echo $sort_by === 'nom' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?>
                        </th>
                        <th>Email</th>
                        <th class="sortable" onclick="changeSort('date_creation')">
                            Inscrit le <?php echo $sort_by === 'date_creation' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <tr class="client-card">
                                <td><?php echo htmlspecialchars($client['id']); ?></td>
                                <td><?php echo htmlspecialchars($client['nom']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($client['date_creation'])); ?></td>
                                <td>
                                    <div class="client-actions">
                                        <a href="client_details.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info" title="Voir détails">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="delete_client.php?id=<?php echo $client['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Aucun client trouvé avec ces critères.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <script>
        // Fonction pour changer le tri
        function changeSort(column) {
            const currentOrder = '<?php echo $sort_order; ?>';
            const newOrder = (column === '<?php echo $sort_by; ?>' && currentOrder === 'ASC') ? 'DESC' : 'ASC';
            window.location.href = `admin_clients.php?sort=${column}&order=${newOrder}&search=<?php echo urlencode($search_term); ?>`;
        }

        // Gestion des paramètres d'URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const sort = urlParams.get('sort');
            const order = urlParams.get('order');

            // Mise en évidence de la colonne triée
            document.querySelectorAll('.sortable').forEach(header => {
                if (header.getAttribute('data-sort') === sort) {
                    header.classList.add('text-primary');
                }
            });
        });
    </script>
</body>
</html>
