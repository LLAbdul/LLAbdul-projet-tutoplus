<?php
/**
 * API admin.php
 * - GET    : liste des comptes (étudiants et tuteurs) ou détails d'un compte
 *            GET ?resource=rendez-vous : liste de tous les rendez-vous
 * - POST   : création d'un nouveau compte (étudiant ou tuteur)
 * - PUT    : modification d'un compte (activer/désactiver, modifier les informations)
 * - DELETE : (non implémenté pour l'instant)
 */

// Démarrer le buffer de sortie pour capturer toute sortie non désirée
ob_start();

// Désactiver l'affichage des erreurs pour éviter qu'elles polluent le JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

session_start();

require_once '../config/database.php';
require_once '../models/Etudiant.php';
require_once '../models/Tuteur.php';
require_once '../models/RendezVous.php';
require_once '../models/Service.php';

header('Content-Type: application/json');

// Vérifier que l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé - Administrateur non connecté']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $etudiantModel = new Etudiant($pdo);
    $tuteurModel = new Tuteur($pdo);
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'GET':
            ob_clean(); // Nettoyer le buffer avant la réponse
            // Déterminer la ressource demandée (comptes ou rendez-vous)
            $resource = $_GET['resource'] ?? 'comptes';
            
            if ($resource === 'rendez-vous') {
                // Récupérer tous les rendez-vous
                $rendezVousModel = new RendezVous($pdo);
                $rendezVous = $rendezVousModel->getAllRendezVous();
                echo json_encode($rendezVous);
            } elseif ($resource === 'services') {
                // Récupérer les services d'un tuteur
                $tuteurId = $_GET['tuteur_id'] ?? null;
                if (!$tuteurId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'tuteur_id est requis']);
                    break;
                }
                
                $serviceModel = new Service($pdo);
                $services = $serviceModel->getServicesByTuteurId($tuteurId);
                echo json_encode($services);
            } else {
                // Récupérer les comptes
                $id = $_GET['id'] ?? null;
                $type = $_GET['type'] ?? null; // 'etudiant' ou 'tuteur'
                
                if ($id) {
                    // Récupérer un compte spécifique (même si inactif - pour admin)
                    if ($type === 'etudiant') {
                        $compte = $etudiantModel->getEtudiantByIdForAdmin($id);
                    } elseif ($type === 'tuteur') {
                        $compte = $tuteurModel->getTuteurByIdForAdmin($id);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Type de compte requis (etudiant ou tuteur)']);
                        break;
                    }
                    
                    if (!$compte) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Compte non trouvé']);
                        break;
                    }
                    
                    // Ajouter le type de compte
                    $compte['type'] = $type;
                    echo json_encode($compte);
                } else {
                    // Récupérer la liste de tous les comptes
                    $comptes = [];
                    
                    // Récupérer tous les étudiants
                    $etudiants = $etudiantModel->getAllEtudiants();
                    foreach ($etudiants as $etudiant) {
                        $etudiant['type'] = 'etudiant';
                        $comptes[] = $etudiant;
                    }
                    
                    // Récupérer tous les tuteurs
                    $tuteurs = $tuteurModel->getAllTuteurs();
                    foreach ($tuteurs as $tuteur) {
                        $tuteur['type'] = 'tuteur';
                        $comptes[] = $tuteur;
                    }
                    
                    echo json_encode($comptes);
                }
            }
            break;
            
        case 'POST':
            ob_clean(); // Nettoyer le buffer avant la réponse
            // Créer un nouveau compte
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                exit;
            }
            
            if (!isset($data['type']) || !in_array($data['type'], ['etudiant', 'tuteur'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'type est requis et doit être "etudiant" ou "tuteur"']);
                exit;
            }
            
            $compteType = $data['type'];
            
            if ($compteType === 'etudiant') {
                // Validation des champs requis pour étudiant
                if (!isset($data['numero_etudiant']) || empty(trim($data['numero_etudiant']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'numero_etudiant est requis']);
                    exit;
                }
                if (!isset($data['nom']) || empty(trim($data['nom']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'nom est requis']);
                    exit;
                }
                if (!isset($data['prenom']) || empty(trim($data['prenom']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'prenom est requis']);
                    exit;
                }
                if (!isset($data['email']) || empty(trim($data['email']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'email est requis']);
                    exit;
                }
                
                $id = $etudiantModel->creerEtudiant(
                    trim($data['numero_etudiant']),
                    trim($data['nom']),
                    trim($data['prenom']),
                    trim($data['email']),
                    isset($data['telephone']) ? trim($data['telephone']) : null,
                    isset($data['niveau']) ? trim($data['niveau']) : null,
                    isset($data['specialite']) ? trim($data['specialite']) : null,
                    isset($data['annee_etude']) ? (int)$data['annee_etude'] : null,
                    isset($data['actif']) ? (bool)$data['actif'] : true
                );
                
                if (!$id) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'Erreur lors de la création de l\'étudiant. Vérifiez que le numéro et l\'email sont uniques.']);
                    exit;
                }
                
                $compte = $etudiantModel->getEtudiantByIdForAdmin($id);
            } else {
                // Validation des champs requis pour tuteur
                if (!isset($data['numero_employe']) || empty(trim($data['numero_employe']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'numero_employe est requis']);
                    exit;
                }
                if (!isset($data['nom']) || empty(trim($data['nom']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'nom est requis']);
                    exit;
                }
                if (!isset($data['prenom']) || empty(trim($data['prenom']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'prenom est requis']);
                    exit;
                }
                if (!isset($data['email']) || empty(trim($data['email']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'email est requis']);
                    exit;
                }
                if (!isset($data['departement']) || empty(trim($data['departement']))) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'departement est requis']);
                    exit;
                }
                if (!isset($data['tarif_horaire'])) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'tarif_horaire est requis']);
                    exit;
                }
                
                $id = $tuteurModel->creerTuteur(
                    trim($data['numero_employe']),
                    trim($data['nom']),
                    trim($data['prenom']),
                    trim($data['email']),
                    trim($data['departement']),
                    (float)$data['tarif_horaire'],
                    isset($data['telephone']) ? trim($data['telephone']) : null,
                    isset($data['specialites']) ? trim($data['specialites']) : null,
                    isset($data['actif']) ? (bool)$data['actif'] : true
                );
                
                if (!$id) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'Erreur lors de la création du tuteur. Vérifiez que le numéro et l\'email sont uniques.']);
                    exit;
                }
                
                $compte = $tuteurModel->getTuteurByIdForAdmin($id);
            }
            
            if (!$compte) {
                ob_clean();
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la récupération du compte créé']);
                exit;
            }
            
            $compte['type'] = $compteType;
            
            // Nettoyer le buffer une dernière fois avant la réponse finale
            ob_clean();
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'compte' => $compte
            ]);
            exit; // Utiliser exit au lieu de break pour éviter toute sortie supplémentaire
            
        case 'PUT':
            ob_clean(); // Nettoyer le buffer avant la réponse
            // Mettre à jour un compte ou un service
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                exit;
            }
            
            // Vérifier si c'est une modification de service
            if (isset($data['resource']) && $data['resource'] === 'service') {
                if (!isset($data['id']) || empty($data['id'])) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'id du service est requis']);
                    exit;
                }
                
                $serviceModel = new Service($pdo);
                $serviceId = $data['id'];
                
                // Vérifier que le service existe
                $service = $serviceModel->getServiceById($serviceId);
                if (!$service) {
                    ob_clean();
                    http_response_code(404);
                    echo json_encode(['error' => 'Service non trouvé']);
                    exit;
                }
                
                // Modifier le service
                $description = isset($data['description']) ? trim($data['description']) : null;
                $nom = isset($data['nom']) ? trim($data['nom']) : null;
                $prix = isset($data['prix']) ? (float)$data['prix'] : null;
                $dureeMinute = isset($data['duree_minute']) ? (int)$data['duree_minute'] : null;
                
                $success = $serviceModel->modifierService($serviceId, $description, $nom, $prix, $dureeMinute);
                
                if (!$success) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'Erreur lors de la modification du service']);
                    exit;
                }
                
                // Récupérer le service mis à jour
                $service = $serviceModel->getServiceById($serviceId);
                
                ob_clean();
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Service modifié avec succès',
                    'service' => $service
                ]);
                exit;
            }
            
            // Sinon, c'est une modification de compte
            if (!isset($data['id']) || empty($data['id'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'id est requis']);
                exit;
            }
            
            if (!isset($data['type']) || !in_array($data['type'], ['etudiant', 'tuteur'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['error' => 'type est requis et doit être "etudiant" ou "tuteur"']);
                exit;
            }
            
            $compteId = $data['id'];
            $compteType = $data['type'];
            
            // Récupérer le compte existant (même si inactif - pour admin)
            if ($compteType === 'etudiant') {
                $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
                if (!$compte) {
                    ob_clean();
                    http_response_code(404);
                    echo json_encode(['error' => 'Étudiant non trouvé']);
                    exit;
                }
            } else {
                $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
                if (!$compte) {
                    ob_clean();
                    http_response_code(404);
                    echo json_encode(['error' => 'Tuteur non trouvé']);
                    exit;
                }
            }
            
            // Si seulement actif est fourni, utiliser updateActif (compatibilité)
            // Vérifier si on a seulement id, type et actif (pas de modification complète)
            $hasFullUpdate = isset($data['nom']) || isset($data['numero_etudiant']) || isset($data['numero_employe']);
            
            if (isset($data['actif']) && !$hasFullUpdate) {
                $actif = (bool)$data['actif'];
                
                if ($compteType === 'etudiant') {
                    $success = $etudiantModel->updateActif($compteId, $actif);
                } else {
                    $success = $tuteurModel->updateActif($compteId, $actif);
                }
                
                if (!$success) {
                    ob_clean();
                    http_response_code(500);
                    echo json_encode(['error' => 'Erreur lors de la mise à jour du statut']);
                    exit;
                }
                
                // Récupérer le compte mis à jour
                if ($compteType === 'etudiant') {
                    $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
                } else {
                    $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
                }
                
                $compte['type'] = $compteType;
                
                ob_clean();
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'compte' => $compte
                ]);
                exit;
            } else {
                // Modification complète des informations
                if ($compteType === 'etudiant') {
                    // Validation des champs requis pour étudiant
                    if (!isset($data['numero_etudiant']) || empty(trim($data['numero_etudiant']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'numero_etudiant est requis']);
                        exit;
                    }
                    if (!isset($data['nom']) || empty(trim($data['nom']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'nom est requis']);
                        exit;
                    }
                    if (!isset($data['prenom']) || empty(trim($data['prenom']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'prenom est requis']);
                        exit;
                    }
                    if (!isset($data['email']) || empty(trim($data['email']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'email est requis']);
                        exit;
                    }
                    
                    $success = $etudiantModel->modifierEtudiant(
                        $compteId,
                        trim($data['numero_etudiant']),
                        trim($data['nom']),
                        trim($data['prenom']),
                        trim($data['email']),
                        isset($data['telephone']) ? trim($data['telephone']) : null,
                        isset($data['niveau']) ? trim($data['niveau']) : null,
                        isset($data['specialite']) ? trim($data['specialite']) : null,
                        isset($data['annee_etude']) ? (int)$data['annee_etude'] : null,
                        isset($data['actif']) ? (bool)$data['actif'] : true
                    );
                } else {
                    // Validation des champs requis pour tuteur
                    if (!isset($data['numero_employe']) || empty(trim($data['numero_employe']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'numero_employe est requis']);
                        exit;
                    }
                    if (!isset($data['nom']) || empty(trim($data['nom']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'nom est requis']);
                        exit;
                    }
                    if (!isset($data['prenom']) || empty(trim($data['prenom']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'prenom est requis']);
                        exit;
                    }
                    if (!isset($data['email']) || empty(trim($data['email']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'email est requis']);
                        exit;
                    }
                    if (!isset($data['departement']) || empty(trim($data['departement']))) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'departement est requis']);
                        exit;
                    }
                    if (!isset($data['tarif_horaire'])) {
                        ob_clean();
                        http_response_code(400);
                        echo json_encode(['error' => 'tarif_horaire est requis']);
                        exit;
                    }
                    
                    $evaluation = null;
                    if (isset($data['evaluation']) && $data['evaluation'] !== '') {
                        $evalValue = (float)$data['evaluation'];
                        if ($evalValue >= 0 && $evalValue <= 5) {
                            $evaluation = $evalValue;
                        }
                    }
                    
                    $success = $tuteurModel->modifierTuteur(
                        $compteId,
                        trim($data['numero_employe']),
                        trim($data['nom']),
                        trim($data['prenom']),
                        trim($data['email']),
                        trim($data['departement']),
                        (float)$data['tarif_horaire'],
                        isset($data['telephone']) ? trim($data['telephone']) : null,
                        isset($data['specialites']) ? trim($data['specialites']) : null,
                        isset($data['actif']) ? (bool)$data['actif'] : true,
                        $evaluation
                    );
                }
                
                if (!$success) {
                    ob_clean();
                    http_response_code(400);
                    echo json_encode(['error' => 'Erreur lors de la modification du compte. Vérifiez que le numéro et l\'email sont uniques.']);
                    exit;
                }
            }
            
            // Récupérer le compte mis à jour (même si inactif - pour admin)
            if ($compteType === 'etudiant') {
                $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
            } else {
                $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
            }
            
            if (!$compte) {
                ob_clean();
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la récupération du compte mis à jour']);
                exit;
            }
            
            $compte['type'] = $compteType;
            
            // Nettoyer le buffer une dernière fois avant la réponse finale
            ob_clean();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Compte mis à jour avec succès',
                'compte' => $compte
            ]);
            exit;
            
        case 'DELETE':
            ob_clean(); // Nettoyer le buffer avant la réponse
            // Gérer les actions sur les rendez-vous (annulation)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                break;
            }
            
            $resource = $data['resource'] ?? null;
            $action = $data['action'] ?? null;
            $id = $data['id'] ?? null;
            
            if ($resource === 'rendez-vous' && $action === 'annuler' && $id) {
                $rendezVousModel = new RendezVous($pdo);
                $raison = $data['raison'] ?? null;
                
                $success = $rendezVousModel->annulerRendezVous($id, $raison);
                
                if ($success) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rendez-vous annulé avec succès'
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Impossible d\'annuler le rendez-vous. Il est peut-être déjà annulé ou terminé.'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Paramètres invalides pour l\'annulation']);
            }
            break;
            
        default:
            ob_clean(); // Nettoyer le buffer avant la réponse
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (PDOException $e) {
    // Nettoyer le buffer de sortie en cas d'erreur
    ob_clean();
    http_response_code(500);
    error_log("Erreur PDO API admin.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur base de données']);
} catch (Exception $e) {
    // Nettoyer le buffer de sortie en cas d'erreur
    ob_clean();
    http_response_code(500);
    error_log("Erreur API admin.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur serveur']);
} catch (Error $e) {
    // Capturer les erreurs fatales PHP 7+
    ob_clean();
    http_response_code(500);
    error_log("Erreur fatale API admin.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur serveur']);
}

// Le buffer sera automatiquement fermé par PHP à la fin du script

