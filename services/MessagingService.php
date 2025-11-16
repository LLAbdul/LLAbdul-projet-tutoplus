<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/MessageContact.php';

class MessagingService
{
    private $pdo;
    private $messageModel;

    // Constantes pour éviter les strings magiques
    public const ROLE_TUTEUR = 'tuteur';
    public const ROLE_ETUDIANT = 'etudiant';

    // Constructeur : reçoit l'instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->messageModel = new MessageContact($pdo);
    }

    // Envoie un message
    // Paramètres : ID étudiant, ID tuteur, sujet, contenu, priorité (optionnelle)
    // Retourne : l'ID du message créé ou false en cas d'erreur
    public function envoyerMessage($etudiantId, $tuteurId, $sujet, $contenu, $priorite = null)
    {
        try {
            $messageId = $this->messageModel->creerMessage(
                $etudiantId,
                $tuteurId,
                $sujet,
                $contenu,
                $priorite
            );

            if (!$messageId) {
                $this->logError("Impossible de créer le message");
                return false;
            }

            return $messageId;
        } catch (Exception $e) {
            $this->logError("envoyerMessage", $e);
            return false;
        }
    }

    // Récupère les messages reçus selon le rôle ('tuteur' ou 'etudiant')
    // Paramètres : ID utilisateur, rôle
    // Retourne : un tableau de messages ou un tableau vide en cas d'erreur
    public function recevoirMessages($utilisateurId, $role)
    {
        try {
            if ($role === self::ROLE_TUTEUR) {
                return $this->messageModel->getMessagesByTuteur($utilisateurId);
            }

            if ($role === self::ROLE_ETUDIANT) {
                return $this->messageModel->getMessagesByEtudiant($utilisateurId);
            }

            $this->logError("Rôle invalide ($role)");
            return [];
        } catch (Exception $e) {
            $this->logError("recevoirMessages", $e);
            return [];
        }
    }

    // Marque un message comme lu
    // Paramètre : ID du message
    // Retourne : true en cas de succès, false sinon
    public function marquerCommeLu($messageId)
    {
        try {
            return $this->messageModel->marquerLu($messageId);
        } catch (Exception $e) {
            $this->logError("marquerCommeLu", $e);
            return false;
        }
    }

    // Supprime un message
    // Paramètre : ID du message
    // Retourne : true en cas de succès, false sinon
    public function supprimerMessage($messageId)
    {
        try {
            return $this->messageModel->supprimerMessage($messageId);
        } catch (Exception $e) {
            $this->logError("supprimerMessage", $e);
            return false;
        }
    }

    // Fonction interne pour log les erreurs
    private function logError($context, $e = null)
    {
        $message = "Erreur MessagingService::$context";
        if ($e) {
            $message .= " - " . $e->getMessage();
        }
        error_log($message);
    }
}
