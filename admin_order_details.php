<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';
$message = null;

// 1. Récupération et validation de l'ID de la commande
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "ID de commande non valide.";
    header("Location: admin_orders.php");
    exit();
}

$orderId = (int)$_GET['id'];

try {
    // 2. Récupération des informations de la commande et du client
    $stmt = $connexion->prepare("
        SELECT c.*, cl.nom AS client_nom, cl.email AS client_email, cl.adresse AS client_adresse, cl.telephone AS client_telephone
        FROM commandes c
        JOIN clients cl ON c.client_id = cl.id
        WHERE c.id = :id
    ");
    $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        $_SESSION['message'] = "Commande introuvable.";
        header("Location: admin_orders.php");
        exit();
    }

    // Détermination de la classe CSS du badge
    $statut = htmlspecialchars($commande['statut']);
    $badge_class = 'bg-secondary';
    if ($statut === 'Livrée') {
        $badge_class = 'bg-success';
    } elseif ($statut === 'En attente') {
        $badge_class = 'bg-warning text-dark';
    } elseif ($statut === 'Annulée') {
        $badge_class = 'bg-danger';
    }

    // 3. Récupération des détails des produits (lignes_commandes)
    $stmt_details = $connexion->prepare("
        SELECT 
            lc.quantite_lot, 
            lc.sous_total, 
            p.nom AS produit_nom,
            op.nom AS option_nom, 
            l.prix AS lot_prix_unitaire
        FROM lignes_commandes lc
        JOIN lots l ON lc.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        LEFT JOIN options_produit op ON lc.option_id = op.id 
        WHERE lc.commande_id = :order_id
    ");
    $stmt_details->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt_details->execute();
    $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $details = [];
    $message = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Commande #<?php echo $orderId; ?></title>
    <!-- Inclure SweetAlert2 pour les notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- Pour les icônes Font Awesome -->
    <script src="https://kit.fontawesome.com/36d988521c.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        /* Styles pour l'impression */
        @media print {
            /* Masquer les éléments inutiles lors de l'impression */
            header, footer, .no-print, .btn, .input-group, .alert, .navbar, .container-fluid {
                display: none !important;
            }

            /* Styles spécifiques pour la facture */
            body {
                background-color: white;
                font-family: Arial, sans-serif;
            }

            .print-area {
                margin: 0;
                padding: 20px;
            }

            /* Style pour le tableau lors de l'impression */
            table {
                page-break-inside: avoid; /* Évite la coupure des lignes de tableau entre pages */
            }

            /* Style pour les lignes du tableau */
            tr {
                page-break-inside: avoid; /* Évite la coupure des lignes entre pages */
            }

            /* Style pour l'en-tête et le pied de page */
            h1, h2, h3, h4, h5, h6 {
                page-break-after: avoid; /* Évite la coupure après les titres */
                page-break-before: avoid; /* Évite la coupure avant les titres */
            }

            /* Style pour les détails de la commande */
            .card {
                border: 1px solid #ddd;
                margin-bottom: 20px;
            }

            /* Style pour le tableau de la facture */
            .table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .table th, .table td {
                border: 1px solid #ddd;
                padding: 8px;
            }

            .table th {
                background-color: #f2f2f2;
            }

            /* Style pour les totaux */
            .table-primary {
                background-color: #e3f2fd !important;
            }

            /* Style pour les informations client */
            .card-body p {
                margin-bottom: 5px;
            }

            /* Style pour les informations client et statut */
            .client-info {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="admin_dashboard.php">Tableau de Bord Admin</a>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_orders.php">Gérer les commandes</a>
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
    <main class="container py-5">
        <a href="admin_orders.php" class="btn btn-outline-secondary mb-4 no-print"><i class="fas fa-arrow-left"></i> Retour aux commandes</a>

        <div class="print-area">
            <h1 class="mb-4">Détails de la Commande #<?php echo htmlspecialchars($orderId); ?></h1>

            <?php if (isset($message)) echo $message; ?>
            <!-- SECTION INFOS CLIENT & STATUT -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informations Client & Statut</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4 mb-md-0 border-end">
                            <h6>Informations Client</h6>
                            <p><strong>Nom :</strong> <?php echo htmlspecialchars($commande['client_nom']); ?></p>
                            <p><strong>Email :</strong> <a href="mailto:<?php echo htmlspecialchars($commande['client_email']); ?>"><?php echo htmlspecialchars($commande['client_email']); ?></a></p>
                            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($commande['client_telephone']); ?></p>
                            <p><strong>Adresse :</strong> <?php echo nl2br(htmlspecialchars($commande['client_adresse'])); ?></p>
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <h6>Gestion de la Commande</h6>
                            <p><strong>Date de Commande :</strong> <?php echo htmlspecialchars($commande['date_commande']); ?></p>
                            <p><strong>Statut Actuel :</strong> <span id="statut-badge" class="badge <?php echo $badge_class; ?> fs-6"><?php echo $statut; ?></span></p>
                            <!-- Le formulaire de mise à jour du statut est masqué lors de l'impression -->
                            <div class="input-group mt-3 no-print">
                                <select class="form-select" id="nouveau-statut">
                                    <option value="En attente" <?php if ($statut === 'En attente') echo 'selected'; ?>>En attente</option>
                                    <option value="Livrée" <?php if ($statut === 'Livrée') echo 'selected'; ?>>Livrée</option>
                                    <option value="Annulée" <?php if ($statut === 'Annulée') echo 'selected'; ?>>Annulée</option>
                                </select>
                                <button class="btn btn-success" type="button" onclick="mettreAJourStatut(<?php echo $orderId; ?>)" class="no-print">
                                    Mettre à jour
                                </button>
                            </div>
                            <small class="text-muted no-print">Changer le statut affectera l'affichage client.</small>
                        </div>
                    </div>

                    <?php if (!empty($commande['commentaires'])): ?>
                        <div class="alert alert-info mt-4">
                            <strong>Commentaires Client :</strong> <?php echo nl2br(htmlspecialchars($commande['commentaires'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- SECTION DÉTAILS FACTURE -->
            <h2 class="mb-3">Détails de la Facture</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Produit (Lot)</th>
                            <th class="text-end">Prix Unitaire du Lot</th>
                            <th class="text-end">Quantité de Lots</th>
                            <th class="text-end">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotal = 0;
                        if (!empty($details)): 
                        ?>
                            <?php foreach ($details as $item): ?>
                                <?php 
                                $quantite = $item['quantite_lot'];
                                $sousTotal = $item['sous_total']; 
                                $prixUnitaireLot = $item['lot_prix_unitaire'];
                                $grandTotal += $sousTotal;
                                // Affichage du nom du produit avec l'option
                                $nom_a_afficher = htmlspecialchars($item['produit_nom']);
                                if (!empty($item['option_nom'])) {
                                    $nom_a_afficher .= ' - ' . htmlspecialchars($item['option_nom']);
                                }
                                ?>
                                <tr>
                                    <td><?php echo $nom_a_afficher; ?></td>
                                    <td class="text-end"><?php echo number_format($prixUnitaireLot, 0, ',', ' '); ?> FCFA</td>
                                    <td class="text-end"><?php echo htmlspecialchars($quantite); ?></td>
                                    <td class="text-end"><?php echo number_format($sousTotal, 0, ',', ' '); ?> FCFA</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucun produit trouvé pour cette commande.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <td colspan="3" class="text-end">TOTAL FINAL</td>
                            <td class="text-end"><?php echo number_format($commande['total_prix'], 0, ',', ' '); ?> FCFA</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="text-center mt-4 no-print">
                <button class="btn btn-primary btn-lg" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Imprimer la Facture
                </button>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /**
         * Fonction JavaScript pour mettre à jour le statut via AJAX.
         * @param {number} commandeId L'ID de la commande à mettre à jour.
         */
        function mettreAJourStatut(commandeId) {
            const selectElement = document.getElementById('nouveau-statut');
            const nouveauStatut = selectElement.value;
            Swal.fire({
                title: 'Confirmer la mise à jour ?',
                text: `Voulez-vous vraiment passer le statut à '${nouveauStatut}' ?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, changer le statut',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {

                    Swal.fire({
                        title: 'Mise à jour en cours...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    const formData = new FormData();
                    formData.append('commande_id', commandeId);
                    formData.append('nouveau_statut', nouveauStatut);
                    fetch('modifier_statut.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Statut mis à jour',
                                text: data.message || `La commande #${commandeId} est maintenant '${nouveauStatut}'.`
                            }).then(() => {
                                // Mettre à jour l'affichage dynamique sur la page
                                const badge = document.getElementById('statut-badge');
                                badge.textContent = nouveauStatut;

                                // Mettre à jour la classe CSS du badge
                                let new_badge_class = 'bg-secondary';
                                if (nouveauStatut === 'Livrée') {
                                    new_badge_class = 'bg-success';
                                } else if (nouveauStatut === 'En attente') {
                                    new_badge_class = 'bg-warning text-dark';
                                } else if (nouveauStatut === 'Annulée') {
                                    new_badge_class = 'bg-danger';
                                }
                                badge.className = `badge fs-6 ${new_badge_class}`;
                                // Assurez-vous que l'option sélectionnée est correcte
                                selectElement.value = nouveauStatut;
                            });
                        } else {
                            Swal.fire('Erreur', data.message || 'Impossible de mettre à jour le statut.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        Swal.fire('Erreur Réseau', 'Problème de connexion au serveur.', 'error');
                        console.error('Erreur:', error);
                    });
                }
            });
        }
    </script>
</body>
</html>
