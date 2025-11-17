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

    // Retourne : nombre de rendez-vous par statut
    public function getRendezVousParStatut(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    statut,
                    COUNT(*) as nombre
                FROM rendez_vous
                GROUP BY statut
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format standardisé
            $stats = [
                'A_VENIR' => 0,
                'EN_COURS' => 0,
                'TERMINE' => 0,
                'ANNULE' => 0,
                'REPORTE' => 0
            ];
            
            foreach ($results as $row) {
                $stats[$row['statut']] = (int)$row['nombre'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getRendezVousParStatut : " . $e->getMessage());
            return [];
        }
    }
}
