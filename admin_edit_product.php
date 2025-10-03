<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';

$message = '';

// Traitement de la mise à jour du produit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $image_url = $_POST['image_url'];

    try {
        $stmt = $connexion->prepare("UPDATE produits SET nom = ?, description = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$nom, $description, $image_url, $id]);
        $message = "<div class='alert alert-success'>Produit mis à jour avec succès !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour: " . $e->getMessage() . "</div>";
    }
}

// Récupérer les données du produit à modifier
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $connexion->prepare("SELECT * FROM produits WHERE id = ?");
        $stmt->execute([$id]);
        $produit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produit) {
            $message = "<div class='alert alert-warning'>Produit non trouvé.</div>";
            $produit = ['id' => '', 'nom' => '', 'description' => '', 'image_url' => ''];
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur de base de données.</div>";
        $produit = ['id' => '', 'nom' => '', 'description' => '', 'image_url' => ''];
    }
} else {
    header("Location: admin_products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Produit - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="admin_dashboard.php">Tableau de Bord Admin</a>
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="btn btn-danger" href="admin_logout.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container py-5">
        <h1 class="mb-4">Modifier le Produit</h1>
        <?php echo $message; ?>
        <a href="admin_products.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Retour à la liste des produits</a>

        <div class="card">
            <div class="card-header">Modifier: <?php echo htmlspecialchars($produit['nom']); ?></div>
            <div class="card-body">
                <form action="admin_edit_product.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($produit['id']); ?>">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du produit</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($produit['nom']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($produit['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image_url" class="form-label">URL de l'image</label>
                        <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($produit['image_url']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>