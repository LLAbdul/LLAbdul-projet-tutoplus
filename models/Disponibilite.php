<?php

class Disponibilite {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Récupère une disponibilité par son ID
    public function getDisponibiliteById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la disponibilité : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère toutes les disponibilités disponibles pour un service
    public function getDisponibilitesByServiceId($serviceId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.service_id = :service_id 
                  AND d.statut = 'DISPONIBLE'
                  AND d.date_debut >= NOW()
                ORDER BY d.date_debut ASC
            ");
            $stmt->bindParam(':service_id', $serviceId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des disponibilités par service : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère toutes les disponibilités disponibles pour un tuteur
    public function getDisponibilitesByTuteurId($tuteurId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                WHERE d.tuteur_id = :tuteur_id
                  AND d.date_debut >= NOW()
                ORDER BY d.date_debut ASC
            ");
            $stmt->bindParam(':tuteur_id', $tuteurId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des disponibilités par tuteur : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère toutes les disponibilités disponibles (futures uniquement)
    public function getAllAvailableDisponibilites() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.id, d.tuteur_id, d.service_id, d.date_debut, d.date_fin, 
                       d.statut, d.prix, d.notes, d.date_creation, d.date_modification,
                       s.nom as service_nom, s.categorie as service_categorie,
                       t.nom as tuteur_nom, t.prenom as tuteur_prenom
                FROM disponibilites d
                LEFT JOIN services s ON d.service_id = s.id
                LEFT JOIN tuteurs t ON d.tuteur_id = t.id
                WHERE d.statut = 'DISPONIBLE'
                  AND d.date_debut >= NOW()
                ORDER BY d.date_debut ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des disponibilités : " . $e->getMessage());
            return [];
        }
    }
    
    // Vérifie si une disponibilité est disponible
    public function estDisponible($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT statut, date_debut
                FROM disponibilites
                WHERE id = :id 
                  AND statut = 'DISPONIBLE'
                  AND date_debut >= NOW()
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de disponibilité : " . $e->getMessage());
            return false;
        }
    }
}

