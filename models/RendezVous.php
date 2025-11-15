<?php
/**
 * Modèle RendezVous - Gestion des rendez-vous confirmés
 */

class RendezVous {
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
    
    // Crée un nouveau rendez-vous
    public function creerRendezVous($demandeId, $etudiantId, $tuteurId, $serviceId, $disponibiliteId, $duree, $lieu = null, $notes = null, $prix) {
        try {
            // Validation : vérifier que l'étudiant existe
            $etudiantStmt = $this->pdo->prepare("SELECT id FROM etudiants WHERE id = :etudiant_id AND actif = TRUE");
            $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $etudiantStmt->execute();
            if (!$etudiantStmt->fetch()) {
                error_log("Erreur : L'étudiant spécifié n'existe pas ou n'est pas actif");
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
            
            // Validation : vérifier que le service existe
            $serviceStmt = $this->pdo->prepare("SELECT id FROM services WHERE id = :service_id AND actif = TRUE");
            $serviceStmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $serviceStmt->execute();
            if (!$serviceStmt->fetch()) {
                error_log("Erreur : Le service spécifié n'existe pas ou n'est pas actif");
                return false;
            }
            
            // Validation : vérifier que la disponibilité existe et est réservée
            $dispoStmt = $this->pdo->prepare("SELECT id, date_debut, statut FROM disponibilites WHERE id = :disponibilite_id");
            $dispoStmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
            $dispoStmt->execute();
            $disponibilite = $dispoStmt->fetch();
            if (!$disponibilite) {
                error_log("Erreur : La disponibilité spécifiée n'existe pas");
                return false;
            }
            if ($disponibilite['statut'] !== 'RESERVE') {
                error_log("Erreur : La disponibilité doit être réservée pour créer un rendez-vous");
                return false;
            }
            
            // Si demande_id est fourni, vérifier qu'il existe
            if ($demandeId !== null) {
                $demandeStmt = $this->pdo->prepare("SELECT id FROM demandes WHERE id = :demande_id");
                $demandeStmt->bindParam(':demande_id', $demandeId, PDO::PARAM_STR);
                $demandeStmt->execute();
                if (!$demandeStmt->fetch()) {
                    error_log("Erreur : La demande spécifiée n'existe pas");
                    return false;
                }
            }
            
            // Utiliser la date_debut de la disponibilité comme date_heure du rendez-vous
            $dateHeure = $disponibilite['date_debut'];
            
            $id = $this->generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO rendez_vous (id, demande_id, etudiant_id, tuteur_id, service_id, disponibilite_id, date_heure, statut, duree, lieu, notes, prix)
                VALUES (:id, :demande_id, :etudiant_id, :tuteur_id, :service_id, :disponibilite_id, :date_heure, 'A_VENIR', :duree, :lieu, :notes, :prix)
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':demande_id', $demandeId, PDO::PARAM_STR);
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
            $stmt->bindParam(':date_heure', $dateHeure, PDO::PARAM_STR);
            $stmt->bindParam(':duree', $duree, PDO::PARAM_INT);
            $stmt->bindParam(':lieu', $lieu, PDO::PARAM_STR);
            $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
            $stmt->bindParam(':prix', $prix, PDO::PARAM_STR);
            
            $stmt->execute();
            
            return $id;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du rendez-vous : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupère un rendez-vous par son ID
    public function getRendezVousById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                       rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.email as etudiant_email,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom, t.email as tuteur_email,
                       s.nom as service_nom, s.categorie as service_categorie
                FROM rendez_vous rv
                LEFT JOIN etudiants e ON rv.etudiant_id = e.id
                LEFT JOIN tuteurs t ON rv.tuteur_id = t.id
                LEFT JOIN services s ON rv.service_id = s.id
                WHERE rv.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du rendez-vous : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère tous les rendez-vous d'un étudiant
    public function getRendezVousByEtudiantId($etudiantId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                       rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom,
                       s.nom as service_nom, s.categorie as service_categorie
                FROM rendez_vous rv
                LEFT JOIN tuteurs t ON rv.tuteur_id = t.id
                LEFT JOIN services s ON rv.service_id = s.id
                WHERE rv.etudiant_id = :etudiant_id
                ORDER BY rv.date_heure ASC
            ");
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rendez-vous de l'étudiant : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère tous les rendez-vous d'un tuteur
    public function getRendezVousByTuteurId($tuteurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                       rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                       e.nom as etudiant_nom, e.prenom as etudiant_prenom, e.email as etudiant_email,
                       s.nom as service_nom, s.categorie as service_categorie
                FROM rendez_vous rv
                LEFT JOIN etudiants e ON rv.etudiant_id = e.id
                LEFT JOIN services s ON rv.service_id = s.id
                WHERE rv.tuteur_id = :tuteur_id
                ORDER BY rv.date_heure ASC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des rendez-vous du tuteur : " . $e->getMessage());
            return [];
        }
    }
    
    // Confirme un rendez-vous (change le statut à EN_COURS si la date est arrivée, sinon reste A_VENIR)
    public function confirmerRendezVous($id) {
        try {
            // Vérifier la date du rendez-vous
            $rv = $this->getRendezVousById($id);
            if (!$rv) {
                error_log("Erreur : Le rendez-vous n'existe pas");
                return false;
            }
            
            $dateHeure = new DateTime($rv['date_heure']);
            $now = new DateTime();
            $statut = ($dateHeure <= $now) ? 'EN_COURS' : 'A_VENIR';
            
            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = :statut
                WHERE id = :id AND statut IN ('A_VENIR', 'EN_COURS')
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la confirmation du rendez-vous : " . $e->getMessage());
            return false;
        }
    }
    
    // Annule un rendez-vous
    public function annulerRendezVous($id, $raison = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = 'ANNULE', notes = COALESCE(:raison, notes)
                WHERE id = :id AND statut IN ('A_VENIR', 'EN_COURS')
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':raison', $raison, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'annulation du rendez-vous : " . $e->getMessage());
            return false;
        }
    }
    
    // Reporte un rendez-vous
    public function reporterRendezVous($id, $nouvelleDate) {
        try {
            // Validation : vérifier que la nouvelle date est dans le futur
            $nouvelleDateObj = new DateTime($nouvelleDate);
            $now = new DateTime();
            if ($nouvelleDateObj <= $now) {
                error_log("Erreur : La nouvelle date doit être dans le futur");
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET date_heure = :nouvelle_date, statut = 'REPORTE'
                WHERE id = :id AND statut IN ('A_VENIR', 'EN_COURS')
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':nouvelle_date', $nouvelleDate, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors du report du rendez-vous : " . $e->getMessage());
            return false;
        }
    }
    
    // Termine un rendez-vous
    public function terminerRendezVous($id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = 'TERMINE'
                WHERE id = :id AND statut = 'EN_COURS'
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la finalisation du rendez-vous : " . $e->getMessage());
            return false;
        }
    }
}

