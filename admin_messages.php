<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php'; // Chemin vers votre fichier de connexion à la base de données



// Traitement des actions (marquer comme lu, supprimer, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['message_id'])) {
        $message_id = $_POST['message_id'];
        $action = $_POST['action'];

        try {
            if ($action === 'marquer_lu') {
                $stmt = $connexion->prepare("UPDATE messages SET statut = 'lu' WHERE id = ?");
                $stmt->execute([$message_id]);
            } elseif ($action === 'supprimer') {
                $stmt = $connexion->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$message_id]);
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données : " . $e->getMessage();
        }
    }
}

// Récupération des messages
try {
    $stmt = $connexion->prepare("SELECT * FROM messages ORDER BY date_envoi DESC");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
    <style>
        .message-card {
            transition: all 0.3s ease;
            border-left: 4px solid #6c757d;
        }
        .message-card.unread {
            border-left: 4px solid #0d6efd;
            background-color: #f8f9fa;
        }
        .message-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .message-status {
            font-size: 0.8rem;
            font-weight: bold;
        }
        .message-status.unread {
            color: #0d6efd;
        }
        .message-status.read {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; // Inclure votre barre de navigation admin ?>

    <div class="container-fluid mt-4">
        <a class="navbar-brand" href="admin_dashboard.php">Tableau de Bord Admin</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
        <div class="row">
            <?php include 'admin_sidebar.php'; // Inclure votre barre latérale admin ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Messages</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Action effectuée avec succès.</div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Boîte de réception</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($messages)): ?>
                            <div class="alert alert-info">Aucun message trouvé.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Expéditeur</th>
                                            <th>Email</th>
                                            <th>Sujet</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                            <tr class="message-row <?php echo $message['statut'] === 'non lu' ? 'unread' : ''; ?>" data-message-id="<?php echo $message['id']; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($message['nom']); ?></strong>
                                                </td>
                                                <td>
                                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-sm btn-outline-primary">
                                                        <?php echo htmlspecialchars($message['email']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($message['sujet'] ?: 'Pas de sujet'); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $message['statut'] === 'non lu' ? 'bg-primary' : 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($message['statut']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="admin_message_detail.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i> Voir
                                                        </a>
                                                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                            <input type="hidden" name="action" value="supprimer">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i> Supprimer
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Marquer un message comme lu lorsqu'on clique dessus
        document.querySelectorAll('.message-row').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('a, button, form')) return; // Ignorer si clic sur un lien ou bouton

                const messageId = this.getAttribute('data-message-id');
                if (this.classList.contains('unread')) {
                    fetch('admin_messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=marquer_lu&message_id=${messageId}`
                    }).then(response => {
                        if (response.ok) {
                            this.classList.remove('unread');
                            this.querySelector('.message-status').classList.remove('unread');
                            this.querySelector('.message-status').classList.add('read');
                            this.querySelector('.badge').classList.remove('bg-primary');
                            this.querySelector('.badge').classList.add('bg-secondary');
                            this.querySelector('.badge').textContent = 'Lu';
                        }
                    });
                }
                window.location.href = `admin_message_detail.php?id=${messageId}`;
            });
        });
    </script>
</body>
</html>
