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
                echo json_encode(['error' => 'Créneau non trouvé']);
                break;
            }
            
            // Validation : vérifier que le créneau est disponible
            if ($disponibilite['statut'] !== 'DISPONIBLE') {
                http_response_code(400);
                echo json_encode(['error' => 'Ce créneau n\'est plus disponible']);
                break;
            }
            
            // TODO: Implémenter la logique de réservation
            // Pour l'instant, retourner une réponse de succès
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'disponibilite_id' => $disponibiliteId
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

