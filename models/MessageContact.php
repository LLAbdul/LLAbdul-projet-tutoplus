<?php
declare(strict_types=1);

/**
 * Modèle MessageContact - Gestion des messages de contact entre étudiants et tuteurs
 */
class MessageContact
{
    private PDO $pdo;

    // Statuts possibles
    public const STATUT_ENVOYE = 'ENVOYE';
    public const STATUT_LU     = 'LU';

    // Longueur max du contenu
    private const MAX_CONTENU_LENGTH = 500;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètres : étudiant, tuteur, sujet, contenu, priorité (opt)
    // Retourne : id du message ou false
    public function creerMessage(
        string $etudiantId,
        string $tuteurId,
        string $sujet,
        string $contenu,
        ?string $priorite = null
    ) {
        try {
            // Vérifier étudiant actif
            $etudiantStmt = $this->pdo->prepare("
                SELECT id 
                FROM etudiants 
                WHERE id = :etudiant_id AND actif = TRUE
            ");
            $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $etudiantStmt->execute();

            if (!$etudiantStmt->fetch()) {
                $this->logError("Étudiant inexistant ou inactif (id: $etudiantId)");
                return false;
            }

            // Vérifier tuteur actif
            $tuteurStmt = $this->pdo->prepare("
                SELECT id 
                FROM tuteurs 
                WHERE id = :tuteur_id AND actif = TRUE
            ");
            $tuteurStmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $tuteurStmt->execute();

            if (!$tuteurStmt->fetch()) {
                $this->logError("Tuteur inexistant ou inactif (id: $tuteurId)");
                return false;
            }

            // Sujet obligatoire
            if (trim($sujet) === '') {
                $this->logError("Sujet obligatoire");
                return false;
            }

            // Contenu obligatoire
            if (trim($contenu) === '') {
                $this->logError("Contenu obligatoire");
                return false;
            }

            if (strlen($contenu) > self::MAX_CONTENU_LENGTH) {
                $this->logError("Contenu > " . self::MAX_CONTENU_LENGTH . " caractères");
                return false;
            }

            $id = $this->generateUUID();

            $stmt = $this->pdo->prepare("
                INSERT INTO messages_contact (
                    id, etudiant_id, tuteur_id, sujet, contenu, 
                    date_envoi, statut, priorite, lu
                ) VALUES (
                    :id, :etudiant_id, :tuteur_id, :sujet, :contenu,
                    NOW(), :statut, :priorite, FALSE
                )
            ");

            $statut = self::STATUT_ENVOYE;

            $stmt->bindParam(':id',          $id,         PDO::PARAM_STR);
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id',   $tuteurId,   PDO::PARAM_STR);
            $stmt->bindParam(':sujet',       $sujet,      PDO::PARAM_STR);
            $stmt->bindParam(':contenu',     $contenu,    PDO::PARAM_STR);
            $stmt->bindParam(':priorite',    $priorite,   PDO::PARAM_STR);
            $stmt->bindParam(':statut',      $statut,     PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->logError("INSERT messages_contact échoué : " . ($errorInfo[2] ?? 'Erreur inconnue'));
                return false;
            }

            return $id;
        } catch (PDOException $e) {
            $this->logError("PDO créerMessage : " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->logError("Exception créerMessage : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id message
    // Retourne : tableau associatif ou null
    public function getMessageById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.id, m.etudiant_id, m.tuteur_id, m.sujet, m.contenu, m.date_envoi, 
                    m.statut, m.priorite, m.lu, m.date_creation, m.date_modification,
                    e.nom   AS etudiant_nom, 
                    e.prenom AS etudiant_prenom, 
                    e.email AS etudiant_email,
                    t.nom   AS tuteur_nom, 
                    t.prenom AS tuteur_prenom, 
                    t.email AS tuteur_email
                FROM messages_contact m
                LEFT JOIN etudiants e ON m.etudiant_id = e.id
                LEFT JOIN tuteurs   t ON m.tuteur_id = t.id
                WHERE m.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            $this->logError("getMessageById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id tuteur
    // Retourne : tableau de messages reçus
    public function getMessagesByTuteur(string $tuteurId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.id, m.etudiant_id, m.tuteur_id, m.sujet, m.contenu, m.date_envoi, 
                    m.statut, m.priorite, m.lu, m.date_creation,
                    e.nom AS etudiant_nom, e.prenom AS etudiant_prenom, e.email AS etudiant_email
                FROM messages_contact m
                LEFT JOIN etudiants e ON m.etudiant_id = e.id
                WHERE m.tuteur_id = :tuteur_id
                ORDER BY m.date_envoi DESC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $this->logError("getMessagesByTuteur : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id étudiant
    // Retourne : tableau de messages envoyés
    public function getMessagesByEtudiant(string $etudiantId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.id, m.etudiant_id, m.tuteur_id, m.sujet, m.contenu, m.date_envoi, 
                    m.statut, m.priorite, m.lu, m.date_creation,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom, t.email AS tuteur_email
                FROM messages_contact m
                LEFT JOIN tuteurs t ON m.tuteur_id = t.id
                WHERE m.etudiant_id = :etudiant_id
                ORDER BY m.date_envoi DESC
            ");
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $this->logError("getMessagesByEtudiant : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id message
    // Retourne : true si succès
    public function marquerLu(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE messages_contact 
                SET lu = TRUE, statut = :statut, date_modification = NOW()
                WHERE id = :id
            ");
            $statut = self::STATUT_LU;

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                $this->logError("marquerLu : execute() a retourné false");
                return false;
            }

            return true;
        } catch (PDOException $e) {
            $this->logError("marquerLu : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : id message, nouveau statut
    // Retourne : true si succès
    public function mettreAJourStatut(string $id, string $statut): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE messages_contact 
                SET statut = :statut, date_modification = NOW()
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                $this->logError("mettreAJourStatut : execute() a retourné false");
                return false;
            }

            return true;
        } catch (PDOException $e) {
            $this->logError("mettreAJourStatut : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id message
    // Retourne : true si succès
    public function supprimerMessage(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM messages_contact 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError("supprimerMessage : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : message d'erreur
    private function logError(string $message): void
    {
        error_log('Erreur MessageContact : ' . $message);
    }

    // Paramètres : aucun
    // Retourne : UUID v4
    private function generateUUID(): string
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
}
