<?php
declare(strict_types=1);

/**
 * Modèle Demande - Gestion des demandes de rendez-vous
 */
class Demande
{
    private PDO $pdo;

    // Constantes de statuts
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_ACCEPTEE   = 'ACCEPTEE';
    public const STATUT_REFUSEE    = 'REFUSEE';

    // Constructeur
    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Génère un UUID v4
    // Retourne : string UUID
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

    // Crée une nouvelle demande
    // Paramètres : id étudiant, id service, id tuteur, id disponibilité (optionnel), motif (optionnel), priorité (optionnelle)
    // Retourne : id de la demande créée ou false en cas d'erreur
    public function creerDemande($etudiantId, $serviceId, $tuteurId, $disponibiliteId = null, $motif = null, $priorite = null)
    {
        try {
            // Vérifier que l'étudiant existe et est actif
            $etudiantStmt = $this->pdo->prepare("
                SELECT id, actif 
                FROM etudiants 
                WHERE id = :etudiant_id
            ");
            $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $etudiantStmt->execute();
            $etudiant = $etudiantStmt->fetch();

            if (!$etudiant) {
                $this->logError("L'étudiant spécifié n'existe pas (ID: $etudiantId)");
                return false;
            }
            if (empty($etudiant['actif'])) {
                $this->logError("L'étudiant spécifié n'est pas actif (ID: $etudiantId)");
                return false;
            }

            // Vérifier que le service existe et est actif
            $serviceStmt = $this->pdo->prepare("
                SELECT id, nom, actif 
                FROM services 
                WHERE id = :service_id
            ");
            $serviceStmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $serviceStmt->execute();
            $service = $serviceStmt->fetch();

            if (!$service) {
                $this->logError("Le service spécifié n'existe pas (ID: $serviceId)");
                return false;
            }
            if (empty($service['actif'])) {
                $nomService = $service['nom'] ?? 'N/A';
                $this->logError("Le service spécifié n'est pas actif (ID: $serviceId, Nom: $nomService)");
                return false;
            }

            // Vérifier que le tuteur existe et est actif
            $tuteurStmt = $this->pdo->prepare("
                SELECT id, nom, prenom, actif 
                FROM tuteurs 
                WHERE id = :tuteur_id
            ");
            $tuteurStmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $tuteurStmt->execute();
            $tuteur = $tuteurStmt->fetch();

            if (!$tuteur) {
                $this->logError("Le tuteur spécifié n'existe pas (ID: $tuteurId)");
                return false;
            }
            if (empty($tuteur['actif'])) {
                $prenom = $tuteur['prenom'] ?? '';
                $nom    = $tuteur['nom'] ?? '';
                $this->logError("Le tuteur spécifié n'est pas actif (ID: $tuteurId, Nom: $prenom $nom)");
                return false;
            }

            // Si une disponibilité est fournie, vérifier qu'elle existe
            if ($disponibiliteId !== null) {
                $dispoStmt = $this->pdo->prepare("
                    SELECT id 
                    FROM disponibilites 
                    WHERE id = :disponibilite_id
                ");
                $dispoStmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
                $dispoStmt->execute();

                if (!$dispoStmt->fetch()) {
                    $this->logError("La disponibilité spécifiée n'existe pas (ID: $disponibiliteId)");
                    return false;
                }
            }

            $id = $this->generateUUID();

            $stmt = $this->pdo->prepare("
                INSERT INTO demandes (
                    id, 
                    etudiant_id, 
                    service_id, 
                    tuteur_id, 
                    disponibilite_id, 
                    motif, 
                    priorite, 
                    statut
                ) VALUES (
                    :id, 
                    :etudiant_id, 
                    :service_id, 
                    :tuteur_id, 
                    :disponibilite_id, 
                    :motif, 
                    :priorite, 
                    :statut
                )
            ");

            $statut = self::STATUT_EN_ATTENTE;

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->bindParam(':disponibilite_id', $disponibiliteId, PDO::PARAM_STR);
            $stmt->bindParam(':motif', $motif, PDO::PARAM_STR);
            $stmt->bindParam(':priorite', $priorite, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->logError("Erreur SQL lors de l'INSERT dans demandes : " . ($errorInfo[2] ?? 'Erreur inconnue'));
                return false;
            }

            return $id;
        } catch (PDOException $e) {
            $this->logError("Erreur PDO lors de la création de la demande : " . $e->getMessage());
            $this->logError("Détails - Etudiant: $etudiantId, Service: $serviceId, Tuteur: $tuteurId, Disponibilite: $disponibiliteId");
            return false;
        } catch (Exception $e) {
            $this->logError("Erreur générale lors de la création de la demande : " . $e->getMessage());
            return false;
        }
    }

    // Récupère une demande par son id
    // Paramètre : id de la demande
    // Retourne : tableau associatif ou null
    public function getDemandeById($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.etudiant_id, d.service_id, d.tuteur_id, d.disponibilite_id,
                    d.date_heure_demande, d.statut, d.motif, d.priorite,
                    d.date_creation, d.date_modification,
                    e.nom   AS etudiant_nom, 
                    e.prenom AS etudiant_prenom, 
                    e.email AS etudiant_email,
                    s.nom   AS service_nom, 
                    s.categorie AS service_categorie,
                    t.nom   AS tuteur_nom, 
                    t.prenom AS tuteur_prenom, 
                    t.email AS tuteur_email,
                    rv.id AS rendez_vous_id, rv.statut AS rendez_vous_statut
                FROM demandes d
                LEFT JOIN etudiants e ON d.etudiant_id = e.id
                LEFT JOIN services  s ON d.service_id = s.id
                LEFT JOIN tuteurs   t ON d.tuteur_id = t.id
                LEFT JOIN rendez_vous rv ON rv.demande_id = d.id
                WHERE d.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError("Erreur lors de la récupération de la demande (ID: $id) : " . $e->getMessage());
            return null;
        }
    }

    // Récupère toutes les demandes d'un étudiant
    // Paramètre : id étudiant
    // Retourne : tableau de demandes (tableaux associatifs)
    /* Testé par Diane Devi le 26/11/2025 Réussi */
    public function getDemandesByEtudiantId($etudiantId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.etudiant_id, d.service_id, d.tuteur_id, d.disponibilite_id,
                    d.date_heure_demande, d.statut, d.motif, d.priorite,
                    d.date_creation, d.date_modification,
                    s.nom AS service_nom, s.categorie AS service_categorie,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom
                FROM demandes d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs  t ON d.tuteur_id = t.id
                WHERE d.etudiant_id = :etudiant_id
                ORDER BY d.date_creation DESC
            ");
            $stmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("Erreur lors de la récupération des demandes de l'étudiant (ID: $etudiantId) : " . $e->getMessage());
            return [];
        }
    }

    // Récupère toutes les demandes d'un tuteur
    // Paramètre : id tuteur
    // Retourne : tableau de demandes (tableaux associatifs)
    /* Testé par Diane Devi le 26/11/2025 Réussi */
    public function getDemandesByTuteurId($tuteurId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.etudiant_id, d.service_id, d.tuteur_id, d.disponibilite_id,
                    d.date_heure_demande, d.statut, d.motif, d.priorite,
                    d.date_creation, d.date_modification,
                    e.nom AS etudiant_nom, e.prenom AS etudiant_prenom, e.email AS etudiant_email,
                    s.nom AS service_nom, s.categorie AS service_categorie,
                    rv.id AS rendez_vous_id, rv.statut AS rendez_vous_statut
                FROM demandes d
                LEFT JOIN etudiants e ON d.etudiant_id = e.id
                LEFT JOIN services  s ON d.service_id = s.id
                LEFT JOIN rendez_vous rv ON rv.demande_id = d.id
                WHERE d.tuteur_id = :tuteur_id
                ORDER BY d.date_creation DESC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("Erreur lors de la récupération des demandes du tuteur (ID: $tuteurId) : " . $e->getMessage());
            return [];
        }
    }

    // Accepte une demande (change le statut en ACCEPTÉE si elle est EN_ATTENTE)
    // Paramètre : id de la demande
    // Retourne : true si une ligne a été modifiée, false sinon
    /* Testé par Diane Devi le 21/11/2025 Réussi */
    /* Testé par Diane Devi le 26/11/2025 Réussi */
    public function accepterDemande($id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE demandes 
                SET statut = :statut, date_modification = CURRENT_TIMESTAMP
                WHERE id = :id AND statut = :statut_en_attente
            ");
            $statutAcceptee = self::STATUT_ACCEPTEE;
            $statutEnAttente = self::STATUT_EN_ATTENTE;

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':statut', $statutAcceptee, PDO::PARAM_STR);
            $stmt->bindParam(':statut_en_attente', $statutEnAttente, PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Erreur lors de l'acceptation de la demande (ID: $id) : " . $e->getMessage());
            return false;
        }
    }

    // Refuse une demande (change le statut en REFUSÉE, avec raison optionnelle)
    // Paramètres : id de la demande, raison (optionnelle)
    // Retourne : true si une ligne a été modifiée, false sinon
    /* Testé par Diane Devi le 21/11/2025 Réussi */
    /* Testé par Diane Devi le 26/11/2025 Réussi */
    public function refuserDemande($id, $raison = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE demandes 
                SET statut = :statut_refusee,
                    motif = COALESCE(:raison, motif),
                    date_modification = CURRENT_TIMESTAMP
                WHERE id = :id AND statut = :statut_en_attente
            ");

            $statutRefusee  = self::STATUT_REFUSEE;
            $statutEnAttente = self::STATUT_EN_ATTENTE;

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':raison', $raison, PDO::PARAM_STR);
            $stmt->bindParam(':statut_refusee', $statutRefusee, PDO::PARAM_STR);
            $stmt->bindParam(':statut_en_attente', $statutEnAttente, PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Erreur lors du refus de la demande (ID: $id) : " . $e->getMessage());
            return false;
        }
    }

    // Met à jour une demande (statut, motif, priorité, disponibilité)
    // Paramètres : id de la demande, tableau de champs à modifier
    // Retourne : true si une ligne a été modifiée, false sinon
    public function mettreAJourDemande($id, array $data): bool
    {
        try {
            $updates = [];
            $params = [':id' => $id];

            if (array_key_exists('statut', $data)) {
                $updates[] = 'statut = :statut';
                $params[':statut'] = $data['statut'];
            }

            if (array_key_exists('motif', $data)) {
                $updates[] = 'motif = :motif';
                $params[':motif'] = $data['motif'];
            }

            if (array_key_exists('priorite', $data)) {
                $updates[] = 'priorite = :priorite';
                $params[':priorite'] = $data['priorite'];
            }

            if (array_key_exists('disponibilite_id', $data)) {
                $updates[] = 'disponibilite_id = :disponibilite_id';
                $params[':disponibilite_id'] = $data['disponibilite_id'];
            }

            // Rien à mettre à jour
            if (empty($updates)) {
                return false;
            }

            $updates[] = 'date_modification = CURRENT_TIMESTAMP';

            $sql = 'UPDATE demandes SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Erreur lors de la mise à jour de la demande (ID: $id) : " . $e->getMessage());
            return false;
        }
    }

    // Log interne des erreurs
    // Paramètre : message d'erreur
    private function logError(string $message): void
    {
        error_log("Erreur Demande : " . $message);
    }
}
