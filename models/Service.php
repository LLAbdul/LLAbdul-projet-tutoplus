<?php
/**
 * Modèle Service - Gestion des services de tutorat
 */

class Service {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    
    // Récupère tous les services actifs

    public function getAllActiveServices() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, nom, description, categorie, duree_minute, prix 
                FROM services 
                WHERE actif = TRUE 
                ORDER BY categorie, nom
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des services : " . $e->getMessage());
            return [];
        }
    }
    
    
    // Récupère un service par son ID
     
    public function getServiceById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, nom, description, categorie, duree_minute, prix 
                FROM services 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du service : " . $e->getMessage());
            return null;
        }
    }
    
    
    // Récupère les services par catégorie
     
    public function getServicesByCategory($categorie) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, nom, description, categorie, duree_minute, prix 
                FROM services 
                WHERE categorie = :categorie AND actif = TRUE 
                ORDER BY nom
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

