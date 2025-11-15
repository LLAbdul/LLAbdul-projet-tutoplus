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
}

