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

    // Retourne : nombre de demandes par statut
    public function getDemandesParStatut(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    statut,
                    COUNT(*) as nombre
                FROM demandes
                GROUP BY statut
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format standardisé
            $stats = [
                'EN_ATTENTE' => 0,
                'ACCEPTEE' => 0,
                'REFUSEE' => 0,
                'EXPIRED' => 0
            ];
            
            foreach ($results as $row) {
                $stats[$row['statut']] = (int)$row['nombre'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getDemandesParStatut : " . $e->getMessage());
            return [];
        }
    }

    // Retourne : nombre de tuteurs et étudiants actifs/inactifs
    public function getUtilisateursParStatut(): array
    {
        try {
            $tuteursActifs = $this->pdo->query("
                SELECT COUNT(*) as nombre 
                FROM tuteurs 
                WHERE actif = TRUE
            ")->fetch(PDO::FETCH_ASSOC);
            
            $tuteursInactifs = $this->pdo->query("
                SELECT COUNT(*) as nombre 
                FROM tuteurs 
                WHERE actif = FALSE
            ")->fetch(PDO::FETCH_ASSOC);
            
            $etudiantsActifs = $this->pdo->query("
                SELECT COUNT(*) as nombre 
                FROM etudiants 
                WHERE actif = TRUE
            ")->fetch(PDO::FETCH_ASSOC);
            
            $etudiantsInactifs = $this->pdo->query("
                SELECT COUNT(*) as nombre 
                FROM etudiants 
                WHERE actif = FALSE
            ")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'tuteurs' => [
                    'actifs' => (int)($tuteursActifs['nombre'] ?? 0),
                    'inactifs' => (int)($tuteursInactifs['nombre'] ?? 0)
                ],
                'etudiants' => [
                    'actifs' => (int)($etudiantsActifs['nombre'] ?? 0),
                    'inactifs' => (int)($etudiantsInactifs['nombre'] ?? 0)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getUtilisateursParStatut : " . $e->getMessage());
            return [];
        }
    }

    // Retourne : top tuteurs par nombre de séances
    public function getTopTuteurs(int $limit = 5): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    t.id,
                    t.nom,
                    t.prenom,
                    t.departement,
                    t.evaluation,
                    t.nb_seances,
                    COUNT(rv.id) as nb_rendez_vous
                FROM tuteurs t
                LEFT JOIN rendez_vous rv ON t.id = rv.tuteur_id
                WHERE t.actif = TRUE
                GROUP BY t.id, t.nom, t.prenom, t.departement, t.evaluation, t.nb_seances
                ORDER BY nb_rendez_vous DESC, t.nb_seances DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getTopTuteurs : " . $e->getMessage());
            return [];
        }
    }
}
