<?php
declare(strict_types=1);

require_once __DIR__ . '/UtilisateurTrait.php';

class Etudiant
{
    use UtilisateurTrait;
    
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètre : id étudiant
    // Retourne : tableau associatif ou null
    public function getEtudiantById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getEtudiantById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : numéro d'étudiant
    // Retourne : tableau associatif ou null
    public function getEtudiantByNumero(string $numeroEtudiant): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE numero_etudiant = :numero_etudiant AND actif = TRUE
            ");
            $stmt->bindParam(':numero_etudiant', $numeroEtudiant, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getEtudiantByNumero : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id étudiant
    // Retourne : true si succès, false sinon
    public function updateDerniereConnexion(string $id): bool
    {
        return $this->updateDerniereConnexionForTable($id, 'etudiants');
    }

    // Paramètres : aucun
    // Retourne : tableau d'étudiants actifs
    public function getAllActiveEtudiants(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getAllActiveEtudiants : " . $e->getMessage());
            return [];
        }
    }

    // Paramètres : aucun
    // Retourne : tableau de tous les étudiants (actifs et inactifs) - pour admin
    public function getAllEtudiants(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif, date_creation, derniere_connexion
                FROM etudiants 
                ORDER BY nom, prenom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getAllEtudiants : " . $e->getMessage());
            return [];
        }
    }

    // Paramètres : id étudiant, actif (booléen)
    // Retourne : true si succès, false sinon
    public function updateActif(string $id, bool $actif): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE etudiants 
                SET actif = :actif 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':actif', $actif ? 1 : 0, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::updateActif : " . $e->getMessage());
            return false;
        }
    }

    // Paramètre : id étudiant (retourne même si inactif - pour admin)
    // Retourne : tableau associatif ou null
    public function getEtudiantByIdForAdmin(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, numero_etudiant, nom, prenom, email, telephone, 
                    niveau, specialite, annee_etude, actif, date_creation, derniere_connexion
                FROM etudiants 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::getEtudiantByIdForAdmin : " . $e->getMessage());
            return null;
        }
    }

    // Créer un nouvel étudiant (pour admin)
    // Paramètres : tous les champs nécessaires
    // Retourne : id de l'étudiant créé ou false en cas d'erreur
    public function creerEtudiant(
        string $numeroEtudiant,
        string $nom,
        string $prenom,
        string $email,
        ?string $telephone = null,
        ?string $niveau = null,
        ?string $specialite = null,
        ?int $anneeEtude = null,
        bool $actif = true
    ) {
        try {
            // Vérifier si le numéro d'étudiant existe déjà
            if ($this->getEtudiantByNumero($numeroEtudiant)) {
                error_log("Erreur Etudiant::creerEtudiant : numéro d'étudiant déjà existant");
                return false;
            }

            // Vérifier si l'email existe déjà
            if ($this->emailExiste($email, 'etudiants')) {
                error_log("Erreur Etudiant::creerEtudiant : email déjà existant");
                return false;
            }

            $id = $this->generateUUID();
            $actifValue = $actif ? 1 : 0;

            $stmt = $this->pdo->prepare("
                INSERT INTO etudiants (
                    id, numero_etudiant, nom, prenom, email, telephone,
                    niveau, specialite, annee_etude, actif, date_creation
                ) VALUES (
                    :id, :numero_etudiant, :nom, :prenom, :email, :telephone,
                    :niveau, :specialite, :annee_etude, :actif, NOW()
                )
            ");

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':numero_etudiant', $numeroEtudiant, PDO::PARAM_STR);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
            $stmt->bindParam(':niveau', $niveau, PDO::PARAM_STR);
            $stmt->bindParam(':specialite', $specialite, PDO::PARAM_STR);
            $stmt->bindParam(':annee_etude', $anneeEtude, PDO::PARAM_INT);
            $stmt->bindValue(':actif', $actifValue, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return $id;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::creerEtudiant : " . $e->getMessage());
            return false;
        }
    }

    // Modifier un étudiant (pour admin)
    // Paramètres : id et tous les champs modifiables
    // Retourne : true si succès, false sinon
    public function modifierEtudiant(
        string $id,
        string $numeroEtudiant,
        string $nom,
        string $prenom,
        string $email,
        ?string $telephone = null,
        ?string $niveau = null,
        ?string $specialite = null,
        ?int $anneeEtude = null,
        bool $actif = true
    ): bool {
        try {
            // Vérifier si l'étudiant existe
            $etudiant = $this->getEtudiantByIdForAdmin($id);
            if (!$etudiant) {
                error_log("Erreur Etudiant::modifierEtudiant : étudiant non trouvé");
                return false;
            }

            // Vérifier si le numéro d'étudiant existe déjà (sauf pour cet étudiant)
            $stmtCheck = $this->pdo->prepare("SELECT id FROM etudiants WHERE numero_etudiant = :numero AND id != :id");
            $stmtCheck->bindParam(':numero', $numeroEtudiant, PDO::PARAM_STR);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_STR);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                error_log("Erreur Etudiant::modifierEtudiant : numéro d'étudiant déjà utilisé");
                return false;
            }

            // Vérifier si l'email existe déjà (sauf pour cet étudiant)
            $stmtCheck = $this->pdo->prepare("SELECT id FROM etudiants WHERE email = :email AND id != :id");
            $stmtCheck->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_STR);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                error_log("Erreur Etudiant::modifierEtudiant : email déjà utilisé");
                return false;
            }

            $actifValue = $actif ? 1 : 0;

            $stmt = $this->pdo->prepare("
                UPDATE etudiants SET
                    numero_etudiant = :numero_etudiant,
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    telephone = :telephone,
                    niveau = :niveau,
                    specialite = :specialite,
                    annee_etude = :annee_etude,
                    actif = :actif
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':numero_etudiant', $numeroEtudiant, PDO::PARAM_STR);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
            $stmt->bindParam(':niveau', $niveau, PDO::PARAM_STR);
            $stmt->bindParam(':specialite', $specialite, PDO::PARAM_STR);
            $stmt->bindParam(':annee_etude', $anneeEtude, PDO::PARAM_INT);
            $stmt->bindValue(':actif', $actifValue, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur Etudiant::modifierEtudiant : " . $e->getMessage());
            return false;
        }
    }

}
