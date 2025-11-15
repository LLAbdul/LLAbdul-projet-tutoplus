<?php
/**
 * Modèle MessageContact - Gestion des messages de contact entre étudiants et tuteurs
 */

class MessageContact {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Génère un UUID v4
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    // Crée un nouveau message de contact
    public function creerMessage($etudiantId, $tuteurId, $sujet, $contenu, $priorite = null) {
        try {
            // Validation : vérifier que l'étudiant existe
            $etudiantStmt = $this->pdo->prepare("SELECT id FROM etudiants WHERE id = :etudiant_id AND actif = TRUE");
            $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $etudiantStmt->execute();
            if (!$etudiantStmt->fetch()) {
                error_log("Erreur MessageContact::creerMessage : L'étudiant spécifié n'existe pas ou n'est pas actif (ID: $etudiantId)");
                return false;
            }
            
            // Validation : vérifier que le tuteur existe
            $tuteurStmt = $this->pdo->prepare("SELECT id FROM tuteurs WHERE id = :tuteur_id AND actif = TRUE");
            $tuteurStmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $tuteurStmt->execute();
            if (!$tuteurStmt->fetch()) {
                error_log("Erreur MessageContact::creerMessage : Le tuteur spécifié n'existe pas ou n'est pas actif (ID: $tuteurId)");
                return false;
            }
            
            // Validation : sujet et contenu obligatoires
            if (empty(trim($sujet))) {
                error_log("Erreur MessageContact::creerMessage : Le sujet est obligatoire");
                return false;
            }
            
            if (empty(trim($contenu))) {
                error_log("Erreur MessageContact::creerMessage : Le contenu est obligatoire");
                return false;
            }
            
            // Validation : limite de 500 caractères pour le contenu
            if (strlen($contenu) > 500) {
                error_log("Erreur MessageContact::creerMessage : Le contenu dépasse 500 caractères");
                return false;
            }
            
            $id = $this->generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO messages_contact (id, etudiant_id, tuteur_id, sujet, contenu, date_envoi, statut, priorite, lu)
                VALUES (:id, :etudiant_id, :tuteur_id, :sujet, :contenu, NOW(), 'ENVOYE', :priorite, FALSE)
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->bindParam(':sujet', $sujet, PDO::PARAM_STR);
            $stmt->bindParam(':contenu', $contenu, PDO::PARAM_STR);
            $stmt->bindParam(':priorite', $priorite, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Erreur SQL lors de l'INSERT dans messages_contact : " . ($errorInfo[2] ?? 'Erreur inconnue'));
                return false;
            }
            
            return $id;
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la création du message : " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Erreur générale lors de la création du message : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupère un message par son ID
    public function getMessageById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.id, m.etudiant_id, m.tuteur_id, m.sujet, m.contenu, m.date_envoi, 
                       m.statut, m.priorite, m.lu, m.date_creation, m.date_modification,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.email as etudiant_email,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.email as tuteur_email
                FROM messages_contact m
                LEFT JOIN etudiants e ON m.etudiant_id = e.id
                LEFT JOIN tuteurs t ON m.tuteur_id = t.id
                WHERE m.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du message : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère tous les messages reçus par un tuteur
    public function getMessagesByTuteur($tuteurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.id, m.etudiant_id, m.tuteur_id, m.sujet, m.contenu, m.date_envoi, 
                       m.statut, m.priorite, m.lu, m.date_creation,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.email as etudiant_email
                FROM messages_contact m
                LEFT JOIN etudiants e ON m.etudiant_id = e.id
                WHERE m.tuteur_id = :tuteur_id
                ORDER BY m.date_envoi DESC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des messages du tuteur : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère tous les messages envoyés par un étudiant
    public function getMessagesByEtudiant($etudiantId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.id, m.etudiant_id, m.tuteur_id, m.sujet, m.contenu, m.date_envoi, 
                       m.statut, m.priorite, m.lu, m.date_creation,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.email as tuteur_email
                FROM messages_contact m
                LEFT JOIN tuteurs t ON m.tuteur_id = t.id
                WHERE m.etudiant_id = :etudiant_id
                ORDER BY m.date_envoi DESC
            ");
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des messages de l'étudiant : " . $e->getMessage());
            return [];
        }
    }
}
