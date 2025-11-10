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
                    <?php if (isset($_SESSION['etudiant_id'])): ?>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['etudiant_prenom'] . ' ' . $_SESSION['etudiant_nom']); ?></span>
                            <span class="user-number"><?php echo htmlspecialchars($_SESSION['etudiant_numero']); ?></span>
                        </div>
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
                                                        <span class="detail-item detail-item-with-action">
                                                            <span class="detail-content">
                                                                <strong>Département:</strong> <?php echo htmlspecialchars($service['departement']); ?>
                                                            </span>
                                                            <button class="btn-plus-creneaux" 
                                                                    data-service-id="<?php echo htmlspecialchars($service['id']); ?>"
                                                                    aria-label="Voir les créneaux">
                                                                <span class="btn-plus-icon">+</span>
                                                            </button>
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
            <p>&copy; <?php echo date('Y'); ?> TutoPlus - Tous droits réservés</p>
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

    <script src="assets/js/script.js"></script>
    <script src="assets/js/creneaux-modal.js"></script>
</body>
</html>

