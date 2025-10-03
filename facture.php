<?php
// facture.php
const SHIPPING_FEES = 1000.0;
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de commande invalide.");
}

$commande_id = $_GET['id'];

try {
    // 1. Récupérer les informations de la commande et du client
    $stmt_commande = $connexion->prepare("
        SELECT c.id AS commande_id, c.date_commande, c.total_prix, c.statut, c.commentaires,
               cl.nom AS client_nom, cl.email AS client_email, cl.telephone AS client_telephone, cl.adresse AS client_adresse
        FROM commandes c
        JOIN clients cl ON c.client_id = cl.id
        WHERE c.id = ?
    ");
    $stmt_commande->execute([$commande_id]);
    $commande = $stmt_commande->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        die("Commande non trouvée.");
    }

    // 2. Récupérer les lignes de commande
    $stmt_lignes = $connexion->prepare("
        SELECT lc.quantite_lot, lc.sous_total,
               p.nom AS produit_nom, op.nom AS option_nom, l.quantite AS quantite_lot_unite
        FROM lignes_commandes lc
        JOIN lots l ON lc.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        LEFT JOIN options_produit op ON lc.option_id = op.id
        WHERE lc.commande_id = ?
    ");
    $stmt_lignes->execute([$commande_id]);
    $lignes_commande = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calculer le sous-total des articles
    $total_articles = 0;
    foreach ($lignes_commande as $ligne) {
        $total_articles += $ligne['sous_total'];
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #<?php echo htmlspecialchars($commande['commande_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .facture-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container facture-container">
        <div class="row mb-4">
            <div class="col-6">
                <h1>Facture</h1>
                <p>Date : <?php echo date("d/m/Y", strtotime($commande['date_commande'])); ?></p>
            </div>
            <div class="col-6 text-end">
                <p><strong>Facture #<?php echo htmlspecialchars($commande['commande_id']); ?></strong></p>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-md-6">
                <h4>Informations Client</h4>
                <p><strong>Nom :</strong> <?php echo htmlspecialchars($commande['client_nom']); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($commande['client_email']); ?></p>
                <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($commande['client_telephone']); ?></p>
                <p><strong>Adresse :</strong> <?php echo nl2br(htmlspecialchars($commande['client_adresse'])); ?></p>
            </div>
        </div>

        <h4 class="mb-3">Détails des Articles</h4>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Article</th>
                    <th>Quantité (Lots)</th>
                    <th>Prix unitaire du Lot</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes_commande as $ligne): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($ligne['produit_nom']); ?>
                            <?php if (!empty($ligne['option_nom'])): ?>
                                - <?php echo htmlspecialchars($ligne['option_nom']); ?>
                            <?php endif; ?>
                            <br><small>Lot de <?php echo htmlspecialchars($ligne['quantite_lot_unite']); ?> unités</small>
                        </td>
                        <td><?php echo htmlspecialchars($ligne['quantite_lot']); ?></td>
                        <td><?php echo number_format($ligne['sous_total'] / $ligne['quantite_lot'], 0, ',', ' '); ?> FCFA</td>
                        <td><?php echo number_format($ligne['sous_total'], 0, ',', ' '); ?> FCFA</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Sous-total Articles</th>
                    <th><?php echo number_format($total_articles, 0, ',', ' '); ?> FCFA</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Frais de livraison</th>
                    <th><?php echo number_format(SHIPPING_FEES, 0, ',', ' '); ?> FCFA</th>
                </tr>
                <tr class="table-success">
                    <th colspan="3" class="text-end">TOTAL FINAL</th>
                    <th><?php echo number_format($commande['total_prix'], 0, ',', ' '); ?> FCFA</th>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($commande['commentaires'])): ?>
            <div class="mt-4 p-3 border rounded bg-light">
                <h5 class="text-primary">Commentaires :</h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($commande['commentaires'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="d-grid gap-2 mt-5 no-print">
            <button class="btn btn-primary btn-lg" onclick="window.print()">Imprimer la facture</button>
        </div>
    </div>
</body>
</html>
