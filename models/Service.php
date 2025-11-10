<?php
/**
 * Modèle Service - Gestion des services de tutorat
 */

class Service {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    
    // Récupère tous les services actifs avec leur tuteur associé

    public function getAllActiveServices() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                       s.tuteur_id,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom,
                       t.numero_employe, t.departement, t.specialites,
                       t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.actif = TRUE AND t.actif = TRUE
                ORDER BY s.categorie, s.nom
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des services : " . $e->getMessage());
            return [];
        }
    }
    
    
    // Récupère un service par son ID avec son tuteur associé
     
    public function getServiceById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                       s.tuteur_id,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom,
                       t.numero_employe, t.departement, t.specialites,
                       t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.id = :id AND s.actif = TRUE AND t.actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du service : " . $e->getMessage());
            return null;
        }
    }
    
    
    // Récupère les services d'un tuteur spécifique
    public function getServicesByTuteurId($tuteurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                       s.tuteur_id,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom,
                       t.numero_employe, t.departement, t.specialites,
                       t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.tuteur_id = :tuteur_id AND s.actif = TRUE AND t.actif = TRUE 
                ORDER BY s.nom
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des services par tuteur : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère les services par catégorie avec leur tuteur associé
     
    public function getServicesByCategory($categorie) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.nom, s.description, s.categorie, s.duree_minute, s.prix,
                       s.tuteur_id,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom,
                       t.numero_employe, t.departement, t.specialites,
                       t.tarif_horaire, t.evaluation, t.nb_seances
                FROM services s
                INNER JOIN tuteurs t ON s.tuteur_id = t.id
                WHERE s.categorie = :categorie AND s.actif = TRUE AND t.actif = TRUE 
                ORDER BY s.nom
            ");
            $stmt->bindParam(':categorie', $categorie, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des services par catégorie : " . $e->getMessage());
            return [];
        }
    }
}

