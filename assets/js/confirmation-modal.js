// Script pour gérer le modal de confirmation de réservation

let confirmationAutoCloseTimeout = null;

/**
 * Ouvre le modal de confirmation
 */
function openConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Annuler l'auto-fermeture précédente si elle existe
        if (confirmationAutoCloseTimeout) {
            clearTimeout(confirmationAutoCloseTimeout);
        }
        
        // Auto-fermeture après 5 secondes
        confirmationAutoCloseTimeout = setTimeout(() => {
            closeConfirmationModal();
        }, 5000);
    }
}

/**
 * Ferme le modal de confirmation
 */
function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Annuler l'auto-fermeture si elle est en cours
        if (confirmationAutoCloseTimeout) {
            clearTimeout(confirmationAutoCloseTimeout);
            confirmationAutoCloseTimeout = null;
        }
    }
}

/*
  Remplit les données de confirmation dans le modal 
  data : Données de la réservation (date, heure, tuteur, service)
*/
function fillConfirmationData(data) {
    const dateTimeElement = document.getElementById('confirmation-date-time');
    const tuteurElement = document.getElementById('confirmation-tuteur');
    const serviceElement = document.getElementById('confirmation-service');
    
    // Formater et afficher la date et l'heure
    if (dateTimeElement) {
        if (data.date && data.heure) {
            dateTimeElement.textContent = `${data.date} de ${data.heure}`;
        } else if (data.dateTime) {
            // Format alternatif si dateTime est fourni directement
            dateTimeElement.textContent = data.dateTime;
        } else {
            dateTimeElement.textContent = '-';
        }
    }
    
    // Afficher le tuteur
    if (tuteurElement) {
        tuteurElement.textContent = data.tuteur || '-';
    }
    
    // Afficher le service
    if (serviceElement) {
        serviceElement.textContent = data.service || '-';
    }
}

/*
    Formate une date pour l'affichage dans la confirmation
    dateString : Date au format ISO (YYYY-MM-DD)
    return : Date formatée (ex: "15 janvier 2025")
 */
function formatDateForConfirmation(dateString) {
    if (!dateString) return '';
    
    try {
        const date = new Date(dateString);
        
        // Vérifier que la date est valide
        if (isNaN(date.getTime())) {
            console.error('Date invalide:', dateString);
            return dateString; // Retourner la date originale si invalide
        }
        
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    } catch (error) {
        console.error('Erreur lors du formatage de la date:', error);
        return dateString; // Retourner la date originale en cas d'erreur
    }
}

/*
    Formate une heure pour l'affichage dans la confirmation
    timeString : Heure au format HH:mm
    return : Heure formatée (ex: "14:30")
*/
function formatTimeForConfirmation(timeString) {
    if (!timeString) return '';
    
    try {
        const [hours, minutes] = timeString.split(':');
        
        // Vérifier que les heures et minutes sont valides
        if (!hours || !minutes || isNaN(parseInt(hours)) || isNaN(parseInt(minutes))) {
            console.error('Format d\'heure invalide:', timeString);
            return timeString; // Retourner l'heure originale si invalide
        }
        
        // Formater avec padding pour les heures/minutes < 10
        const formattedHours = hours.padStart(2, '0');
        const formattedMinutes = minutes.padStart(2, '0');
        
        return `${formattedHours}:${formattedMinutes}`;
    } catch (error) {
        console.error('Erreur lors du formatage de l\'heure:', error);
        return timeString; // Retourner l'heure originale en cas d'erreur
    }
}

// Gestion des événements pour le modal de confirmation
document.addEventListener('DOMContentLoaded', function() {
    const btnClose = document.getElementById('btnConfirmationClose');
    const modal = document.getElementById('confirmationModal');
    const overlay = modal ? modal.querySelector('.confirmation-modal-overlay') : null;
    
    // Fermer avec le bouton
    if (btnClose) {
        btnClose.addEventListener('click', closeConfirmationModal);
    }
    
    // Fermer avec l'overlay
    if (overlay) {
        overlay.addEventListener('click', closeConfirmationModal);
    }
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeConfirmationModal();
        }
    });
    
    // Annuler l'auto-fermeture si l'utilisateur interagit avec le modal
    if (modal) {
        modal.addEventListener('mouseenter', function() {
            if (confirmationAutoCloseTimeout) {
                clearTimeout(confirmationAutoCloseTimeout);
                confirmationAutoCloseTimeout = null;
            }
        });
        
        modal.addEventListener('click', function(e) {
            // Si l'utilisateur clique dans le modal (pas sur l'overlay), annuler l'auto-fermeture
            if (e.target.closest('.confirmation-modal-content')) {
                if (confirmationAutoCloseTimeout) {
                    clearTimeout(confirmationAutoCloseTimeout);
                    confirmationAutoCloseTimeout = null;
                }
            }
        });
    }
});

