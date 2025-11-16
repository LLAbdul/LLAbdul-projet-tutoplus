# TutoPlus - Système de Tutorat

Système de tutorat pour le Collège Ahuntsic permettant aux étudiants de découvrir les services de tutorat, consulter les créneaux horaires disponibles et réserver des séances avec des tuteurs.

## Équipe

**Chef d'équipe :** Abdul Rahman Zahid  
**Développeur :** Adel Tamani  
**Testeur :** Diane Devi

---

## Structure du Projet

```
LLAbdul-projet-tutoplus/
├── assets/
│   ├── css/
│   │   ├── style.css                    # Styles CSS principaux
│   │   ├── login.css                    # Styles pour la page de connexion
│   │   ├── creneaux-modal.css           # Styles pour le modal des créneaux
│   │   ├── confirmation-modal.css       # Styles pour le modal de confirmation
│   │   ├── gestion-disponibilites.css   # Styles pour la gestion des disponibilités
│   │   ├── admin.css                    # Styles pour les pages d'administration
│   │   └── statistiques.css             # Styles pour la page de statistiques
│   └── js/
│       ├── script.js                    # Scripts JavaScript principaux
│       ├── login.js                     # Scripts pour la connexion
│       ├── creneaux-modal.js            # Scripts pour le modal des créneaux
│       ├── confirmation-modal.js        # Scripts pour le modal de confirmation
│       ├── gestion-disponibilites.js    # Scripts pour la gestion des disponibilités
│       ├── gestion-demandes.js          # Scripts pour la gestion des demandes
│       ├── admin.js                     # Scripts pour les pages d'administration
│       ├── statistiques.js              # Scripts pour la page de statistiques
│       ├── historique.js                # Scripts pour l'historique des séances
│       └── user-dropdown-menu.js        # Scripts pour le menu déroulant utilisateur
├── api/
│   ├── creneaux.php                     # API pour récupérer les créneaux disponibles
│   ├── disponibilites.php               # API pour gérer les disponibilités (CRUD)
│   ├── reservations.php                 # API pour créer des réservations
│   ├── demandes.php                     # API pour gérer les demandes de rendez-vous
│   ├── rendez-vous.php                  # API pour gérer les rendez-vous confirmés
│   ├── admin.php                        # API pour les actions d'administration
│   ├── statistiques.php                 # API pour récupérer les statistiques
│   ├── tuteurs.php                      # API pour gérer les tuteurs
│   └── messages.php                     # API pour gérer les messages de contact
├── config/
│   └── database.php                     # Configuration de la base de données
├── database/
│   └── schema.sql                       # Script SQL pour créer les tables et données de test
├── models/
│   ├── Service.php                      # Modèle pour gérer les services
│   ├── Tuteur.php                       # Modèle pour gérer les tuteurs
│   ├── Etudiant.php                     # Modèle pour gérer les étudiants
│   ├── Administrateur.php               # Modèle pour gérer les administrateurs
│   ├── Disponibilite.php               # Modèle pour gérer les disponibilités
│   ├── Demande.php                      # Modèle pour gérer les demandes de rendez-vous
│   ├── RendezVous.php                   # Modèle pour gérer les rendez-vous confirmés
│   ├── Statistiques.php                 # Modèle pour récupérer les statistiques
│   ├── MessageContact.php               # Modèle pour gérer les messages de contact
│   └── UtilisateurTrait.php             # Trait PHP pour les fonctionnalités communes aux utilisateurs
├── services/
│   ├── ReservationService.php           # Service d'orchestration des réservations
│   └── MessagingService.php             # Service pour la gestion des messages
├── UML/                                 # Diagrammes UML
│   ├── TutoPlus_diagramme_cas_d'utilisation.png
│   └── TutoPlus_diagramme_de_classes.png
├── index.php                            # Page d'accueil - Liste des services
├── login.php                            # Page de connexion (étudiant/tuteur/admin)
├── login_process.php                    # Traitement de la connexion étudiant
├── login_tuteur_process.php             # Traitement de la connexion tuteur
├── login_admin_process.php              # Traitement de la connexion administrateur
├── logout.php                           # Déconnexion
├── gestion_disponibilites.php           # Page de gestion des disponibilités (tuteurs)
├── gestion_demandes.php                 # Page de gestion des demandes (tuteurs)
├── historique.php                       # Page d'historique des séances (étudiants)
├── admin.php                            # Page d'administration (administrateurs)
├── statistiques.php                     # Page de statistiques (administrateurs)
├── diagramUML.puml                      # Diagramme UML PlantUML
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

### 5. Notes importantes

- **CDN et Tracking Prevention** : Le projet utilise des CDN pour Chart.js et FullCalendar. Certains navigateurs peuvent afficher des avertissements "Tracking Prevention blocked access to storage" dans la console. Ces avertissements sont normaux et n'affectent pas le fonctionnement de l'application.

- **Connexion Internet** : Les bibliothèques externes (Chart.js, FullCalendar) sont chargées via CDN. Une connexion Internet est nécessaire pour que ces fonctionnalités fonctionnent correctement.

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

#### Historique des Séances

- Consultation de l'historique des séances réservées
- Affichage des rendez-vous passés et à venir
- Informations détaillées : date, heure, durée, tuteur, service, statut
- Durée calculée automatiquement à partir des disponibilités
- Filtrage par statut (A_VENIR, EN_COURS, TERMINE, ANNULE)

### Pour les Tuteurs

#### US-007 : Gestion des Disponibilités

- Calendrier interactif (FullCalendar 6.x)
- Création de disponibilités (date, heure, statut, notes)
- Modification de disponibilités (drag & drop, resize)
- Suppression de disponibilités (sauf si réservées)
- Statuts disponibles : DISPONIBLE, RESERVE, BLOQUE
- Localisation française intégrée
- Règles métier :
  - Durée minimum de 30 minutes
  - Impossible de supprimer un créneau réservé
  - Association automatique avec le service par défaut du tuteur

#### Gestion des Demandes

- Consultation des demandes de rendez-vous reçues
- Affichage du statut du rendez-vous associé (si créé)
- Acceptation ou refus des demandes
- Visualisation des informations de l'étudiant et du service demandé
- Filtrage par statut (EN_ATTENTE, ACCEPTEE, REFUSEE, EXPIRED)

### Pour les Administrateurs

#### Gestion des Comptes

- Consultation de tous les comptes (étudiants et tuteurs)
- Modification des informations des comptes
- Activation/désactivation des comptes
- Recherche et filtrage des comptes
- Interface de gestion complète avec modals

#### Gestion des Rendez-vous

- Consultation de tous les rendez-vous du système
- Annulation de rendez-vous avec raison
- Finalisation de rendez-vous (changement de statut)
- Filtrage par statut, date, tuteur, étudiant
- Mise à jour automatique des statuts (A_VENIR → EN_COURS → TERMINE)

#### US-011 : Statistiques

- Consultation de statistiques générales :
  - Nombre total de rendez-vous
  - Nombre de tuteurs actifs
  - Nombre d'étudiants actifs
  - Nombre de rendez-vous terminés
- Graphiques interactifs avec Chart.js :
  - Graphique en barres
  - Graphique en lignes
  - Graphique en camembert
  - Graphique en donut
- Statistiques détaillées :
  - Rendez-vous par statut
  - Demandes par statut
  - Utilisateurs par statut
  - Top tuteurs
  - Rendez-vous par département
  - Services par catégorie

### Authentification

- Connexion simulée pour étudiants (numéro d'étudiant)
- Connexion simulée pour tuteurs (numéro d'employé)
- Connexion simulée pour administrateurs (identifiant admin)
- Gestion de session PHP
- Affichage du nom de l'utilisateur connecté dans le header
- Menu déroulant utilisateur avec options de navigation
- Déconnexion

### Design

#### US-009 : Harmonisation Visuelle

- Logo du Collège Ahuntsic intégré (header et footer)
- Charte graphique respectée (couleurs, typographie)
- Design moderne avec Material Design
- Responsive design (mobile, tablette, desktop)
- Animations fluides et transitions

---

## Modèle de données

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
- `duree` (INT): Durée en minutes (calculée automatiquement à partir de la disponibilité)
- `lieu` (VARCHAR(255)): Lieu du rendez-vous (optionnel)
- `notes` (TEXT): Notes sur le rendez-vous (optionnel)
- `prix` (DECIMAL(10,2)): Prix du rendez-vous
- `date_creation`

**Fonctionnalités automatiques :**

- Mise à jour automatique des statuts : `A_VENIR` → `EN_COURS` → `TERMINE` selon la date/heure actuelle
- Recalcul de la durée à partir des disponibilités pour garantir l'exactitude

#### `administrateurs`

- `id` (CHAR(36)): UUID de l'administrateur
- `identifiant` (VARCHAR(50)): Identifiant unique de l'administrateur
- `nom`, `prenom`, `email`
- `actif`, `date_creation`, `derniere_connexion`

#### `messages_contact`

- `id` (CHAR(36)): UUID du message
- `nom`, `email`, `sujet`, `message` (TEXT)
- `date_creation`

---

## Technologies utilisées

- **Backend :**

  - PHP 7.4+
  - MySQL/MariaDB
  - PDO pour la connexion à la base de données
  - Sessions PHP

- **Frontend :**

  - HTML5
  - CSS3 (Variables CSS, Flexbox, Grid, Animations)
  - JavaScript (ES6+)
  - Chart.js 4.4.0 (CDN) - Bibliothèque pour les graphiques
  - FullCalendar 6.1.10 (CDN) - Bibliothèque pour les calendriers interactifs

- **Architecture :**

  - Architecture MVC simplifiée
  - PHP Traits pour la réutilisation de code (UtilisateurTrait)
  - Services pour l'orchestration de la logique métier
  - API REST pour la communication frontend/backend

- **Outils :**
  - Git pour le contrôle de version
  - PlantUML pour les diagrammes UML

---

## Données de test

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

**Administrateurs :**

- Identifiant : Voir `database/schema.sql` pour les identifiants de test

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

#### Administration

- `GET /api/admin.php?resource=comptes` : Liste de tous les comptes (étudiants et tuteurs)
- `GET /api/admin.php?resource=comptes&type={etudiant|tuteur}` : Filtrer par type
- `PUT /api/admin.php` : Mettre à jour un compte
  - Body : `{ "resource": "comptes", "type": "etudiant|tuteur", "id": "...", "champs": {...} }`
- `GET /api/admin.php?resource=rendez-vous` : Liste de tous les rendez-vous
- `PUT /api/admin.php` : Gérer un rendez-vous
  - Body : `{ "resource": "rendez-vous", "id": "...", "action": "annuler|terminer", "raison": "..." }`

#### Statistiques

- `GET /api/statistiques.php` : Récupérer toutes les statistiques (administrateurs uniquement)
  - Retourne : statistiques générales, rendez-vous par statut, demandes par statut, utilisateurs par statut, top tuteurs, rendez-vous par département, services par catégorie

#### Tuteurs

- `GET /api/tuteurs.php` : Liste de tous les tuteurs
- `GET /api/tuteurs.php?id={id}` : Détails d'un tuteur

#### Messages

- `GET /api/messages.php` : Liste des messages de contact
- `POST /api/messages.php` : Créer un message de contact

---

## Contact

Pour toute question ou suggestion, contactez l'équipe de développement par discord : llabdul, adeltamani, dianee08.
