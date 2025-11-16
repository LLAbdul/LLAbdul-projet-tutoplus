<?php
declare(strict_types=1);

class Etudiant
{
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètre : id étudiant
    // Retourne : tableau associatif ou null
    public function getEtudiantById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getEtudiantById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : numéro d'étudiant
    // Retourne : tableau associatif ou null
    public function getEtudiantByNumero(string $numeroEtudiant): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE numero_etudiant = :numero_etudiant AND actif = TRUE
            ");
            $stmt->bindParam(':numero_etudiant', $numeroEtudiant, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getEtudiantByNumero : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id étudiant
    // Retourne : true si succès, false sinon
    public function updateDerniereConnexion(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE etudiants 
                SET derniere_connexion = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::updateDerniereConnexion : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : aucun
    // Retourne : tableau d'étudiants actifs
    public function getAllActiveEtudiants(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getAllActiveEtudiants : " . $e->getMessage());
            return [];
        }
    }
}
