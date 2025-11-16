<?php
/**
 * Page admin.php
 * - Accessible uniquement aux administrateurs connectés
 * - Affiche la liste des comptes (étudiants et tuteurs)
 * - Permet de gérer les comptes (modifier, activer/désactiver)
 * - Permet de gérer les rendez-vous
 */

session_start();

// Vérifier que l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php?type=admin');
    exit;
}

$logoAhuntsicFull = 'https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png';
$logoAhuntsicShort = 'assets/images/collegeahuntsiclogoshort.png';
$cacheBuster = time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - TutoPlus</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= $cacheBuster ?>">
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <a href="index.php" class="logo-link">
                    <img
                        src="<?= $logoAhuntsicFull ?>"
                        alt="Collège Ahuntsic"
                        class="college-logo college-logo-desktop"
                    >
                    <img
                        src="<?= $logoAhuntsicShort ?>"
                        alt="Collège Ahuntsic"
                        class="college-logo college-logo-mobile"
                    >
                </a>
            </div>

            <div class="header-center">
                <a href="index.php" class="header-title-link">
                    <h1>
                        <span class="logo-text">Tuto</span><span class="logo-accent">Plus</span>
                    </h1>
                </a>
                <p class="subtitle">Système de tutorat pour votre école</p>
            </div>

            <div class="header-right">
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <div class="user-info">
                        <span class="user-name">
                            <?= htmlspecialchars($_SESSION['admin_prenom'] . ' ' . $_SESSION['admin_nom'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="user-number">
                            <?= htmlspecialchars($_SESSION['admin_numero'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <a href="logout.php" class="btn-logout-quick" aria-label="Déconnexion" title="Déconnexion">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
                            <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main>
    <section class="admin-section container">
        <div class="admin-header">
            <h1>Administration</h1>
            <p class="admin-subtitle">
                Gestion des comptes utilisateurs et des rendez-vous
            </p>
        </div>

        <div class="admin-content">
            <!-- Onglets pour basculer entre les sections -->
            <div class="admin-tabs">
                <button class="admin-tab active" data-tab="comptes" type="button">
                    Gestion des Comptes
                </button>
                <button class="admin-tab" data-tab="rendez-vous" type="button">
                    Gestion des Rendez-vous
                </button>
            </div>

            <!-- Section Gestion des Comptes -->
            <div class="admin-tab-content active" id="tab-comptes">
                <!-- Filtres pour les comptes -->
                <div class="admin-filters">
                    <button class="filter-btn active" data-filter="all" type="button">
                        Tous
                    </button>
                    <button class="filter-btn" data-filter="etudiants" type="button">
                        Étudiants
                    </button>
                    <button class="filter-btn" data-filter="tuteurs" type="button">
                        Tuteurs
                    </button>
                    <button class="filter-btn" data-filter="actifs" type="button">
                        Actifs
                    </button>
                    <button class="filter-btn" data-filter="inactifs" type="button">
                        Inactifs
                    </button>
                </div>

                <!-- Zone de chargement -->
                <div id="loadingIndicator" class="loading-indicator">
                    <div class="spinner"></div>
                    <p>Chargement des comptes...</p>
                </div>

                <!-- Message d'erreur -->
                <div id="errorMessage" class="error-message" style="display: none;">
                    <p id="errorText"></p>
                </div>

                <!-- Message si aucun compte -->
                <div id="noComptes" class="no-comptes" style="display: none;">
                    <div class="no-comptes-icon" aria-hidden="true">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="32" cy="32" r="32" fill="#e9ecef"/>
                            <path d="M32 20V32L40 40" stroke="#6c757d" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Aucun compte</h3>
                    <p>Aucun compte trouvé avec les filtres sélectionnés.</p>
                </div>

                <!-- Liste des comptes -->
                <div id="comptesList" class="comptes-list" style="display: none;">
                    <!-- Les comptes seront injectés ici par JavaScript -->
                </div>
            </div>

            <!-- Section Gestion des Rendez-vous -->
            <div class="admin-tab-content" id="tab-rendez-vous">
                <div class="admin-placeholder">
                    <p>La gestion des rendez-vous sera disponible prochainement.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> TutoPlus - Tous droits réservés</p>
            <img
                src="<?= $logoAhuntsicFull ?>"
                alt="Collège Ahuntsic"
                class="footer-logo"
            >
        </div>
    </div>
</footer>

<script src="assets/js/admin.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>

