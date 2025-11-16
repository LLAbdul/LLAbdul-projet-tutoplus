<?php
/**
 * Script logout.php
 * - Détruit la session en cours
 * - Redirige vers la page d'accueil (index.php)
 */

session_start();

// Détruire tous les données de session
$_SESSION = [];
session_destroy();

// Rediriger vers la page d'accueil
header('Location: index.php');
exit;
