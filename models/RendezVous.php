<?php
declare(strict_types=1);

/**
 * Modèle RendezVous - Gestion des rendez-vous confirmés
 */
class RendezVous
{
    private PDO $pdo;

    // Statuts possibles
    public const STATUT_A_VENIR = 'A_VENIR';
    public const STATUT_EN_COURS = 'EN_COURS';
    public const STATUT_ANNULE   = 'ANNULE';
    public const STATUT_REPORTE  = 'REPORTE';
    public const STATUT_TERMINE  = 'TERMINE';

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètres : demande, étudiant, tuteur, service, dispo, durée, lieu (opt), notes (opt), prix
    // Retourne : id du rendez-vous créé ou false
    public function creerRendezVous(
        ?string $demandeId,
        string $etudiantId,
        string $tuteurId,
        string $serviceId,
        string $disponibiliteId,
        int $duree,
        ?string $lieu,
        ?string $notes,
        float $prix
    ) {
        try {
            // Étudiant actif ?
            $etudiantStmt = $this->pdo->prepare("
                SELECT id 
                FROM etudiants 
                WHERE id = :etudiant_id AND actif = TRUE
            ");
            $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $etudiantStmt->execute();
            if (!$etudiantStmt->fetch()) {
                $this->logError("Étudiant inexistant ou inactif (id: $etudiantId)");
                return false;
            }

            // Tuteur actif ?
            $tuteurStmt = $this->pdo->prepare("
                SELECT id 
                FROM tuteurs 
                WHERE id = :tuteur_id AND actif = TRUE
            ");
            $tuteurStmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $tuteurStmt->execute();
            if (!$tuteurStmt->fetch()) {
                $this->logError("Tuteur inexistant ou inactif (id: $tuteurId)");
                return false;
            }

            // Service actif ?
            $serviceStmt = $this->pdo->prepare("
                SELECT id 
                FROM services 
                WHERE id = :service_id AND actif = TRUE
            ");
            $serviceStmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $serviceStmt->execute();
            if (!$serviceStmt->fetch()) {
                $this->logError("Service inexistant ou inactif (id: $serviceId)");
                return false;
            }

            // Disponibilité RESERVE ?
            $dispoStmt = $this->pdo->prepare("
                SELECT id, date_debut, statut 
                FROM disponibilites 
                WHERE id = :disponibilite_id
            ");
            $dispoStmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
            $dispoStmt->execute();

            $disponibilite = $dispoStmt->fetch();
            if (!$disponibilite) {
                $this->logError("Disponibilité inexistante (id: $disponibiliteId)");
                return false;
            }
            if ($disponibilite['statut'] !== 'RESERVE') {
                $currentStatus = $disponibilite['statut'] ?? 'NULL';
                $this->logError("Disponibilité doit être RESERVE (actuel: $currentStatus)");
                return false;
            }

            // Demande associée ?
            if ($demandeId !== null) {
                $demandeStmt = $this->pdo->prepare("
                    SELECT id 
                    FROM demandes 
                    WHERE id = :demande_id
                ");
                $demandeStmt->bindParam(':demande_id', $demandeId, PDO::PARAM_STR);
                $demandeStmt->execute();
                if (!$demandeStmt->fetch()) {
                    $this->logError("Demande inexistante (id: $demandeId)");
                    return false;
                }
            }

            $dateHeure = $disponibilite['date_debut'];
            $id        = $this->generateUUID();
            $statut    = self::STATUT_A_VENIR;

            $stmt = $this->pdo->prepare("
                INSERT INTO rendez_vous (
                    id, demande_id, etudiant_id, tuteur_id, service_id, disponibilite_id,
                    date_heure, statut, duree, lieu, notes, prix
                ) VALUES (
                    :id, :demande_id, :etudiant_id, :tuteur_id, :service_id, :disponibilite_id,
                    :date_heure, :statut, :duree, :lieu, :notes, :prix
                )
            ");

            $stmt->bindParam(':id',              $id,              PDO::PARAM_STR);
            $stmt->bindParam(':demande_id',      $demandeId,       PDO::PARAM_STR);
            $stmt->bindParam(':etudiant_id',     $etudiantId,      PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id',       $tuteurId,        PDO::PARAM_STR);
            $stmt->bindParam(':service_id',      $serviceId,       PDO::PARAM_STR);
            $stmt->bindParam(':disponibilite_id',$disponibiliteId, PDO::PARAM_STR);
            $stmt->bindParam(':date_heure',      $dateHeure,       PDO::PARAM_STR);
            $stmt->bindParam(':statut',          $statut,          PDO::PARAM_STR);
            $stmt->bindParam(':duree',           $duree,           PDO::PARAM_INT);
            $stmt->bindParam(':lieu',            $lieu,            PDO::PARAM_STR);
            $stmt->bindParam(':notes',           $notes,           PDO::PARAM_STR);
            $stmt->bindParam(':prix',            $prix);

            $result = $stmt->execute();

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->logError("INSERT rendez_vous échoué : " . ($errorInfo[2] ?? 'Erreur inconnue'));
                return false;
            }

            return $id;
        } catch (PDOException $e) {
            $this->logError("PDO creerRendezVous : " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->logError("Exception creerRendezVous : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id rendez-vous
    // Retourne : tableau associatif ou null
    public function getRendezVousById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                    rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                    e.nom AS etudiant_nom, e.prenom AS etudiant_prenom, e.email AS etudiant_email,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom, t.email AS tuteur_email,
                    s.nom AS service_nom, s.categorie AS service_categorie
                FROM rendez_vous rv
                LEFT JOIN etudiants e ON rv.etudiant_id = e.id
                LEFT JOIN tuteurs   t ON rv.tuteur_id = t.id
                LEFT JOIN services  s ON rv.service_id = s.id
                WHERE rv.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            $this->logError("getRendezVousById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id étudiant
    // Retourne : tableau de rendez-vous
    public function getRendezVousByEtudiantId(string $etudiantId): array
    {
        try {
            // Mettre à jour automatiquement les statuts avant de récupérer
            $this->updateStatutsAutomatiques();
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                    rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    s.nom AS service_nom, s.categorie AS service_categorie
                FROM rendez_vous rv
                LEFT JOIN tuteurs  t ON rv.tuteur_id = t.id
                LEFT JOIN services s ON rv.service_id = s.id
                WHERE rv.etudiant_id = :etudiant_id
                ORDER BY rv.date_heure ASC
            ");
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("getRendezVousByEtudiantId : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id tuteur
    // Retourne : tableau de rendez-vous
    public function getRendezVousByTuteurId(string $tuteurId): array
    {
        try {
            // Mettre à jour automatiquement les statuts avant de récupérer
            $this->updateStatutsAutomatiques();
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                    rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                    e.nom AS etudiant_nom, e.prenom AS etudiant_prenom, e.email AS etudiant_email,
                    s.nom AS service_nom, s.categorie AS service_categorie
                FROM rendez_vous rv
                LEFT JOIN etudiants e ON rv.etudiant_id = e.id
                LEFT JOIN services  s ON rv.service_id = s.id
                WHERE rv.tuteur_id = :tuteur_id
                ORDER BY rv.date_heure ASC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("getRendezVousByTuteurId : " . $e->getMessage());
            return [];
        }
    }

    // Met à jour automatiquement les statuts des rendez-vous basés sur la date/heure
    // Retourne : nombre de rendez-vous mis à jour
    public function updateStatutsAutomatiques(): int
    {
        try {
            $now = new DateTime();
            $nowStr = $now->format('Y-m-d H:i:s');
            
            $aVenir = self::STATUT_A_VENIR;
            $enCours = self::STATUT_EN_COURS;
            $termine = self::STATUT_TERMINE;
            
            // Mettre à jour les rendez-vous A_VENIR dont la date est passée -> EN_COURS
            $stmt1 = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = :en_cours
                WHERE statut = :a_venir 
                AND date_heure <= :now
            ");
            $stmt1->bindParam(':en_cours', $enCours, PDO::PARAM_STR);
            $stmt1->bindParam(':a_venir', $aVenir, PDO::PARAM_STR);
            $stmt1->bindParam(':now', $nowStr, PDO::PARAM_STR);
            $stmt1->execute();
            $updated1 = $stmt1->rowCount();
            
            // Mettre à jour les rendez-vous EN_COURS dont la date + durée est passée -> TERMINE
            $stmt2 = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = :termine
                WHERE statut = :en_cours 
                AND DATE_ADD(date_heure, INTERVAL duree MINUTE) <= :now
            ");
            $stmt2->bindParam(':termine', $termine, PDO::PARAM_STR);
            $stmt2->bindParam(':en_cours', $enCours, PDO::PARAM_STR);
            $stmt2->bindParam(':now', $nowStr, PDO::PARAM_STR);
            $stmt2->execute();
            $updated2 = $stmt2->rowCount();
            
            return $updated1 + $updated2;
        } catch (PDOException $e) {
            $this->logError("updateStatutsAutomatiques : " . $e->getMessage());
            return 0;
        }
    }

    // Retourne : tableau de tous les rendez-vous (pour admin)
    public function getAllRendezVous(): array
    {
        try {
            // Mettre à jour automatiquement les statuts avant de récupérer
            $this->updateStatutsAutomatiques();
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    rv.id, rv.demande_id, rv.etudiant_id, rv.tuteur_id, rv.service_id, rv.disponibilite_id,
                    rv.date_heure, rv.statut, rv.duree, rv.lieu, rv.notes, rv.prix, rv.date_creation,
                    e.nom AS etudiant_nom, e.prenom AS etudiant_prenom, e.email AS etudiant_email,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom, t.email AS tuteur_email,
                    s.nom AS service_nom, s.categorie AS service_categorie
                FROM rendez_vous rv
                LEFT JOIN etudiants e ON rv.etudiant_id = e.id
                LEFT JOIN tuteurs   t ON rv.tuteur_id = t.id
                LEFT JOIN services  s ON rv.service_id = s.id
                ORDER BY rv.date_heure DESC
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("getAllRendezVous : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id rendez-vous
    // Retourne : true si statut mis à jour (EN_COURS/A_VENIR)
    public function confirmerRendezVous(string $id): bool
    {
        try {
            $rv = $this->getRendezVousById($id);
            if (!$rv) {
                $this->logError("confirmerRendezVous : rendez-vous inexistant (id: $id)");
                return false;
            }

            $dateHeure = new DateTime($rv['date_heure']);
            $now       = new DateTime();

            $statut = $dateHeure <= $now
                ? self::STATUT_EN_COURS
                : self::STATUT_A_VENIR;

            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = :statut
                WHERE id = :id AND statut IN (:a_venir, :en_cours)
            ");

            $aVenir  = self::STATUT_A_VENIR;
            $enCours = self::STATUT_EN_COURS;

            $stmt->bindParam(':id',      $id,     PDO::PARAM_STR);
            $stmt->bindParam(':statut',  $statut, PDO::PARAM_STR);
            $stmt->bindParam(':a_venir', $aVenir, PDO::PARAM_STR);
            $stmt->bindParam(':en_cours',$enCours,PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("confirmerRendezVous : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : id rendez-vous, raison (opt)
    // Retourne : true si annulé
    public function annulerRendezVous(string $id, ?string $raison = null): bool
    {
        try {
            $statutAnnule  = self::STATUT_ANNULE;
            $aVenir        = self::STATUT_A_VENIR;
            $enCours       = self::STATUT_EN_COURS;

            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = :statut_annule, notes = COALESCE(:raison, notes)
                WHERE id = :id AND statut IN (:a_venir, :en_cours)
            ");
            $stmt->bindParam(':id',            $id,           PDO::PARAM_STR);
            $stmt->bindParam(':raison',        $raison,       PDO::PARAM_STR);
            $stmt->bindParam(':statut_annule', $statutAnnule, PDO::PARAM_STR);
            $stmt->bindParam(':a_venir',       $aVenir,       PDO::PARAM_STR);
            $stmt->bindParam(':en_cours',      $enCours,      PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("annulerRendezVous : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : id rendez-vous, nouvelle date (string)
    // Retourne : true si reporté
    public function reporterRendezVous(string $id, string $nouvelleDate): bool
    {
        try {
            $nouvelleDateObj = new DateTime($nouvelleDate);
            $now             = new DateTime();

            if ($nouvelleDateObj <= $now) {
                $this->logError("reporterRendezVous : nouvelle date doit être dans le futur");
                return false;
            }

            $statutReporte = self::STATUT_REPORTE;
            $aVenir        = self::STATUT_A_VENIR;
            $enCours       = self::STATUT_EN_COURS;

            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET date_heure = :nouvelle_date, statut = :statut_reporte
                WHERE id = :id AND statut IN (:a_venir, :en_cours)
            ");
            $stmt->bindParam(':id',             $id,             PDO::PARAM_STR);
            $stmt->bindParam(':nouvelle_date',  $nouvelleDate,   PDO::PARAM_STR);
            $stmt->bindParam(':statut_reporte', $statutReporte,  PDO::PARAM_STR);
            $stmt->bindParam(':a_venir',        $aVenir,         PDO::PARAM_STR);
            $stmt->bindParam(':en_cours',       $enCours,        PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("reporterRendezVous : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id rendez-vous
    // Retourne : true si terminé
    public function terminerRendezVous(string $id): bool
    {
        try {
            $statutTermine = self::STATUT_TERMINE;
            $aVenir        = self::STATUT_A_VENIR;
            $enCours       = self::STATUT_EN_COURS;

            $stmt = $this->pdo->prepare("
                UPDATE rendez_vous 
                SET statut = :statut_termine
                WHERE id = :id AND statut IN (:a_venir, :en_cours)
            ");
            $stmt->bindParam(':id',             $id,           PDO::PARAM_STR);
            $stmt->bindParam(':statut_termine', $statutTermine, PDO::PARAM_STR);
            $stmt->bindParam(':a_venir',        $aVenir,       PDO::PARAM_STR);
            $stmt->bindParam(':en_cours',       $enCours,      PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("terminerRendezVous : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : message d'erreur
    private function logError(string $message): void
    {
        error_log('Erreur RendezVous : ' . $message);
    }

    // Paramètres : aucun
    // Retourne : UUID v4
    private function generateUUID(): string
    {
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
