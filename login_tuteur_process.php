<?php
/**
 * Script login_tuteur_process.php
 * - POST uniquement : traitement de la connexion simulée pour les tuteurs
 * - Recherche du tuteur par numéro d’employé
 * - Remplit la session + met à jour la dernière connexion
 * - Redirige vers gestion_disponibilites.php ou login.php?type=tuteur
 */

session_start();

require_once 'config/database.php';
require_once 'models/Tuteur.php';

// Vérifier que le formulaire a été soumis en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?type=tuteur');
    exit;
}

// Récupérer le numéro d'employé
$numeroEmploye = isset($_POST['numero_employe']) ? trim($_POST['numero_employe']) : '';

if ($numeroEmploye === '') {
    $_SESSION['error'] = 'Le numéro d\'employé est requis.';
    header('Location: login.php?type=tuteur');
    exit;
}

try {
    $pdo = getDBConnection();
    $tuteurModel = new Tuteur($pdo);

    // Rechercher le tuteur par numéro (connexion simulée)
    $tuteur = $tuteurModel->getTuteurByNumero($numeroEmploye);

    if ($tuteur) {
        // Connexion réussie : stocker les infos en session
        $_SESSION['tuteur_id']         = $tuteur['id'];
        $_SESSION['tuteur_numero']     = $tuteur['numero_employe'];
        $_SESSION['tuteur_nom']        = $tuteur['nom'];
        $_SESSION['tuteur_prenom']     = $tuteur['prenom'];
        $_SESSION['tuteur_email']      = $tuteur['email'];
        $_SESSION['tuteur_departement'] = $tuteur['departement'];

        // Mettre à jour la dernière connexion
        $tuteurModel->updateDerniereConnexion($tuteur['id']);

        // Rediriger vers la page de gestion des disponibilités
        header('Location: gestion_disponibilites.php');
        exit;
    }

    // Échec de connexion
    $_SESSION['error'] = 'Numéro d\'employé invalide. Veuillez réessayer.';
    header('Location: login.php?type=tuteur');
    exit;

} catch (Exception $e) {
    error_log('Erreur lors de la connexion tuteur : ' . $e->getMessage());
    $_SESSION['error'] = 'Une erreur est survenue. Veuillez réessayer plus tard.';
    header('Location: login.php?type=tuteur');
    exit;
}
