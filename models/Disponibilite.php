<?php
declare(strict_types=1);

class Disponibilite
{
    private PDO $pdo;

    // Statuts possibles
    public const STATUT_DISPONIBLE = 'DISPONIBLE';
    public const STATUT_RESERVE    = 'RESERVE';
    public const STATUT_BLOQUE     = 'BLOQUE';

    // Durée minimum en minutes
    private const DUREE_MINIMUM_MINUTES = 30;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètre : id de la disponibilité
    // Retourne : tableau associatif ou null
    public function getDisponibiliteById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                    d.statut, d.etudiant_id, d.prix, d.notes, d.date_creation, d.date_modification,
                    s.nom AS service_nom, s.categorie AS service_categorie,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    e.nom AS etudiant_nom, e.prenom AS etudiant_prenom
                FROM disponibilites d
                LEFT JOIN services   s ON d.service_id = s.id
                LEFT JOIN tuteurs    t ON d.tuteur_id = t.id
                LEFT JOIN etudiants  e ON d.etudiant_id = e.id
                WHERE d.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            $this->logError("Récupération par id : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id de service
    // Retourne : tableau de disponibilités (tableaux associatifs)
    public function getDisponibilitesByServiceId(string $serviceId): array
    {
        try {
            // Récupérer le tuteur du service
            $serviceStmt = $this->pdo->prepare("
                SELECT tuteur_id 
                FROM services 
                WHERE id = :service_id
            ");
            $serviceStmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $serviceStmt->execute();
            $service = $serviceStmt->fetch();

            if (!$service) {
                return [];
            }

            $tuteurId = $service['tuteur_id'];

            // Disponibilités spécifiques au service OU générales du tuteur
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                    d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                    s.nom AS service_nom, s.categorie AS service_categorie,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs  t ON d.tuteur_id = t.id
                WHERE d.statut = :statut_disponible
                  AND d.date_fin >= NOW()
                  AND (
                      d.service_id = :service_id
                      OR (d.service_id IS NULL AND d.tuteur_id = :tuteur_id)
                  )
                ORDER BY d.date_debut ASC
            ");
            $statutDisponible = self::STATUT_DISPONIBLE;
            $stmt->bindParam(':statut_disponible', $statutDisponible, PDO::PARAM_STR);
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("Récupération par service : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id du tuteur
    // Retourne : tableau de disponibilités
    public function getDisponibilitesByTuteurId(string $tuteurId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                    d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                    s.nom AS service_nom, s.categorie AS service_categorie
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                WHERE d.tuteur_id = :tuteur_id
                ORDER BY d.date_debut ASC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("Récupération par tuteur : " . $e->getMessage());
            return [];
        }
    }

    // Paramètres : aucun
    // Retourne : toutes les dispos DISPONIBLE futures
    public function getAllAvailableDisponibilites(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                    d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                    s.nom AS service_nom, s.categorie AS service_categorie,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs  t ON d.tuteur_id = t.id
                WHERE d.statut = :statut_disponible
                  AND d.date_debut >= NOW()
                ORDER BY d.date_debut ASC
            ");
            $statutDisponible = self::STATUT_DISPONIBLE;
            $stmt->bindParam(':statut_disponible', $statutDisponible, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError("Récupération des dispos disponibles : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id de la disponibilité
    // Retourne : true si disponible, false sinon
    public function estDisponible(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT statut, date_debut
                FROM disponibilites
                WHERE id = :id 
                  AND statut = :statut_disponible
                  AND date_debut >= NOW()
            ");
            $statutDisponible = self::STATUT_DISPONIBLE;
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':statut_disponible', $statutDisponible, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            $this->logError("Vérification estDisponible : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : tuteur, début, fin, statut, service (opt), prix (opt), notes (opt)
    // Retourne : id de la disponibilité créée ou false
    public function creerDisponibilite(
        string $tuteurId,
        string $dateDebut,
        string $dateFin,
        string $statut = self::STATUT_DISPONIBLE,
        ?string $serviceId = null,
        ?float $prix = null,
        ?string $notes = null
    ) {
        try {
            if (!$this->validerPeriode($dateDebut, $dateFin)) {
                return false;
            }

            $id = $this->generateUUID();

            $stmt = $this->pdo->prepare("
                INSERT INTO disponibilites (
                    id, tuteur_id, service_id, date_debut, date_fin, 
                    statut, prix, notes
                )
                VALUES (
                    :id, :tuteur_id, :service_id, :date_debut, :date_fin, 
                    :statut, :prix, :notes
                )
            ");

            $stmt->bindParam(':id',         $id,        PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id',  $tuteurId,  PDO::PARAM_STR);
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':date_debut', $dateDebut, PDO::PARAM_STR);
            $stmt->bindParam(':date_fin',   $dateFin,   PDO::PARAM_STR);
            $stmt->bindParam(':statut',     $statut,    PDO::PARAM_STR);
            $stmt->bindParam(':prix',       $prix);
            $stmt->bindParam(':notes',      $notes,     PDO::PARAM_STR);

            $stmt->execute();
            return $id;
        } catch (PDOException $e) {
            $this->logError("Création disponibilité : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : id, début, fin, statut (opt), service (opt), prix (opt), notes (opt), étudiant (opt)
    // Retourne : true si modifiée, false sinon
    public function modifierDisponibilite(
        string $id,
        string $dateDebut,
        string $dateFin,
        ?string $statut = null,
        ?string $serviceId = null,
        ?float $prix = null,
        ?string $notes = null,
        ?string $etudiantId = null
    ): bool {
        try {
            // Récupérer la disponibilité actuelle pour appliquer les règles métier
            $disponibiliteActuelle = $this->getDisponibiliteById($id);
            if (!$disponibiliteActuelle) {
                $this->logError("Modification : la disponibilité n'existe pas (id: $id)");
                return false;
            }

            // Règle métier : un créneau déjà réservé avec un rendez-vous ne doit pas être modifiable
            // On considère qu'il est "réservé par un étudiant" si :
            // - son statut est RESERVE
            // - et un rendez-vous existe pour cette disponibilité
            if ($disponibiliteActuelle['statut'] === self::STATUT_RESERVE) {
                try {
                    $stmtRv = $this->pdo->prepare("
                        SELECT id 
                        FROM rendez_vous 
                        WHERE disponibilite_id = :disponibilite_id
                        LIMIT 1
                    ");
                    $stmtRv->bindParam(':disponibilite_id', $id, PDO::PARAM_STR);
                    $stmtRv->execute();
                    $rendezVousLie = $stmtRv->fetch();

                    if ($rendezVousLie) {
                        $this->logError("Modification impossible : créneau déjà réservé avec un rendez-vous (disponibilite_id: $id)");
                        return false;
                    }
                } catch (PDOException $e) {
                    // En cas d'erreur lors du contrôle, on logue et bloque par prudence
                    $this->logError("Contrôle rendez-vous lié : " . $e->getMessage());
                    return false;
                }
            }

            if (!$this->validerPeriode($dateDebut, $dateFin)) {
                return false;
            }

            $updates = [];
            $params  = [
                ':id'         => $id,
                ':date_debut' => $dateDebut,
                ':date_fin'   => $dateFin,
            ];

            $updates[] = 'date_debut = :date_debut';
            $updates[] = 'date_fin   = :date_fin';

            if ($statut !== null) {
                $updates[]           = 'statut = :statut';
                $params[':statut']   = $statut;
            }

            if ($serviceId !== null) {
                $updates[]           = 'service_id = :service_id';
                $params[':service_id'] = $serviceId;
            }

            if ($prix !== null) {
                $updates[]         = 'prix = :prix';
                $params[':prix']   = $prix;
            }

            if ($notes !== null) {
                $updates[]         = 'notes = :notes';
                $params[':notes']  = $notes;
            }

            // Gestion de l'étudiant lié à la réservation
            if ($etudiantId !== null) {
                // Vérifier que l'étudiant existe
                $etudiantStmt = $this->pdo->prepare("
                    SELECT id 
                    FROM etudiants 
                    WHERE id = :etudiant_id
                ");
                $etudiantStmt->bindParam(':etudiant_id', $etudiantId, PDO::PARAM_STR);
                $etudiantStmt->execute();

                if (!$etudiantStmt->fetch()) {
                    $this->logError("Étudiant inexistant pour la disponibilité (etudiant_id: $etudiantId)");
                    return false;
                }

                $updates[]             = 'etudiant_id = :etudiant_id';
                $params[':etudiant_id'] = $etudiantId;
            } elseif ($statut !== null && $statut !== self::STATUT_RESERVE) {
                // Si on sort du statut RESERVE, on remet l'étudiant à NULL
                $updates[] = 'etudiant_id = NULL';
            }

            $sql  = 'UPDATE disponibilites SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Modification disponibilité : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id de la disponibilité
    // Retourne : true si supprimée, false sinon
    /* Testé par Diane Devi le 24/11/2025 Réussi */
    public function supprimerDisponibilite(string $id): bool
    {
        try {
            $disponibilite = $this->getDisponibiliteById($id);

            if (!$disponibilite) {
                $this->logError("Suppression : la disponibilité n'existe pas (id: $id)");
                return false;
            }

            if ($disponibilite['statut'] === self::STATUT_RESERVE) {
                $this->logError("Suppression impossible : créneau réservé (id: $id)");
                return false;
            }

            $stmt = $this->pdo->prepare("
                DELETE FROM disponibilites 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Suppression disponibilité : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : date début, date fin (string)
    // Retourne : true si valide (même jour, >= 30 min, fin > début)
    private function validerPeriode(string $dateDebut, string $dateFin): bool
    {
        try {
            $dateDebutObj = new DateTime($dateDebut);
            $dateFinObj   = new DateTime($dateFin);

            $diff    = $dateDebutObj->diff($dateFinObj);
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

            if ($minutes < self::DUREE_MINIMUM_MINUTES) {
                $this->logError("Durée minimum non respectée (min " . self::DUREE_MINIMUM_MINUTES . " minutes)");
                return false;
            }

            if ($dateFinObj <= $dateDebutObj) {
                $this->logError("date_fin doit être > date_debut");
                return false;
            }

            if ($dateDebutObj->format('Y-m-d') !== $dateFinObj->format('Y-m-d')) {
                $this->logError("début et fin doivent être le même jour");
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->logError("Validation période : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : message d'erreur
    private function logError(string $message): void
    {
        error_log('Erreur Disponibilite : ' . $message);
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
