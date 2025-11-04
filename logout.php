<?php
/**
 * Déconnexion
 * TutoPlus - Système de tutorat
 */

session_start();

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: index.php');
exit;

