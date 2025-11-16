<?php
declare(strict_types=1);

/**
 * Modèle Service - Gestion des services de tutorat
 */
class Service
{
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètres : aucun
    // Retourne : tous les services actifs avec leur tuteur
    public function getAllActiveServices(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.actif = TRUE AND t.actif = TRUE
                ORDER BY s.categorie, s.nom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Service::getAllActiveServices : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id service
    // Retourne : service avec tuteur ou null
    public function getServiceById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.id = :id AND s.actif = TRUE AND t.actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Service::getServiceById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id tuteur
    // Retourne : services actifs de ce tuteur
    public function getServicesByTuteurId(string $tuteurId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.tuteur_id = :tuteur_id AND s.actif = TRUE AND t.actif = TRUE 
                ORDER BY s.nom
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Service::getServicesByTuteurId : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : catégorie
    // Retourne : services actifs pour cette catégorie
    public function getServicesByCategory(string $categorie): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.categorie = :categorie AND s.actif = TRUE AND t.actif = TRUE 
                ORDER BY s.nom
            ");
            $stmt->bindParam(':categorie', $categorie, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Service::getServicesByCategory : " . $e->getMessage());
            return [];
        }
    }
}
