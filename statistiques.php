<?php
/**
 * Page statistiques.php
 * - Accessible uniquement aux administrateurs connectés
 * - Affiche des statistiques avec graphiques Chart.js
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
    <title>Statistiques - TutoPlus</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/statistiques.css?v=<?= $cacheBuster ?>">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                    <!-- Menu burger pour PC -->
                    <button class="burger-menu-btn-desktop" id="burgerMenuBtnDesktop" aria-label="Menu" type="button">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <div class="burger-menu-desktop" id="burgerMenuDesktop">
                        <a href="index.php#services" class="burger-menu-link">Services</a>
                        <a href="admin.php" class="burger-menu-link">Administration</a>
                        <a href="statistiques.php" class="burger-menu-link">Statistiques</a>
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

                    <!-- Menu dropdown pour mobile -->
                    <div class="user-menu-dropdown">
                        <button
                            class="user-initial-btn"
                            id="userMenuBtn"
                            aria-label="Menu utilisateur"
                            type="button"
                        >
                            <?= strtoupper(substr($_SESSION['admin_prenom'], 0, 1) . substr($_SESSION['admin_nom'], 0, 1)) ?>
                        </button>
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="index.php#services" class="dropdown-menu-link">
                                <span>Services</span>
                            </a>
                            <a href="admin.php" class="dropdown-menu-link">
                                <span>Administration</span>
                            </a>
                            <a href="statistiques.php" class="dropdown-menu-link">
                                <span>Statistiques</span>
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

<main class="statistiques-main">
    <div class="container">
        <div class="statistiques-header">
            <h2>Statistiques</h2>
            <p>Vue d'ensemble des données du système</p>
        </div>

        <!-- Zone de chargement -->
        <div id="loadingIndicator" class="loading-indicator">
            <div class="spinner"></div>
            <p>Chargement des statistiques...</p>
        </div>

        <!-- Message d'erreur -->
        <div id="errorMessage" class="error-message" style="display: none;">
            <p id="errorText"></p>
        </div>

        <!-- Contenu des statistiques -->
        <div id="statistiquesContent" class="statistiques-content" style="display: none;">
            <!-- Statistiques générales -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-card-icon">📊</div>
                    <div class="stat-card-content">
                        <h3 id="totalRendezVous">0</h3>
                        <p>Rendez-vous total</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">👥</div>
                    <div class="stat-card-content">
                        <h3 id="totalTuteurs">0</h3>
                        <p>Tuteurs actifs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">🎓</div>
                    <div class="stat-card-content">
                        <h3 id="totalEtudiants">0</h3>
                        <p>Étudiants actifs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">✅</div>
                    <div class="stat-card-content">
                        <h3 id="rendezVousTermines">0</h3>
                        <p>Rendez-vous terminés</p>
                    </div>
                </div>
            </div>

            <!-- Graphique principal -->
            <div class="chart-container">
                <h3>Rendez-vous par statut</h3>
                <canvas id="rendezVousChart"></canvas>
            </div>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="footer-content">
            <p>&copy; 2025 TutoPlus - Collège Ahuntsic. Tous droits réservés.</p>
            <div class="footer-logo">
                <img src="<?= $logoAhuntsicShort ?>" alt="Collège Ahuntsic" class="footer-logo-img">
            </div>
        </div>
    </div>
</footer>

<script src="assets/js/user-dropdown-menu.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/statistiques.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>

