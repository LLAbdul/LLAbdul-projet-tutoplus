<?php
/**
 * API tuteurs.php
 * - GET : liste des tuteurs actifs (id, nom, prenom, departement, etc.)
 * - PUT : modification d'un service du tuteur connecté
 */

session_start();

require_once '../config/database.php';
require_once '../models/Tuteur.php';
require_once '../models/Service.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $tuteurModel = new Tuteur($pdo);
    
    if ($method === 'GET') {
        // Récupérer tous les tuteurs actifs
        $tuteurs = $tuteurModel->getAllActiveTuteurs();
        
        // Formater les données pour la réponse
        $tuteursFormatted = array_map(function($tuteur) {
            return [
                'id' => $tuteur['id'],
                'nom' => $tuteur['nom'],
                'prenom' => $tuteur['prenom'],
                'nom_complet' => $tuteur['prenom'] . ' ' . $tuteur['nom'],
                'departement' => $tuteur['departement'],
                'evaluation' => $tuteur['evaluation'] ?? 0
            ];
        }, $tuteurs);
        
        http_response_code(200);
        echo json_encode($tuteursFormatted);
    } elseif ($method === 'PUT') {
        // Vérifier que le tuteur est connecté
        if (!isset($_SESSION['tuteur_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé - Tuteur non connecté']);
            exit;
        }
        
        // Modifier un service du tuteur
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Format JSON invalide']);
            exit;
        }
        
        if (!isset($data['resource']) || $data['resource'] !== 'service') {
            http_response_code(400);
            echo json_encode(['error' => 'resource doit être "service"']);
            exit;
        }
        
        if (!isset($data['id']) || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id du service est requis']);
            exit;
        }
        
        $serviceModel = new Service($pdo);
        $serviceId = $data['id'];
        $tuteurId = $_SESSION['tuteur_id'];
        
        // Vérifier que le service appartient au tuteur
        $service = $serviceModel->getServiceByIdForTuteur($serviceId, $tuteurId);
        if (!$service) {
            http_response_code(404);
            echo json_encode(['error' => 'Service non trouvé ou ne vous appartient pas']);
            exit;
        }
        
        // Modifier le service
        $description = isset($data['description']) ? trim($data['description']) : null;
        $nom = isset($data['nom']) ? trim($data['nom']) : null;
        $prix = isset($data['prix']) ? (float)$data['prix'] : null;
        $dureeMinute = isset($data['duree_minute']) ? (int)$data['duree_minute'] : null;
        
        $success = $serviceModel->modifierService($serviceId, $description, $nom, $prix, $dureeMinute);
        
        if (!$success) {
            http_response_code(400);
            echo json_encode(['error' => 'Erreur lors de la modification du service']);
            exit;
        }
        
        // Récupérer le service mis à jour
        $service = $serviceModel->getServiceByIdForTuteur($serviceId, $tuteurId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Service modifié avec succès',
            'service' => $service
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    error_log("Erreur API tuteurs : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}

