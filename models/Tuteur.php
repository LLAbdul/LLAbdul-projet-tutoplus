<?php

class Tuteur {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Récupère un tuteur par son ID
    public function getTuteurById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, numero_employe, nom, prenom, email, telephone, 
                       departement, specialites, tarif_horaire, evaluation, 
                       nb_seances, actif
                FROM tuteurs 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du tuteur : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère tous les tuteurs actifs
    public function getAllActiveTuteurs() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, numero_employe, nom, prenom, email, telephone, 
                       departement, specialites, tarif_horaire, evaluation, 
                       nb_seances, actif
                FROM tuteurs 
                WHERE actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des tuteurs : " . $e->getMessage());
            return [];
        }
    }
    
    // Récupère les tuteurs par département
    public function getTuteursByDepartement($departement) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, numero_employe, nom, prenom, email, telephone, 
                       departement, specialites, tarif_horaire, evaluation, 
                       nb_seances, actif
                FROM tuteurs 
                WHERE departement = :departement AND actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->bindParam(':departement', $departement, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des tuteurs par département : " . $e->getMessage());
            return [];
        }
    }
    
    // Méthodes du UML que je vais implémenter plus tard
    // +gererDisponibilites(): void
    // +accepterDemande(): void
    // +refuserDemande(): void
    // +consulterPlanning(): void
}

