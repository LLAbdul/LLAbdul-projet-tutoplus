<?php
/* Testé par Diane Devi le 24/11/2025 Réussi */
/**
 * Page gestion_disponibilites.php
 * - Accessible uniquement aux tuteurs connectés
 * - Affiche le calendrier des disponibilités (FullCalendar)
 * - Permet de créer / modifier / supprimer des créneaux
 */
/* Testé par Diane Devi le 21/11/2025 Réussi */
session_start();

// Vérifier que le tuteur est connecté
if (!isset($_SESSION['tuteur_id'])) {
    header('Location: login.php?type=tuteur');
    exit;
}

require_once 'config/database.php';
require_once 'models/Tuteur.php';
require_once 'models/Service.php';

// Connexion à la base de données
$pdo = getDBConnection();

// Récupération des informations du tuteur
$tuteurModel = new Tuteur($pdo);
$tuteur = $tuteurModel->getTuteurById($_SESSION['tuteur_id']);

// Récupération des services du tuteur pour le formulaire
$serviceModel = new Service($pdo);
$services = $serviceModel->getServicesByTuteurId($_SESSION['tuteur_id']);

// Si aucun service n'existe, créer un service par défaut
if (empty($services) && $tuteur) {
    $serviceId = $serviceModel->creerServiceParDefaut(
        $_SESSION['tuteur_id'],
        $tuteur['departement'] ?? 'Général',
        (float)($tuteur['tarif_horaire'] ?? 0)
    );
    
    if ($serviceId) {
        // Recharger les services après création
        $services = $serviceModel->getServicesByTuteurId($_SESSION['tuteur_id']);
    }
}

// Déterminer le service par défaut (premier service du tuteur)
$serviceParDefaut = !empty($services) ? $services[0] : null;

// Petites constantes d'affichage
$logoAhuntsicFull = 'https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png';
$logoAhuntsicShort = 'assets/images/collegeahuntsiclogoshort.png';
$cacheBuster = time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Disponibilités - TutoPlus</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/gestion-disponibilites.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/creneaux-modal.css?v=<?= $cacheBuster ?>">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
</head>
<body>
/* Testé par Diane Devi le 23/11/2025 Réussi */    
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

            <div id="notification-container" class="notification-container"></div>

            <?php if (!empty($services)): 
                $serviceActuel = $services[0];
            ?>
                <div class="service-info-section" style="margin-bottom: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.125rem;">Mon Service</h3>
                            <p style="margin: 0; color: #6c757d;">
                                <strong><?= htmlspecialchars($serviceActuel['nom'], ENT_QUOTES, 'UTF-8') ?></strong> - 
                                <?= htmlspecialchars($serviceActuel['categorie'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <button 
                            type="button" 
                            id="btnModifierService" 
                            class="btn-primary"
                            style="padding: 0.625rem 1.25rem;"
                            data-service-id="<?= htmlspecialchars($serviceActuel['id'], ENT_QUOTES, 'UTF-8') ?>"
                            data-service-nom="<?= htmlspecialchars($serviceActuel['nom'], ENT_QUOTES, 'UTF-8') ?>"
                            data-service-description="<?= htmlspecialchars($serviceActuel['description'], ENT_QUOTES, 'UTF-8') ?>"
                            data-service-prix="<?= htmlspecialchars($serviceActuel['prix'], ENT_QUOTES, 'UTF-8') ?>"
                            data-service-duree="<?= htmlspecialchars($serviceActuel['duree_minute'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            Modifier mon service
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="calendrier-container">
                <div id="calendrier-disponibilites"></div>
            </div>
        </div>
    </section>
</main>

<!-- Modal pour créer/modifier une disponibilité -->
<div
    id="modal-disponibilite"
    class="creneaux-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
>
    <div class="creneaux-modal-overlay"></div>

    <div class="creneaux-modal-content">
        <div class="creneaux-modal-header">
            <h3 class="creneaux-modal-title" id="modal-title">Créer une disponibilité</h3>
            <button type="button" class="creneaux-modal-close" id="modal-close" aria-label="Fermer le modal">
                &times;
            </button>
        </div>

        <div class="creneaux-modal-body">
            <form id="form-disponibilite">
                <input type="hidden" id="disponibilite-id" name="id">

                <div class="form-group">
                    <label for="date-debut" class="form-label">Date et heure de début</label>
                    <input
                        type="datetime-local"
                        id="date-debut"
                        name="date_debut"
                        class="form-input"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="date-fin" class="form-label">Date et heure de fin</label>
                    <input
                        type="datetime-local"
                        id="date-fin"
                        name="date_fin"
                        class="form-input"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="service-id" class="form-label">Service (optionnel)</label>
                    <select id="service-id" name="service_id" class="form-input">
                        <option value="">Aucun service spécifique</option>

                        <?php foreach ($services as $service): ?>
                            <option
                                value="<?= htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8') ?>"
                                data-prix="<?= htmlspecialchars($service['prix'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($serviceParDefaut && $service['id'] === $serviceParDefaut['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($service['nom'] . ' - ' . $service['categorie'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prix" class="form-label">Prix (optionnel)</label>
                    <input
                        type="number"
                        id="prix"
                        name="prix"
                        class="form-input"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        value="<?= $serviceParDefaut ? number_format($serviceParDefaut['prix'], 2, '.', '') : '' ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="statut" class="form-label">Statut</label>
                    <select id="statut" name="statut" class="form-input">
                        <option value="DISPONIBLE">Disponible</option>
                        <option value="BLOQUE">Bloqué</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes" class="form-label">Notes (optionnel)</label>
                    <textarea
                        id="notes"
                        name="notes"
                        class="form-input"
                        rows="2"
                    ></textarea>
                </div>

                <div id="modal-error" class="error-message" style="display: none;"></div>

                <div class="modal-actions">
                    <div class="modal-actions-left">
                        <button
                            type="button"
                            class="btn-danger"
                            id="modal-delete"
                            style="display: none;"
                        >
                            Supprimer
                        </button>
                    </div>

                    <div class="modal-actions-right">
                        <button type="button" class="btn-secondary" id="modal-cancel">
                            Annuler
                        </button>
                        <button type="submit" class="btn-primary" id="modal-submit">
                            Créer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour modifier le service -->
<div id="modal-service" class="creneaux-modal" role="dialog" aria-modal="true" aria-labelledby="modal-service-title">
    <div class="creneaux-modal-overlay"></div>
    <div class="creneaux-modal-content">
        <div class="creneaux-modal-header">
            <h3 class="creneaux-modal-title" id="modal-service-title">Modifier mon service</h3>
            <button type="button" class="creneaux-modal-close" id="modal-service-close" aria-label="Fermer le modal">
                &times;
            </button>
        </div>
        <div class="creneaux-modal-body">
            <form id="form-service">
                <input type="hidden" id="service-id-edit" name="service_id">
                
                <div class="form-group">
                    <label for="service-nom-edit" class="form-label">Nom du service</label>
                    <input type="text" id="service-nom-edit" name="nom" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="service-description-edit" class="form-label">Description</label>
                    <textarea id="service-description-edit" name="description" class="form-input" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="service-prix-edit" class="form-label">Prix ($)</label>
                    <input type="number" id="service-prix-edit" name="prix" class="form-input" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="service-duree-edit" class="form-label">Durée (minutes)</label>
                    <input type="number" id="service-duree-edit" name="duree_minute" class="form-input" min="15" step="15" required>
                </div>

                <div id="modal-service-error" class="error-message" style="display: none;"></div>

                <div class="modal-actions">
                    <div class="modal-actions-right">
                        <button type="button" class="btn-secondary" id="modal-service-cancel">
                            Annuler
                        </button>
                        <button type="submit" class="btn-primary" id="modal-service-submit">
                            Enregistrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

/* Testé par Diane Devi le 23/11/2025 Réussi */
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

<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="assets/js/gestion-disponibilites.js?v=<?= $cacheBuster ?>"></script>
<script src="assets/js/user-dropdown-menu.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>
