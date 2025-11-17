<?php
declare(strict_types=1);

/**
 * Modèle Statistiques - Gestion des statistiques pour les administrateurs
 */
class Statistiques
{
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
