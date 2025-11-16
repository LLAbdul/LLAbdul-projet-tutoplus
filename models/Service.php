<?php
declare(strict_types=1);

/**
 * Modèle Service - Gestion des services de tutorat
 */
class Service
{
    private PDO $pdo;

    // Paramètre : instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Paramètres : aucun
    // Retourne : tous les services actifs avec leur tuteur
    public function getAllActiveServices(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.actif = TRUE AND t.actif = TRUE
                ORDER BY s.categorie, s.nom
            ");
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Service::getAllActiveServices : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : id service
    // Retourne : service avec tuteur ou null
    public function getServiceById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.id = :id AND s.actif = TRUE AND t.actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Service::getServiceById : " . $e->getMessage());
            return null;
        }
    }

    // Paramètre : id tuteur
    // Retourne : services actifs de ce tuteur
    public function getServicesByTuteurId(string $tuteurId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.tuteur_id = :tuteur_id AND s.actif = TRUE AND t.actif = TRUE 
                ORDER BY s.nom
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Service::getServicesByTuteurId : " . $e->getMessage());
            return [];
        }
    }

    // Paramètre : catégorie
    // Retourne : services actifs pour cette catégorie
    public function getServicesByCategory(string $categorie): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id,
                    t.nom AS tuteur_nom, t.prenom AS tuteur_prenom,
                    t.numero_employe, t.departement, t.specialites,
                    t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.categorie = :categorie AND s.actif = TRUE AND t.actif = TRUE 
                ORDER BY s.nom
            ");
            $stmt->bindParam(':categorie', $categorie, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Service::getServicesByCategory : " . $e->getMessage());
            return [];
        }
    }

    // Créer un service par défaut pour un tuteur
    // Paramètres : tuteur_id, département, tarif_horaire
    // Retourne : id du service créé ou false en cas d'erreur
    public function creerServiceParDefaut(
        string $tuteurId,
        string $departement,
        float $tarifHoraire
    ) {
        try {
            // Mapper le département vers une catégorie
            $categorie = $this->mapDepartementToCategorie($departement);
            
            // Générer le nom et la description du service
            $nom = "Tutorat en " . $departement;
            $description = "Soutien personnalisé en " . strtolower($departement) . ". Aide à la compréhension des concepts et préparation aux examens.";
            
            // Déterminer la durée et le prix
            $dureeMinute = 60; // Par défaut 60 minutes
            $prix = $tarifHoraire; // Utiliser le tarif horaire du tuteur
            
            $id = $this->generateUUID();

            $stmt = $this->pdo->prepare("
                INSERT INTO services (
                    id, tuteur_id, nom, description, categorie, duree_minute, prix, actif, date_creation
                ) VALUES (
                    :id, :tuteur_id, :nom, :description, :categorie, :duree_minute, :prix, TRUE, NOW()
                )
            ");

            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':categorie', $categorie, PDO::PARAM_STR);
            $stmt->bindParam(':duree_minute', $dureeMinute, PDO::PARAM_INT);
            $stmt->bindParam(':prix', $prix);

            if ($stmt->execute()) {
                return $id;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur Service::creerServiceParDefaut : " . $e->getMessage());
            return false;
        }
    }

    // Mapper un département vers une catégorie de service
    // Paramètre : département
    // Retourne : catégorie (Mathématiques, Sciences, Informatique, Langues, ou Général)
    private function mapDepartementToCategorie(string $departement): string
    {
        $departementLower = strtolower(trim($departement));
        
        // Mapping des départements vers les catégories
        if (strpos($departementLower, 'math') !== false) {
            return 'Mathématiques';
        }
        if (strpos($departementLower, 'science') !== false && strpos($departementLower, 'informatique') === false) {
            return 'Sciences';
        }
        if (strpos($departementLower, 'informatique') !== false || strpos($departementLower, 'programmation') !== false) {
            return 'Informatique';
        }
        if (strpos($departementLower, 'langue') !== false || strpos($departementLower, 'français') !== false || 
            strpos($departementLower, 'anglais') !== false || strpos($departementLower, 'littérature') !== false) {
            return 'Langues';
        }
        
        // Par défaut, utiliser "Général"
        return 'Général';
    }

    // Modifier un service (pour admin ou tuteur)
    // Paramètres : id service, description (optionnel), nom (optionnel), prix (optionnel), duree_minute (optionnel)
    // Retourne : true si succès, false sinon
    public function modifierService(
        string $id,
        ?string $description = null,
        ?string $nom = null,
        ?float $prix = null,
        ?int $dureeMinute = null
    ): bool {
        try {
            // Construire la requête UPDATE dynamiquement
            $updateFields = [];
            $params = [':id' => $id];
            
            if ($description !== null) {
                $updateFields[] = 'description = :description';
                $params[':description'] = $description;
            }
            if ($nom !== null) {
                $updateFields[] = 'nom = :nom';
                $params[':nom'] = $nom;
            }
            if ($prix !== null) {
                $updateFields[] = 'prix = :prix';
                $params[':prix'] = $prix;
            }
            if ($dureeMinute !== null) {
                $updateFields[] = 'duree_minute = :duree_minute';
                $params[':duree_minute'] = $dureeMinute;
            }
            
            if (empty($updateFields)) {
                return false; // Aucun champ à mettre à jour
            }
            
            $updateFields[] = 'date_modification = NOW()';
            
            $updateQuery = "UPDATE services SET " . implode(', ', $updateFields) . " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($updateQuery);
            
            foreach ($params as $key => $value) {
                if ($key === ':duree_minute') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } elseif ($key === ':prix') {
                    $stmt->bindValue($key, $value);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur Service::modifierService : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupérer un service par ID pour un tuteur (vérifie que le service appartient au tuteur)
    // Paramètres : id service, id tuteur
    // Retourne : service ou null
    public function getServiceByIdForTuteur(string $serviceId, string $tuteurId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                    s.tuteur_id, s.actif
                FROM services s
                WHERE s.id = :service_id AND s.tuteur_id = :tuteur_id
            ");
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Erreur Service::getServiceByIdForTuteur : " . $e->getMessage());
            return null;
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
}
