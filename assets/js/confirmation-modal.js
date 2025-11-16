// Script pour gérer le modal de confirmation de réservation

let confirmationAutoCloseTimeout = null;

/**
 * Récupère l'élément modal de confirmation
 */
function getConfirmationModal() {
    return document.getElementById('confirmationModal');
}

/**
 * Ouvre le modal de confirmation
 */
function openConfirmationModal() {
    const modal = getConfirmationModal();
    if (!modal) {
        console.error('Modal de confirmation non trouvé dans le DOM');
        return;
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Annuler l'auto-fermeture précédente si elle existe
    if (confirmationAutoCloseTimeout !== null) {
        clearTimeout(confirmationAutoCloseTimeout);
    }

    // Auto-fermeture après 5 secondes
    confirmationAutoCloseTimeout = setTimeout(closeConfirmationModal, 5000);
}

/**
 * Ferme le modal de confirmation
 */
function closeConfirmationModal() {
    const modal = getConfirmationModal();
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';

    // Annuler l'auto-fermeture si elle est en cours
    if (confirmationAutoCloseTimeout !== null) {
        clearTimeout(confirmationAutoCloseTimeout);
        confirmationAutoCloseTimeout = null;
    }
}

/**
 * Remplit les données de confirmation dans le modal
 * data : { date, heure, tuteur, service, notificationEnabled }
 */
function fillConfirmationData(data) {
    if (!data) {
        console.error('Aucune donnée fournie pour la confirmation');
        return;
    }

    const dateTimeElement = document.getElementById('confirmation-date-time');
    const tuteurElement = document.getElementById('confirmation-tuteur');
    const serviceElement = document.getElementById('confirmation-service');

    // Date + heure
    if (dateTimeElement) {
        if (data.date && data.heure) {
            dateTimeElement.textContent = `${data.date} de ${data.heure}`;
        } else if (data.dateTime) {
            dateTimeElement.textContent = data.dateTime;
        } else {
            dateTimeElement.textContent = '-';
        }
    } else {
        console.error('Élément confirmation-date-time non trouvé dans le DOM');
    }

    // Tuteur
    if (tuteurElement) {
        tuteurElement.textContent = data.tuteur || '-';
    } else {
        console.error('Élément confirmation-tuteur non trouvé dans le DOM');
    }

    // Service
    if (serviceElement) {
        serviceElement.textContent = data.service || '-';
    } else {
        console.error('Élément confirmation-service non trouvé dans le DOM');
    }

    // Row notification
    const notificationRow = document.getElementById('confirmation-notification-row');
    const notificationElement = document.getElementById('confirmation-notification');

    if (notificationRow && notificationElement) {
        if (data.enAttente === true) {
            // Afficher un message indiquant que la demande est en attente
            notificationRow.style.display = 'flex';
            notificationElement.textContent = 'Votre demande est en attente. Le tuteur doit l\'accepter pour confirmer le rendez-vous.';
        } else if (data.notificationEnabled === true) {
            notificationRow.style.display = 'flex';
            notificationElement.textContent = 'Vous serez notifié(e) 1 jour avant votre rendez-vous';
        } else {
            notificationRow.style.display = 'none';
        }
    }
    
    // Mettre à jour le titre et sous-titre si la demande est en attente
    if (data.enAttente === true) {
        const titleElement = document.querySelector('#confirmationModal .confirmation-title');
        const subtitleElement = document.querySelector('#confirmationModal .confirmation-subtitle');
        
        if (titleElement) {
            titleElement.textContent = 'Demande envoyée !';
        }
        
        if (subtitleElement) {
            subtitleElement.textContent = 'Votre demande de rendez-vous a été envoyée au tuteur';
        }
    }
}

/**
 * Formate une date pour l'affichage dans la confirmation
 * dateString : Date au format ISO (YYYY-MM-DD)
 */
function formatDateForConfirmation(dateString) {
    if (!dateString) return '';

    try {
        // Créer la date en heure locale pour éviter les problèmes de fuseau horaire
        // dateString est au format "YYYY-MM-DD"
        const [year, month, day] = dateString.split('-').map(Number);
        const date = new Date(year, month - 1, day);

        if (isNaN(date.getTime())) {
            console.error('Date invalide:', dateString);
            return dateString;
        }

        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    } catch (error) {
        console.error('Erreur lors du formatage de la date:', error);
        return dateString;
    }
}

/**
 * Formate une heure pour l'affichage dans la confirmation
 * timeString : Heure au format HH:mm
 */
function formatTimeForConfirmation(timeString) {
    if (!timeString) return '';

    try {
        const [hours, minutes] = timeString.split(':');

        if (!hours || !minutes || isNaN(parseInt(hours)) || isNaN(parseInt(minutes))) {
            console.error('Format d\'heure invalide:', timeString);
            return timeString;
        }

        const formattedHours = hours.padStart(2, '0');
        const formattedMinutes = minutes.padStart(2, '0');

        return `${formattedHours}:${formattedMinutes}`;
    } catch (error) {
        console.error('Erreur lors du formatage de l\'heure:', error);
        return timeString;
    }
}

// Gestion des événements pour le modal de confirmation
document.addEventListener('DOMContentLoaded', () => {
    const modal = getConfirmationModal();
    if (!modal) return;

    const btnClose = document.getElementById('btnConfirmationClose');
    const overlay = modal.querySelector('.confirmation-modal-overlay');
    const modalContent = modal.querySelector('.confirmation-modal-content');

    if (btnClose) {
        btnClose.addEventListener('click', closeConfirmationModal);
    }

    if (overlay) {
        overlay.addEventListener('click', closeConfirmationModal);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeConfirmationModal();
        }
    });

    // Annuler l'auto-fermeture si interaction dans le modal
    if (modal) {
        modal.addEventListener('mouseenter', () => {
            if (confirmationAutoCloseTimeout !== null) {
                clearTimeout(confirmationAutoCloseTimeout);
                confirmationAutoCloseTimeout = null;
            }
        });

        if (modalContent) {
            modalContent.addEventListener('click', () => {
                if (confirmationAutoCloseTimeout !== null) {
                    clearTimeout(confirmationAutoCloseTimeout);
                    confirmationAutoCloseTimeout = null;
                }
            });
        }
    }
});
