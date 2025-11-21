<?php
/**
 * API demandes.php
 * - GET    : liste ou détail des demandes (selon présence de id)
 * - POST   : création d'une demande (étudiant connecté)
 * - PUT    : mise à jour (tuteur : accepter/refuser, étudiant : modifier en attente)
 * - DELETE : (non implémenté pour l'instant)
 */

session_start();

require_once '../config/database.php';
require_once '../models/Demande.php';
require_once '../services/ReservationService.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $demandeModel = new Demande($pdo);
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'GET':
            // Récupérer les demandes
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                // Récupérer une demande spécifique
                $demande = $demandeModel->getDemandeById($id);
                
                if (!$demande) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Demande non trouvée']);
                    break;
                }
                
                // Vérifier les permissions : étudiant ou tuteur associé
                if (isset($_SESSION['etudiant_id'])) {
                    if ($demande['etudiant_id'] !== $_SESSION['etudiant_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Non autorisé - Cette demande ne vous appartient pas']);
                        break;
                    }
                } elseif (isset($_SESSION['tuteur_id'])) {
                    if ($demande['tuteur_id'] !== $_SESSION['tuteur_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Non autorisé - Cette demande ne vous appartient pas']);
                        break;
                    }
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Non autorisé - Vous devez être connecté']);
                    break;
                }
                
                echo json_encode($demande);
            } else {
                // Récupérer la liste des demandes
                if (isset($_SESSION['etudiant_id'])) {
                    // Étudiant : voir ses propres demandes
                    $demandes = $demandeModel->getDemandesByEtudiantId($_SESSION['etudiant_id']);
                    echo json_encode($demandes);
                } elseif (isset($_SESSION['tuteur_id'])) {
                    // Tuteur : voir les demandes qui lui sont adressées
                    $demandes = $demandeModel->getDemandesByTuteurId($_SESSION['tuteur_id']);
                    echo json_encode($demandes);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Non autorisé - Vous devez être connecté']);
                }
            }
            break;
            
        case 'POST':
            // Créer une nouvelle demande
            if (!isset($_SESSION['etudiant_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Seuls les étudiants peuvent créer des demandes']);
                break;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Vérifier que le JSON est valide
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                break;
            }
            
            // Validation des champs requis
            if (!isset($data['service_id']) || empty($data['service_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'service_id est requis']);
                break;
            }
            
            if (!isset($data['tuteur_id']) || empty($data['tuteur_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'tuteur_id est requis']);
                break;
            }
            
            $etudiantId = $_SESSION['etudiant_id'];
            $serviceId = $data['service_id'];
            $tuteurId = $data['tuteur_id'];
            $disponibiliteId = $data['disponibilite_id'] ?? null;
            $motif = $data['motif'] ?? null;
            $priorite = $data['priorite'] ?? null;
            
            $demandeId = $demandeModel->creerDemande(
                $etudiantId,
                $serviceId,
                $tuteurId,
                $disponibiliteId,
                $motif,
                $priorite
            );
            
            if ($demandeId) {
                http_response_code(201);
                $demande = $demandeModel->getDemandeById($demandeId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Demande créée avec succès',
                    'demande' => $demande
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Erreur lors de la création de la demande']);
            }
            break;
            
        /* Testé par Diane Devi le 21/11/2025 Réussi */    
        case 'PUT':
            // Mettre à jour une demande (accepter/refuser pour les tuteurs, modifier pour les étudiants)
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
            
            $demandeId = $data['id'];
            $demande = $demandeModel->getDemandeById($demandeId);
            
            if (!$demande) {
                http_response_code(404);
                echo json_encode(['error' => 'Demande non trouvée']);
                break;
            }
            
            // Vérifier les permissions
            if (isset($_SESSION['tuteur_id'])) {
                // Tuteur : peut accepter/refuser
                if ($demande['tuteur_id'] !== $_SESSION['tuteur_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Non autorisé - Cette demande ne vous appartient pas']);
                    break;
                }
                
                if (isset($data['action'])) {
                    $reservationService = new ReservationService($pdo);
                    
                    if ($data['action'] === 'accepter') {
                        // Accepter la demande et créer le rendez-vous
                        $rendezVousId = $reservationService->confirmerDemande($demandeId);
                        $success = $rendezVousId !== false;
                        
                        if ($success) {
                            $message = 'Demande acceptée avec succès. Le rendez-vous a été créé.';
                        } else {
                            // Récupérer plus d'informations sur l'erreur
                            $demandeCheck = $demandeModel->getDemandeById($demandeId);
                            if ($demandeCheck && $demandeCheck['statut'] !== 'EN_ATTENTE') {
                                $message = 'Cette demande ne peut plus être acceptée (statut: ' . $demandeCheck['statut'] . ')';
                            } else {
                                $message = 'Erreur lors de l\'acceptation de la demande. Vérifiez les logs pour plus de détails.';
                            }
                        }
                    } elseif ($data['action'] === 'refuser') {
                        // Refuser la demande et libérer la disponibilité
                        $raison = $data['raison'] ?? null;
                        $success = $demandeModel->refuserDemande($demandeId, $raison);
                        
                        // Libérer la disponibilité si elle était réservée
                        if ($success && $demande['disponibilite_id']) {
                            require_once '../models/Disponibilite.php';
                            $disponibiliteModel = new Disponibilite($pdo);
                            $disponibilite = $disponibiliteModel->getDisponibiliteById($demande['disponibilite_id']);
                            
                            if ($disponibilite) {
                                $disponibiliteModel->modifierDisponibilite(
                                    $demande['disponibilite_id'],
                                    $disponibilite['date_debut'],
                                    $disponibilite['date_fin'],
                                    'DISPONIBLE',
                                    null,
                                    null,
                                    null,
                                    null  // libérer l'etudiant_id
                                );
                            }
                        }
                        
                        $message = $success ? 'Demande refusée avec succès' : 'Erreur lors du refus de la demande';
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Action invalide. Utilisez "accepter" ou "refuser"']);
                        break;
                    }
                    
                    if ($success) {
                        http_response_code(200);
                        $demande = $demandeModel->getDemandeById($demandeId);
                        echo json_encode([
                            'success' => true,
                            'message' => $message,
                            'demande' => $demande
                        ]);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => $message]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Action requise pour les tuteurs (accepter/refuser)']);
                }
            } elseif (isset($_SESSION['etudiant_id'])) {
                // Étudiant : peut modifier sa demande si elle est en attente
                if ($demande['etudiant_id'] !== $_SESSION['etudiant_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Non autorisé - Cette demande ne vous appartient pas']);
                    break;
                }
                
                if ($demande['statut'] !== 'EN_ATTENTE') {
                    http_response_code(400);
                    echo json_encode(['error' => 'Impossible de modifier une demande qui n\'est pas en attente']);
                    break;
                }
                
                $updateData = [];
                if (isset($data['motif'])) {
                    $updateData['motif'] = $data['motif'];
                }
                if (isset($data['priorite'])) {
                    $updateData['priorite'] = $data['priorite'];
                }
                if (isset($data['disponibilite_id'])) {
                    $updateData['disponibilite_id'] = $data['disponibilite_id'];
                }
                
                if (empty($updateData)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Aucune donnée à mettre à jour']);
                    break;
                }
                
                $success = $demandeModel->mettreAJourDemande($demandeId, $updateData);
                
                if ($success) {
                    http_response_code(200);
                    $demande = $demandeModel->getDemandeById($demandeId);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Demande mise à jour avec succès',
                        'demande' => $demande
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Erreur lors de la mise à jour de la demande']);
                }
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Vous devez être connecté']);
            }
            break;
            
        case 'DELETE':
            // Supprimer une demande (seulement si EN_ATTENTE et par l'étudiant)
            if (!isset($_SESSION['etudiant_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Seuls les étudiants peuvent supprimer leurs demandes']);
                break;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            $id = $data['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id est requis']);
                break;
            }
            
            $demande = $demandeModel->getDemandeById($id);
            
            if (!$demande) {
                http_response_code(404);
                echo json_encode(['error' => 'Demande non trouvée']);
                break;
            }
            
            // Vérifier que la demande appartient à l'étudiant
            if ($demande['etudiant_id'] !== $_SESSION['etudiant_id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé - Cette demande ne vous appartient pas']);
                break;
            }
            
            // Vérifier que la demande est en attente
            if ($demande['statut'] !== 'EN_ATTENTE') {
                http_response_code(400);
                echo json_encode(['error' => 'Impossible de supprimer une demande qui n\'est pas en attente']);
                break;
            }
            
            // Note : On devrait avoir une méthode supprimerDemande dans le modèle
            // Pour l'instant, on refuse la suppression via l'API
            // On pourrait mettre le statut à EXPIRED à la place
            http_response_code(501);
            echo json_encode(['error' => 'La suppression de demandes n\'est pas encore implémentée']);
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

