<?php
/**
 * API Statistiques
 * Endpoint pour récupérer les statistiques (admin uniquement)
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

session_start();

// Vérifier que l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé. Administrateur requis.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Statistiques.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de connexion à la base de données']);
        exit;
    }
    $statistiquesModel = new Statistiques($pdo);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Récupérer toutes les statistiques
        $stats = $statistiquesModel->getAllStatistiques();
        
        http_response_code(200);
        echo json_encode($stats, JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        exit;
    }
}
