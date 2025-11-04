<?php

class Etudiant {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Récupère un étudiant par son ID
    public function getEtudiantById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, numero_etudiant, nom, prenom, email, telephone, 
                       niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE id = :id AND actif = TRUE
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'étudiant : " . $e->getMessage());
            return null;
        }
    }
    
    // Récupère un étudiant par son numéro d'étudiant (pour la connexion)
    public function getEtudiantByNumero($numeroEtudiant) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, numero_etudiant, nom, prenom, email, telephone, 
                       niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE numero_etudiant = :numero_etudiant AND actif = TRUE
            ");
            $stmt->bindParam(':numero_etudiant', $numeroEtudiant, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'étudiant par numéro : " . $e->getMessage());
            return null;
        }
    }
    
    // Met à jour la dernière connexion
    public function updateDerniereConnexion($id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE etudiants 
                SET derniere_connexion = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la dernière connexion : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupère tous les étudiants actifs
    public function getAllActiveEtudiants() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, numero_etudiant, nom, prenom, email, telephone, 
                       niveau, specialite, annee_etude, actif
                FROM etudiants 
                WHERE actif = TRUE 
                ORDER BY nom, prenom
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des étudiants : " . $e->getMessage());
            return [];
        }
    }
}

