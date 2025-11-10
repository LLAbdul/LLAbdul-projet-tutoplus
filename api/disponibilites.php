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
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

