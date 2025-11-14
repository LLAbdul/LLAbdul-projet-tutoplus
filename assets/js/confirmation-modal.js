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
    const date = new Date(dateString);
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

/*
    Formate une heure pour l'affichage dans la confirmation
    timeString : Heure au format HH:mm
    return : Heure formatée (ex: "14:30")
*/
function formatTimeForConfirmation(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    return `${hours}:${minutes}`;
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

