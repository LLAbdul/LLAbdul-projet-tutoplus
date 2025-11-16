<?php
declare(strict_types=1);

/**
 * API admin.php
 * - GET
 *      - ?resource=comptes (défaut) : liste des comptes (étudiants + tuteurs)
 *      - ?resource=rendez-vous     : liste de tous les rendez-vous
 *      - ?resource=services&tuteur_id=... : services d’un tuteur
 * - POST   : création d'un compte (étudiant ou tuteur)
 * - PUT    :
 *      - compte (activer/désactiver ou modification complète)
 *      - service (resource=service)
 * - DELETE :
 *      - rendez-vous (resource=rendez-vous, action=annuler)
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

session_start();

require_once '../config/database.php';
require_once '../models/Etudiant.php';
require_once '../models/Tuteur.php';
require_once '../models/RendezVous.php';
require_once '../models/Service.php';

header('Content-Type: application/json; charset=utf-8');

/* === Helpers JSON === */

function json_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function json_error(int $status, string $message): void
{
    json_response($status, ['error' => $message]);
}

function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        json_error(400, 'Format JSON invalide dans la requête');
    }

    return $data;
}

/* === Vérification de l’authentification admin === */

if (!isset($_SESSION['admin_id'])) {
    json_error(401, 'Non autorisé - Administrateur non connecté');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $pdo          = getDBConnection();
    $etudiantModel = new Etudiant($pdo);
    $tuteurModel   = new Tuteur($pdo);

    switch ($method) {
        /* === GET === */
        case 'GET': {
            $resource = $_GET['resource'] ?? 'comptes';

            // 1) Liste des rendez-vous
            if ($resource === 'rendez-vous') {
                $rendezVousModel = new RendezVous($pdo);
                $rendezVous      = $rendezVousModel->getAllRendezVous();
                json_response(200, $rendezVous);
            }

            // 2) Services d’un tuteur
            if ($resource === 'services') {
                $tuteurId = $_GET['tuteur_id'] ?? null;
                if (!$tuteurId) {
                    json_error(400, 'tuteur_id est requis');
                }

                $serviceModel = new Service($pdo);
                $services     = $serviceModel->getServicesByTuteurId($tuteurId);

                json_response(200, $services);
            }

            // 3) Comptes (par défaut)
            $id   = $_GET['id']   ?? null;
            $type = $_GET['type'] ?? null; // 'etudiant' ou 'tuteur'

            // 3.a) Un compte en particulier
            if ($id !== null && $id !== '') {
                if ($type === 'etudiant') {
                    $compte = $etudiantModel->getEtudiantByIdForAdmin($id);
                } elseif ($type === 'tuteur') {
                    $compte = $tuteurModel->getTuteurByIdForAdmin($id);
                } else {
                    json_error(400, 'Type de compte requis (etudiant ou tuteur)');
                }

                if (!$compte) {
                    json_error(404, 'Compte non trouvé');
                }

                $compte['type'] = $type;
                json_response(200, $compte);
            }

            // 3.b) Tous les comptes
            $comptes = [];

            $etudiants = $etudiantModel->getAllEtudiants();
            foreach ($etudiants as $etudiant) {
                $etudiant['type'] = 'etudiant';
                $comptes[]        = $etudiant;
            }

            $tuteurs = $tuteurModel->getAllTuteurs();
            foreach ($tuteurs as $tuteur) {
                $tuteur['type'] = 'tuteur';
                $comptes[]      = $tuteur;
            }

            json_response(200, $comptes);
        }

        /* === POST : création compte === */
        case 'POST': {
            $data = get_json_body();

            if (
                !isset($data['type'])
                || !in_array($data['type'], ['etudiant', 'tuteur'], true)
            ) {
                json_error(400, 'type est requis et doit être "etudiant" ou "tuteur"');
            }

            $compteType = $data['type'];

            /* --- Création étudiant --- */
            if ($compteType === 'etudiant') {
                if (empty(trim($data['numero_etudiant'] ?? ''))) {
                    json_error(400, 'numero_etudiant est requis');
                }
                if (empty(trim($data['nom'] ?? ''))) {
                    json_error(400, 'nom est requis');
                }
                if (empty(trim($data['prenom'] ?? ''))) {
                    json_error(400, 'prenom est requis');
                }
                if (empty(trim($data['email'] ?? ''))) {
                    json_error(400, 'email est requis');
                }

                $id = $etudiantModel->creerEtudiant(
                    trim($data['numero_etudiant']),
                    trim($data['nom']),
                    trim($data['prenom']),
                    trim($data['email']),
                    isset($data['telephone'])  ? trim((string)$data['telephone'])  : null,
                    isset($data['niveau'])     ? trim((string)$data['niveau'])     : null,
                    isset($data['specialite']) ? trim((string)$data['specialite']) : null,
                    isset($data['annee_etude']) ? (int)$data['annee_etude']        : null,
                    isset($data['actif']) ? (bool)$data['actif'] : true
                );

                if (!$id) {
                    json_error(
                        400,
                        'Erreur lors de la création de l\'étudiant. Vérifiez que le numéro et l\'email sont uniques.'
                    );
                }

                $compte = $etudiantModel->getEtudiantByIdForAdmin($id);
            }
            /* --- Création tuteur --- */
            else {
                if (empty(trim($data['numero_employe'] ?? ''))) {
                    json_error(400, 'numero_employe est requis');
                }
                if (empty(trim($data['nom'] ?? ''))) {
                    json_error(400, 'nom est requis');
                }
                if (empty(trim($data['prenom'] ?? ''))) {
                    json_error(400, 'prenom est requis');
                }
                if (empty(trim($data['email'] ?? ''))) {
                    json_error(400, 'email est requis');
                }
                if (empty(trim($data['departement'] ?? ''))) {
                    json_error(400, 'departement est requis');
                }
                if (!isset($data['tarif_horaire'])) {
                    json_error(400, 'tarif_horaire est requis');
                }

                // Gérer l'évaluation si fournie
                $evaluation = null;
                if (isset($data['evaluation']) && $data['evaluation'] !== '') {
                    $evalValue = (float)$data['evaluation'];
                    if ($evalValue >= 0 && $evalValue <= 5) {
                        $evaluation = $evalValue;
                    }
                }

                $id = $tuteurModel->creerTuteur(
                    trim($data['numero_employe']),
                    trim($data['nom']),
                    trim($data['prenom']),
                    trim($data['email']),
                    trim($data['departement']),
                    (float)$data['tarif_horaire'],
                    isset($data['telephone'])   ? trim((string)$data['telephone'])   : null,
                    isset($data['specialites']) ? trim((string)$data['specialites']) : null,
                    isset($data['actif']) ? (bool)$data['actif'] : true,
                    $evaluation
                );

                if (!$id) {
                    json_error(
                        400,
                        'Erreur lors de la création du tuteur. Vérifiez que le numéro et l\'email sont uniques.'
                    );
                }

                $compte = $tuteurModel->getTuteurByIdForAdmin($id);
            }

            if (!$compte) {
                json_error(500, 'Erreur lors de la récupération du compte créé');
            }

            $compte['type'] = $compteType;

            json_response(201, [
                'success' => true,
                'message' => 'Compte créé avec succès',
                'compte'  => $compte,
            ]);
        }

        /* === PUT : modification compte, service ou rendez-vous === */
        case 'PUT': {
            $data = get_json_body();

            // 1) Modification de service
            if (($data['resource'] ?? null) === 'service') {
                if (empty($data['id'] ?? '')) {
                    json_error(400, 'id du service est requis');
                }

                $serviceModel = new Service($pdo);
                $serviceId    = (string)$data['id'];

                $service = $serviceModel->getServiceById($serviceId);
                if (!$service) {
                    json_error(404, 'Service non trouvé');
                }

                $description  = isset($data['description'])  ? trim((string)$data['description'])  : null;
                $nom          = isset($data['nom'])          ? trim((string)$data['nom'])          : null;
                $prix         = isset($data['prix'])         ? (float)$data['prix']                : null;
                $dureeMinute  = isset($data['duree_minute']) ? (int)$data['duree_minute']          : null;

                $success = $serviceModel->modifierService($serviceId, $description, $nom, $prix, $dureeMinute);

                if (!$success) {
                    json_error(400, 'Erreur lors de la modification du service');
                }

                $service = $serviceModel->getServiceById($serviceId);

                json_response(200, [
                    'success' => true,
                    'message' => 'Service modifié avec succès',
                    'service' => $service,
                ]);
            }

            // 2) Gestion des rendez-vous (terminer)
            if (isset($data['resource']) && $data['resource'] === 'rendez-vous') {
                $action = $data['action'] ?? null;
                $id     = $data['id']     ?? null;

                if ($action === 'terminer' && $id) {
                    $rendezVousModel = new RendezVous($pdo);
                    $success         = $rendezVousModel->terminerRendezVous($id);

                    if ($success) {
                        json_response(200, [
                            'success' => true,
                            'message' => 'Rendez-vous marqué comme terminé avec succès',
                        ]);
                    }

                    json_error(
                        400,
                        'Impossible de terminer le rendez-vous. Il doit être à venir ou en cours.'
                    );
                }

                json_error(400, 'Action invalide pour les rendez-vous');
            }

            // 3) Modification de compte
            if (empty($data['id'] ?? '')) {
                json_error(400, 'id est requis');
            }

            if (
                !isset($data['type'])
                || !in_array($data['type'], ['etudiant', 'tuteur'], true)
            ) {
                json_error(400, 'type est requis et doit être "etudiant" ou "tuteur"');
            }

            $compteId   = (string)$data['id'];
            $compteType = $data['type'];

            // Récupérer le compte existant
            if ($compteType === 'etudiant') {
                $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
                if (!$compte) {
                    json_error(404, 'Étudiant non trouvé');
                }
            } else {
                $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
                if (!$compte) {
                    json_error(404, 'Tuteur non trouvé');
                }
            }

            // Cas simple : on ne met à jour que "actif"
            $hasFullUpdate = isset($data['nom']) ||
                             isset($data['numero_etudiant']) ||
                             isset($data['numero_employe']);

            if (isset($data['actif']) && !$hasFullUpdate) {
                $actif = (bool)$data['actif'];

                if ($compteType === 'etudiant') {
                    $success = $etudiantModel->updateActif($compteId, $actif);
                } else {
                    $success = $tuteurModel->updateActif($compteId, $actif);
                }

                if (!$success) {
                    json_error(500, 'Erreur lors de la mise à jour du statut');
                }

                if ($compteType === 'etudiant') {
                    $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
                } else {
                    $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
                }

                if (!$compte) {
                    json_error(500, 'Erreur lors de la récupération du compte mis à jour');
                }

                $compte['type'] = $compteType;

                json_response(200, [
                    'success' => true,
                    'message' => 'Statut mis à jour avec succès',
                    'compte'  => $compte,
                ]);
            }

            // Cas : modification complète
            if ($compteType === 'etudiant') {
                if (empty(trim($data['numero_etudiant'] ?? ''))) {
                    json_error(400, 'numero_etudiant est requis');
                }
                if (empty(trim($data['nom'] ?? ''))) {
                    json_error(400, 'nom est requis');
                }
                if (empty(trim($data['prenom'] ?? ''))) {
                    json_error(400, 'prenom est requis');
                }
                if (empty(trim($data['email'] ?? ''))) {
                    json_error(400, 'email est requis');
                }

                $success = $etudiantModel->modifierEtudiant(
                    $compteId,
                    trim($data['numero_etudiant']),
                    trim($data['nom']),
                    trim($data['prenom']),
                    trim($data['email']),
                    isset($data['telephone'])  ? trim((string)$data['telephone'])  : null,
                    isset($data['niveau'])     ? trim((string)$data['niveau'])     : null,
                    isset($data['specialite']) ? trim((string)$data['specialite']) : null,
                    isset($data['annee_etude']) ? (int)$data['annee_etude']        : null,
                    isset($data['actif']) ? (bool)$data['actif'] : true
                );
            } else {
                if (empty(trim($data['numero_employe'] ?? ''))) {
                    json_error(400, 'numero_employe est requis');
                }
                if (empty(trim($data['nom'] ?? ''))) {
                    json_error(400, 'nom est requis');
                }
                if (empty(trim($data['prenom'] ?? ''))) {
                    json_error(400, 'prenom est requis');
                }
                if (empty(trim($data['email'] ?? ''))) {
                    json_error(400, 'email est requis');
                }
                if (empty(trim($data['departement'] ?? ''))) {
                    json_error(400, 'departement est requis');
                }
                if (!isset($data['tarif_horaire'])) {
                    json_error(400, 'tarif_horaire est requis');
                }

                $evaluation = null;
                if (isset($data['evaluation']) && $data['evaluation'] !== '') {
                    $evalValue = (float)$data['evaluation'];
                    if ($evalValue >= 0 && $evalValue <= 5) {
                        $evaluation = $evalValue;
                    }
                }

                $success = $tuteurModel->modifierTuteur(
                    $compteId,
                    trim($data['numero_employe']),
                    trim($data['nom']),
                    trim($data['prenom']),
                    trim($data['email']),
                    trim($data['departement']),
                    (float)$data['tarif_horaire'],
                    isset($data['telephone'])   ? trim((string)$data['telephone'])   : null,
                    isset($data['specialites']) ? trim((string)$data['specialites']) : null,
                    isset($data['actif']) ? (bool)$data['actif'] : true,
                    $evaluation
                );
            }

            if (!$success) {
                json_error(
                    400,
                    'Erreur lors de la modification du compte. Vérifiez que le numéro et l\'email sont uniques.'
                );
            }

            if ($compteType === 'etudiant') {
                $compte = $etudiantModel->getEtudiantByIdForAdmin($compteId);
            } else {
                $compte = $tuteurModel->getTuteurByIdForAdmin($compteId);
            }

            if (!$compte) {
                json_error(500, 'Erreur lors de la récupération du compte mis à jour');
            }

            $compte['type'] = $compteType;

            json_response(200, [
                'success' => true,
                'message' => 'Compte mis à jour avec succès',
                'compte'  => $compte,
            ]);
        }

        /* === DELETE : annulation rendez-vous === */
        case 'DELETE': {
            $data    = get_json_body();
            $resource = $data['resource'] ?? null;
            $action   = $data['action']   ?? null;
            $id       = $data['id']       ?? null;

            if ($resource === 'rendez-vous' && $action === 'annuler' && $id) {
                $rendezVousModel = new RendezVous($pdo);
                $raison          = $data['raison'] ?? null;

                $success = $rendezVousModel->annulerRendezVous($id, $raison);

                if ($success) {
                    json_response(200, [
                        'success' => true,
                        'message' => 'Rendez-vous annulé avec succès',
                    ]);
                }

                json_error(
                    400,
                    'Impossible d\'annuler le rendez-vous. Il est peut-être déjà annulé ou terminé.'
                );
            }

            json_error(400, 'Paramètres invalides pour l\'annulation');
        }

        /* === Méthode non supportée === */
        default:
            json_error(405, 'Méthode non autorisée');
    }

} catch (PDOException $e) {
    error_log("Erreur PDO API admin.php: " . $e->getMessage());
    json_error(500, 'Erreur base de données');
} catch (Exception $e) {
    error_log("Erreur API admin.php: " . $e->getMessage());
    json_error(500, 'Erreur serveur');
} catch (Error $e) {
    error_log("Erreur fatale API admin.php: " . $e->getMessage());
    json_error(500, 'Erreur serveur');
}
