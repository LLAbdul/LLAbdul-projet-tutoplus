<?php
/**
 * Modèle Demande - Gestion des demandes de rendez-vous
 */

class Demande {
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
    
    // Crée une nouvelle demande
    public function creerDemande($etudiantId, $serviceId, $tuteurId, $disponibiliteId = null, $motif = null, $priorite = null) {
        try {
            // Validation : vérifier que l'étudiant existe
            $etudiantStmt = $this->pdo->prepare("SELECT id FROM etudiants WHERE id = :etudiant_id AND actif = TRUE");
            $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $etudiantStmt->execute();
            if (!$etudiantStmt->fetch()) {
                error_log("Erreur : L'étudiant spécifié n'existe pas ou n'est pas actif");
                return false;
            }
            
            // Validation : vérifier que le service existe
            $serviceStmt = $this->pdo->prepare("SELECT id FROM services WHERE id = :service_id AND actif = TRUE");
            $serviceStmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $serviceStmt->execute();
            if (!$serviceStmt->fetch()) {
                error_log("Erreur : Le service spécifié n'existe pas ou n'est pas actif");
                return false;
            }
            
            // Validation : vérifier que le tuteur existe
            $tuteurStmt = $this->pdo->prepare("SELECT id FROM tuteurs WHERE id = :tuteur_id AND actif = TRUE");
            $tuteurStmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $tuteurStmt->execute();
            if (!$tuteurStmt->fetch()) {
                error_log("Erreur : Le tuteur spécifié n'existe pas ou n'est pas actif");
                return false;
            }
            
            // Si disponibilite_id est fourni, vérifier qu'il existe
            if ($disponibiliteId !== null) {
                $dispoStmt = $this->pdo->prepare("SELECT id FROM disponibilites WHERE id = :disponibilite_id");
                $dispoStmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
                $dispoStmt->execute();
                if (!$dispoStmt->fetch()) {
                    error_log("Erreur : La disponibilité spécifiée n'existe pas");
                    return false;
                }
            }
            
            $id = $this->generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO demandes (id, etudiant_id, service_id, tuteur_id, disponibilite_id, motif, priorite, statut)
                VALUES (:id, :etudiant_id, :service_id, :tuteur_id, :disponibilite_id, :motif, :priorite, 'EN_ATTENTE')
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
            $stmt->bindParam(':motif', $motif, PDO::PARAM_STR);
            $stmt->bindParam(':priorite', $priorite, PDO::PARAM_STR);
            
            $stmt->execute();
            
            return $id;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la demande : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupère une demande par son ID
    public function getDemandeById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.etudiant_id, d.service_id, d.tuteur_id, d.disponibilite_id,
                       d.date_heure_demande, d.statut, d.motif, d.priorite,
                       d.date_creation, d.date_modification,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.email as etudiant_email,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.email as tuteur_email
                FROM demandes d
                LEFT JOIN etudiants e ON d.etudiant_id = e.id
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la demande : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère toutes les demandes d'un étudiant
    public function getDemandesByEtudiantId($etudiantId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.etudiant_id, d.service_id, d.tuteur_id, d.disponibilite_id,
                       d.date_heure_demande, d.statut, d.motif, d.priorite,
                       d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom
                FROM demandes d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.etudiant_id = :etudiant_id
                ORDER BY d.date_creation DESC
            ");
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des demandes de l'étudiant : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère toutes les demandes d'un tuteur
    public function getDemandesByTuteurId($tuteurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.etudiant_id, d.service_id, d.tuteur_id, d.disponibilite_id,
                       d.date_heure_demande, d.statut, d.motif, d.priorite,
                       d.date_creation, d.date_modification,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.email as etudiant_email,
                       s.nom as service_nom, s.categorie as service_categorie
                FROM demandes d
                LEFT JOIN etudiants e ON d.etudiant_id = e.id
                LEFT JOIN services s ON d.service_id = s.id
                WHERE d.tuteur_id = :tuteur_id
                ORDER BY d.date_creation DESC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des demandes du tuteur : " . $e->getMessage());
            return [];
        }
    }
    
    // Accepte une demande
    public function accepterDemande($id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE demandes 
                SET statut = 'ACCEPTEE', date_modification = CURRENT_TIMESTAMP
                WHERE id = :id AND statut = 'EN_ATTENTE'
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'acceptation de la demande : " . $e->getMessage());
            return false;
        }
    }
    
    // Refuse une demande
    public function refuserDemande($id, $raison = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE demandes 
                SET statut = 'REFUSEE', motif = COALESCE(:raison, motif), date_modification = CURRENT_TIMESTAMP
                WHERE id = :id AND statut = 'EN_ATTENTE'
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':raison', $raison, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du refus de la demande : " . $e->getMessage());
            return false;
        }
    }
    
    // Met à jour une demande
    public function mettreAJourDemande($id, $data) {
        try {
            $updates = [];
            $params = [':id' => $id];
            
            if (isset($data['statut'])) {
                $updates[] = "statut = :statut";
                $params[':statut'] = $data['statut'];
            }
            
            if (isset($data['motif'])) {
                $updates[] = "motif = :motif";
                $params[':motif'] = $data['motif'];
            }
            
            if (isset($data['priorite'])) {
                $updates[] = "priorite = :priorite";
                $params[':priorite'] = $data['priorite'];
            }
            
            if (isset($data['disponibilite_id'])) {
                $updates[] = "disponibilite_id = :disponibilite_id";
                $params[':disponibilite_id'] = $data['disponibilite_id'];
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $updates[] = "date_modification = CURRENT_TIMESTAMP";
            
            $sql = "UPDATE demandes SET " . implode(", ", $updates) . " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la demande : " . $e->getMessage());
            return false;
        }
    }
}

