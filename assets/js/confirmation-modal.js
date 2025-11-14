// Script pour gérer le modal de confirmation de réservation

/**
 * Ouvre le modal de confirmation
 */
function openConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
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
    
    if (dateTimeElement && data.date && data.heure) {
        dateTimeElement.textContent = `${data.date} ${data.heure}`;
    }
    
    if (tuteurElement && data.tuteur) {
        tuteurElement.textContent = data.tuteur;
    }
    
    if (serviceElement && data.service) {
        serviceElement.textContent = data.service;
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

