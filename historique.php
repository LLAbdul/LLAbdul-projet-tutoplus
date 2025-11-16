<?php
/**
 * Page historique.php
 * - Accessible uniquement aux étudiants connectés
 * - Affiche l'historique (passé / à venir) des séances de tutorat
 * - Les rendez-vous sont chargés en AJAX via historique.js
 */

session_start();

// Rediriger vers login si l'étudiant n'est pas connecté
if (!isset($_SESSION['etudiant_id'])) {
    header('Location: login.php');
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
    <title>Historique des Séances - TutoPlus</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/historique.css?v=<?= $cacheBuster ?>">
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
                <?php if (isset($_SESSION['etudiant_id'])): ?>
                    <!-- Menu burger pour PC -->
                    <button class="burger-menu-btn-desktop" id="burgerMenuBtnDesktop" aria-label="Menu" type="button">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <div class="burger-menu-desktop" id="burgerMenuDesktop">
                        <a href="index.php" class="burger-menu-link">Services</a>
                        <a href="historique.php" class="burger-menu-link">Mes Séances</a>
                        <a href="logout.php" class="burger-menu-link burger-menu-link-logout">
                            <span>Déconnexion</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
                                <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                            </svg>
                        </a>
                    </div>
                    <div class="user-info">
                        <span class="user-name">
                            <?= htmlspecialchars($_SESSION['etudiant_prenom'] . ' ' . $_SESSION['etudiant_nom'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="user-number">
                            <?= htmlspecialchars($_SESSION['etudiant_numero'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <a href="logout.php" class="btn-logout-quick" aria-label="Déconnexion" title="Déconnexion">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
                            <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                        </svg>
                    </a>

                    <!-- Menu dropdown pour mobile -->
                    <div class="user-menu-dropdown">
                        <button
                            class="user-initial-btn"
                            id="userMenuBtn"
                            aria-label="Menu utilisateur"
                            type="button"
                        >
                            <?= strtoupper(substr($_SESSION['etudiant_prenom'], 0, 1) . substr($_SESSION['etudiant_nom'], 0, 1)) ?>
                        </button>

                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="index.php" class="dropdown-menu-link">
                                <span>Services</span>
                            </a>
                            <a href="historique.php" class="dropdown-menu-link">
                                <span>Mes Séances</span>
                            </a>
                            <a href="logout.php" class="dropdown-menu-link dropdown-menu-link-logout">
                                <span>Déconnexion</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main>
    <section class="historique-section container">
        <div class="historique-header">
            <h1>Historique de mes séances</h1>
            <p class="historique-subtitle">
                Consultez toutes vos séances de tutorat passées et à venir
            </p>
        </div>

        <div class="historique-content">
            <!-- Filtres de statut -->
            <div class="historique-filters" id="historiqueFilters" style="display: none;">
                <button class="filter-btn active" data-filter="all" type="button">
                    Tous
                </button>
                <button class="filter-btn" data-filter="EN_ATTENTE" type="button">
                    En attente
                </button>
                <button class="filter-btn" data-filter="A_VENIR" type="button">
                    À venir
                </button>
                <button class="filter-btn" data-filter="REFUSEE" type="button">
                    Refusées
                </button>
                <button class="filter-btn" data-filter="TERMINE" type="button">
                    Terminé
                </button>
                <button class="filter-btn" data-filter="ANNULE" type="button">
                    Annulé
                </button>
            </div>

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
                <div class="no-rendez-vous-icon" aria-hidden="true">
                    <svg width="64" height="64" viewBox="0 0 64 64" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="32" fill="#e9ecef"/>
                        <path d="M32 20V32L40 40"
                              stroke="#6c757d"
                              stroke-width="3"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                        />
                    </svg>
                </div>
                <h3>Aucune séance enregistrée</h3>
                <p>
                    Vous n'avez pas encore de séances de tutorat.
                    <a href="index.php#services">Réservez votre première séance</a>
                </p>
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
            <p>
                &copy; <?= date('Y') ?> TutoPlus - Collège Ahuntsic. Tous droits réservés.
            </p>
            <img
                src="<?= $logoAhuntsicFull ?>"
                alt="Collège Ahuntsic"
                class="footer-logo"
            >
        </div>
    </div>
</footer>

<script src="assets/js/historique.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/user-dropdown-menu.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>
