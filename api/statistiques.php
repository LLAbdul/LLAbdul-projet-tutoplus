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
