<?php
/**
 * API endpoint pour récupérer la liste des tuteurs actifs
 * TutoPlus - Système de tutorat
 */

session_start();

require_once '../config/database.php';
require_once '../models/Tuteur.php';

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
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    error_log("Erreur API tuteurs : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur lors de la récupération des tuteurs']);
}

