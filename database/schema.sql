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

-- Table des étudiants (hérite de Utilisateur selon UML)
CREATE TABLE IF NOT EXISTS etudiants (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID de l\'étudiant',
    numero_etudiant VARCHAR(50) NOT NULL UNIQUE COMMENT 'Numéro d\'étudiant',
    nom VARCHAR(255) NOT NULL COMMENT 'Nom de l\'étudiant',
    prenom VARCHAR(255) NOT NULL COMMENT 'Prénom de l\'étudiant',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email de l\'étudiant',
    telephone VARCHAR(20) COMMENT 'Téléphone de l\'étudiant',
    niveau VARCHAR(50) COMMENT 'Niveau d\'études (ex: DEC, AEC)',
    specialite VARCHAR(100) COMMENT 'Spécialité de l\'étudiant',
    annee_etude INT COMMENT 'Année d\'études',
    actif BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Indique si l\'étudiant est actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    derniere_connexion DATETIME COMMENT 'Dernière connexion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des étudiants';

-- Table des disponibilités (créneaux horaires)
CREATE TABLE IF NOT EXISTS disponibilites (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID de la disponibilité',
    tuteur_id CHAR(36) NOT NULL COMMENT 'UUID du tuteur propriétaire',
    service_id CHAR(36) COMMENT 'UUID du service associé (optionnel)',
    date_debut DATETIME NOT NULL COMMENT 'Date et heure de début du créneau',
    date_fin DATETIME NOT NULL COMMENT 'Date et heure de fin du créneau',
    statut ENUM('DISPONIBLE', 'RESERVE', 'BLOQUE') NOT NULL DEFAULT 'DISPONIBLE' COMMENT 'Statut du créneau',
    prix DECIMAL(10, 2) COMMENT 'Prix spécifique pour ce créneau (peut hériter du service)',
    notes TEXT COMMENT 'Notes additionnelles sur le créneau',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    FOREIGN KEY (tuteur_id) REFERENCES tuteurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_tuteur (tuteur_id),
    INDEX idx_service (service_id),
    INDEX idx_date_debut (date_debut),
    INDEX idx_statut (statut),
    CHECK (date_fin > date_debut),
    CHECK (TIMESTAMPDIFF(MINUTE, date_debut, date_fin) >= 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des disponibilités et créneaux horaires';

-- Insertion de données de test pour les tuteurs
INSERT INTO tuteurs (id, numero_employe, nom, prenom, email, telephone, departement, specialites, tarif_horaire, evaluation, nb_seances, actif) VALUES
(UUID(), 'T001', 'Dupont', 'Jean', 'jean.dupont@college.qc.ca', '514-123-4567', 'Mathématiques', 'Algèbre,Calcul différentiel,Géométrie', 25.00, 4.5, 120, TRUE),
(UUID(), 'T002', 'Martin', 'Marie', 'marie.martin@college.qc.ca', '514-234-5678', 'Sciences', 'Physique,Chimie,Biologie', 25.00, 4.8, 95, TRUE),
(UUID(), 'T003', 'Tremblay', 'Pierre', 'pierre.tremblay@college.qc.ca', '514-345-6789', 'Informatique', 'Java,Python,JavaScript,Web', 30.00, 4.7, 150, TRUE),
(UUID(), 'T004', 'Gagnon', 'Sophie', 'sophie.gagnon@college.qc.ca', '514-456-7890', 'Langues', 'Français,Littérature', 20.00, 4.6, 80, TRUE),
(UUID(), 'T005', 'Roy', 'Luc', 'luc.roy@college.qc.ca', '514-567-8901', 'Langues', 'Anglais,Communication', 20.00, 4.4, 110, TRUE),
(UUID(), 'T006', 'Lavoie', 'Anne', 'anne.lavoie@college.qc.ca', '514-678-9012', 'Général', 'Méthodologie,Organisation', 22.00, 4.3, 65, TRUE);

-- Insertion de données de test pour les étudiants
INSERT INTO etudiants (id, numero_etudiant, nom, prenom, email, telephone, niveau, specialite, annee_etude, actif) VALUES
(UUID(), 'E001', 'Tremblay', 'Alex', 'alex.tremblay@college.qc.ca', '514-111-2222', 'DEC', 'Sciences de la nature', 1, TRUE),
(UUID(), 'E002', 'Gagnon', 'Sarah', 'sarah.gagnon@college.qc.ca', '514-222-3333', 'DEC', 'Sciences informatiques', 2, TRUE),
(UUID(), 'E003', 'Roy', 'Marc', 'marc.roy@college.qc.ca', '514-333-4444', 'DEC', 'Sciences de la nature', 1, TRUE),
(UUID(), 'E004', 'Lavoie', 'Julie', 'julie.lavoie@college.qc.ca', '514-444-5555', 'AEC', 'Programmation', 1, TRUE),
(UUID(), 'E005', 'Fortin', 'Thomas', 'thomas.fortin@college.qc.ca', '514-555-6666', 'DEC', 'Sciences humaines', 2, TRUE);

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

-- Insertion de données de test pour les disponibilités (créneaux horaires)
-- Créneaux pour les prochains jours (aujourd'hui + 1 à 7 jours)
-- Chaque service a plusieurs créneaux disponibles

-- Créneaux pour le service Mathématiques
INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 9 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR,
    'DISPONIBLE',
    s.prix,
    'Créneau disponible en matinée'
FROM services s
WHERE s.categorie = 'Mathématiques' AND s.actif = TRUE
LIMIT 1;

INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 14 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR,
    'DISPONIBLE',
    s.prix,
    'Créneau disponible en après-midi'
FROM services s
WHERE s.categorie = 'Mathématiques' AND s.actif = TRUE
LIMIT 1;

INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 10 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 11 HOUR,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Mathématiques' AND s.actif = TRUE
LIMIT 1;

-- Créneaux pour le service Sciences
INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 13 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 14 HOUR,
    'DISPONIBLE',
    s.prix,
    'Créneau disponible'
FROM services s
WHERE s.categorie = 'Sciences' AND s.actif = TRUE
LIMIT 1;

INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 4 DAY) + INTERVAL 15 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 4 DAY) + INTERVAL 16 HOUR,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Sciences' AND s.actif = TRUE
LIMIT 1;

-- Créneaux pour le service Informatique
INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 11 HOUR + INTERVAL 30 MINUTE,
    'DISPONIBLE',
    s.prix,
    'Session de 90 minutes'
FROM services s
WHERE s.categorie = 'Informatique' AND s.actif = TRUE
LIMIT 1;

INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 14 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 15 HOUR + INTERVAL 30 MINUTE,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Informatique' AND s.actif = TRUE
LIMIT 1;

-- Créneaux pour le service Français
INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 11 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 12 HOUR,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Langues' AND s.nom LIKE '%Français%' AND s.actif = TRUE
LIMIT 1;

INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 6 DAY) + INTERVAL 9 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 6 DAY) + INTERVAL 10 HOUR,
    'RESERVE',
    s.prix,
    'Créneau réservé'
FROM services s
WHERE s.categorie = 'Langues' AND s.nom LIKE '%Français%' AND s.actif = TRUE
LIMIT 1;

-- Créneaux pour le service Anglais
INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 13 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 14 HOUR,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Langues' AND s.nom LIKE '%Anglais%' AND s.actif = TRUE
LIMIT 1;

-- Créneaux pour le service Aide aux devoirs
INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 4 DAY) + INTERVAL 10 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 4 DAY) + INTERVAL 11 HOUR,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Général' AND s.actif = TRUE
LIMIT 1;

INSERT INTO disponibilites (id, tuteur_id, service_id, date_debut, date_fin, statut, prix, notes)
SELECT 
    UUID(),
    s.tuteur_id,
    s.id,
    DATE_ADD(CURDATE(), INTERVAL 7 DAY) + INTERVAL 15 HOUR,
    DATE_ADD(CURDATE(), INTERVAL 7 DAY) + INTERVAL 16 HOUR,
    'DISPONIBLE',
    s.prix,
    NULL
FROM services s
WHERE s.categorie = 'Général' AND s.actif = TRUE
LIMIT 1;

