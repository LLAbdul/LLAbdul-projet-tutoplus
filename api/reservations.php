<?php
/* Testé par Diane Devi le 24/11/2025 Réussi */
// Testé par Abdul Rahman Zahid le 16/11/2025 Réussi
/**
 * API reservations.php
 * - POST : réservation d’un créneau (création demande + rendez-vous)
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
            
            // Créer une demande (statut EN_ATTENTE - le tuteur devra l'accepter)
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
                echo json_encode(['error' => 'Erreur lors de la création de la demande. Veuillez réessayer.']);
                break;
            }
            
            // Marquer temporairement la disponibilité comme réservée pour éviter les doubles réservations
            // Si le tuteur refuse, la disponibilité sera libérée
            $reserveOk = $disponibiliteModel->modifierDisponibilite(
                $disponibiliteId,
                $disponibilite['date_debut'],
                $disponibilite['date_fin'],
                'RESERVE',
                null,
                null,
                null,
                $etudiantId
            );
            
            if (!$reserveOk) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la réservation du créneau']);
                break;
            }
            
            // Récupérer la demande créée
            require_once '../models/Demande.php';
            $demandeModel = new Demande($pdo);
            $demandeComplete = $demandeModel->getDemandeById($demandeId);
            
            if (!$demandeComplete) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la récupération des données de la demande']);
                break;
            }
            
            // Récupérer les informations complètes de la disponibilité pour l'affichage
            $disponibiliteComplete = $disponibiliteModel->getDisponibiliteById($disponibiliteId);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Demande créée avec succès. Le tuteur doit l\'accepter pour confirmer le rendez-vous.',
                'disponibilite_id' => $disponibiliteId,
                'demande_id' => $demandeId,
                'demande' => $demandeComplete,
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

