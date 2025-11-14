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

