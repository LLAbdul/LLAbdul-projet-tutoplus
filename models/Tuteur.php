<?php
declare(strict_types=1);

class Tuteur
{
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètre : id tuteur
    // Retourne : tableau associatif ou null
    public function getTuteurById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteurById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : numéro employé
    // Retourne : tableau associatif ou null
    public function getTuteurByNumero(string $numeroEmploye): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE numero_employe = :numero_employe AND actif = TRUE
            ");
            $stmt->bindParam(':numero_employe', $numeroEmploye, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteurByNumero : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id tuteur
    // Retourne : true si succès
    public function updateDerniereConnexion(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE tuteurs 
                SET derniere_connexion = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::updateDerniereConnexion : " . $e->getMessage());
            return false;
        }
    }

    // Paramètres : aucun
    // Retourne : tous les tuteurs actifs
    public function getAllActiveTuteurs(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getAllActiveTuteurs : " . $e->getMessage());
            return [];
        }
    }

    // Paramètres : aucun
    // Retourne : tableau de tous les tuteurs (actifs et inactifs) - pour admin
    public function getAllTuteurs(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif, date_creation, derniere_connexion
                FROM tuteurs 
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getAllTuteurs : " . $e->getMessage());
            return [];
        }
    }

    // Paramètres : id tuteur, actif (booléen)
    // Retourne : true si succès, false sinon
    public function updateActif(string $id, bool $actif): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE tuteurs 
                SET actif = :actif 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':actif', $actif ? 1 : 0, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::updateActif : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id tuteur (retourne même si inactif - pour admin)
    // Retourne : tableau associatif ou null
    public function getTuteurByIdForAdmin(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif, date_creation, derniere_connexion
                FROM tuteurs 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteurByIdForAdmin : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : département
    // Retourne : tuteurs actifs de ce département
    public function getTuteursByDepartement(string $departement): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_employe, nom, prenom, email, telephone, 
                    departement, specialites, tarif_horaire, evaluation, 
                    nb_seances, actif
                FROM tuteurs 
                WHERE departement = :departement AND actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->bindParam(':departement', $departement, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::getTuteursByDepartement : " . $e->getMessage());
            return [];
        }
    }

    // Créer un nouveau tuteur (pour admin)
    // Paramètres : tous les champs nécessaires
    // Retourne : id du tuteur créé ou false en cas d'erreur
    public function creerTuteur(
        string $numeroEmploye,
        string $nom,
        string $prenom,
        string $email,
        string $departement,
        float $tarifHoraire,
        ?string $telephone = null,
        ?string $specialites = null,
        bool $actif = true
    ) {
        try {
            // Vérifier si le numéro d'employé existe déjà
            if ($this->getTuteurByNumero($numeroEmploye)) {
                error_log("Erreur Tuteur::creerTuteur : numéro d'employé déjà existant");
                return false;
            }

            // Vérifier si l'email existe déjà
            $stmtCheck = $this->pdo->prepare("SELECT id FROM tuteurs WHERE email = :email");
            $stmtCheck->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                error_log("Erreur Tuteur::creerTuteur : email déjà existant");
                return false;
            }

            $id = $this->generateUUID();
            $actifValue = $actif ? 1 : 0;

            $stmt = $this->pdo->prepare("
                INSERT INTO tuteurs (
                    id, numero_employe, nom, prenom, email, telephone,
                    departement, specialites, tarif_horaire, actif, date_creation
                ) VALUES (
                    :id, :numero_employe, :nom, :prenom, :email, :telephone,
                    :departement, :specialites, :tarif_horaire, :actif, NOW()
                )
            ");

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':numero_employe', $numeroEmploye, PDO::PARAM_STR);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
            $stmt->bindParam(':departement', $departement, PDO::PARAM_STR);
            $stmt->bindParam(':specialites', $specialites, PDO::PARAM_STR);
            $stmt->bindParam(':tarif_horaire', $tarifHoraire);
            $stmt->bindValue(':actif', $actifValue, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Créer automatiquement un service par défaut pour ce tuteur
                try {
                    // Vérifier si la classe Service existe, sinon l'inclure
                    if (!class_exists('Service')) {
                        require_once __DIR__ . '/Service.php';
                    }
                    
                    $serviceModel = new Service($this->pdo);
                    $serviceId = $serviceModel->creerServiceParDefaut($id, $departement, $tarifHoraire);
                    
                    if ($serviceId === false) {
                        error_log("Avertissement Tuteur::creerTuteur : échec de la création du service par défaut pour le tuteur $id");
                        // On continue quand même, le tuteur est créé
                    }
                } catch (Throwable $serviceException) {
                    // Ne pas faire échouer la création du tuteur si la création du service échoue
                    error_log("Avertissement Tuteur::creerTuteur : erreur lors de la création du service : " . $serviceException->getMessage());
                }
                
                return $id;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::creerTuteur : " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Erreur Tuteur::creerTuteur (exception générale) : " . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log("Erreur fatale Tuteur::creerTuteur : " . $e->getMessage());
            return false;
        }
    }

    // Modifier un tuteur (pour admin)
    // Paramètres : id et tous les champs modifiables
    // Retourne : true si succès, false sinon
    public function modifierTuteur(
        string $id,
        string $numeroEmploye,
        string $nom,
        string $prenom,
        string $email,
        string $departement,
        float $tarifHoraire,
        ?string $telephone = null,
        ?string $specialites = null,
        bool $actif = true,
        ?float $evaluation = null
    ): bool {
        try {
            // Vérifier si le tuteur existe
            $tuteur = $this->getTuteurByIdForAdmin($id);
            if (!$tuteur) {
                error_log("Erreur Tuteur::modifierTuteur : tuteur non trouvé");
                return false;
            }

            // Vérifier si le numéro d'employé existe déjà (sauf pour ce tuteur)
            $stmtCheck = $this->pdo->prepare("SELECT id FROM tuteurs WHERE numero_employe = :numero AND id != :id");
            $stmtCheck->bindParam(':numero', $numeroEmploye, PDO::PARAM_STR);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_STR);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                error_log("Erreur Tuteur::modifierTuteur : numéro d'employé déjà utilisé");
                return false;
            }

            // Vérifier si l'email existe déjà (sauf pour ce tuteur)
            $stmtCheck = $this->pdo->prepare("SELECT id FROM tuteurs WHERE email = :email AND id != :id");
            $stmtCheck->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_STR);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                error_log("Erreur Tuteur::modifierTuteur : email déjà utilisé");
                return false;
            }

            $actifValue = $actif ? 1 : 0;

            // Construire la requête UPDATE dynamiquement pour inclure l'évaluation si fournie
            $updateFields = [
                'numero_employe = :numero_employe',
                'nom = :nom',
                'prenom = :prenom',
                'email = :email',
                'telephone = :telephone',
                'departement = :departement',
                'specialites = :specialites',
                'tarif_horaire = :tarif_horaire',
                'actif = :actif'
            ];
            
            if ($evaluation !== null) {
                $updateFields[] = 'evaluation = :evaluation';
            }
            
            $updateQuery = "UPDATE tuteurs SET " . implode(', ', $updateFields) . " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($updateQuery);

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':numero_employe', $numeroEmploye, PDO::PARAM_STR);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
            $stmt->bindParam(':departement', $departement, PDO::PARAM_STR);
            $stmt->bindParam(':specialites', $specialites, PDO::PARAM_STR);
            $stmt->bindParam(':tarif_horaire', $tarifHoraire);
            $stmt->bindValue(':actif', $actifValue, PDO::PARAM_INT);
            
            if ($evaluation !== null) {
                $stmt->bindParam(':evaluation', $evaluation);
            }

            $success = $stmt->execute();
            
            // Si la modification réussit, vérifier si un service existe pour ce tuteur
            // Si aucun service n'existe, en créer un par défaut
            if ($success) {
                try {
                    // Vérifier si la classe Service existe, sinon l'inclure
                    if (!class_exists('Service')) {
                        require_once __DIR__ . '/Service.php';
                    }
                    
                    $serviceModel = new Service($this->pdo);
                    $services = $serviceModel->getServicesByTuteurId($id);
                    
                    if (empty($services)) {
                        // Aucun service n'existe, en créer un par défaut
                        $serviceId = $serviceModel->creerServiceParDefaut($id, $departement, $tarifHoraire);
                        if ($serviceId === false) {
                            error_log("Avertissement Tuteur::modifierTuteur : échec de la création du service par défaut pour le tuteur $id");
                        }
                    }
                } catch (Throwable $serviceException) {
                    // Ne pas faire échouer la modification du tuteur si la création du service échoue
                    error_log("Avertissement Tuteur::modifierTuteur : erreur lors de la création du service : " . $serviceException->getMessage());
                }
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur Tuteur::modifierTuteur : " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Erreur Tuteur::modifierTuteur (exception générale) : " . $e->getMessage());
            return false;
        }
    }

    // Générer un UUID v4
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

    // Méthodes du UML que je vais implémenter plus tard
    // +gererDisponibilites(): void
    // +accepterDemande(): void
    // +refuserDemande(): void
    // +consulterPlanning(): void
}

