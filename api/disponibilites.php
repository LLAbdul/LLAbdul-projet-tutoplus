<?php
/**
 * API endpoint pour gérer les disponibilités des tuteurs
 * TutoPlus - Système de tutorat
 */

session_start();

require_once '../config/database.php';
require_once '../models/Disponibilite.php';

header('Content-Type: application/json');

// Vérifier que le tuteur est connecté
if (!isset($_SESSION['tuteur_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$tuteurId = $_SESSION['tuteur_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $disponibiliteModel = new Disponibilite($pdo);
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'GET':
            // Récupérer les disponibilités du tuteur
            $disponibilites = $disponibiliteModel->getDisponibilitesByTuteurId($tuteurId);
            
            // Formater les disponibilités pour FullCalendar
            $events = [];
            foreach ($disponibilites as $dispo) {
                $events[] = [
                    'id' => $dispo['id'],
                    'title' => $dispo['statut'] === 'RESERVE' ? 'Réservé' : ($dispo['service_nom'] ?? 'Disponible'),
                    'start' => $dispo['date_debut'],
                    'end' => $dispo['date_fin'],
                    'color' => $dispo['statut'] === 'RESERVE' ? '#dc3545' : ($dispo['statut'] === 'BLOQUE' ? '#6c757d' : '#28a745'),
                    'extendedProps' => [
                        'statut' => $dispo['statut'],
                        'service_id' => $dispo['service_id'],
                        'service_nom' => $dispo['service_nom'],
                        'prix' => $dispo['prix'],
                        'notes' => $dispo['notes']
                    ]
                ];
            }
            
            echo json_encode($events);
            break;
            
        case 'POST':
            // Créer une nouvelle disponibilité
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['date_debut']) || !isset($data['date_fin'])) {
                http_response_code(400);
                echo json_encode(['error' => 'date_debut et date_fin sont requis']);
                break;
            }
            
            $dateDebut = $data['date_debut'];
            $dateFin = $data['date_fin'];
            $statut = $data['statut'] ?? 'DISPONIBLE';
            $serviceId = $data['service_id'] ?? null;
            $prix = $data['prix'] ?? null;
            $notes = $data['notes'] ?? null;
            
            $id = $disponibiliteModel->creerDisponibilite($tuteurId, $dateDebut, $dateFin, $statut, $serviceId, $prix, $notes);
            
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Disponibilité créée avec succès']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Erreur lors de la création de la disponibilité']);
            }
            break;
            
        case 'PUT':
            // Modifier une disponibilité existante
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['date_debut']) || !isset($data['date_fin'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id, date_debut et date_fin sont requis']);
                break;
            }
            
            $id = $data['id'];
            
            // Vérifier que la disponibilité appartient au tuteur
            $disponibilite = $disponibiliteModel->getDisponibiliteById($id);
            if (!$disponibilite || $disponibilite['tuteur_id'] !== $tuteurId) {
                http_response_code(403);
                echo json_encode(['error' => 'Disponibilité non trouvée ou non autorisée']);
                break;
            }
            
            $dateDebut = $data['date_debut'];
            $dateFin = $data['date_fin'];
            $statut = $data['statut'] ?? null;
            $serviceId = $data['service_id'] ?? null;
            $prix = $data['prix'] ?? null;
            $notes = $data['notes'] ?? null;
            
            $success = $disponibiliteModel->modifierDisponibilite($id, $dateDebut, $dateFin, $statut, $serviceId, $prix, $notes);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Disponibilité modifiée avec succès']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Erreur lors de la modification de la disponibilité']);
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

