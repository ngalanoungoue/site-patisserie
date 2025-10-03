<?php
// menu.php
// Inclus le fichier de connexion à la base de données
include 'db.php';

// Fonctions non utilisées (conservées comme dans votre code)
function getProduitLots($connexion, $produit_id) {
    // Reste inchangée
}
function getProduitOptions($connexion, $produit_id) {
    // Reste inchangée
}

// Logique pour le chargement efficace des données
$produits_map = []; // Pour stocker les produits, les lots et les options ensemble
try {
    // ÉTAPE A : Récupérer TOUS les produits (1 requête)
    $stmt_produits = $connexion->prepare("SELECT id, nom, description, image_url FROM produits");
    $stmt_produits->execute();
    $produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

    // Initialiser chaque produit avec des tableaux vides pour les lots et les options
    foreach ($produits as $produit) {
        $produit['lots'] = [];
        $produit['options'] = [];
        $produits_map[$produit['id']] = $produit;
    }

    // ÉTAPE B : Récupérer TOUS les lots (1 requête)
    $stmt_lots = $connexion->prepare("SELECT id, produit_id, quantite, prix FROM lots ORDER BY quantite ASC");
    $stmt_lots->execute();
    $lots = $stmt_lots->fetchAll(PDO::FETCH_ASSOC);

    // Lier les lots à leurs produits
    foreach ($lots as $lot) {
        $produit_id = $lot['produit_id'];
        if (isset($produits_map[$produit_id])) {
            $produits_map[$produit_id]['lots'][] = $lot;
        }
    }

    // ÉTAPE C : Récupérer TOUTES les options (1 requête)
    $stmt_options = $connexion->prepare("SELECT id, produit_id, nom, image_url FROM options_produit");
    $stmt_options->execute();
    $options = $stmt_options->fetchAll(PDO::FETCH_ASSOC);

    // Lier les options à leurs produits
    foreach ($options as $option) {
        $produit_id = $option['produit_id'];
        if (isset($produits_map[$produit_id])) {
            $produits_map[$produit_id]['options'][] = $option;
        }
    }

    // Convertir la map en tableau pour la boucle foreach HTML
    $produits_affiches = array_values($produits_map);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Une erreur est survenue lors du chargement des produits: " . $e->getMessage() . "</div>";
    die();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Les Biscuits de Maman - Menu</title>
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .produit-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .produit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .produit-card img {
            height: 200px;
            object-fit: cover;
        }
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ff6600;
            cursor: pointer;
        }
        .video-preview-container {
            position: relative;
            margin-top: 3rem;
        }
        .video-part {
            position: relative;
        }
        .video-part:hover .play-button {
            opacity: 1;
        }
        .play-button {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.html">
                    <img src="IMAGES&VIDEOS-SITES/VOTRE MENU.png" alt="Logo" class="logo-img">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="index.html"><i class="fas fa-home me-1"></i>Accueil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="menu.php"><i class="fas fa-cookie-bite me-1"></i>Menu</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="histoire.html"><i class="fas fa-book-open me-1"></i>Notre Histoire</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="panier.html"><i class="fas fa-shopping-basket me-1"></i>Panier</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.html"><i class="fas fa-envelope me-1"></i>Contact</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="livraison.html"><i class="fas fa-truck-moving me-1"></i>Conditions de livraison</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container py-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <h2 class="text-center mb-5">Découvrez nos délicieuses créations 🍪</h2>

            <?php foreach ($produits_affiches as $produit): ?>
                <div class="categorie-produits mb-5">
                    <h3 class="text-center mb-4"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                    <p class="text-center mb-5"><?php echo nl2br(htmlspecialchars($produit['description'])); ?></p>

                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php if (!empty($produit['options'])): ?>
                            <?php foreach ($produit['options'] as $option): ?>
                                <div class="col">
                                    <div class="card h-100 produit-card">
                                        <img src="<?php echo htmlspecialchars($option['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($option['nom']); ?>">
                                        <div class="card-body text-center">
                                            <h5 class="card-title"><?php echo htmlspecialchars($option['nom']); ?></h5>
                                            <?php foreach ($produit['lots'] as $lot): ?>
                                                <div class="mb-3">
                                                    <p class="mb-1">Lot de <?php echo htmlspecialchars($lot['quantite']); ?> biscuits</p>
                                                    <p class="fw-bold"><?php echo number_format($lot['prix'], 0, ',', ' '); ?> FCFA</p>
                                                    <button class="btn btn-primary ajouter-panier w-100"
                                                        data-produit-id="<?php echo $produit['id']; ?>"
                                                        data-produit-nom="<?php echo htmlspecialchars($produit['nom']); ?>"
                                                        data-lot-id="<?php echo $lot['id']; ?>"
                                                        data-quantite-lot="<?php echo $lot['quantite']; ?>"
                                                        data-prix="<?php echo $lot['prix']; ?>"
                                                        data-option-nom="<?php echo htmlspecialchars($option['nom']); ?>"
                                                        data-option-id="<?php echo $option['id']; ?>">
                                                        <i class="fas fa-cart-plus me-1"></i> Ajouter au panier
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($produit['lots'] as $lot): ?>
                                <div class="col">
                                    <div class="card h-100 produit-card">
                                        <img src="<?php echo htmlspecialchars($produit['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($produit['nom']); ?>">
                                        <div class="card-body text-center">
                                            <h5 class="card-title"><?php echo htmlspecialchars($produit['nom']); ?></h5>
                                            <p class="card-text">Lot de <?php echo htmlspecialchars($lot['quantite']); ?> biscuits</p>
                                            <p class="fw-bold"><?php echo number_format($lot['prix'], 0, ',', ' '); ?> FCFA</p>
                                            <button class="btn btn-primary ajouter-panier w-100"
                                                data-produit-id="<?php echo $produit['id']; ?>"
                                                data-produit-nom="<?php echo htmlspecialchars($produit['nom']); ?>"
                                                data-lot-id="<?php echo $lot['id']; ?>"
                                                data-quantite-lot="<?php echo $lot['quantite']; ?>"
                                                data-prix="<?php echo $lot['prix']; ?>">
                                                <i class="fas fa-cart-plus me-1"></i> Ajouter au panier
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <hr class="my-5">
            <?php endforeach; ?>
        <?php endif; ?>
         <section>            <div class="video-preview-container mt-5">                <div class="description-part">                    <h3>Une touche d'amour dans chaque biscuit ❤️</h3>                    <p>Découvrez les coulisses de notre passion ! Cette vidéo vous montre comment chaque biscuit est préparé avec soin, selon la recette de famille. Notre secret ? La confiture d'ananas, faite maison avec des fruits frais. Chaque étape, de la pâte à la garniture, est un geste d'amour pour vous offrir une gourmandise unique.</p>                </div>                <div class="video-part">                    <a href="IMAGES&VIDEOS-SITES/COMMENT PREPARER DES BISCUITS A LA CONFITURE D'ANANAS.mp4" target="_blank">                        <img src="IMAGES&VIDEOS-SITES/femme-noire-a-la-tete-d-une-petite-entreprise.jpg" alt="Voir la préparation de nos biscuits" class="img-fluid rounded shadow-sm">                        <div class="play-button"><i class="fas fa-play"></i></div>                    </a>                </div>            </div>        </section>
    </main>

    <footer class="bg-dark text-white text-center py-4 mt-auto">
        <div class="container">
            <div class="d-flex justify-content-center align-items-center gap-4 mb-4">
                <a href="mailto:mireillestella8@gmail.com" aria-label="E-mail" class="text-white"><i class="fas fa-envelope fa-lg"></i></a>
                <a href="https://www.instagram.com/mire_illestella" target="_blank" aria-label="Instagram" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="https://youtube.com/shorts/09HSs9nAgo8" target="_blank" aria-label="YouTube" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                <a href="https://www.tiktok.com/@stellamireille6" target="_blank" aria-label="TikTok" class="text-white"><i class="fab fa-tiktok fa-lg"></i></a>
                <a href="https://www.facebook.com/share/17KCWTpVCj/" target="_blank" aria-label="Facebook" class="text-white"><i class="fab fa-facebook-f fa-lg"></i></a>
                <a href="https://www.linkedin.com/in/stella-noungoue" target="_blank" aria-label="LinkedIn" class="text-white"><i class="fab fa-linkedin-in fa-lg"></i></a>
                <a href="https://stellachill.wordpress.com" target="_blank" aria-label="WordPress" class="text-white"><i class="fab fa-wordpress fa-lg"></i></a>
            </div>
            <p class="mb-0">&copy; 2025 Les biscuits de maman. Tous droits réservés.
                <a href="politique-confidentialite.html" class="text-white text-decoration-none ms-3">Politique de Confidentialité</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="menu.js"></script>
</body>
</html>
