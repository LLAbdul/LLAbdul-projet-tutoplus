# TutoPlus - Système de Tutorat

Système de tutorat pour le Collège Ahuntsic permettant aux étudiants de découvrir les services de tutorat, consulter les créneaux horaires disponibles et réserver des séances avec des tuteurs.

## 👥 Équipe

**Chef d'équipe :** Abdul Rahman Zahid  
**Développeur :** Adel Tamani  
**Testeur :** Diane Devi

---

## 📋 Structure du Projet

```
LLAbdul-projet-tutoplus/
├── assets/
│   ├── css/
│   │   ├── style.css                    # Styles CSS principaux
│   │   ├── login.css                    # Styles pour la page de connexion
│   │   ├── creneaux-modal.css           # Styles pour le modal des créneaux
│   │   ├── confirmation-modal.css       # Styles pour le modal de confirmation
│   │   └── gestion-disponibilites.css   # Styles pour la gestion des disponibilités
│   └── js/
│       ├── script.js                    # Scripts JavaScript principaux
│       ├── login.js                     # Scripts pour la connexion
│       ├── creneaux-modal.js            # Scripts pour le modal des créneaux
│       ├── confirmation-modal.js        # Scripts pour le modal de confirmation
│       └── gestion-disponibilites.js    # Scripts pour la gestion des disponibilités
├── api/
│   ├── creneaux.php                     # API pour récupérer les créneaux disponibles
│   ├── disponibilites.php               # API pour gérer les disponibilités (CRUD)
│   ├── reservations.php                 # API pour créer des réservations
│   ├── demandes.php                     # API pour gérer les demandes de rendez-vous
│   └── rendez-vous.php                  # API pour gérer les rendez-vous confirmés
├── config/
│   └── database.php                     # Configuration de la base de données
├── database/
│   └── schema.sql                       # Script SQL pour créer les tables et données de test
├── models/
│   ├── Service.php                      # Modèle pour gérer les services
│   ├── Tuteur.php                       # Modèle pour gérer les tuteurs
│   ├── Etudiant.php                     # Modèle pour gérer les étudiants
│   ├── Disponibilite.php               # Modèle pour gérer les disponibilités
│   ├── Demande.php                      # Modèle pour gérer les demandes de rendez-vous
│   └── RendezVous.php                   # Modèle pour gérer les rendez-vous confirmés
├── services/
│   └── ReservationService.php           # Service d'orchestration des réservations
├── UML/                                 # Diagrammes UML
│   ├── TutoPlus_diagramme_cas_d'utilisation.png
│   └── TutoPlus_diagramme_de_classes.png
├── index.php                            # Page d'accueil - Liste des services
├── login.php                            # Page de connexion (étudiant/tuteur)
├── login_process.php                    # Traitement de la connexion étudiant
├── login_tuteur_process.php             # Traitement de la connexion tuteur
├── logout.php                           # Déconnexion
├── gestion_disponibilites.php           # Page de gestion des disponibilités (tuteurs)
└── README.md                            # Documentation
```

---

## Installation

### 1. Prérequis

- PHP 7.4+
- MySQL/MariaDB 5.7+
- Serveur web (Apache/Nginx) ou XAMPP/WAMP/MAMP
- Navigateur web moderne

### 2. Base de données

1. Créer une base de données MySQL via phpMyAdmin ou ligne de commande
2. Exécuter le script `database/schema.sql` pour créer les tables et insérer les données de test :
   ```sql
   mysql -u root -p < database/schema.sql
   ```
   Ou via phpMyAdmin : importer le fichier `database/schema.sql`

### 3. Configuration

Modifier les paramètres de connexion dans `config/database.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tutoplus');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 4. Accès

Ouvrir `index.php` dans votre navigateur :

- Local : `http://localhost/LLAbdul-projet-tutoplus/`
- Ou via votre serveur web configuré

---

## Fonctionnalités

### Pour les Étudiants

#### US-001 : Découverte du Service

- Affichage de la liste des services offerts par catégorie
- Filtrage par catégorie (onglets)
- Informations détaillées pour chaque service :
  - Nom et description
  - Catégorie
  - Durée (en minutes)
  - Prix
  - Tuteur associé
  - Département

#### US-002 : Demande de Rendez-vous

- Consultation des créneaux horaires disponibles pour chaque service
- Affichage dans un modal avec calendrier
- Groupement des créneaux par date
- Réservation de créneaux disponibles
- Option de notification (activation pour rappel 1 jour avant)
- Connexion simulée (sans validation Omnivox réelle)
- Processus de réservation : Création d'une Demande → Confirmation → Création d'un RendezVous

#### US-003 : Confirmation de Rendez-vous

- Message de confirmation affiché après réservation réussie
- Affichage des informations essentielles :
  - Date et heure du rendez-vous
  - Nom du tuteur
  - Service réservé
  - Option de notification (si activée)
- Modal de confirmation avec design moderne
- Message visible pendant au moins 5 secondes
- Fermeture automatique ou manuelle

### Pour les Tuteurs

#### US-007 : Gestion des Disponibilités

- Calendrier interactif (FullCalendar)
- Création de disponibilités (date, heure, statut, notes)
- Modification de disponibilités (drag & drop, resize)
- Suppression de disponibilités (sauf si réservées)
- Statuts disponibles : DISPONIBLE, RESERVE, BLOQUE
- Règles métier :
  - Durée minimum de 30 minutes
  - Impossible de supprimer un créneau réservé
  - Association automatique avec le service par défaut du tuteur

### Authentification

- Connexion simulée pour étudiants (numéro d'étudiant)
- Connexion simulée pour tuteurs (numéro d'employé)
- Gestion de session PHP
- Affichage du nom de l'utilisateur connecté dans le header
- Déconnexion

### Design

#### US-009 : Harmonisation Visuelle

- Logo du Collège Ahuntsic intégré (header et footer)
- Charte graphique respectée (couleurs, typographie)
- Design moderne avec Material Design
- Responsive design (mobile, tablette, desktop)
- Animations fluides et transitions

---

## 🗄️ Modèle de données

### Tables principales

#### `tuteurs`

- `id` (CHAR(36)): UUID du tuteur
- `numero_employe` (VARCHAR(50)): Numéro d'employé unique
- `nom`, `prenom`, `email`, `telephone`
- `departement`, `specialites`
- `tarif_horaire`, `evaluation`, `nb_seances`
- `actif`, `date_creation`, `derniere_connexion`

#### `services`

- `id` (CHAR(36)): UUID du service
- `tuteur_id` (CHAR(36)): UUID du tuteur associé (FK)
- `nom`, `description`, `categorie`
- `duree_minute`, `prix`
- `actif`, `date_creation`, `date_modification`

#### `etudiants`

- `id` (CHAR(36)): UUID de l'étudiant
- `numero_etudiant` (VARCHAR(50)): Numéro d'étudiant unique
- `nom`, `prenom`, `email`, `telephone`
- `niveau`, `specialite`, `annee_etude`
- `actif`, `date_creation`, `derniere_connexion`

#### `disponibilites`

- `id` (CHAR(36)): UUID de la disponibilité
- `tuteur_id` (CHAR(36)): UUID du tuteur propriétaire (FK)
- `service_id` (CHAR(36)): UUID du service associé (FK, optionnel)
- `date_debut`, `date_fin` (DATETIME)
- `statut` (ENUM): DISPONIBLE, RESERVE, BLOQUE
- `etudiant_id` (CHAR(36)): UUID de l'étudiant qui a réservé (FK, NULL si non réservé)
- `prix` (DECIMAL(10,2)): Prix spécifique (optionnel)
- `notes` (TEXT): Notes additionnelles
- `date_creation`, `date_modification`

**Contraintes :**

- Durée minimum : 30 minutes
- `date_fin` > `date_debut`
- Impossible de supprimer un créneau avec statut RESERVE

#### `demandes`

- `id` (CHAR(36)): UUID de la demande
- `etudiant_id` (CHAR(36)): UUID de l'étudiant demandeur (FK)
- `service_id` (CHAR(36)): UUID du service demandé (FK)
- `tuteur_id` (CHAR(36)): UUID du tuteur (FK)
- `disponibilite_id` (CHAR(36)): UUID de la disponibilité associée (FK, optionnel)
- `date_heure_demande` (DATETIME): Date et heure de la demande
- `statut` (ENUM): EN_ATTENTE, ACCEPTEE, REFUSEE, EXPIRED
- `motif` (TEXT): Motif de la demande (optionnel)
- `priorite` (VARCHAR(50)): Priorité de la demande (optionnel)
- `date_creation`, `date_modification`

#### `rendez_vous`

- `id` (CHAR(36)): UUID du rendez-vous
- `demande_id` (CHAR(36)): UUID de la demande associée (FK, optionnel)
- `etudiant_id` (CHAR(36)): UUID de l'étudiant (FK)
- `tuteur_id` (CHAR(36)): UUID du tuteur (FK)
- `service_id` (CHAR(36)): UUID du service (FK)
- `disponibilite_id` (CHAR(36)): UUID de la disponibilité réservée (FK)
- `date_heure` (DATETIME): Date et heure du rendez-vous
- `statut` (ENUM): A_VENIR, EN_COURS, TERMINE, ANNULE, REPORTE
- `duree` (INT): Durée en minutes
- `lieu` (VARCHAR(255)): Lieu du rendez-vous (optionnel)
- `notes` (TEXT): Notes sur le rendez-vous (optionnel)
- `prix` (DECIMAL(10,2)): Prix du rendez-vous
- `date_creation`

---

## 🛠️ Technologies utilisées

- **Backend :**

  - PHP 7.4+
  - MySQL/MariaDB
  - PDO pour la connexion à la base de données
  - Sessions PHP

- **Frontend :**

  - HTML5
  - CSS3 (Variables CSS, Flexbox, Grid, Animations)
  - JavaScript (ES6+)
  - FullCalendar 5.11.5 (CDN)

- **Outils :**
  - Git pour le contrôle de version
  - PlantUML pour les diagrammes UML

---

## 📝 Données de test

Le script `database/schema.sql` inclut des données de test :

- **6 tuteurs** (T001 à T006) dans différents départements
- **5 étudiants** (E001 à E005)
- **6 services** (Mathématiques, Sciences, Informatique, Français, Anglais, Aide aux devoirs)
- **Plusieurs disponibilités** pour les prochains jours

### Connexion de test

**Étudiants :**

- Numéro : `E001` à `E005`

**Tuteurs :**

- Numéro : `T001` à `T006`

---

## Développement

### Structure MVC

Le projet suit une architecture MVC simplifiée :

- **Models** (`models/`) : Classes PHP pour interagir avec la base de données
- **Views** (fichiers `.php`) : Templates HTML avec logique PHP minimale
- **Controllers** (fichiers `.php` et `api/`) : Logique métier et traitement des requêtes

### API REST

Les endpoints API suivent les conventions REST :

#### Disponibilités et Créneaux

- `GET /api/creneaux.php?service_id={id}` : Récupérer les créneaux disponibles pour un service
- `GET /api/disponibilites.php` : Récupérer les disponibilités du tuteur connecté
- `POST /api/disponibilites.php` : Créer une disponibilité
- `PUT /api/disponibilites.php` : Modifier une disponibilité
- `DELETE /api/disponibilites.php` : Supprimer une disponibilité

#### Réservations

- `POST /api/reservations.php` : Créer une réservation (étudiants)
  - Processus : Création d'une Demande → Confirmation automatique → Création d'un RendezVous
  - Body : `{ "disponibilite_id": "...", "motif": "...", "priorite": "..." }`

#### Demandes

- `GET /api/demandes.php` : Liste des demandes de l'étudiant/tuteur connecté
- `GET /api/demandes.php?id={id}` : Détails d'une demande
- `POST /api/demandes.php` : Créer une demande (étudiants uniquement)
- `PUT /api/demandes.php` : Mettre à jour une demande
  - Tuteurs : accepter/refuser (`{ "id": "...", "action": "accepter|refuser", "raison": "..." }`)
  - Étudiants : modifier si EN_ATTENTE (`{ "id": "...", "motif": "...", "priorite": "..." }`)

#### Rendez-vous

- `GET /api/rendez-vous.php` : Liste des rendez-vous de l'étudiant/tuteur connecté
- `GET /api/rendez-vous.php?id={id}` : Détails d'un rendez-vous
- `GET /api/rendez-vous.php?statut={statut}` : Filtrer par statut
- `GET /api/rendez-vous.php?date={date}` : Filtrer par date (format YYYY-MM-DD)
- `PUT /api/rendez-vous.php` : Mettre à jour un rendez-vous
  - Actions : `confirmer`, `annuler`, `reporter`, `terminer`
  - Body : `{ "id": "...", "action": "...", "nouvelle_date": "..." (si reporter), "raison": "..." (si annuler) }`

---

## Contact

Pour toute question ou suggestion, contactez l'équipe de développement par discord : llabdul, adeltamani, dianee08.
