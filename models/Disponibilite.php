<?php

class Disponibilite {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Récupère une disponibilité par son ID
    public function getDisponibiliteById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.etudiant_id, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                LEFT JOIN etudiants e ON d.etudiant_id = e.id
                WHERE d.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la disponibilité : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère toutes les disponibilités disponibles pour un service
    public function getDisponibilitesByServiceId($serviceId) {
        try {
            // D'abord, récupérer le tuteur_id du service
            $serviceStmt = $this->pdo->prepare("SELECT tuteur_id FROM services WHERE id = :service_id");
            $serviceStmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $serviceStmt->execute();
            $service = $serviceStmt->fetch();
            
            if (!$service) {
                return [];
            }
            
            $tuteurId = $service['tuteur_id'];
            
            // Récupérer les disponibilités :
            // 1. Disponibilités spécifiques au service (d.service_id = :service_id)
            // 2. OU disponibilités générales du tuteur du service (d.service_id IS NULL ET d.tuteur_id = tuteur_id_du_service)
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.statut = 'DISPONIBLE'
                  AND d.date_fin >= NOW()
                  AND (
                      d.service_id = :service_id
                      OR (d.service_id IS NULL AND d.tuteur_id = :tuteur_id)
                  )
                ORDER BY d.date_debut ASC
            ");
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des disponibilités par service : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère toutes les disponibilités disponibles pour un tuteur
    public function getDisponibilitesByTuteurId($tuteurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                WHERE d.tuteur_id = :tuteur_id
                ORDER BY d.date_debut ASC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des disponibilités par tuteur : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère toutes les disponibilités disponibles (futures uniquement)
    public function getAllAvailableDisponibilites() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.statut = 'DISPONIBLE'
                  AND d.date_debut >= NOW()
                ORDER BY d.date_debut ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des disponibilités : " . $e->getMessage());
            return [];
        }
    }
    
    // Vérifie si une disponibilité est disponible
    public function estDisponible($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT statut, date_debut
                FROM disponibilites
                WHERE id = :id 
                  AND statut = 'DISPONIBLE'
                  AND date_debut >= NOW()
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de disponibilité : " . $e->getMessage());
            return false;
        }
    }
    
    // Crée une nouvelle disponibilité
    public function creerDisponibilite($tuteurId, $dateDebut, $dateFin, $statut = 'DISPONIBLE', $serviceId = null, $prix = null, $notes = null) {
        try {
            // Validation : durée minimum 30 minutes
            $dateDebutObj = new DateTime($dateDebut);
            $dateFinObj = new DateTime($dateFin);
            $diff = $dateDebutObj->diff($dateFinObj);
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            
            if ($minutes < 30) {
                error_log("Erreur : La durée minimum doit être de 30 minutes");
                return false;
            }
            
            // Validation : date_fin > date_debut
            if ($dateFinObj <= $dateDebutObj) {
                error_log("Erreur : La date de fin doit être supérieure à la date de début");
                return false;
            }
            
            // Générer un UUID pour l'ID
            $id = $this->generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
                VALUES (:id, :tuteur_id, :service_id, :date_debut, :date_fin, :statut, :prix, :notes)
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':date_debut', $dateDebut, PDO::PARAM_STR);
            $stmt->bindParam(':date_fin', $dateFin, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
            $stmt->bindParam(':prix', $prix, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            
            $stmt->execute();
            
            return $id;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la disponibilité : " . $e->getMessage());
            return false;
        }
    }
    
    // Modifie une disponibilité existante
    public function modifierDisponibilite($id, $dateDebut, $dateFin, $statut = null, $serviceId = null, $prix = null, $notes = null, $etudiantId = null) {
        try {
            // Validation : durée minimum 30 minutes
            $dateDebutObj = new DateTime($dateDebut);
            $dateFinObj = new DateTime($dateFin);
            $diff = $dateDebutObj->diff($dateFinObj);
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            
            if ($minutes < 30) {
                error_log("Erreur : La durée minimum doit être de 30 minutes");
                return false;
            }
            
            // Validation : date_fin > date_debut
            if ($dateFinObj <= $dateDebutObj) {
                error_log("Erreur : La date de fin doit être supérieure à la date de début");
                return false;
            }
            
            // Construire la requête UPDATE dynamiquement
            $updates = [];
            $params = [':id' => $id, ':date_debut' => $dateDebut, ':date_fin' => $dateFin];
            
            $updates[] = "date_debut = :date_debut";
            $updates[] = "date_fin = :date_fin";
            
            if ($statut !== null) {
                $updates[] = "statut = :statut";
                $params[':statut'] = $statut;
            }
            
            if ($serviceId !== null) {
                $updates[] = "service_id = :service_id";
                $params[':service_id'] = $serviceId;
            }
            
            if ($prix !== null) {
                $updates[] = "prix = :prix";
                $params[':prix'] = $prix;
            }
            
            if ($notes !== null) {
                $updates[] = "notes = :notes";
                $params[':notes'] = $notes;
            }
            
            // Gérer etudiant_id : si fourni, on le met à jour, sinon on peut le mettre à NULL si statut change
            if ($etudiantId !== null) {
                // Validation : vérifier que l'étudiant existe
                $etudiantStmt = $this->pdo->prepare("SELECT id FROM etudiants WHERE id = :etudiant_id");
                $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
                $etudiantStmt->execute();
                if (!$etudiantStmt->fetch()) {
                    error_log("Erreur : L'étudiant spécifié n'existe pas");
                    return false;
                }
                $updates[] = "etudiant_id = :etudiant_id";
                $params[':etudiant_id'] = $etudiantId;
            } elseif ($statut !== null && $statut !== 'RESERVE') {
                // Si le statut change de RESERVE à autre chose, on réinitialise etudiant_id à NULL
                $updates[] = "etudiant_id = NULL";
            }
            
            $sql = "UPDATE disponibilites SET " . implode(", ", $updates) . " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erreur lors de la modification de la disponibilité : " . $e->getMessage());
        return false;
    }
}

// Supprime une disponibilité (ne peut pas supprimer si réservée)
public function supprimerDisponibilite($id) {
        try {
            // Vérifier que la disponibilité existe et n'est pas réservée
            $disponibilite = $this->getDisponibiliteById($id);
            
            if (!$disponibilite) {
                error_log("Erreur : La disponibilité n'existe pas");
                return false;
            }
            
            if ($disponibilite['statut'] === 'RESERVE') {
                error_log("Erreur : Impossible de supprimer un créneau réservé");
                return false;
            }
            
            // Supprimer la disponibilité
            $stmt = $this->pdo->prepare("DELETE FROM disponibilites WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la disponibilité : " . $e->getMessage());
            return false;
        }
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
}

