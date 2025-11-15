<?php
/**
 * Page d'accueil - Liste des services offerts
 * TutoPlus - Système de tutorat
 */

session_start();

require_once 'config/database.php';
require_once 'models/Service.php';
require_once 'models/Tuteur.php';

// Connexion à la base de données
$pdo = getDBConnection();

// Récupération des services
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TutoPlus - Services de Tutorat</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/creneaux-modal.css">
    <link rel="stylesheet" href="assets/css/confirmation-modal.css">
    <link rel="stylesheet" href="assets/css/contact-modal.css">
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
                        <a href="historique.php" class="btn-login-link">Mes Séances</a>
                        <a href="logout.php" class="btn-logout">Déconnexion</a>
                    <?php elseif (isset($_SESSION['tuteur_id'])): ?>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['tuteur_prenom'] . ' ' . $_SESSION['tuteur_nom']); ?></span>
                            <span class="user-number"><?php echo htmlspecialchars($_SESSION['tuteur_numero']); ?></span>
                        </div>
                        <a href="gestion_disponibilites.php" class="btn-login-link">Mes Disponibilités</a>
                        <a href="logout.php" class="btn-logout">Déconnexion</a>
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
                <p class="hero-description">Plateforme complète de tutorat : accompagnement personnalisé, suivi de progression, outils pédagogiques et interface moderne. Disponible en ligne et en présentiel.</p>
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
                        foreach ($servicesByCategory as $categorie => $servicesList): 
                        ?>
                            <button class="tab-btn <?php echo $firstCategory ? 'active' : ''; ?>" 
                                    data-category="<?php echo htmlspecialchars($categorie); ?>">
                                <?php echo htmlspecialchars($categorie); ?>
                            </button>
                        <?php 
                        $firstCategory = false;
                        endforeach; 
                        ?>
                    </div>

                    <div class="tabs-content">
                        <?php 
                        $firstCategory = true;
                        foreach ($servicesByCategory as $categorie => $servicesList): 
                        ?>
                            <div class="tab-panel <?php echo $firstCategory ? 'active' : ''; ?>" 
                                 data-category="<?php echo htmlspecialchars($categorie); ?>">
                                <div class="services-grid">
                                    <?php foreach ($servicesList as $index => $service): ?>
                                        <div class="service-card slide-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                            <div class="service-header">
                                                <h4><?php echo htmlspecialchars($service['nom']); ?></h4>
                                                <?php if (isset($service['tuteur_nom']) && isset($service['tuteur_prenom'])): ?>
                                                    <div class="service-tuteur">
                                                        <span class="tuteur-label">Tuteur:</span>
                                                        <span class="tuteur-name"><?php echo htmlspecialchars($service['tuteur_prenom'] . ' ' . $service['tuteur_nom']); ?></span>
                                                        <?php if (isset($service['evaluation']) && $service['evaluation'] > 0): ?>
                                                            <span class="tuteur-rating">★ <?php echo number_format($service['evaluation'], 1); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="service-body">
                                                <p class="service-description">
                                                    <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                                                </p>
                                                <div class="service-details">
                                                    <span class="detail-item">
                                                        <strong>Durée:</strong> <?php echo $service['duree_minute']; ?> minutes
                                                    </span>
                                                    <span class="detail-item">
                                                        <strong>Prix:</strong> <?php echo number_format($service['prix'], 2); ?> $CA
                                                    </span>
                                                    <?php if (isset($service['departement'])): ?>
                                                        <span class="detail-item <?php echo !isset($_SESSION['tuteur_id']) ? 'detail-item-with-action' : ''; ?>">
                                                            <span class="detail-content">
                                                                <strong>Département:</strong> <?php echo htmlspecialchars($service['departement']); ?>
                                                            </span>
                                                            <?php if (!isset($_SESSION['tuteur_id']) && isset($_SESSION['etudiant_id'])): ?>
                                                                <button class="btn-contact-tuteur" 
                                                                        data-tuteur-id="<?php echo htmlspecialchars($service['tuteur_id']); ?>"
                                                                        data-tuteur-nom="<?php echo htmlspecialchars($service['tuteur_prenom'] . ' ' . $service['tuteur_nom']); ?>"
                                                                        aria-label="Contacter le tuteur">
                                                                    <span class="btn-contact-icon">✉</span>
                                                                </button>
                                                                <button class="btn-plus-creneaux" 
                                                                        data-service-id="<?php echo htmlspecialchars($service['id']); ?>"
                                                                        aria-label="Voir les créneaux">
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
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> TutoPlus - Tous droits réservés</p>
                <img src="https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png" 
                     alt="Collège Ahuntsic" 
                     class="footer-logo">
            </div>
        </div>
    </footer>

    <!-- Modal pour les créneaux -->
    <div id="creneauxModal" class="creneaux-modal">
        <div class="creneaux-modal-overlay"></div>
        <div class="creneaux-modal-content">
            <div class="creneaux-modal-header">
                <h2 class="creneaux-modal-title">Sélectionner un créneau</h2>
                <button class="creneaux-modal-close" aria-label="Fermer">&times;</button>
            </div>
            <div class="creneaux-modal-body" id="creneauxModalBody">
            </div>
        </div>
    </div>

    <!-- Modal de contact tuteur -->
    <div id="contactModal" class="contact-modal">
        <div class="contact-modal-overlay"></div>
        <div class="contact-modal-content">
            <div class="contact-modal-header">
                <h2 class="contact-modal-title">Contacter le tuteur</h2>
                <button class="contact-modal-close" aria-label="Fermer">&times;</button>
            </div>
            <div class="contact-modal-body">
                <form id="contactForm" class="contact-form">
                    <div class="form-group">
                        <label for="contact-tuteur-select" class="form-label">Tuteur <span class="required">*</span></label>
                        <select id="contact-tuteur-select" name="tuteur_id" class="form-select" required>
                            <option value="">Sélectionnez un tuteur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="contact-sujet" class="form-label">Sujet <span class="required">*</span></label>
                        <input type="text" id="contact-sujet" name="sujet" class="form-input" required maxlength="255" placeholder="Ex: Question sur le service">
                    </div>
                    <div class="form-group">
                        <label for="contact-contenu" class="form-label">Message <span class="required">*</span></label>
                        <textarea id="contact-contenu" name="contenu" class="form-textarea" required maxlength="500" rows="5" placeholder="Votre message (maximum 500 caractères)"></textarea>
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
                        <button type="button" class="btn-contact-cancel" id="btnContactCancel">Annuler</button>
                        <button type="submit" class="btn-contact-submit" id="btnContactSubmit">Envoyer</button>
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
                <div class="confirmation-icon">
                    <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="32" fill="#d4edda"/>
                        <path d="M20 32L28 40L44 24" stroke="#155724" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="confirmation-title">Réservation confirmée !</h2>
                <p class="confirmation-subtitle">Votre rendez-vous a été réservé avec succès</p>
            </div>
            
            <div class="confirmation-body">
                <div class="confirmation-info-card">
                    <div class="info-row">
                        <div class="info-label">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16.5C6.41 16.5 3.5 13.59 3.5 10C3.5 6.41 6.41 3.5 10 3.5C13.59 3.5 16.5 6.41 16.5 10C16.5 13.59 13.59 16.5 10 16.5Z" fill="#6c757d"/>
                                <path d="M10 5.5C9.59 5.5 9.25 5.84 9.25 6.25V10C9.25 10.41 9.59 10.75 10 10.75C10.41 10.75 10.75 10.41 10.75 10V6.25C10.75 5.84 10.41 5.5 10 5.5Z" fill="#6c757d"/>
                                <path d="M10 12.5C9.59 12.5 9.25 12.84 9.25 13.25C9.25 13.66 9.59 14 10 14C10.41 14 10.75 13.66 10.75 13.25C10.75 12.84 10.41 12.5 10 12.5Z" fill="#6c757d"/>
                            </svg>
                            <span>Date et heure</span>
                        </div>
                        <div class="info-value" id="confirmation-date-time">-</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 10C11.38 10 12.5 8.88 12.5 7.5C12.5 6.12 11.38 5 10 5C8.62 5 7.5 6.12 7.5 7.5C7.5 8.88 8.62 10 10 10ZM10 11.25C8.28 11.25 5 12.09 5 13.75V15H15V13.75C15 12.09 11.72 11.25 10 11.25Z" fill="#6c757d"/>
                            </svg>
                            <span>Tuteur</span>
                        </div>
                        <div class="info-value" id="confirmation-tuteur">-</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 2L2 7L10 12L18 7L10 2Z" fill="#6c757d"/>
                                <path d="M2 13L10 18L18 13" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 10L10 15L18 10" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Service</span>
                        </div>
                        <div class="info-value" id="confirmation-service">-</div>
                    </div>
                    
                    <div class="info-row" id="confirmation-notification-row" style="display: none;">
                        <div class="info-label">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 2C5.58 2 2 5.58 2 10C2 14.42 5.58 18 10 18C14.42 18 18 14.42 18 10C18 5.58 14.42 2 10 2ZM10 16.5C6.41 16.5 3.5 13.59 3.5 10C3.5 6.41 6.41 3.5 10 3.5C13.59 3.5 16.5 6.41 16.5 10C16.5 13.59 13.59 16.5 10 16.5Z" fill="#6c757d"/>
                                <path d="M10 5.5C9.59 5.5 9.25 5.84 9.25 6.25V10C9.25 10.41 9.59 10.75 10 10.75C10.41 10.75 10.75 10.41 10.75 10V6.25C10.75 5.84 10.41 5.5 10 5.5Z" fill="#6c757d"/>
                                <path d="M10 12.5C9.59 12.5 9.25 12.84 9.25 13.25C9.25 13.66 9.59 14 10 14C10.41 14 10.75 13.66 10.75 13.25C10.75 12.84 10.41 12.5 10 12.5Z" fill="#6c757d"/>
                            </svg>
                            <span>Notification</span>
                        </div>
                        <div class="info-value" id="confirmation-notification">-</div>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-footer">
                <button class="btn-confirmation-secondary" id="btnConfirmationClose">Fermer</button>
                <a href="historique.php" class="btn-confirmation-primary" id="btnConfirmationViewSessions">Voir mes séances</a>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/confirmation-modal.js"></script>
    <script src="assets/js/creneaux-modal.js"></script>
    <script src="assets/js/contact-modal.js"></script>
</body>
</html>

