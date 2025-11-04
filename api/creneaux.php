<?php
/**
 * API endpoint pour récupérer les créneaux d'un service
 */

require_once '../config/database.php';
require_once '../models/Service.php';
require_once '../models/Disponibilite.php';

header('Content-Type: application/json');

if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'service_id requis']);
    exit;
}

$serviceId = $_GET['service_id'];

try {
    $pdo = getDBConnection();
    
    // Récupération du service
    $serviceModel = new Service($pdo);
    $service = $serviceModel->getServiceById($serviceId);
    
    if (!$service) {
        http_response_code(404);
        echo json_encode(['error' => 'Service non trouvé']);
        exit;
    }
    
    // Récupération des disponibilités
    $disponibiliteModel = new Disponibilite($pdo);
    $disponibilites = $disponibiliteModel->getDisponibilitesByServiceId($serviceId);
    
    // Grouper par date
    $creneauxParDate = [];
    foreach ($disponibilites as $dispo) {
        $date = date('Y-m-d', strtotime($dispo['date_debut']));
        if (!isset($creneauxParDate[$date])) {
            $creneauxParDate[$date] = [];
        }
        $creneauxParDate[$date][] = [
            'id' => $dispo['id'],
            'date_debut' => $dispo['date_debut'],
            'date_fin' => $dispo['date_fin'],
            'heure_debut' => date('H:i', strtotime($dispo['date_debut'])),
            'heure_fin' => date('H:i', strtotime($dispo['date_fin'])),
            'prix' => $dispo['prix'] ?? $service['prix']
        ];
    }
    
    ksort($creneauxParDate);
    
    echo json_encode([
        'service' => [
            'id' => $service['id'],
            'nom' => $service['nom'],
            'categorie' => $service['categorie']
        ],
        'creneaux' => $creneauxParDate
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

