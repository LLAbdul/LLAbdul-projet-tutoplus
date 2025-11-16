<!-- Testé par Abdul Rahman Zahid le 16/11/2025 Réussi -->
<?php
/**
 * Page index.php
 * - Page d'accueil : liste les services de tutorat actifs
 * - Regroupe les services par catégorie
 * - Header adapté selon le type d'utilisateur (visiteur, étudiant, tuteur)
 * - Permet :
 *   - ouverture du modal des créneaux (+)
 *   - contact du tuteur (icône ✉)
 *   - affichage du modal de confirmation après réservation
 */

session_start();

require_once 'config/database.php';
require_once 'models/Service.php';

// Connexion à la base de données
$pdo = getDBConnection();

// Récupération des services actifs
$serviceModel = new Service($pdo);
$services = $serviceModel->getAllActiveServices();

// Grouper les services par catégorie
$servicesByCategory = [];
foreach ($services as $service) {
    $categorie = $service['categorie'];
    if (!isset($servicesByCategory[$categorie])) {
        $servicesByCategory[$categorie] = [];
    }
    $servicesByCategory[$categorie][] = $service;
}

$logoAhuntsicFull  = 'https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png';
$logoAhuntsicShort = 'assets/images/collegeahuntsiclogoshort.png';
$cacheBuster       = time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TutoPlus - Services de Tutorat</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/creneaux-modal.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/confirmation-modal.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/contact-modal.css?v=<?= $cacheBuster ?>">
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
                            <a href="historique.php" class="dropdown-menu-link">
                                <span>Mes Séances</span>
                            </a>
                            <a href="logout.php" class="dropdown-menu-link dropdown-menu-link-logout">
                                <span>Déconnexion</span>
                            </a>
                        </div>
                    </div>

                <?php elseif (isset($_SESSION['tuteur_id'])): ?>
                    <!-- Menu burger pour PC -->
                    <button class="burger-menu-btn-desktop" id="burgerMenuBtnDesktop" aria-label="Menu" type="button">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <div class="burger-menu-desktop" id="burgerMenuDesktop">
                        <a href="gestion_disponibilites.php" class="burger-menu-link">Mes Disponibilités</a>
                        <a href="gestion_demandes.php" class="burger-menu-link">Mes Demandes</a>
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
                            <?= htmlspecialchars($_SESSION['tuteur_prenom'] . ' ' . $_SESSION['tuteur_nom'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="user-number">
                            <?= htmlspecialchars($_SESSION['tuteur_numero'], ENT_QUOTES, 'UTF-8') ?>
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

                <?php elseif (isset($_SESSION['admin_id'])): ?>
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
                            Système Admin
                        </span>
                        <span class="user-number">
                            <?= htmlspecialchars($_SESSION['admin_numero'] ?? 'ADMIN', ENT_QUOTES, 'UTF-8') ?>
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
                            <?= strtoupper(substr($_SESSION['admin_prenom'] ?? 'A', 0, 1) . substr($_SESSION['admin_nom'] ?? 'D', 0, 1)) ?>
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
                <?php else: ?>
                    <a href="login.php" class="btn-login-link">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <h2 class="hero-title">Services de tutorat pour votre réussite</h2>
            <p class="hero-description">
                Plateforme complète de tutorat : accompagnement personnalisé, suivi de progression,
                outils pédagogiques et interface moderne. Disponible en ligne et en présentiel.
            </p>
            <a href="#services" class="hero-cta">Découvrir nos services</a>
        </div>
        <div class="hero-background"></div>
    </section>

    <section id="services" class="services-section container">
        <h2>Nos Services de Tutorat</h2>

        <?php if (empty($services)): ?>
            <div class="no-services">
                <p>Aucun service disponible pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="services-tabs">
                <div class="tabs-nav">
                    <?php
                    $firstCategory = true;
                    foreach ($servicesByCategory as $categorie => $servicesList): ?>
                        <button
                            class="tab-btn <?= $firstCategory ? 'active' : '' ?>"
                            data-category="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>"
                            type="button"
                        >
                            <?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <?php
                        $firstCategory = false;
                    endforeach;
                    ?>
                </div>

                <div class="tabs-content">
                    <?php
                    $firstCategory = true;
                    foreach ($servicesByCategory as $categorie => $servicesList): ?>
                        <div
                            class="tab-panel <?= $firstCategory ? 'active' : '' ?>"
                            data-category="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <div class="services-grid">
                                <?php foreach ($servicesList as $index => $service): ?>
                                    <div
                                        class="service-card slide-up"
                                        style="animation-delay: <?= $index * 0.1 ?>s;"
                                    >
                                        <div class="service-header">
                                            <h4>
                                                <?= htmlspecialchars($service['nom'], ENT_QUOTES, 'UTF-8') ?>
                                            </h4>

                                            <?php if (isset($service['tuteur_nom'], $service['tuteur_prenom'])): ?>
                                                <div class="service-tuteur">
                                                    <span class="tuteur-label">Tuteur:</span>
                                                    <span class="tuteur-name">
                                                        <?= htmlspecialchars(
                                                            $service['tuteur_prenom'] . ' ' . $service['tuteur_nom'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>

                                                    <?php if (isset($service['evaluation']) && $service['evaluation'] > 0): ?>
                                                        <span class="tuteur-rating">
                                                            ★ <?= number_format((float)$service['evaluation'], 1) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="service-body">
                                            <p class="service-description">
                                                <?= nl2br(htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8')) ?>
                                            </p>

                                            <div class="service-details">
                                                <span class="detail-item">
                                                    <strong>Durée:</strong>
                                                    <?= (int)$service['duree_minute'] ?> minutes
                                                </span>

                                                <span class="detail-item">
                                                    <strong>Prix:</strong>
                                                    <?= number_format((float)$service['prix'], 2) ?> $CA
                                                </span>

                                                <?php if (isset($service['departement'])): ?>
                                                    <span class="detail-item <?= !isset($_SESSION['tuteur_id']) ? 'detail-item-with-action' : '' ?>">
                                                        <span class="detail-content">
                                                            <strong>Département:</strong>
                                                            <?= htmlspecialchars($service['departement'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>

                                                        <?php if (!isset($_SESSION['tuteur_id']) && isset($_SESSION['etudiant_id'])): ?>
                                                            <button
                                                                class="btn-contact-tuteur"
                                                                data-tuteur-id="<?= htmlspecialchars($service['tuteur_id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-tuteur-nom="<?= htmlspecialchars($service['tuteur_prenom'] . ' ' . $service['tuteur_nom'], ENT_QUOTES, 'UTF-8') ?>"
                                                                aria-label="Contacter le tuteur"
                                                                type="button"
                                                            >
                                                                <span class="btn-contact-icon">✉</span>
                                                            </button>

                                                            <button
                                                                class="btn-plus-creneaux"
                                                                data-service-id="<?= htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                aria-label="Voir les créneaux"
                                                                type="button"
                                                            >
                                                                <span class="btn-plus-icon">+</span>
                                                            </button>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php
                        $firstCategory = false;
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
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

<!-- Modal pour les créneaux -->
<div id="creneauxModal" class="creneaux-modal">
    <div class="creneaux-modal-overlay"></div>
    <div class="creneaux-modal-content">
        <div class="creneaux-modal-header">
            <h2 class="creneaux-modal-title">Sélectionner un créneau</h2>
            <button class="creneaux-modal-close" aria-label="Fermer" type="button">&times;</button>
        </div>
        <div class="creneaux-modal-body" id="creneauxModalBody"></div>
    </div>
</div>

<!-- Modal de contact tuteur -->
<div id="contactModal" class="contact-modal">
    <div class="contact-modal-overlay"></div>
    <div class="contact-modal-content">
        <div class="contact-modal-header">
            <h2 class="contact-modal-title">Contacter le tuteur</h2>
            <button class="contact-modal-close" aria-label="Fermer" type="button">&times;</button>
        </div>
        <div class="contact-modal-body">
            <form id="contactForm" class="contact-form">
                <div class="form-group">
                    <label for="contact-tuteur-select" class="form-label">
                        Tuteur <span class="required">*</span>
                    </label>
                    <select id="contact-tuteur-select" name="tuteur_id" class="form-select" required>
                        <option value="">Sélectionnez un tuteur</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact-sujet" class="form-label">
                        Sujet <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="contact-sujet"
                        name="sujet"
                        class="form-input"
                        required
                        maxlength="255"
                        placeholder="Ex: Question sur le service"
                    >
                </div>

                <div class="form-group">
                    <label for="contact-contenu" class="form-label">
                        Message <span class="required">*</span>
                    </label>
                    <textarea
                        id="contact-contenu"
                        name="contenu"
                        class="form-textarea"
                        required
                        maxlength="500"
                        rows="5"
                        placeholder="Votre message (maximum 500 caractères)"
                    ></textarea>
                    <div class="char-counter">
                        <span id="char-count">0</span>/500 caractères
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact-priorite" class="form-label">Priorité</label>
                    <select id="contact-priorite" name="priorite" class="form-select">
                        <option value="">Normale</option>
                        <option value="HAUTE">Haute</option>
                        <option value="URGENTE">Urgente</option>
                    </select>
                </div>

                <div id="contact-error" class="error-message" style="display: none;"></div>

                <div class="contact-modal-footer">
                    <button type="button" class="btn-contact-cancel" id="btnContactCancel">
                        Annuler
                    </button>
                    <button type="submit" class="btn-contact-submit" id="btnContactSubmit">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de réservation -->
<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-modal-overlay"></div>
    <div class="confirmation-modal-content">
        <div class="confirmation-header">
            <div class="confirmation-icon" aria-hidden="true">
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="32" fill="#d4edda"/>
                    <path d="M20 32L28 40L44 24"
                          stroke="#155724"
                          stroke-width="4"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                    />
                </svg>
            </div>
            <h2 class="confirmation-title">Réservation confirmée !</h2>
            <p class="confirmation-subtitle">
                Votre rendez-vous a été réservé avec succès
            </p>
        </div>

        <div class="confirmation-body">
            <div class="confirmation-info-card">
                <div class="info-row">
                    <div class="info-label">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16.5C6.41 16.5 3.5 13.59 3.5 10C3.5 6.41 6.41 3.5 10 3.5C13.59 3.5 16.5 6.41 16.5 10C16.5 13.59 13.59 16.5 10 16.5Z"
                                  fill="#6c757d"/>
                            <path d="M10 5.5C9.59 5.5 9.25 5.84 9.25 6.25V10C9.25 10.41 9.59 10.75 10 10.75C10.41 10.75 10.75 10.41 10.75 10V6.25C10.75 5.84 10.41 5.5 10 5.5Z"
                                  fill="#6c757d"/>
                            <path d="M10 12.5C9.59 12.5 9.25 12.84 9.25 13.25C9.25 13.66 9.59 14 10 14C10.41 14 10.75 13.66 10.75 13.25C10.75 12.84 10.41 12.5 10 12.5Z"
                                  fill="#6c757d"/>
                        </svg>
                        <span>Date et heure</span>
                    </div>
                    <div class="info-value" id="confirmation-date-time">-</div>
                </div>

                <div class="info-row">
                    <div class="info-label">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 10C11.38 10 12.5 8.88 12.5 7.5C12.5 6.12 11.38 5 10 5C8.62 5 7.5 6.12 7.5 7.5C7.5 8.88 8.62 10 10 10ZM10 11.25C8.28 11.25 5 12.09 5 13.75V15H15V13.75C15 12.09 11.72 11.25 10 11.25Z"
                                  fill="#6c757d"/>
                        </svg>
                        <span>Tuteur</span>
                    </div>
                    <div class="info-value" id="confirmation-tuteur">-</div>
                </div>

                <div class="info-row">
                    <div class="info-label">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2L2 7L10 12L18 7L10 2Z" fill="#6c757d"/>
                            <path d="M2 13L10 18L18 13" stroke="#6c757d" stroke-width="2"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 10L10 15L18 10" stroke="#6c757d" stroke-width="2"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Service</span>
                    </div>
                    <div class="info-value" id="confirmation-service">-</div>
                </div>

                <div class="info-row" id="confirmation-notification-row" style="display: none;">
                    <div class="info-label">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16.5C6.41 16.5 3.5 13.59 3.5 10C3.5 6.41 6.41 3.5 10 3.5C13.59 3.5 16.5 6.41 16.5 10C16.5 13.59 13.59 16.5 10 16.5Z"
                                  fill="#6c757d"/>
                            <path d="M10 5.5C9.59 5.5 9.25 5.84 9.25 6.25V10C9.25 10.41 9.59 10.75 10 10.75C10.41 10.75 10.75 10.41 10.75 10V6.25C10.75 5.84 10.41 5.5 10 5.5Z"
                                  fill="#6c757d"/>
                            <path d="M10 12.5C9.59 12.5 9.25 12.84 9.25 13.25C9.25 13.66 9.59 14 10 14C10.41 14 10.75 13.66 10.75 13.25C10.75 12.84 10.41 12.5 10 12.5Z"
                                  fill="#6c757d"/>
                        </svg>
                        <span>Notification</span>
                    </div>
                    <div class="info-value" id="confirmation-notification">-</div>
                </div>
            </div>
        </div>

        <div class="confirmation-footer">
            <button class="btn-confirmation-secondary" id="btnConfirmationClose" type="button">
                Fermer
            </button>
            <a href="historique.php" class="btn-confirmation-primary" id="btnConfirmationViewSessions">
                Voir mes séances
            </a>
        </div>
    </div>
</div>

<script src="assets/js/script.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/confirmation-modal.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/creneaux-modal.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/contact-modal.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/user-dropdown-menu.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>
