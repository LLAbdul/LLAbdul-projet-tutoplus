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

    // Retourne : répartition des rendez-vous par département
    public function getRendezVousParDepartement(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    t.departement,
                    COUNT(rv.id) as nombre
                FROM rendez_vous rv
                INNER JOIN tuteurs t ON rv.tuteur_id = t.id
                GROUP BY t.departement
                ORDER BY nombre DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getRendezVousParDepartement : " . $e->getMessage());
            return [];
        }
    }

    // Retourne : nombre de services actifs par catégorie
    public function getServicesParCategorie(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    categorie,
                    COUNT(*) as nombre
                FROM services
                WHERE actif = TRUE
                GROUP BY categorie
                ORDER BY nombre DESC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getServicesParCategorie : " . $e->getMessage());
            return [];
        }
    }

    // Retourne : statistiques générales (totaux)
    public function getStatistiquesGenerales(): array
    {
        try {
            $totalRendezVous = $this->pdo->query("
                SELECT COUNT(*) as total FROM rendez_vous
            ")->fetch(PDO::FETCH_ASSOC);
            
            $totalDemandes = $this->pdo->query("
                SELECT COUNT(*) as total FROM demandes
            ")->fetch(PDO::FETCH_ASSOC);
            
            $totalTuteurs = $this->pdo->query("
                SELECT COUNT(*) as total FROM tuteurs WHERE actif = TRUE
            ")->fetch(PDO::FETCH_ASSOC);
            
            $totalEtudiants = $this->pdo->query("
                SELECT COUNT(*) as total FROM etudiants WHERE actif = TRUE
            ")->fetch(PDO::FETCH_ASSOC);
            
            $totalServices = $this->pdo->query("
                SELECT COUNT(*) as total FROM services WHERE actif = TRUE
            ")->fetch(PDO::FETCH_ASSOC);
            
            $rendezVousTermines = $this->pdo->query("
                SELECT COUNT(*) as total FROM rendez_vous WHERE statut = 'TERMINE'
            ")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_rendez_vous' => (int)($totalRendezVous['total'] ?? 0),
                'total_demandes' => (int)($totalDemandes['total'] ?? 0),
                'total_tuteurs' => (int)($totalTuteurs['total'] ?? 0),
                'total_etudiants' => (int)($totalEtudiants['total'] ?? 0),
                'total_services' => (int)($totalServices['total'] ?? 0),
                'rendez_vous_termines' => (int)($rendezVousTermines['total'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Erreur Statistiques::getStatistiquesGenerales : " . $e->getMessage());
            return [];
        }
    }

    // Retourne : toutes les statistiques en un seul appel
    public function getAllStatistiques(): array
    {
        return [
            'generales' => $this->getStatistiquesGenerales(),
            'rendez_vous_par_statut' => $this->getRendezVousParStatut(),
            'demandes_par_statut' => $this->getDemandesParStatut(),
            'utilisateurs_par_statut' => $this->getUtilisateursParStatut(),
            'top_tuteurs' => $this->getTopTuteurs(5),
            'rendez_vous_par_departement' => $this->getRendezVousParDepartement(),
            'services_par_categorie' => $this->getServicesParCategorie()
        ];
    }
}

