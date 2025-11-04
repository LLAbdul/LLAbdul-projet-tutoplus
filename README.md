# TutoPlus - Système de Tutorat

Système de tutorat pour l'école permettant aux étudiants de réserver des séances de tutorat avec des tuteurs.

## Structure du Projet

```
LLAbdul-projet-tutoplus/
├── assets/
│   └── css/
│       └── style.css          # Styles CSS pour l'interface
├── config/
│   └── database.php           # Configuration de la base de données
├── database/
│   └── schema.sql             # Script SQL pour créer les tables
├── models/
│   └── Service.php            # Modèle pour gérer les services
├── index.php                  # Page d'accueil - Liste des services
├── UML/                       # Diagrammes UML
└── README.md                  # Documentation
```

## Installation

### 1. Base de données

1. Créer une base de données MySQL via phpMyAdmin
2. Exécuter le script `database/schema.sql` pour créer les tables et insérer les données de test

### 2. Configuration

Modifier les paramètres de connexion dans `config/database.php` :
- `DB_HOST`: hôte de la base de données (généralement 'localhost')
- `DB_NAME`: nom de la base de données ('tutoplus')
- `DB_USER`: nom d'utilisateur MySQL
- `DB_PASS`: mot de passe MySQL

### 3. Accès

Ouvrir `index.php` dans votre navigateur pour voir la page d'accueil avec la liste des services.

## Fonctionnalités

### Page d'accueil
- Affichage de la liste des services offerts
- Chaque service affiche :
  - Nom
  - Description
  - Catégorie
  - Durée (en minutes)
  - Prix

## Technologies utilisées

- PHP 7.4+
- MySQL/MariaDB
- HTML5
- CSS3
- PDO pour la connexion à la base de données

## Modèle de données

### Table: services
- `id` (CHAR(36)): UUID du service
- `nom` (VARCHAR(255)): Nom du service
- `description` (TEXT): Description détaillée
- `categorie` (VARCHAR(100)): Catégorie du service
- `duree_minute` (INT): Durée en minutes
- `prix` (DECIMAL(10,2)): Prix du service
- `actif` (BOOLEAN): Indique si le service est actif
- `date_creation` (DATETIME): Date de création
- `date_modification` (DATETIME): Date de modification