<?php
/**
 * Script login_admin_process.php
 * - POST uniquement : traitement de la connexion simulée pour les administrateurs
 * - Recherche de l'administrateur par numéro
 * - Remplit la session + met à jour la dernière connexion
 * - Redirige vers admin.php ou login.php avec message d'erreur
 */

session_start();

require_once 'config/database.php';
require_once 'models/Administrateur.php';

// Vérifier que le formulaire a été soumis en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?type=admin');
    exit;
}

// Récupérer le numéro d'administrateur
$numeroAdmin = isset($_POST['numero_admin']) ? trim($_POST['numero_admin']) : '';

if ($numeroAdmin === '') {
    $_SESSION['error'] = 'Le numéro d\'administrateur est requis.';
    header('Location: login.php?type=admin');
    exit;
}

try {
    $pdo = getDBConnection();
    $adminModel = new Administrateur($pdo);

    // Rechercher l'administrateur par numéro (connexion simulée)
    $admin = $adminModel->getAdministrateurByNumero($numeroAdmin);

    if ($admin) {
        // Connexion réussie : stocker les infos en session
        $_SESSION['admin_id']         = $admin['id'];
        $_SESSION['admin_numero']     = $admin['numero_admin'];
        $_SESSION['admin_nom']        = $admin['nom'];
        $_SESSION['admin_prenom']     = $admin['prenom'];
        $_SESSION['admin_email']      = $admin['email'];
        $_SESSION['admin_niveau']     = $admin['niveau_acces'];

        // Mettre à jour la dernière connexion
        $adminModel->updateDerniereConnexion($admin['id']);

        // Rediriger vers la page d'administration
        header('Location: admin.php');
        exit;
    }

    // Échec de connexion
    $_SESSION['error'] = 'Numéro d\'administrateur invalide. Veuillez réessayer.';
    header('Location: login.php?type=admin');
    exit;

} catch (Exception $e) {
    error_log('Erreur lors de la connexion administrateur : ' . $e->getMessage());
    $_SESSION['error'] = 'Une erreur est survenue lors de la connexion. Veuillez réessayer.';
    header('Location: login.php?type=admin');
    exit;
}

