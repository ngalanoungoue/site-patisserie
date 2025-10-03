<?php
session_start();
// Vérifier si l'utilisateur est bien l'administrateur
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
// Inclure la connexion à la base de données
include 'db.php';

// Récupérer les données importantes pour le tableau de bord
try {
    // Nombre total de commandes
    $stmt_commandes = $connexion->query("SELECT COUNT(*) FROM commandes");
    $total_commandes = $stmt_commandes->fetchColumn();

    // Nombre total de clients
    $stmt_clients = $connexion->query("SELECT COUNT(*) FROM clients");
    $total_clients = $stmt_clients->fetchColumn();

    // Chiffre d'affaires total
    $stmt_ca = $connexion->query("SELECT SUM(total_prix) FROM commandes");
    $chiffre_affaires = $stmt_ca->fetchColumn();

    // Commandes par statut
    $stmt_statut = $connexion->query("
        SELECT statut, COUNT(*) as count
        FROM commandes
        GROUP BY statut
    ");
    $commandes_par_statut = $stmt_statut->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les 5 dernières commandes
    $stmt_dernieres_commandes = $connexion->query("
        SELECT c.*, cl.nom AS client_nom, cl.email AS client_email
        FROM commandes c
        JOIN clients cl ON c.client_id = cl.id
        ORDER BY c.date_commande DESC
        LIMIT 5
    ");
    $dernieres_commandes = $stmt_dernieres_commandes->fetchAll(PDO::FETCH_ASSOC);

    // Produits les plus vendus
    $stmt_produits_populaires = $connexion->query("
        SELECT p.nom as produit_nom, SUM(lc.quantite_lot) as quantite_totale
        FROM lignes_commandes lc
        JOIN lots l ON lc.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        GROUP BY p.nom
        ORDER BY quantite_totale DESC
        LIMIT 3
    ");
    $produits_populaires = $stmt_produits_populaires->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Gérer l'erreur de base de données
    $error = "Erreur de base de données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <style>
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card i {
            font-size: 2rem;
            opacity: 0.7;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
            border-radius: 10px;
        }
        .status-en-attente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-livree {
            background-color: #d4edda;
            color: #155724;
        }
        .status-annulee {
            background-color: #f8d7da;
            color: #721c24;
        }
        .sidebar {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #495057;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar py-4">
                <div class="text-center mb-4">
                    <i class="fas fa-tachometer-alt fa-2x mb-3"></i>
                    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
                    <h4 class="h5">Tableau de Bord</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">
                            <i class="fas fa-home me-2"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_orders.php">
                            <i class="fas fa-shopping-bag me-2"></i> Commandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_products.php">
                            <i class="fas fa-box-open me-2"></i> Produits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_clients.php">
                            <i class="fas fa-users me-2"></i> Clients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_messages.php">
                            <i class="bi bi-envelope me-2"></i> Messages
                            <?php
                            try {
                                $stmt = $connexion->prepare("SELECT COUNT(*) FROM messages WHERE statut = 'non lu'");
                                $stmt->execute();
                                $unread_count = $stmt->fetchColumn();
                                if ($unread_count > 0) {
                                    echo '<span class="badge bg-primary rounded-pill float-end">' . $unread_count . '</span>';
                                }
                            } catch (PDOException $e) {
                                // Gérer l'erreur si nécessaire
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_settings.php">
                            <i class="fas fa-cog me-2"></i> Paramètres
                        </a>
                    </li>
                </ul>
                <div class="mt-4">
                    <a class="btn btn-danger w-100" href="admin_logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                    </a>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-auto px-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
                    <h1 class="h2">Tableau de Bord</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar-alt me-1"></i> Ce mois
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Statistiques principales -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="stat-label">Total Commandes</p>
                                        <h3 class="stat-value"><?php echo $total_commandes; ?></h3>
                                    </div>
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="mt-3">
                                    <small>+12% vs mois dernier</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="stat-label">Chiffre d'Affaires</p>
                                        <h3 class="stat-value"><?php echo number_format($chiffre_affaires, 0, ',', ' '); ?> FCFA</h3>
                                    </div>
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="mt-3">
                                    <small>+8% vs mois dernier</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="stat-label">Total Clients</p>
                                        <h3 class="stat-value"><?php echo $total_clients; ?></h3>
                                    </div>
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="mt-3">
                                    <small>+5% vs mois dernier</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Produits populaires et statut des commandes -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i> Produits Populaires</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($produits_populaires)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Produit</th>
                                                    <th class="text-end">Ventes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($produits_populaires as $produit): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($produit['produit_nom']); ?></td>
                                                        <td class="text-end"><?php echo $produit['quantite_totale']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Aucune donnée disponible</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Statut des Commandes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($commandes_par_statut)): ?>
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Aucune donnée disponible</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dernières commandes -->
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Dernières Commandes</h5>
                        <a href="admin_orders.php" class="btn btn-sm btn-outline-primary">Voir toutes</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($dernieres_commandes)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Email</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th class="text-end">Total</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dernieres_commandes as $commande): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($commande['id']); ?></td>
                                                <td><?php echo htmlspecialchars($commande['client_nom']); ?></td>
                                                <td><?php echo htmlspecialchars($commande['client_email']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                                <td>
                                                    <span class="badge
                                                        <?php
                                                        if ($commande['statut'] === 'Livrée') echo 'status-livree';
                                                        elseif ($commande['statut'] === 'Annulée') echo 'status-annulee';
                                                        else echo 'status-en-attente';
                                                        ?>">
                                                        <?php echo htmlspecialchars($commande['statut']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end"><?php echo number_format($commande['total_prix'], 0, ',', ' '); ?> FCFA</td>
                                                <td>
                                                    <a href="admin_order_details.php?id=<?php echo $commande['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Voir
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Aucune commande récente</div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Inclure Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialiser le graphique des statuts de commandes
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statusChart');
            if (ctx) {
                const data = {
                    labels: <?php echo json_encode(array_column($commandes_par_statut, 'statut')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($commandes_par_statut, 'count')); ?>,
                        backgroundColor: [
                            '#ffc107', // En attente (jaune)
                            '#28a745', // Livrée (vert)
                            '#dc3545'  // Annulée (rouge)
                        ],
                        borderWidth: 1
                    }]
                };

                new Chart(ctx, {
                    type: 'pie',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
