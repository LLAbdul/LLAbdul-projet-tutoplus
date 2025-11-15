<?php
/**
 * Page d'historique des séances de tutorat
 * TutoPlus - Système de tutorat
 */

session_start();

// Rediriger vers login si l'étudiant n'est pas connecté
if (!isset($_SESSION['etudiant_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Séances - TutoPlus</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/historique.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="logo-link">
                        <img src="https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png" 
                             alt="Collège Ahuntsic" 
                             class="college-logo">
                    </a>
                </div>
                <div class="header-center">
                    <a href="index.php" class="header-title-link">
                        <h1><span class="logo-text">Tuto</span><span class="logo-accent">Plus</span></h1>
                    </a>
                    <p class="subtitle">Système de tutorat pour votre école</p>
                </div>
                <div class="header-right">
                    <?php if (isset($_SESSION['etudiant_id'])): ?>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['etudiant_prenom'] . ' ' . $_SESSION['etudiant_nom']); ?></span>
                            <span class="user-number"><?php echo htmlspecialchars($_SESSION['etudiant_numero']); ?></span>
                        </div>
                        <a href="index.php" class="btn-login-link">Services</a>
                        <a href="historique.php" class="btn-login-link">Mes Séances</a>
                        <a href="logout.php" class="btn-logout">Déconnexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="historique-section container">
            <div class="historique-header">
                <h1>Historique de mes séances</h1>
                <p class="historique-subtitle">Consultez toutes vos séances de tutorat passées et à venir</p>
            </div>

            <div class="historique-content">
                <!-- Zone de chargement -->
                <div id="loadingIndicator" class="loading-indicator">
                    <div class="spinner"></div>
                    <p>Chargement de vos séances...</p>
                </div>

                <!-- Message d'erreur -->
                <div id="errorMessage" class="error-message" style="display: none;">
                    <p id="errorText"></p>
                </div>

                <!-- Message si aucun rendez-vous -->
                <div id="noRendezVous" class="no-rendez-vous" style="display: none;">
                    <div class="no-rendez-vous-icon">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="32" cy="32" r="32" fill="#e9ecef"/>
                            <path d="M32 20V32L40 40" stroke="#6c757d" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Aucune séance enregistrée</h3>
                    <p>Vous n'avez pas encore de séances de tutorat. <a href="index.php">Réservez votre première séance</a></p>
                </div>

                <!-- Liste des rendez-vous -->
                <div id="rendezVousList" class="rendez-vous-list" style="display: none;">
                    <!-- Les rendez-vous seront injectés ici par JavaScript -->
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> TutoPlus - Collège Ahuntsic. Tous droits réservés.</p>
                <img src="https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png" 
                     alt="Collège Ahuntsic" 
                     class="footer-logo">
            </div>
        </div>
    </footer>

    <script src="assets/js/historique.js"></script>
</body>
</html>

