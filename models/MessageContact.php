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
}
