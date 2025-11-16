<?php
/**
 * Page gestion_demandes.php
 * - Accessible uniquement aux tuteurs connectés
 * - Affiche la liste des demandes des étudiants
 * - Permet d'accepter ou refuser les demandes
 */

session_start();

// Vérifier que le tuteur est connecté
if (!isset($_SESSION['tuteur_id'])) {
    header('Location: login.php?type=tuteur');
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
    <title>Gestion des Demandes - TutoPlus</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/gestion-demandes.css?v=<?= $cacheBuster ?>">
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
                <?php if (isset($_SESSION['tuteur_id'])): ?>
                    <div class="user-info">
                        <span class="user-name">
                            <?= htmlspecialchars($_SESSION['tuteur_prenom'] . ' ' . $_SESSION['tuteur_nom'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="user-number">
                            <?= htmlspecialchars($_SESSION['tuteur_numero'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <a href="logout.php" class="btn-logout">Déconnexion</a>

                    <!-- Menu dropdown pour mobile -->
                    <div class="user-menu-dropdown">
                        <button
                            class="user-initial-btn"
                            id="userMenuBtn"
                            aria-label="Menu utilisateur"
                            type="button"
                        >
                            <?= strtoupper(substr($_SESSION['tuteur_prenom'], 0, 1) . substr($_SESSION['tuteur_nom'], 0, 1)) ?>
                        </button>

                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="gestion_disponibilites.php" class="dropdown-menu-link">
                                <span>Mes Disponibilités</span>
                            </a>
                            <a href="gestion_demandes.php" class="dropdown-menu-link">
                                <span>Mes Demandes</span>
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
    <section class="demandes-section container">
        <div class="demandes-header">
            <h1>Gestion des demandes</h1>
            <p class="demandes-subtitle">
                Consultez et répondez aux demandes de rendez-vous des étudiants
            </p>
        </div>

        <div class="demandes-content">
            <!-- Zone de chargement -->
            <div id="loadingIndicator" class="loading-indicator">
                <div class="spinner"></div>
                <p>Chargement des demandes...</p>
            </div>

            <!-- Message d'erreur -->
            <div id="errorMessage" class="error-message" style="display: none;">
                <p id="errorText"></p>
            </div>

            <!-- Message si aucune demande -->
            <div id="noDemandes" class="no-demandes" style="display: none;">
                <div class="no-demandes-icon" aria-hidden="true">
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
                <h3>Aucune demande</h3>
                <p>
                    Vous n'avez pas encore de demandes de rendez-vous.
                </p>
            </div>

            <!-- Liste des demandes -->
            <div id="demandesList" class="demandes-list" style="display: none;">
                <!-- Les demandes seront injectées ici par JavaScript -->
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

<script src="assets/js/user-dropdown-menu.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/gestion-demandes.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>

