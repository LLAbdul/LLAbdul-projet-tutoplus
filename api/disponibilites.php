<?php
/**
 * API disponibilite.php
 * - GET    : liste des disponibilités du tuteur (format FullCalendar)
 * - POST   : création d'une disponibilité
 * - PUT    : modification d'une disponibilité
 * - DELETE : suppression d'une disponibilité (sauf si RESERVE)
 */


session_start();

require_once '../config/database.php';
require_once '../models/Disponibilite.php';

header('Content-Type: application/json');

// Vérifier que le tuteur est connecté
if (!isset($_SESSION['tuteur_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$tuteurId = $_SESSION['tuteur_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    $disponibiliteModel = new Disponibilite($pdo);
    
    // Gérer les différentes méthodes HTTP
    switch ($method) {
        case 'GET':
            // Récupérer les disponibilités du tuteur
            $disponibilites = $disponibiliteModel->getDisponibilitesByTuteurId($tuteurId);

            // Préparer une carte des demandes EN_ATTENTE par disponibilite_id
            $demandesEnAttenteParDispo = [];
            // Préparer une carte des rendez-vous liés par disponibilite_id
            $rendezVousParDispo = [];
            if (!empty($disponibilites)) {
                $ids = array_column($disponibilites, 'id');
                // Construire dynamiquement la clause IN
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                // Récupérer les demandes EN_ATTENTE
                $sqlDemandes = "
                    SELECT disponibilite_id, COUNT(*) AS nb
                    FROM demandes
                    WHERE disponibilite_id IN ($placeholders)
                      AND statut = 'EN_ATTENTE'
                    GROUP BY disponibilite_id
                ";
                $stmtDemandes = $pdo->prepare($sqlDemandes);
                foreach ($ids as $index => $id) {
                    $stmtDemandes->bindValue($index + 1, $id, PDO::PARAM_STR);
                }
                $stmtDemandes->execute();
                $rowsDemandes = $stmtDemandes->fetchAll();
                foreach ($rowsDemandes as $row) {
                    $demandesEnAttenteParDispo[$row['disponibilite_id']] = (int)$row['nb'];
                }
                
                // Récupérer les rendez-vous liés (pour savoir si le créneau est vraiment réservé avec un rendez-vous)
                $sqlRendezVous = "
                    SELECT disponibilite_id, COUNT(*) AS nb
                    FROM rendez_vous
                    WHERE disponibilite_id IN ($placeholders)
                    GROUP BY disponibilite_id
                ";
                $stmtRendezVous = $pdo->prepare($sqlRendezVous);
                foreach ($ids as $index => $id) {
                    $stmtRendezVous->bindValue($index + 1, $id, PDO::PARAM_STR);
                }
                $stmtRendezVous->execute();
                $rowsRendezVous = $stmtRendezVous->fetchAll();
                foreach ($rowsRendezVous as $row) {
                    $rendezVousParDispo[$row['disponibilite_id']] = (int)$row['nb'];
                }
            }
            
            // Formater les disponibilités pour FullCalendar
            $events = [];
            foreach ($disponibilites as $dispo) {
                // Vérifier s'il existe au moins une demande EN_ATTENTE pour ce créneau
                $hasPendingRequest = !empty($demandesEnAttenteParDispo[$dispo['id']]);
                // Vérifier s'il existe un rendez-vous lié à ce créneau
                $hasRendezVous = !empty($rendezVousParDispo[$dispo['id']]);

                // Déterminer le titre et la couleur selon le statut et la présence d'une demande en attente
                $title = '';
                $color = '#28a745'; // vert par défaut

                if ($dispo['statut'] === 'RESERVE') {
                    if ($hasPendingRequest) {
                        // Créneau réservé car une demande est en attente de décision du tuteur
                        $title = 'Demande en attente';
                        $color = '#ffc107'; // orange / jaune (attention)
                    } else {
                        // Créneau réservé avec rendez-vous confirmé
                        $title = 'Réservé';
                        $color = '#dc3545'; // rouge
                    }
                } elseif ($dispo['statut'] === 'BLOQUE') {
                    $title = 'Bloqué';
                    $color = '#6c757d'; // gris
                } else {
                    $title = $dispo['service_nom'] ?? 'Disponible';
                    $color = '#28a745'; // vert
                }
                
                $events[] = [
                    'id' => $dispo['id'],
                    'title' => $title,
                    'start' => $dispo['date_debut'],
                    'end' => $dispo['date_fin'],
                    'color' => $color,
                    'extendedProps' => [
                        'statut' => $dispo['statut'],
                        'service_id' => $dispo['service_id'],
                        'service_nom' => $dispo['service_nom'],
                        'prix' => $dispo['prix'],
                        'notes' => $dispo['notes'],
                        'hasRendezVous' => $hasRendezVous // Indique si un rendez-vous est lié
                    ]
                ];
            }
            
            echo json_encode($events);
            break;
            
        case 'POST':
            // Créer une nouvelle disponibilité
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['date_debut']) || !isset($data['date_fin'])) {
                http_response_code(400);
                echo json_encode(['error' => 'date_debut et date_fin sont requis']);
                break;
            }
            
            $dateDebut = $data['date_debut'];
            $dateFin = $data['date_fin'];
            $statut = $data['statut'] ?? 'DISPONIBLE';
            $serviceId = $data['service_id'] ?? null;
            $prix = $data['prix'] ?? null;
            $notes = $data['notes'] ?? null;
            
            $id = $disponibiliteModel->creerDisponibilite($tuteurId, $dateDebut, $dateFin, $statut, $serviceId, $prix, $notes);
            
            if ($id) {
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $id, 'message' => 'Disponibilité créée avec succès']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Erreur lors de la création de la disponibilité']);
            }
            break;
            
        case 'PUT':
            // Modifier une disponibilité existante
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['date_debut']) || !isset($data['date_fin'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id, date_debut et date_fin sont requis']);
                break;
            }
            
            $id = $data['id'];
            
            // Vérifier que la disponibilité appartient au tuteur
            $disponibilite = $disponibiliteModel->getDisponibiliteById($id);
            if (!$disponibilite || $disponibilite['tuteur_id'] !== $tuteurId) {
                http_response_code(403);
                echo json_encode(['error' => 'Disponibilité non trouvée ou non autorisée']);
                break;
            }
            
            // Vérifier si un rendez-vous est lié à cette disponibilité
            $stmtRv = $pdo->prepare("
                SELECT id 
                FROM rendez_vous 
                WHERE disponibilite_id = :disponibilite_id
                LIMIT 1
            ");
            $stmtRv->bindParam(':disponibilite_id', $id, PDO::PARAM_STR);
            $stmtRv->execute();
            $rendezVousLie = $stmtRv->fetch();
            
            if ($rendezVousLie) {
                http_response_code(403);
                echo json_encode(['error' => 'Impossible de modifier un créneau réservé avec un rendez-vous']);
                break;
            }
            
            $dateDebut = $data['date_debut'];
            $dateFin = $data['date_fin'];
            $statut = $data['statut'] ?? null;
            $serviceId = $data['service_id'] ?? null;
            $prix = $data['prix'] ?? null;
            $notes = $data['notes'] ?? null;
            
            // Le tuteur ne peut pas directement assigner un étudiant (seulement via changement de statut)
            // Si le statut change de RESERVE à autre chose, etudiant_id sera automatiquement mis à NULL
            $success = $disponibiliteModel->modifierDisponibilite($id, $dateDebut, $dateFin, $statut, $serviceId, $prix, $notes, null);            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Disponibilité modifiée avec succès']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Erreur lors de la modification de la disponibilité']);
            }
            break;
            
        case 'DELETE':
            // Supprimer une disponibilité
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id est requis']);
                break;
            }
            
            // Vérifier que la disponibilité appartient au tuteur
            $disponibilite = $disponibiliteModel->getDisponibiliteById($id);
            if (!$disponibilite || $disponibilite['tuteur_id'] !== $tuteurId) {
                http_response_code(403);
                echo json_encode(['error' => 'Disponibilité non trouvée ou non autorisée']);
                break;
            }
            
            $success = $disponibiliteModel->supprimerDisponibilite($id);
            
            if ($success) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Disponibilité supprimée avec succès']);
            } else {
                http_response_code(400);
                $message = 'Erreur lors de la suppression de la disponibilité';
                if ($disponibilite && $disponibilite['statut'] === 'RESERVE') {
                    $message = 'Impossible de supprimer un créneau réservé';
                }
                echo json_encode(['error' => $message]);
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

