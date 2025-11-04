<?php
/**
 * Traitement de la connexion simulée
 * TutoPlus - Système de tutorat
 */

session_start();

require_once 'config/database.php';
require_once 'models/Etudiant.php';

// Vérifier que le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Récupérer le numéro d'étudiant
$numeroEtudiant = isset($_POST['numero_etudiant']) ? trim($_POST['numero_etudiant']) : '';

if (empty($numeroEtudiant)) {
    $_SESSION['error'] = 'Le numéro d\'étudiant est requis.';
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $etudiantModel = new Etudiant($pdo);
    
    // Rechercher l'étudiant par numéro (connexion simulée)
    $etudiant = $etudiantModel->getEtudiantByNumero($numeroEtudiant);
    
    if ($etudiant) {
        // Connexion réussie
        $_SESSION['etudiant_id'] = $etudiant['id'];
        $_SESSION['etudiant_numero'] = $etudiant['numero_etudiant'];
        $_SESSION['etudiant_nom'] = $etudiant['nom'];
        $_SESSION['etudiant_prenom'] = $etudiant['prenom'];
        $_SESSION['etudiant_email'] = $etudiant['email'];
        
        // Mettre à jour la dernière connexion
        $etudiantModel->updateDerniereConnexion($etudiant['id']);
        
        // Rediriger vers la page d'accueil
        header('Location: index.php');
        exit;
    } else {
        // Échec de connexion
        $_SESSION['error'] = 'Numéro d\'étudiant invalide. Veuillez réessayer.';
        header('Location: login.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la connexion : " . $e->getMessage());
    $_SESSION['error'] = 'Une erreur est survenue. Veuillez réessayer plus tard.';
    header('Location: login.php');
    exit;
}

