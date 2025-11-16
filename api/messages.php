<?php
/**
 * API messages.php
 * - GET    : liste ou détail (selon id) des messages d’un utilisateur
 * - POST   : création d’un message (étudiant → tuteur)
 * - PUT    : mettre à jour (lu ou statut)
 * - DELETE : suppression (uniquement par l'expéditeur)
 */


session_start();

require_once '../config/database.php';
require_once '../services/MessagingService.php';
require_once '../models/MessageContact.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $messagingService = new MessagingService($pdo);
    $messageModel = new MessageContact($pdo);
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'GET':
            // Récupérer les messages selon le rôle de l'utilisateur
            if (!isset($_SESSION['etudiant_id']) && !isset($_SESSION['tuteur_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Utilisateur non connecté']);
                break;
            }
            
            // Récupérer un message spécifique par ID
            if (isset($_GET['id'])) {
                $messageId = $_GET['id'];
                $message = $messageModel->getMessageById($messageId);
                
                if (!$message) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Message non trouvé']);
                    break;
                }
                
                // Vérifier que l'utilisateur a le droit de voir ce message
                if (isset($_SESSION['etudiant_id'])) {
                    if ($message['etudiant_id'] !== $_SESSION['etudiant_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Accès refusé']);
                        break;
                    }
                } elseif (isset($_SESSION['tuteur_id'])) {
                    if ($message['tuteur_id'] !== $_SESSION['tuteur_id']) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Accès refusé']);
                        break;
                    }
                }
                
                http_response_code(200);
                echo json_encode($message);
                break;
            }
            
            // Récupérer la liste des messages
            if (isset($_SESSION['etudiant_id'])) {
                $messages = $messagingService->recevoirMessages($_SESSION['etudiant_id'], 'etudiant');
            } elseif (isset($_SESSION['tuteur_id'])) {
                $messages = $messagingService->recevoirMessages($_SESSION['tuteur_id'], 'tuteur');
            } else {
                $messages = [];
            }
            
            http_response_code(200);
            echo json_encode($messages);
            break;
            
        case 'POST':
            // Créer un nouveau message (seulement pour les étudiants)
            if (!isset($_SESSION['etudiant_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Seuls les étudiants peuvent envoyer des messages']);
                break;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Format JSON invalide dans la requête']);
                break;
            }
            
            if (!isset($data['tuteur_id']) || empty($data['tuteur_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'tuteur_id est requis']);
                break;
            }
            
            if (!isset($data['sujet']) || empty(trim($data['sujet']))) {
                http_response_code(400);
                echo json_encode(['error' => 'sujet est requis']);
                break;
            }
            
            if (!isset($data['contenu']) || empty(trim($data['contenu']))) {
                http_response_code(400);
                echo json_encode(['error' => 'contenu est requis']);
                break;
            }
            
            // Validation : limite de 500 caractères
            if (strlen($data['contenu']) > 500) {
                http_response_code(400);
                echo json_encode(['error' => 'Le contenu ne doit pas dépasser 500 caractères']);
                break;
            }
            
            $etudiantId = $_SESSION['etudiant_id'];
            $tuteurId = $data['tuteur_id'];
            $sujet = trim($data['sujet']);
            $contenu = trim($data['contenu']);
            $priorite = $data['priorite'] ?? null;
            
            $messageId = $messagingService->envoyerMessage($etudiantId, $tuteurId, $sujet, $contenu, $priorite);
            
            if (!$messageId) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de l\'envoi du message']);
                break;
            }
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'id' => $messageId
            ]);
            break;
            
        case 'PUT':
            // Marquer un message comme lu ou mettre à jour le statut
            if (!isset($_SESSION['etudiant_id']) && !isset($_SESSION['tuteur_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Utilisateur non connecté']);
                break;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!isset($data['id']) || empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id est requis']);
                break;
            }
            
            $messageId = $data['id'];
            
            // Vérifier que le message existe et que l'utilisateur a le droit de le modifier
            $message = $messageModel->getMessageById($messageId);
            if (!$message) {
                http_response_code(404);
                echo json_encode(['error' => 'Message non trouvé']);
                break;
            }
            
            // Vérifier les permissions
            if (isset($_SESSION['etudiant_id'])) {
                if ($message['etudiant_id'] !== $_SESSION['etudiant_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Accès refusé']);
                    break;
                }
            } elseif (isset($_SESSION['tuteur_id'])) {
                if ($message['tuteur_id'] !== $_SESSION['tuteur_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Accès refusé']);
                    break;
                }
            }
            
            // Marquer comme lu
            if (isset($data['lu']) && $data['lu'] === true) {
                $result = $messagingService->marquerCommeLu($messageId);
                if ($result) {
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Message marqué comme lu']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erreur lors du marquage du message']);
                }
                break;
            }
            
            // Mettre à jour le statut
            if (isset($data['statut'])) {
                $result = $messageModel->mettreAJourStatut($messageId, $data['statut']);
                if ($result) {
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erreur lors de la mise à jour du statut']);
                }
                break;
            }
            
            http_response_code(400);
            echo json_encode(['error' => 'Aucune action spécifiée (lu ou statut)']);
            break;
            
        case 'DELETE':
            // Supprimer un message
            if (!isset($_SESSION['etudiant_id']) && !isset($_SESSION['tuteur_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Non autorisé - Utilisateur non connecté']);
                break;
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!isset($data['id']) || empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id est requis']);
                break;
            }
            
            $messageId = $data['id'];
            
            // Vérifier que le message existe et que l'utilisateur a le droit de le supprimer
            $message = $messageModel->getMessageById($messageId);
            if (!$message) {
                http_response_code(404);
                echo json_encode(['error' => 'Message non trouvé']);
                break;
            }
            
            // Vérifier les permissions (seul l'expéditeur peut supprimer)
            if (isset($_SESSION['etudiant_id'])) {
                if ($message['etudiant_id'] !== $_SESSION['etudiant_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Accès refusé - Vous ne pouvez supprimer que vos propres messages']);
                    break;
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Seuls les étudiants peuvent supprimer leurs messages']);
                break;
            }
            
            $result = $messagingService->supprimerMessage($messageId);
            
            if ($result) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Message supprimé avec succès']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la suppression du message']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}

