<?php
declare(strict_types=1);

/**
 * Service ReservationService - Orchestration des opérations de réservation
 * Utilise les modèles Demande, RendezVous, Disponibilite et Service
 */

require_once __DIR__ . '/../models/Demande.php';
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../models/Disponibilite.php';
require_once __DIR__ . '/../models/Service.php';

class ReservationService
{
    private $pdo;
    private $demandeModel;
    private $rendezVousModel;
    private $disponibiliteModel;
    private $serviceModel;

    // Constantes pour éviter les strings magiques
    public const STATUT_DEMANDE_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_DISPO_RESERVE      = 'RESERVE';
    public const STATUT_DISPO_DISPONIBLE   = 'DISPONIBLE';

    // Constructeur : reçoit l'instance PDO
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->demandeModel = new Demande($pdo);
        $this->rendezVousModel = new RendezVous($pdo);
        $this->disponibiliteModel = new Disponibilite($pdo);
        $this->serviceModel = new Service($pdo);
    }

    // Crée une demande de rendez-vous
    // Paramètres : id étudiant, id service, id tuteur, id disponibilité (optionnel), motif (optionnel), priorité (optionnelle)
    // Retourne : id de la demande créée ou false en cas d'erreur
    public function creerDemande($etudiantId, $serviceId, $tuteurId, $disponibiliteId = null, $motif = null, $priorite = null)
    {
        try {
            // Vérifier que la disponibilité existe et est disponible si fournie
            if ($disponibiliteId !== null) {
                if (!$this->verifierDisponibilite($disponibiliteId)) {
                    $this->logError("La disponibilité n'est pas disponible (ID: $disponibiliteId)");
                    return false;
                }
            }

            $result = $this->demandeModel->creerDemande(
                $etudiantId,
                $serviceId,
                $tuteurId,
                $disponibiliteId,
                $motif,
                $priorite
            );

            if (!$result) {
                $this->logError("creerDemande a retourné false - Étudiant: $etudiantId, Service: $serviceId, Tuteur: $tuteurId");
            }

            return $result;
        } catch (Exception $e) {
            $this->logError('creerDemande', $e);
            return false;
        }
    }

    // Confirme une demande et crée un rendez-vous
    // Paramètre : id de la demande
    // Retourne : id du rendez-vous créé ou false en cas d'erreur
    public function confirmerDemande($demandeId)
    {
        try {
            // Récupérer la demande
            $demande = $this->demandeModel->getDemandeById($demandeId);
            if (!$demande) {
                $this->logError("La demande n'existe pas (ID: $demandeId)");
                return false;
            }

            // Vérifier que la demande est en attente
            if (($demande['statut'] ?? null) !== self::STATUT_DEMANDE_EN_ATTENTE) {
                $statut = $demande['statut'] ?? 'N/A';
                $this->logError("La demande n'est pas en attente (statut: $statut)");
                return false;
            }

            // Vérifier qu'il n'existe pas déjà un rendez-vous pour cette demande
            // En vérifiant dans la base de données directement
            try {
                $stmt = $this->pdo->prepare("SELECT id FROM rendez_vous WHERE demande_id = :demande_id LIMIT 1");
                $stmt->bindParam(':demande_id', $demandeId, PDO::PARAM_STR);
                $stmt->execute();
                $rendezVousExistant = $stmt->fetch();
                if ($rendezVousExistant) {
                    $this->logError("Un rendez-vous existe déjà pour cette demande (ID: $demandeId)");
                    return false;
                }
            } catch (PDOException $e) {
                $this->logError("Erreur lors de la vérification du rendez-vous existant : " . $e->getMessage());
                // Continuer même en cas d'erreur de vérification
            }

            // Vérifier qu'une disponibilité est associée
            if ($demande['disponibilite_id'] === null) {
                $this->logError("Aucune disponibilité associée à la demande (ID: $demandeId)");
                return false;
            }

            $disponibiliteId = $demande['disponibilite_id'];

            // Récupérer la disponibilité
            $disponibilite = $this->disponibiliteModel->getDisponibiliteById($disponibiliteId);
            if (!$disponibilite) {
                $this->logError("Impossible de récupérer la disponibilité (ID: $disponibiliteId)");
                return false;
            }

            // Vérifier que la disponibilité est soit DISPONIBLE, soit RESERVE par le même étudiant
            // (elle peut être déjà RESERVE si elle a été réservée lors de la création de la demande)
            $statutValide = false;
            if ($disponibilite['statut'] === self::STATUT_DISPO_DISPONIBLE) {
                $statutValide = true;
            } elseif ($disponibilite['statut'] === self::STATUT_DISPO_RESERVE) {
                // Vérifier que c'est bien réservé par le même étudiant que la demande
                if ($disponibilite['etudiant_id'] === $demande['etudiant_id']) {
                    $statutValide = true;
                } else {
                    $this->logError("La disponibilité est déjà réservée par un autre étudiant (ID: $disponibiliteId)");
                    return false;
                }
            }

            if (!$statutValide) {
                $this->logError("La disponibilité n'est pas disponible (statut: " . ($disponibilite['statut'] ?? 'N/A') . ", ID: $disponibiliteId)");
                return false;
            }

            // Vérifier que la date n'est pas passée
            $dateDebut = new DateTime($disponibilite['date_debut']);
            $now = new DateTime();
            if ($dateDebut < $now) {
                $this->logError("La disponibilité est dans le passé (ID: $disponibiliteId)");
                return false;
            }

            // S'assurer que la disponibilité est bien réservée (si elle ne l'est pas déjà)
            if ($disponibilite['statut'] !== self::STATUT_DISPO_RESERVE || $disponibilite['etudiant_id'] !== $demande['etudiant_id']) {
                $reserveOk = $this->disponibiliteModel->modifierDisponibilite(
                    $disponibiliteId,
                    $disponibilite['date_debut'],
                    $disponibilite['date_fin'],
                    self::STATUT_DISPO_RESERVE,
                    null,                               // serviceId
                    null,                               // prix
                    null,                               // notes
                    $demande['etudiant_id']            // etudiant_id
                );

                if (!$reserveOk) {
                    $this->logError("Impossible de réserver la disponibilité (ID: $disponibiliteId)");
                    return false;
                }
            }

            // Accepter la demande
            if (!$this->demandeModel->accepterDemande($demandeId)) {
                $this->logError("Impossible d'accepter la demande (ID: $demandeId)");
                return false;
            }

            // Récupérer la disponibilité (après mise à jour) si nécessaire
            $disponibilite = $this->disponibiliteModel->getDisponibiliteById($disponibiliteId);
            if (!$disponibilite) {
                $this->logError("Impossible de récupérer la disponibilité après réservation (ID: $disponibiliteId)");
                return false;
            }

            // Calculer la durée réelle à partir de la disponibilité (date_fin - date_debut)
            $dateDebut = new DateTime($disponibilite['date_debut']);
            $dateFin = new DateTime($disponibilite['date_fin']);
            $diff = $dateDebut->diff($dateFin);
            // Calculer la durée totale en minutes
            $duree = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

            // Si la durée calculée est 0 ou invalide, utiliser la durée du service par défaut
            if ($duree <= 0) {
                $service = $this->serviceModel->getServiceById($demande['service_id']);
                if (!$service) {
                    $this->logError("Le service n'existe pas (ID: " . $demande['service_id'] . ')');
                    return false;
                }
                $duree = $service['duree_minute'] ?? 60;
            }

            // Récupérer les infos du service pour calculer le prix
            $service = $this->serviceModel->getServiceById($demande['service_id']);
            if (!$service) {
                $this->logError("Le service n'existe pas (ID: " . $demande['service_id'] . ')');
                return false;
            }

            $prix = $this->calculerPrix($demande['service_id'], $duree);

            // Créer le rendez-vous
            $rendezVousId = $this->rendezVousModel->creerRendezVous(
                $demandeId,
                $demande['etudiant_id'],
                $demande['tuteur_id'],
                $demande['service_id'],
                $disponibiliteId,
                $duree,
                null,                 // lieu
                $demande['motif'],    // notes
                $prix
            );

            if (!$rendezVousId) {
                $this->logError("Impossible de créer le rendez-vous (Demande: $demandeId)");
                return false;
            }

            return $rendezVousId;
        } catch (Exception $e) {
            $this->logError('confirmerDemande', $e);
            return false;
        }
    }

    // Annule une réservation (rendez-vous)
    // Paramètres : id du rendez-vous, raison (optionnelle)
    // Retourne : true si succès, false sinon
    public function annulerReservation($rendezVousId, $raison = null)
    {
        try {
            // Récupérer le rendez-vous
            $rendezVous = $this->rendezVousModel->getRendezVousById($rendezVousId);
            if (!$rendezVous) {
                $this->logError("Le rendez-vous n'existe pas (ID: $rendezVousId)");
                return false;
            }

            // Annuler le rendez-vous
            if (!$this->rendezVousModel->annulerRendezVous($rendezVousId, $raison)) {
                $this->logError("Impossible d'annuler le rendez-vous (ID: $rendezVousId)");
                return false;
            }

            // Libérer la disponibilité si elle existe
            if ($rendezVous['disponibilite_id'] !== null) {
                $disponibiliteId = $rendezVous['disponibilite_id'];
                $disponibilite = $this->disponibiliteModel->getDisponibiliteById($disponibiliteId);

                if ($disponibilite) {
                    $this->disponibiliteModel->modifierDisponibilite(
                        $disponibiliteId,
                        $disponibilite['date_debut'],
                        $disponibilite['date_fin'],
                        self::STATUT_DISPO_DISPONIBLE,
                        null,  // serviceId
                        null,  // prix
                        null,  // notes
                        null   // etudiant_id (reset à NULL)
                    );
                }
            }

            return true;
        } catch (Exception $e) {
            $this->logError("annulerReservation", $e);
            return false;
        }
    }

    // Reporte une réservation (rendez-vous)
    // Paramètres : id du rendez-vous, nouvelle date (DATETIME)
    // Retourne : true si succès, false sinon
    public function reporterReservation($rendezVousId, $nouvelleDate)
    {
        try {
            // Récupérer le rendez-vous
            $rendezVous = $this->rendezVousModel->getRendezVousById($rendezVousId);
            if (!$rendezVous) {
                $this->logError("Le rendez-vous n'existe pas (ID: $rendezVousId)");
                return false;
            }

            return $this->rendezVousModel->reporterRendezVous($rendezVousId, $nouvelleDate);
        } catch (Exception $e) {
            $this->logError("reporterReservation", $e);
            return false;
        }
    }

    // Vérifie si une disponibilité est disponible
    // Paramètre : id de la disponibilité
    // Retourne : true si disponible, false sinon
    public function verifierDisponibilite($disponibiliteId)
    {
        return $this->disponibiliteModel->estDisponible($disponibiliteId);
    }

    // Calcule le prix d'un service pour une durée donnée (en minutes)
    // Paramètres : id du service, durée en minutes
    // Retourne : prix calculé (float)
    public function calculerPrix($serviceId, $duree)
    {
        try {
            $service = $this->serviceModel->getServiceById($serviceId);
            if (!$service) {
                $this->logError("Le service n'existe pas (ID: $serviceId)");
                return 0.0;
            }

            // Prix fixe du service
            if (!empty($service['prix']) && $service['prix'] > 0) {
                $dureeService = $service['duree_minute'] ?? 60;
                $prixParMinute = $service['prix'] / $dureeService;
                return round($prixParMinute * $duree, 2);
            }

            // Tarif horaire
            if (isset($service['tarif_horaire']) && $service['tarif_horaire'] > 0) {
                $prixParMinute = $service['tarif_horaire'] / 60;
                return round($prixParMinute * $duree, 2);
            }

            return 0.0;
        } catch (Exception $e) {
            $this->logError("calculerPrix", $e);
            return 0.0;
        }
    }

    // Log interne des erreurs
    // Paramètres : contexte (string), exception (optionnelle)
    private function logError(string $context, Exception $e = null): void
    {
        $message = "Erreur ReservationService::$context";
        if ($e) {
            $message .= ' : ' . $e->getMessage();
        }
        error_log($message);
    }
}
