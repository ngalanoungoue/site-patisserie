<?php
session_start();
// Vérification d'authentification
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}
include 'db.php';



if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_messages.php');
    exit;
}

$message_id = $_GET['id'];

// Récupération du message
try {
    $stmt = $connexion->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        header('Location: admin_messages.php');
        exit;
    }

    // Marquer le message comme lu
    if ($message['statut'] === 'non lu') {
        $stmt = $connexion->prepare("UPDATE messages SET statut = 'lu' WHERE id = ?");
        $stmt->execute([$message_id]);
    }
} catch (PDOException $e) {
    $error = "Erreur de base de données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail du Message - Admin</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="IMAGES&VIDEOS-SITES/favicon.png">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <?php include 'admin_sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Détail du Message</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="admin_messages.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Message de <?php echo htmlspecialchars($message['nom']); ?></h5>
                        <span class="badge <?php echo $message['statut'] === 'non lu' ? 'bg-primary' : 'bg-secondary'; ?>">
                            <?php echo ucfirst($message['statut']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="card-title">Sujet : <?php echo htmlspecialchars($message['sujet'] ?: 'Pas de sujet'); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                Envoyé le <?php echo date('d/m/Y à H:i', strtotime($message['date_envoi'])); ?>
                            </h6>
                            <p class="card-text">
                                <strong>Email :</strong>
                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-sm btn-outline-primary ms-2">
                                    <?php echo htmlspecialchars($message['email']); ?>
                                </a>
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6>Contenu du message :</h6>
                            <div class="p-3 border rounded bg-light">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-primary">
                                <i class="bi bi-envelope"></i> Répondre par email
                            </a>
                            <form method="POST" action="admin_messages.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vérifier périodiquement les nouveaux messages
function checkNewMessages() {
    fetch('admin_check_messages.php')
        .then(response => response.json())
        .then(data => {
            if (data.unread_count > 0) {
                document.getElementById('unread-messages-badge').textContent = data.unread_count;
                document.getElementById('unread-messages-badge').style.display = 'inline';
            } else {
                document.getElementById('unread-messages-badge').style.display = 'none';
            }
        });
}

// Vérifier toutes les 30 secondes
setInterval(checkNewMessages, 30000);

// Appeler une première fois au chargement de la page
document.addEventListener('DOMContentLoaded', checkNewMessages);

    </script>
</body>
</html>
