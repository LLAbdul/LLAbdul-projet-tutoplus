<?php
declare(strict_types=1);

require_once __DIR__ . '/UtilisateurTrait.php';

class Administrateur
{
    use UtilisateurTrait;
    
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètre : id administrateur
    // Retourne : tableau associatif ou null
    public function getAdministrateurById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_admin, nom, prenom, email, telephone, 
                    niveau_acces, permissions, actif, date_creation, derniere_connexion
                FROM administrateurs 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Administrateur::getAdministrateurById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : numéro d'administrateur
    // Retourne : tableau associatif ou null
    public function getAdministrateurByNumero(string $numeroAdmin): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_admin, nom, prenom, email, telephone, 
                    niveau_acces, permissions, actif, date_creation, derniere_connexion
                FROM administrateurs 
                WHERE numero_admin = :numero_admin AND actif = TRUE
            ");
            $stmt->bindParam(':numero_admin', $numeroAdmin, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Administrateur::getAdministrateurByNumero : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id administrateur
    // Retourne : true si succès, false sinon
    public function updateDerniereConnexion(string $id): bool
    {
        return $this->updateDerniereConnexionForTable($id, 'administrateurs');
    }

    // Paramètres : aucun
    // Retourne : tableau de tous les administrateurs actifs
    public function getAllAdministrateurs(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_admin, nom, prenom, email, telephone, 
                    niveau_acces, permissions, actif, date_creation, derniere_connexion
                FROM administrateurs 
                WHERE actif = TRUE
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Administrateur::getAllAdministrateurs : " . $e->getMessage());
            return [];
        }
    }
}

