<?php
/**
 * API endpoint pour gérer les réservations des étudiants
 * TutoPlus - Système de tutorat
 */

session_start();

require_once '../config/database.php';
require_once '../models/Disponibilite.php';

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
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'POST':
            // Créer une réservation
            $data = json_decode(file_get_contents('php://input'), true);
            
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
            
            // Changer le statut du créneau à RESERVE
            $result = $disponibiliteModel->modifierDisponibilite(
                $disponibiliteId,
                $disponibilite['date_debut'], // date_debut (inchangé)
                $disponibilite['date_fin'], // date_fin (inchangé)
                'RESERVE' // statut
            );
            
            if (!$result) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la réservation du créneau']);
                break;
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'disponibilite_id' => $disponibiliteId,
                'disponibilite' => $disponibilite
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

