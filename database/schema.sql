-- Base de données TutoPlus
-- Script SQL pour créer la table des services

CREATE DATABASE IF NOT EXISTS tutoplus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tutoplus;

-- Table des tuteurs (hérite de Utilisateur selon UML)
CREATE TABLE IF NOT EXISTS tuteurs (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID du tuteur',
    numero_employe VARCHAR(50) NOT NULL UNIQUE COMMENT 'Numéro d\'employé du tuteur',
    nom VARCHAR(255) NOT NULL COMMENT 'Nom du tuteur',
    prenom VARCHAR(255) NOT NULL COMMENT 'Prénom du tuteur',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email du tuteur',
    telephone VARCHAR(20) COMMENT 'Téléphone du tuteur',
    departement VARCHAR(100) NOT NULL COMMENT 'Département du tuteur',
    specialites TEXT COMMENT 'Spécialités du tuteur (séparées par des virgules)',
    tarif_horaire DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Tarif horaire du tuteur',
    evaluation DECIMAL(3, 2) DEFAULT 0.00 COMMENT 'Évaluation moyenne du tuteur (0-5)',
    nb_seances INT DEFAULT 0 COMMENT 'Nombre de séances effectuées',
    actif BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Indique si le tuteur est actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    derniere_connexion DATETIME COMMENT 'Dernière connexion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des tuteurs';

-- Table des services
CREATE TABLE IF NOT EXISTS services (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID du service',
    tuteur_id CHAR(36) NOT NULL COMMENT 'UUID du tuteur associé',
    nom VARCHAR(255) NOT NULL COMMENT 'Nom du service',
    description TEXT NOT NULL COMMENT 'Description détaillée du service',
    categorie VARCHAR(100) NOT NULL COMMENT 'Catégorie du service (ex: Mathématiques, Sciences, etc.)',
    duree_minute INT NOT NULL DEFAULT 60 COMMENT 'Durée en minutes',
    prix DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Prix du service',
    actif BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Indique si le service est actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    FOREIGN KEY (tuteur_id) REFERENCES tuteurs(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_tuteur (tuteur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des services de tutorat';

-- Insertion de données de test pour les tuteurs
INSERT INTO tuteurs (id, numero_employe, nom, prenom, email, telephone, departement, specialites, tarif_horaire, evaluation, nb_seances, actif) VALUES
(UUID(), 'T001', 'Dupont', 'Jean', 'jean.dupont@college.qc.ca', '514-123-4567', 'Mathématiques', 'Algèbre,Calcul différentiel,Géométrie', 25.00, 4.5, 120, TRUE),
(UUID(), 'T002', 'Martin', 'Marie', 'marie.martin@college.qc.ca', '514-234-5678', 'Sciences', 'Physique,Chimie,Biologie', 25.00, 4.8, 95, TRUE),
(UUID(), 'T003', 'Tremblay', 'Pierre', 'pierre.tremblay@college.qc.ca', '514-345-6789', 'Informatique', 'Java,Python,JavaScript,Web', 30.00, 4.7, 150, TRUE),
(UUID(), 'T004', 'Gagnon', 'Sophie', 'sophie.gagnon@college.qc.ca', '514-456-7890', 'Langues', 'Français,Littérature', 20.00, 4.6, 80, TRUE),
(UUID(), 'T005', 'Roy', 'Luc', 'luc.roy@college.qc.ca', '514-567-8901', 'Langues', 'Anglais,Communication', 20.00, 4.4, 110, TRUE),
(UUID(), 'T006', 'Lavoie', 'Anne', 'anne.lavoie@college.qc.ca', '514-678-9012', 'Général', 'Méthodologie,Organisation', 22.00, 4.3, 65, TRUE);

-- Insertion de données de test pour les services (avec tuteurs associés)
-- Note: Les UUIDs des tuteurs doivent être récupérés après l'insertion des tuteurs
-- Pour simplifier, on utilise une sous-requête pour obtenir le premier tuteur de chaque département
INSERT INTO services (id, tuteur_id, nom, description, categorie, duree_minute, prix, actif) 
SELECT 
    UUID(),
    (SELECT id FROM tuteurs WHERE departement = 'Mathématiques' AND actif = TRUE LIMIT 1),
    'Tutorat en Mathématiques',
    'Soutien personnalisé en mathématiques pour tous les niveaux. Aide à la compréhension des concepts, résolution de problèmes et préparation aux examens.',
    'Mathématiques',
    60,
    25.00,
    TRUE
WHERE EXISTS (SELECT 1 FROM tuteurs WHERE departement = 'Mathématiques' AND actif = TRUE);

INSERT INTO services (id, tuteur_id, nom, description, categorie, duree_minute, prix, actif)
SELECT 
    UUID(),
    (SELECT id FROM tuteurs WHERE departement = 'Sciences' AND actif = TRUE LIMIT 1),
    'Tutorat en Sciences',
    'Accompagnement en sciences (physique, chimie, biologie). Explications détaillées et exercices pratiques.',
    'Sciences',
    60,
    25.00,
    TRUE
WHERE EXISTS (SELECT 1 FROM tuteurs WHERE departement = 'Sciences' AND actif = TRUE);

INSERT INTO services (id, tuteur_id, nom, description, categorie, duree_minute, prix, actif)
SELECT 
    UUID(),
    (SELECT id FROM tuteurs WHERE departement = 'Informatique' AND actif = TRUE LIMIT 1),
    'Tutorat en Programmation',
    'Aide en programmation (Java, Python, JavaScript, etc.). Débogage, explication de concepts et projets pratiques.',
    'Informatique',
    90,
    30.00,
    TRUE
WHERE EXISTS (SELECT 1 FROM tuteurs WHERE departement = 'Informatique' AND actif = TRUE);

INSERT INTO services (id, tuteur_id, nom, description, categorie, duree_minute, prix, actif)
SELECT 
    UUID(),
    (SELECT id FROM tuteurs WHERE departement = 'Langues' AND specialites LIKE '%Français%' AND actif = TRUE LIMIT 1),
    'Tutorat en Français',
    'Soutien en français : grammaire, orthographe, rédaction et compréhension de textes.',
    'Langues',
    60,
    20.00,
    TRUE
WHERE EXISTS (SELECT 1 FROM tuteurs WHERE departement = 'Langues' AND specialites LIKE '%Français%' AND actif = TRUE);

INSERT INTO services (id, tuteur_id, nom, description, categorie, duree_minute, prix, actif)
SELECT 
    UUID(),
    (SELECT id FROM tuteurs WHERE departement = 'Langues' AND specialites LIKE '%Anglais%' AND actif = TRUE LIMIT 1),
    'Tutorat en Anglais',
    'Amélioration de l''anglais : conversation, grammaire, vocabulaire et préparation aux examens.',
    'Langues',
    60,
    20.00,
    TRUE
WHERE EXISTS (SELECT 1 FROM tuteurs WHERE departement = 'Langues' AND specialites LIKE '%Anglais%' AND actif = TRUE);

INSERT INTO services (id, tuteur_id, nom, description, categorie, duree_minute, prix, actif)
SELECT 
    UUID(),
    (SELECT id FROM tuteurs WHERE departement = 'Général' AND actif = TRUE LIMIT 1),
    'Aide aux devoirs',
    'Support général pour les devoirs et les études. Organisation et méthodologie.',
    'Général',
    60,
    22.00,
    TRUE
WHERE EXISTS (SELECT 1 FROM tuteurs WHERE departement = 'Général' AND actif = TRUE);

