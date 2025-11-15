<?php
/**
 * Service MessagingService - Orchestration des opérations de messagerie
 * Utilise le modèle MessageContact
 */

require_once __DIR__ . '/../models/MessageContact.php';

class MessagingService {
    private $pdo;
    private $messageModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->messageModel = new MessageContact($pdo);
    }
    
    // Envoie un message (méthode envoyerMessage() du diagramme UML)
    public function envoyerMessage($etudiantId, $tuteurId, $sujet, $contenu, $priorite = null) {
        try {
            $messageId = $this->messageModel->creerMessage($etudiantId, $tuteurId, $sujet, $contenu, $priorite);
            
            if (!$messageId) {
                error_log("Erreur MessagingService : Impossible de créer le message");
                return false;
            }
            
            return $messageId;
        } catch (Exception $e) {
            error_log("Erreur MessagingService::envoyerMessage : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupère les messages reçus par un utilisateur (méthode recevoirMessages() du diagramme UML)
    public function recevoirMessages($utilisateurId, $role) {
        try {
            if ($role === 'tuteur') {
                return $this->messageModel->getMessagesByTuteur($utilisateurId);
            } elseif ($role === 'etudiant') {
                return $this->messageModel->getMessagesByEtudiant($utilisateurId);
            } else {
                error_log("Erreur MessagingService : Rôle invalide ($role)");
                return [];
            }
        } catch (Exception $e) {
            error_log("Erreur MessagingService::recevoirMessages : " . $e->getMessage());
            return [];
        }
    }
}

