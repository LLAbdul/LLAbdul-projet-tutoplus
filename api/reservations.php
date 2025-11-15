<?php
/**
 * API endpoint pour gérer les réservations des étudiants
 * TutoPlus - Système de tutorat
 */

session_start();

require_once '../config/database.php';
require_once '../models/Disponibilite.php';
require_once '../services/ReservationService.php';

header('Content-Type: application/json');

// Vérifier que l'étudiant est connecté
if (!isset($_SESSION['etudiant_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé - Étudiant non connecté']);
    exit;
}

$etudiantId = $_SESSION['etudiant_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $disponibiliteModel = new Disponibilite($pdo);
    $reservationService = new ReservationService($pdo);
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'POST':
            // Créer une réservation via le service (Demande → RendezVous)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Vérifier que le JSON est valide
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                break;
            }
            
            if (!isset($data['disponibilite_id']) || empty($data['disponibilite_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'disponibilite_id est requis']);
                break;
            }
            
            $disponibiliteId = $data['disponibilite_id'];
            
            // Validation : vérifier que le créneau existe
            $disponibilite = $disponibiliteModel->getDisponibiliteById($disponibiliteId);
            
            if (!$disponibilite) {
                http_response_code(404);
                echo json_encode(['error' => 'Le créneau sélectionné n\'existe pas ou a été supprimé']);
                break;
            }
            
            // Validation : vérifier que le créneau est disponible
            if ($disponibilite['statut'] !== 'DISPONIBLE') {
                $statutMessage = $disponibilite['statut'] === 'RESERVE' 
                    ? 'Ce créneau a déjà été réservé par un autre étudiant' 
                    : 'Ce créneau n\'est plus disponible';
                http_response_code(400);
                echo json_encode(['error' => $statutMessage]);
                break;
            }
            
            // Validation : vérifier que le créneau n'est pas dans le passé
            $dateDebut = new DateTime($disponibilite['date_debut']);
            $now = new DateTime();
            
            if ($dateDebut < $now) {
                http_response_code(400);
                echo json_encode(['error' => 'Impossible de réserver un créneau qui est déjà passé']);
                break;
            }
            
            // Validation : vérifier que service_id et tuteur_id sont présents
            if (!$disponibilite['service_id'] || !$disponibilite['tuteur_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Le créneau doit être associé à un service et un tuteur']);
                break;
            }
            
            // Créer une demande
            $motif = $data['motif'] ?? null;
            $priorite = $data['priorite'] ?? null;
            $demandeId = $reservationService->creerDemande(
                $etudiantId,
                $disponibilite['service_id'],
                $disponibilite['tuteur_id'],
                $disponibiliteId,
                $motif,
                $priorite
            );
            
            if (!$demandeId) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la création de la demande']);
                break;
            }
            
            // Confirmer la demande (crée automatiquement le rendez-vous et réserve la disponibilité)
            $rendezVousId = $reservationService->confirmerDemande($demandeId);
            
            if (!$rendezVousId) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la confirmation de la réservation']);
                break;
            }
            
            // Récupérer les informations complètes de la disponibilité après modification
            $disponibiliteComplete = $disponibiliteModel->getDisponibiliteById($disponibiliteId);
            
            if (!$disponibiliteComplete) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la récupération des données de réservation']);
                break;
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'disponibilite_id' => $disponibiliteId,
                'demande_id' => $demandeId,
                'rendez_vous_id' => $rendezVousId,
                'disponibilite' => $disponibiliteComplete
            ]);
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

