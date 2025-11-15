<?php
/**
 * Service ReservationService - Orchestration des opérations de réservation
 * Utilise les modèles Demande, RendezVous, Disponibilite et Service
 */

require_once __DIR__ . '/../models/Demande.php';
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../models/Disponibilite.php';
require_once __DIR__ . '/../models/Service.php';

class ReservationService {
    private $pdo;
    private $demandeModel;
    private $rendezVousModel;
    private $disponibiliteModel;
    private $serviceModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->demandeModel = new Demande($pdo);
        $this->rendezVousModel = new RendezVous($pdo);
        $this->disponibiliteModel = new Disponibilite($pdo);
        $this->serviceModel = new Service($pdo);
    }
    
    /**
     * Crée une demande de rendez-vous
     * @param string $etudiantId UUID de l'étudiant
     * @param string $serviceId UUID du service
     * @param string $tuteurId UUID du tuteur
     * @param string|null $disponibiliteId UUID de la disponibilité (optionnel)
     * @param string|null $motif Motif de la demande
     * @param string|null $priorite Priorité de la demande
     * @return string|false UUID de la demande créée ou false en cas d'erreur
     */
    public function creerDemande($etudiantId, $serviceId, $tuteurId, $disponibiliteId = null, $motif = null, $priorite = null) {
        // Vérifier que la disponibilité existe et est disponible si fournie
        if ($disponibiliteId !== null) {
            if (!$this->verifierDisponibilite($disponibiliteId)) {
                error_log("Erreur : La disponibilité n'est pas disponible");
                return false;
            }
        }
        
        return $this->demandeModel->creerDemande($etudiantId, $serviceId, $tuteurId, $disponibiliteId, $motif, $priorite);
    }
    
    /**
     * Confirme une demande et crée un rendez-vous
     * @param string $demandeId UUID de la demande
     * @return string|false UUID du rendez-vous créé ou false en cas d'erreur
     */
    public function confirmerDemande($demandeId) {
        try {
            // Récupérer la demande
            $demande = $this->demandeModel->getDemandeById($demandeId);
            if (!$demande) {
                error_log("Erreur : La demande n'existe pas");
                return false;
            }
            
            // Vérifier que la demande est en attente
            if ($demande['statut'] !== 'EN_ATTENTE') {
                error_log("Erreur : La demande n'est pas en attente (statut: " . $demande['statut'] . ")");
                return false;
            }
            
            // Si une disponibilité est associée, vérifier qu'elle est toujours disponible
            if ($demande['disponibilite_id'] !== null) {
                if (!$this->verifierDisponibilite($demande['disponibilite_id'])) {
                    error_log("Erreur : La disponibilité n'est plus disponible");
                    return false;
                }
                
                // Réserver la disponibilité
                $disponibilite = $this->disponibiliteModel->getDisponibiliteById($demande['disponibilite_id']);
                $result = $this->disponibiliteModel->modifierDisponibilite(
                    $demande['disponibilite_id'],
                    $disponibilite['date_debut'],
                    $disponibilite['date_fin'],
                    'RESERVE',
                    null, // serviceId
                    null, // prix
                    null, // notes
                    $demande['etudiant_id'] // etudiant_id
                );
                
                if (!$result) {
                    error_log("Erreur : Impossible de réserver la disponibilité");
                    return false;
                }
            }
            
            // Accepter la demande
            if (!$this->demandeModel->accepterDemande($demandeId)) {
                error_log("Erreur : Impossible d'accepter la demande");
                return false;
            }
            
            // Récupérer les informations du service pour calculer la durée et le prix
            $service = $this->serviceModel->getServiceById($demande['service_id']);
            if (!$service) {
                error_log("Erreur : Le service n'existe pas");
                return false;
            }
            
            $duree = $service['duree_minute'];
            $prix = $this->calculerPrix($demande['service_id'], $duree);
            
            // Récupérer la disponibilité pour obtenir la date_heure
            $disponibilite = null;
            if ($demande['disponibilite_id'] !== null) {
                $disponibilite = $this->disponibiliteModel->getDisponibiliteById($demande['disponibilite_id']);
            }
            
            // Créer le rendez-vous
            $rendezVousId = $this->rendezVousModel->creerRendezVous(
                $demandeId,
                $demande['etudiant_id'],
                $demande['tuteur_id'],
                $demande['service_id'],
                $demande['disponibilite_id'] ?? null,
                $duree,
                null, // lieu
                $demande['motif'], // notes
                $prix
            );
            
            return $rendezVousId;
        } catch (Exception $e) {
            error_log("Erreur lors de la confirmation de la demande : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Annule une réservation (rendez-vous)
     * @param string $rendezVousId UUID du rendez-vous
     * @param string|null $raison Raison de l'annulation
     * @return bool True si succès, false sinon
     */
    public function annulerReservation($rendezVousId, $raison = null) {
        try {
            // Récupérer le rendez-vous
            $rendezVous = $this->rendezVousModel->getRendezVousById($rendezVousId);
            if (!$rendezVous) {
                error_log("Erreur : Le rendez-vous n'existe pas");
                return false;
            }
            
            // Annuler le rendez-vous
            if (!$this->rendezVousModel->annulerRendezVous($rendezVousId, $raison)) {
                return false;
            }
            
            // Libérer la disponibilité si elle existe
            if ($rendezVous['disponibilite_id'] !== null) {
                $disponibilite = $this->disponibiliteModel->getDisponibiliteById($rendezVous['disponibilite_id']);
                if ($disponibilite) {
                    $this->disponibiliteModel->modifierDisponibilite(
                        $rendezVous['disponibilite_id'],
                        $disponibilite['date_debut'],
                        $disponibilite['date_fin'],
                        'DISPONIBLE',
                        null, // serviceId
                        null, // prix
                        null, // notes
                        null  // etudiant_id - réinitialiser à NULL
                    );
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de l'annulation de la réservation : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reporte une réservation (rendez-vous)
     * @param string $rendezVousId UUID du rendez-vous
     * @param string $nouvelleDate Nouvelle date/heure (format DATETIME)
     * @return bool True si succès, false sinon
     */
    public function reporterReservation($rendezVousId, $nouvelleDate) {
        try {
            // Récupérer le rendez-vous
            $rendezVous = $this->rendezVousModel->getRendezVousById($rendezVousId);
            if (!$rendezVous) {
                error_log("Erreur : Le rendez-vous n'existe pas");
                return false;
            }
            
            // Reporter le rendez-vous
            return $this->rendezVousModel->reporterRendezVous($rendezVousId, $nouvelleDate);
        } catch (Exception $e) {
            error_log("Erreur lors du report de la réservation : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie si une disponibilité est disponible
     * @param string $disponibiliteId UUID de la disponibilité
     * @return bool True si disponible, false sinon
     */
    public function verifierDisponibilite($disponibiliteId) {
        return $this->disponibiliteModel->estDisponible($disponibiliteId);
    }
    
    /**
     * Calcule le prix d'un service pour une durée donnée
     * @param string $serviceId UUID du service
     * @param int $duree Durée en minutes
     * @return float Prix calculé
     */
    public function calculerPrix($serviceId, $duree) {
        try {
            $service = $this->serviceModel->getServiceById($serviceId);
            if (!$service) {
                error_log("Erreur : Le service n'existe pas");
                return 0.0;
            }
            
            // Si le service a un prix fixe, l'utiliser
            if ($service['prix'] > 0) {
                // Calculer le prix proportionnel à la durée
                $dureeService = $service['duree_minute'] ?? 60;
                $prixParMinute = $service['prix'] / $dureeService;
                return round($prixParMinute * $duree, 2);
            }
            
            // Sinon, utiliser le tarif horaire du tuteur (déjà inclus dans le service)
            if (isset($service['tarif_horaire']) && $service['tarif_horaire'] > 0) {
                $prixParMinute = $service['tarif_horaire'] / 60;
                return round($prixParMinute * $duree, 2);
            }
            
            return 0.0;
        } catch (Exception $e) {
            error_log("Erreur lors du calcul du prix : " . $e->getMessage());
            return 0.0;
        }
    }
}

