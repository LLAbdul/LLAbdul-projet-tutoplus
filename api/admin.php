<?php
/**
 * API admin.php
 * - GET    : liste des comptes (étudiants et tuteurs) ou détails d'un compte
 *            GET ?resource=rendez-vous : liste de tous les rendez-vous
 * - PUT    : modification d'un compte (activer/désactiver, modifier les informations)
 * - DELETE : (non implémenté pour l'instant)
 */

session_start();

require_once '../config/database.php';
require_once '../models/Etudiant.php';
require_once '../models/Tuteur.php';
require_once '../models/RendezVous.php';

header('Content-Type: application/json');

// Vérifier que l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
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
            // Déterminer la ressource demandée (comptes ou rendez-vous)
            $resource = $_GET['resource'] ?? 'comptes';
            
            if ($resource === 'rendez-vous') {
                // Récupérer tous les rendez-vous
                $rendezVousModel = new RendezVous($pdo);
                $rendezVous = $rendezVousModel->getAllRendezVous();
                echo json_encode($rendezVous);
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
            
        case 'PUT':
            // Mettre à jour un compte
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                break;
            }
            
            if (!isset($data['id']) || empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id est requis']);
                break;
            }
            
            if (!isset($data['type']) || !in_array($data['type'], ['etudiant', 'tuteur'])) {
                http_response_code(400);
                echo json_encode(['error' => 'type est requis et doit être "etudiant" ou "tuteur"']);
                break;
            }
            
            $compteId = $data['id'];
            $compteType = $data['type'];
            
            // Récupérer le compte existant (même si inactif - pour admin)
            if ($compteType === 'etudiant') {
                $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
                if (!$compte) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Étudiant non trouvé']);
                    break;
                }
            } else {
                $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
                if (!$compte) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Tuteur non trouvé']);
                    break;
                }
            }
            
            // Mettre à jour le statut actif si fourni
            if (isset($data['actif'])) {
                $actif = (bool)$data['actif'];
                
                if ($compteType === 'etudiant') {
                    $success = $etudiantModel->updateActif($compteId, $actif);
                } else {
                    $success = $tuteurModel->updateActif($compteId, $actif);
                }
                
                if (!$success) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erreur lors de la mise à jour du statut']);
                    break;
                }
            }
            
            // Récupérer le compte mis à jour (même si inactif - pour admin)
            if ($compteType === 'etudiant') {
                $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
            } else {
                $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
            }
            
            $compte['type'] = $compteType;
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Compte mis à jour avec succès',
                'compte' => $compte
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erreur API admin.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

