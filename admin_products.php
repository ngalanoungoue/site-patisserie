<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';

// Traitement de l'ajout d'un produit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);
        $prix = isset($_POST['prix']) ? (float)$_POST['prix'] : 0;
        $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
        $categorie = isset($_POST['categorie']) ? trim($_POST['categorie']) : '';

        try {
            $stmt = $connexion->prepare("INSERT INTO produits (nom, description, image_url, prix, stock, categorie) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $description, $image_url, $prix, $stock, $categorie]);
            $message = "<div class='alert alert-success'>Produit ajouté avec succès !</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erreur lors de l'ajout du produit: " . $e->getMessage() . "</div>";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        try {
            // Vérifier si le produit existe
            $check = $connexion->prepare("SELECT id FROM produits WHERE id = ?");
            $check->execute([$product_id]);
            if ($check->fetch()) {
                $stmt = $connexion->prepare("DELETE FROM produits WHERE id = ?");
                $stmt->execute([$product_id]);
                $message = "<div class='alert alert-success'>Produit supprimé avec succès !</div>";
            } else {
                $message = "<div class='alert alert-warning'>Produit introuvable.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erreur lors de la suppression: " . $e->getMessage() . "</div>";
        }
    }
}

// Récupérer tous les produits avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $stmt_count = $connexion->query("SELECT COUNT(*) FROM produits");
    $total_products = $stmt_count->fetchColumn();
    $total_pages = ceil($total_products / $limit);

    $stmt_produits = $connexion->prepare("SELECT * FROM produits ORDER BY nom LIMIT ? OFFSET ?");
    $stmt_produits->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt_produits->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt_produits->execute();
    $produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produits = [];
    $message = "<div class='alert alert-danger'>Erreur de base de données: " . $e->getMessage() . "</div>";
}

// Récupérer les catégories pour le formulaire
try {
    $stmt_categories = $connexion->query("SELECT DISTINCT categorie FROM produits WHERE categorie IS NOT NULL AND categorie != ''");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Produits - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        .product-card {
            transition: transform 0.2s;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .product-img {
            height: 200px;
            object-fit: cover;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .search-box {
            max-width: 300px;
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
                            <a class="nav-link active" href="admin_products.php">Produits</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_clients.php">Clients</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="btn btn-danger" href="admin_logout.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2"><i class="bi bi-box-seam me-2"></i> Gestion des Produits</h1>
            <div class="d-flex gap-2">
                <div class="search-box">
                    <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un produit...">
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle me-1"></i> Ajouter
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select class="form-select" id="categoryFilter">
                            <option value="">Toutes catégories</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo htmlspecialchars($categorie); ?>">
                                    <?php echo htmlspecialchars($categorie); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="stockFilter">
                            <option value="">Disponibilité</option>
                            <option value="in_stock">En stock</option>
                            <option value="out_stock">Rupture</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des produits -->
        <div class="row g-4" id="productsContainer">
            <?php if (!empty($produits)): ?>
                <?php foreach ($produits as $produit): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card product-card h-100">
                            <img src="<?php echo htmlspecialchars($produit['image_url']); ?>"
                                 class="card-img-top product-img"
                                 alt="<?php echo htmlspecialchars($produit['nom']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($produit['nom']); ?></h5>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($produit['categorie'] ?: 'Sans catégorie'); ?></p>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($produit['description'], 0, 100)) . (strlen($produit['description']) > 100 ? '...' : '')); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><?php echo number_format($produit['prix'], 0, ',', ' '); ?> FCFA</span>
                                    <span class="badge <?php echo $produit['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $produit['stock'] > 0 ? $produit['stock'] . ' en stock' : 'Rupture'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-flex justify-content-between">
                                    <a href="admin_edit_product.php?id=<?php echo $produit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $produit['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Aucun produit trouvé.</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4" aria-label="Navigation des produits">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </main>

    <!-- Modal pour ajouter un produit -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un nouveau produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom du produit</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="prix" class="form-label">Prix (FCFA)</label>
                                    <input type="number" class="form-control" id="prix" name="prix" min="0" step="100" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo htmlspecialchars($categorie); ?>">
                                        <?php echo htmlspecialchars($categorie); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="nouvelle">+ Nouvelle catégorie</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label">URL de l'image</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <script>
        // Filtrage et recherche des produits
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const stockFilter = document.getElementById('stockFilter');
            const productsContainer = document.getElementById('productsContainer');

            function filterProducts() {
                const searchTerm = searchInput.value.toLowerCase();
                const category = categoryFilter.value;
                const stockFilterValue = stockFilter.value;

                const products = productsContainer.querySelectorAll('.product-card');

                products.forEach(product => {
                    const productName = product.querySelector('.card-title').textContent.toLowerCase();
                    const productCategory = product.querySelector('.card-text.text-muted').textContent;
                    const stockBadge = product.querySelector('.badge');

                    let show = true;

                    // Filtre de recherche
                    if (searchTerm && !productName.includes(searchTerm)) {
                        show = false;
                    }

                    // Filtre de catégorie
                    if (category && !productCategory.includes(category)) {
                        show = false;
                    }

                    // Filtre de stock
                    if (stockFilterValue === 'in_stock' && stockBadge.classList.contains('bg-danger')) {
                        show = false;
                    } else if (stockFilterValue === 'out_stock' && stockBadge.classList.contains('bg-success')) {
                        show = false;
                    }

                    product.style.display = show ? 'block' : 'none';
                });
            }

            searchInput.addEventListener('input', filterProducts);
            categoryFilter.addEventListener('change', filterProducts);
            stockFilter.addEventListener('change', filterProducts);
        });
    </script>
</body>
</html>
