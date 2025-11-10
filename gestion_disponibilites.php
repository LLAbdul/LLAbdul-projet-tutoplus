<?php
/**
 * Page de gestion des disponibilités pour les tuteurs
 * TutoPlus - Système de tutorat
 */

session_start();

// Vérifier que le tuteur est connecté
if (!isset($_SESSION['tuteur_id'])) {
    header('Location: login.php?type=tuteur');
    exit;
}

require_once 'config/database.php';
require_once 'models/Tuteur.php';

// Connexion à la base de données
$pdo = getDBConnection();

// Récupération des informations du tuteur
$tuteurModel = new Tuteur($pdo);
$tuteur = $tuteurModel->getTuteurById($_SESSION['tuteur_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Disponibilités - TutoPlus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="logo-link">
                        <h1><span class="logo-text">Tuto</span><span class="logo-accent">Plus</span></h1>
                    </a>
                    <p class="subtitle">Système de tutorat pour votre école</p>
                </div>
                <div class="header-right">
                    <?php if (isset($_SESSION['tuteur_id'])): ?>
                        <div class="user-info">
                            <div class="user-details">
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['tuteur_prenom'] . ' ' . $_SESSION['tuteur_nom']); ?></span>
                                <span class="user-number"><?php echo htmlspecialchars($_SESSION['tuteur_numero']); ?></span>
                            </div>
                            <a href="logout.php" class="btn-logout">Déconnexion</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="gestion-disponibilites-section">
            <div class="container">
                <div class="page-header">
                    <h2 class="page-title">Gestion des Disponibilités</h2>
                    <p class="page-subtitle">Gérez vos créneaux de tutorat</p>
                </div>
                
                <div class="calendrier-container">
                    <div id="calendrier-disponibilites"></div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> TutoPlus - Tous droits réservés</p>
        </div>
    </footer>
</body>
</html>

