<?php
declare(strict_types=1);

class Tuteur
{
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètre : id tuteur
    // Retourne : tableau associatif ou null
    public function getTuteurById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteurById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : numéro employé
    // Retourne : tableau associatif ou null
    public function getTuteurByNumero(string $numeroEmploye): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE numero_employe = :numero_employe AND actif = TRUE
            ");
            $stmt->bindParam(':numero_employe', $numeroEmploye, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteurByNumero : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id tuteur
    // Retourne : true si succès
    public function updateDerniereConnexion(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE tuteurs 
                SET derniere_connexion = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::updateDerniereConnexion : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : aucun
    // Retourne : tous les tuteurs actifs
    public function getAllActiveTuteurs(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getAllActiveTuteurs : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : département
    // Retourne : tuteurs actifs de ce département
    public function getTuteursByDepartement(string $departement): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE departement = :departement AND actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->bindParam(':departement', $departement, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteursByDepartement : " . $e->getMessage());
            return [];
        }
    }
    // Méthodes du UML que je vais implémenter plus tard
    // +gererDisponibilites(): void
    // +accepterDemande(): void
    // +refuserDemande(): void
    // +consulterPlanning(): void
}

