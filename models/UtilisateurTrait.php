<?php
declare(strict_types=1);

/**
 * Trait UtilisateurTrait
 * Partage les fonctionnalités communes entre Étudiant, Tuteur et Administrateur
 */
trait UtilisateurTrait
{
    // Mettre à jour la dernière connexion
    // Paramètres : id utilisateur, nom de la table (etudiants, tuteurs, administrateurs)
    // Retourne : true si succès, false sinon
    protected function updateDerniereConnexionForTable(string $id, string $tableName): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$tableName} 
                SET derniere_connexion = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur UtilisateurTrait::updateDerniereConnexionForTable ({$tableName}) : " . $e->getMessage());
            return false;
        }
    }

    // Générer un UUID v4
    // Retourne : string UUID
    protected function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // Valider un email
    // Paramètre : email
    // Retourne : true si valide, false sinon
    protected function validerEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Vérifier si un email existe déjà dans une table
    // Paramètres : email, nom de la table, id à exclure (optionnel, pour les modifications)
    // Retourne : true si l'email existe, false sinon
    protected function emailExiste(string $email, string $tableName, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT id FROM {$tableName} WHERE email = :email";
            if ($excludeId !== null) {
                $sql .= " AND id != :exclude_id";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if ($excludeId !== null) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_STR);
            }
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erreur UtilisateurTrait::emailExiste ({$tableName}) : " . $e->getMessage());
            return false;
        }
    }
}

