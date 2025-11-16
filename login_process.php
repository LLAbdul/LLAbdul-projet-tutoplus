<?php
/**
 * Script login_process.php
 * - POST uniquement : traitement de la connexion simulée pour les étudiants
 * - Recherche de l’étudiant par numéro
 * - Remplit la session + met à jour la dernière connexion
 * - Redirige vers index.php ou login.php avec message d’erreur
 */

session_start();

require_once 'config/database.php';
require_once 'models/Etudiant.php';

// Vérifier que le formulaire a été soumis en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Récupérer le numéro d'étudiant
$numeroEtudiant = isset($_POST['numero_etudiant']) ? trim($_POST['numero_etudiant']) : '';

if ($numeroEtudiant === '') {
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
        // Connexion réussie : stocker les infos en session
        $_SESSION['etudiant_id']      = $etudiant['id'];
        $_SESSION['etudiant_numero']  = $etudiant['numero_etudiant'];
        $_SESSION['etudiant_nom']     = $etudiant['nom'];
        $_SESSION['etudiant_prenom']  = $etudiant['prenom'];
        $_SESSION['etudiant_email']   = $etudiant['email'];

        // Mettre à jour la dernière connexion
        $etudiantModel->updateDerniereConnexion($etudiant['id']);

        // Rediriger vers la page d'accueil
        header('Location: index.php');
        exit;
    }

    // Échec de connexion
    $_SESSION['error'] = 'Numéro d\'étudiant invalide. Veuillez réessayer.';
    header('Location: login.php');
    exit;

} catch (Exception $e) {
    error_log('Erreur lors de la connexion étudiant : ' . $e->getMessage());
    $_SESSION['error'] = 'Une erreur est survenue. Veuillez réessayer plus tard.';
    header('Location: login.php');
    exit;
}
