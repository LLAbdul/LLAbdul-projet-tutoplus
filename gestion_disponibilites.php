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
require_once 'models/Service.php';

// Connexion à la base de données
$pdo = getDBConnection();

// Récupération des informations du tuteur
$tuteurModel = new Tuteur($pdo);
$tuteur = $tuteurModel->getTuteurById($_SESSION['tuteur_id']);

// Récupération des services du tuteur pour le formulaire
$serviceModel = new Service($pdo);
$services = $serviceModel->getServicesByTuteurId($_SESSION['tuteur_id']);

// Déterminer le service par défaut (premier service du tuteur)
$serviceParDefaut = !empty($services) ? $services[0] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Disponibilités - TutoPlus</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/gestion-disponibilites.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/creneaux-modal.css?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="logo-link">
                        <img src="https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png" 
                             alt="Collège Ahuntsic" 
                             class="college-logo college-logo-desktop">
                        <img src="assets/images/collegeahuntsiclogoshort.png" 
                             alt="Collège Ahuntsic" 
                             class="college-logo college-logo-mobile">
                    </a>
                </div>
                <div class="header-center">
                    <a href="index.php" class="header-title-link">
                        <h1><span class="logo-text">Tuto</span><span class="logo-accent">Plus</span></h1>
                    </a>
                    <p class="subtitle">Système de tutorat pour votre école</p>
                </div>
                <div class="header-right">
                    <?php if (isset($_SESSION['tuteur_id'])): ?>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['tuteur_prenom'] . ' ' . $_SESSION['tuteur_nom']); ?></span>
                            <span class="user-number"><?php echo htmlspecialchars($_SESSION['tuteur_numero']); ?></span>
                        </div>
                        <a href="logout.php" class="btn-logout">Déconnexion</a>
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
                
                <div class="calendrier-container">
                    <div id="calendrier-disponibilites"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal pour créer/modifier une disponibilité -->
    <div id="modal-disponibilite" class="creneaux-modal">
        <div class="creneaux-modal-overlay"></div>
        <div class="creneaux-modal-content">
            <div class="creneaux-modal-header">
                <h3 class="creneaux-modal-title" id="modal-title">Créer une disponibilité</h3>
                <button type="button" class="creneaux-modal-close" id="modal-close">&times;</button>
            </div>
            <div class="creneaux-modal-body">
                <form id="form-disponibilite">
                    <input type="hidden" id="disponibilite-id" name="id">
                    
                    <div class="form-group">
                        <label for="date-debut" class="form-label">Date et heure de début</label>
                        <input type="datetime-local" id="date-debut" name="date_debut" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date-fin" class="form-label">Date et heure de fin</label>
                        <input type="datetime-local" id="date-fin" name="date_fin" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-id" class="form-label">Service (optionnel)</label>
                        <select id="service-id" name="service_id" class="form-input">
                            <option value="">Aucun service spécifique</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['id']); ?>" 
                                        data-prix="<?php echo htmlspecialchars($service['prix']); ?>"
                                        <?php echo ($serviceParDefaut && $service['id'] === $serviceParDefaut['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['nom'] . ' - ' . $service['categorie']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="prix" class="form-label">Prix (optionnel)</label>
                        <input type="number" id="prix" name="prix" class="form-input" min="0" step="0.01" placeholder="0.00" value="<?php echo $serviceParDefaut ? number_format($serviceParDefaut['prix'], 2, '.', '') : ''; ?>">
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
                        <textarea id="notes" name="notes" class="form-input" rows="2"></textarea>
                    </div>
                    
                    <div id="modal-error" class="error-message" style="display: none;"></div>
                    
                    <div class="modal-actions">
                        <div class="modal-actions-left">
                            <button type="button" class="btn-danger" id="modal-delete" style="display: none;">Supprimer</button>
                        </div>
                        <div class="modal-actions-right">
                            <button type="button" class="btn-secondary" id="modal-cancel">Annuler</button>
                            <button type="submit" class="btn-primary" id="modal-submit">Créer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/fr-ca.js"></script>
    <script src="assets/js/gestion-disponibilites.js"></script>
</body>
</html>

