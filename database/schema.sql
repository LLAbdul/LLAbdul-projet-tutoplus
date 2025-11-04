-- Base de données TutoPlus
-- Script SQL pour créer la table des services

CREATE DATABASE IF NOT EXISTS tutoplus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tutoplus;

-- Table des services
CREATE TABLE IF NOT EXISTS services (
    id CHAR(36) PRIMARY KEY COMMENT 'UUID du service',
    nom VARCHAR(255) NOT NULL COMMENT 'Nom du service',
    description TEXT NOT NULL COMMENT 'Description détaillée du service',
    categorie VARCHAR(100) NOT NULL COMMENT 'Catégorie du service (ex: Mathématiques, Sciences, etc.)',
    duree_minute INT NOT NULL DEFAULT 60 COMMENT 'Durée en minutes',
    prix DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Prix du service',
    actif BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Indique si le service est actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des services de tutorat';

-- Insertion de données de test
INSERT INTO services (id, nom, description, categorie, duree_minute, prix, actif) VALUES
(UUID(), 'Tutorat en Mathématiques', 'Soutien personnalisé en mathématiques pour tous les niveaux. Aide à la compréhension des concepts, résolution de problèmes et préparation aux examens.', 'Mathématiques', 60, 25.00, TRUE),
(UUID(), 'Tutorat en Sciences', 'Accompagnement en sciences (physique, chimie, biologie). Explications détaillées et exercices pratiques.', 'Sciences', 60, 25.00, TRUE),
(UUID(), 'Tutorat en Programmation', 'Aide en programmation (Java, Python, JavaScript, etc.). Débogage, explication de concepts et projets pratiques.', 'Informatique', 90, 30.00, TRUE),
(UUID(), 'Tutorat en Français', 'Soutien en français : grammaire, orthographe, rédaction et compréhension de textes.', 'Langues', 60, 20.00, TRUE),
(UUID(), 'Tutorat en Anglais', 'Amélioration de l''anglais : conversation, grammaire, vocabulaire et préparation aux examens.', 'Langues', 60, 20.00, TRUE),
(UUID(), 'Aide aux devoirs', 'Support général pour les devoirs et les études. Organisation et méthodologie.', 'Général', 60, 22.00, TRUE);

