<?php
/**
 * API rendez-vous.php
 * - GET : liste ou détail des rendez-vous (selon id, filtrable par statut/date)
 * - PUT : actions sur un rendez-vous (confirmer/annuler/reporter/terminer)
 */


session_start();

require_once '../config/database.php';
require_once '../models/RendezVous.php';
require_once '../models/Demande.php';
require_once '../models/Disponibilite.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $rendezVousModel = new RendezVous($pdo);
    
    /* Testé par Diane Devi le 21/11/2025 Réussi */
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'GET':
            // Récupérer les rendez-vous
            $id = $_GET['id'] ?? null;
            $statut = $_GET['statut'] ?? null;
            $date = $_GET['date'] ?? null;
            
            if ($id) {
                // Récupérer un rendez-vous spécifique
                $rendezVous = $rendezVousModel->getRendezVousById($id);
                
                if (!$rendezVous) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Rendez-vous non trouvé']);
                    break;
                }
                
                // Vérifier les permissions : étudiant ou tuteur associé
                if (isset($_SESSION['etudiant_id'])) {
                    if ($rendezVous['etudiant_id'] !== $_SESSION['etudiant_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Non autorisé - Ce rendez-vous ne vous appartient pas']);
                        break;
                    }
                } elseif (isset($_SESSION['tuteur_id'])) {
                    if ($rendezVous['tuteur_id'] !== $_SESSION['tuteur_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Non autorisé - Ce rendez-vous ne vous appartient pas']);
                        break;
                    }
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Non autorisé - Vous devez être connecté']);
                    break;
                }
                
                echo json_encode($rendezVous);
            } else {
                // Récupérer la liste des rendez-vous
                $rendezVousList = [];
                
                if (isset($_SESSION['etudiant_id'])) {
                    // Étudiant : voir ses propres rendez-vous
                    $rendezVousList = $rendezVousModel->getRendezVousByEtudiantId($_SESSION['etudiant_id']);
                    
                    // Ajouter aussi les demandes en attente et refusées (sans rendez-vous créé)
                    $demandeModel = new Demande($pdo);
                    $disponibiliteModel = new Disponibilite($pdo);
                    $toutesDemandes = $demandeModel->getDemandesByEtudiantId($_SESSION['etudiant_id']);
                    
                    // Filtrer pour ne garder que les demandes en attente ou refusées
                    $demandesAInclure = array_filter($toutesDemandes, function($demande) {
                        $statut = $demande['statut'] ?? null;
                        return $statut === 'EN_ATTENTE' || $statut === 'REFUSEE';
                    });
                    
                    foreach ($demandesAInclure as $demande) {
                        // Vérifier qu'il n'y a pas déjà un rendez-vous pour cette demande
                        $stmt = $pdo->prepare("SELECT id FROM rendez_vous WHERE demande_id = :demande_id LIMIT 1");
                        $stmt->bindParam(':demande_id', $demande['id'], PDO::PARAM_STR);
                        $stmt->execute();
                        $rendezVousExistant = $stmt->fetch();
                        
                        if (!$rendezVousExistant && $demande['disponibilite_id']) {
                            // Récupérer les informations de la disponibilité
                            $disponibilite = $disponibiliteModel->getDisponibiliteById($demande['disponibilite_id']);
                            
                            if ($disponibilite) {
                                // Utiliser le statut de la demande (EN_ATTENTE ou REFUSEE)
                                $statutDemande = $demande['statut'] ?? 'EN_ATTENTE';
                                
                                // Créer un objet similaire à un rendez-vous mais avec le statut de la demande
                                $rendezVousList[] = [
                                    'id' => null, // Pas encore de rendez-vous
                                    'demande_id' => $demande['id'],
                                    'etudiant_id' => $demande['etudiant_id'],
                                    'tuteur_id' => $demande['tuteur_id'],
                                    'service_id' => $demande['service_id'],
                                    'disponibilite_id' => $demande['disponibilite_id'],
                                    'date_heure' => $disponibilite['date_debut'], // Utiliser la date de début de la disponibilité
                                    'statut' => $statutDemande, // Statut de la demande (EN_ATTENTE ou REFUSEE)
                                    'duree' => null, // Sera calculé plus tard
                                    'lieu' => null,
                                    'notes' => $demande['motif'] ?? null, // Utiliser motif (qui contient la raison du refus si refusée)
                                    'prix' => null, // Sera calculé plus tard
                                    'date_creation' => $demande['date_creation'],
                                    'tuteur_nom' => $demande['tuteur_nom'] ?? null,
                                    'tuteur_prenom' => $demande['tuteur_prenom'] ?? null,
                                    'service_nom' => $demande['service_nom'] ?? null,
                                    'service_categorie' => $demande['service_categorie'] ?? null
                                ];
                            }
                        }
                    }
                } elseif (isset($_SESSION['tuteur_id'])) {
                    // Tuteur : voir ses rendez-vous
                    $rendezVousList = $rendezVousModel->getRendezVousByTuteurId($_SESSION['tuteur_id']);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Non autorisé - Vous devez être connecté']);
                    break;
                }
                
                // Filtrer par statut si fourni
                if ($statut) {
                    $rendezVousList = array_filter($rendezVousList, function($rv) use ($statut) {
                        return $rv['statut'] === $statut;
                    });
                    $rendezVousList = array_values($rendezVousList); // Réindexer
                }
                
                // Filtrer par date si fourni (format YYYY-MM-DD)
                if ($date) {
                    $dateFilter = new DateTime($date);
                    $rendezVousList = array_filter($rendezVousList, function($rv) use ($dateFilter) {
                        $rvDate = new DateTime($rv['date_heure']);
                        return $rvDate->format('Y-m-d') === $dateFilter->format('Y-m-d');
                    });
                    $rendezVousList = array_values($rendezVousList); // Réindexer
                }
                
                echo json_encode($rendezVousList);
            }
            break;
        /* Testé par Diane Devi le 23/11/2025 Réussi */    
        case 'PUT':
            // Mettre à jour un rendez-vous (confirmer, annuler, reporter, terminer)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Vérifier que le JSON est valide
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
            
            $rendezVousId = $data['id'];
            $rendezVous = $rendezVousModel->getRendezVousById($rendezVousId);
            
            if (!$rendezVous) {
                http_response_code(404);
                echo json_encode(['error' => 'Rendez-vous non trouvé']);
                break;
            }
            
            // Vérifier les permissions : étudiant ou tuteur associé
            $isAuthorized = false;
            if (isset($_SESSION['etudiant_id']) && $rendezVous['etudiant_id'] === $_SESSION['etudiant_id']) {
                $isAuthorized = true;
            } elseif (isset($_SESSION['tuteur_id']) && $rendezVous['tuteur_id'] === $_SESSION['tuteur_id']) {
                $isAuthorized = true;
            }
            
            if (!$isAuthorized) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé - Ce rendez-vous ne vous appartient pas']);
                break;
            }
            
            // Gérer les différentes actions
            if (!isset($data['action'])) {
                http_response_code(400);
                echo json_encode(['error' => 'action est requise (confirmer, annuler, reporter, terminer)']);
                break;
            }
            
            $action = $data['action'];
            $success = false;
            $message = '';
            
            switch ($action) {
                case 'confirmer':
                    $success = $rendezVousModel->confirmerRendezVous($rendezVousId);
                    $message = $success ? 'Rendez-vous confirmé avec succès' : 'Erreur lors de la confirmation du rendez-vous';
                    break;
                    
                case 'annuler':
                    $raison = $data['raison'] ?? null;
                    $success = $rendezVousModel->annulerRendezVous($rendezVousId, $raison);
                    $message = $success ? 'Rendez-vous annulé avec succès' : 'Erreur lors de l\'annulation du rendez-vous';
                    break;
                    
                case 'reporter':
                    if (!isset($data['nouvelle_date']) || empty($data['nouvelle_date'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'nouvelle_date est requise pour reporter un rendez-vous']);
                        break 2;
                    }
                    $nouvelleDate = $data['nouvelle_date'];
                    $success = $rendezVousModel->reporterRendezVous($rendezVousId, $nouvelleDate);
                    $message = $success ? 'Rendez-vous reporté avec succès' : 'Erreur lors du report du rendez-vous';
                    break;
                    
                case 'terminer':
                    // Seul le tuteur peut terminer un rendez-vous
                    if (!isset($_SESSION['tuteur_id']) || $rendezVous['tuteur_id'] !== $_SESSION['tuteur_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Seul le tuteur peut terminer un rendez-vous']);
                        break 2;
                    }
                    $success = $rendezVousModel->terminerRendezVous($rendezVousId);
                    $message = $success ? 'Rendez-vous terminé avec succès' : 'Erreur lors de la finalisation du rendez-vous';
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action invalide. Utilisez: confirmer, annuler, reporter, terminer']);
                    break 2;
            }
            
            if ($success) {
                http_response_code(200);
                $rendezVous = $rendezVousModel->getRendezVousById($rendezVousId);
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'rendez_vous' => $rendezVous
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => $message]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

