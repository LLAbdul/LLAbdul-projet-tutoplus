// Configuration et gestion du calendrier des disponibilités
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendrier-disponibilites');
    
    if (!calendarEl) {
        console.error('Élément calendrier non trouvé');
        return;
    }
    
    // Initialiser FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'fr-ca',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        firstDay: 1, // Lundi
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        slotDuration: '00:30:00',
        allDaySlot: false,
        height: 'auto',
        
        // Charger les disponibilités existantes
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('api/disponibilites.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur lors du chargement des disponibilités');
                    }
                    return response.json();
                })
                .then(data => {
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    failureCallback(error);
                });
        },
        
        // Gérer la sélection d'une plage horaire (création de créneau)
        select: function(selectInfo) {
            openModalCreate(selectInfo.start, selectInfo.end);
            calendar.unselect();
        },
        
        // Gérer le clic sur un événement existant (modification)
        eventClick: function(info) {
            // TODO: Ouvrir modal pour modifier/supprimer une disponibilité
            console.log('Événement cliqué:', info);
        },
        
        // Gérer le déplacement d'un événement (modification de date/heure)
        eventDrop: function(info) {
            // TODO: Mettre à jour la disponibilité
            console.log('Événement déplacé:', info);
        },
        
        // Gérer le redimensionnement d'un événement (modification de durée)
        eventResize: function(info) {
            // TODO: Mettre à jour la disponibilité
            console.log('Événement redimensionné:', info);
        }
    });
    
    // Afficher le calendrier
    calendar.render();
    
    // Variables globales pour le modal et le calendrier
    window.calendar = calendar;
    window.openModalCreate = openModalCreate;
    window.closeModal = closeModal;
    window.submitDisponibilite = submitDisponibilite;
    
    // Initialiser les événements du modal
    initModal();
});

// Fonction pour ouvrir le modal de création
function openModalCreate(start, end) {
    const modal = document.getElementById('modal-disponibilite');
    const form = document.getElementById('form-disponibilite');
    const title = document.getElementById('modal-title');
    const submitBtn = document.getElementById('modal-submit');
    
    // Réinitialiser le formulaire
    form.reset();
    document.getElementById('disponibilite-id').value = '';
    title.textContent = 'Créer une disponibilité';
    submitBtn.textContent = 'Créer';
    
    // Formater les dates pour datetime-local (format: YYYY-MM-DDTHH:mm)
    const startStr = formatDateTimeLocal(start);
    const endStr = formatDateTimeLocal(end);
    
    document.getElementById('date-debut').value = startStr;
    document.getElementById('date-fin').value = endStr;
    
    // Afficher le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fonction pour fermer le modal
function closeModal() {
    const modal = document.getElementById('modal-disponibilite');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('modal-error').style.display = 'none';
}

// Fonction pour formater une date en format datetime-local
function formatDateTimeLocal(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Fonction pour initialiser les événements du modal
function initModal() {
    const modal = document.getElementById('modal-disponibilite');
    const closeBtn = document.getElementById('modal-close');
    const cancelBtn = document.getElementById('modal-cancel');
    const overlay = modal.querySelector('.creneaux-modal-overlay');
    const form = document.getElementById('form-disponibilite');
    
    // Fermer le modal
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    // Soumettre le formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitDisponibilite();
    });
}

// Fonction pour soumettre le formulaire (créer une disponibilité)
function submitDisponibilite() {
    const form = document.getElementById('form-disponibilite');
    const formData = new FormData(form);
    const errorDiv = document.getElementById('modal-error');
    
    // Récupérer les valeurs du formulaire
    const dateDebut = formData.get('date_debut');
    const dateFin = formData.get('date_fin');
    const statut = formData.get('statut');
    const notes = formData.get('notes');
    
    // Validation côté client : durée minimum 30 minutes
    const debut = new Date(dateDebut);
    const fin = new Date(dateFin);
    const diffMinutes = (fin - debut) / (1000 * 60);
    
    if (diffMinutes < 30) {
        errorDiv.textContent = 'La durée minimum doit être de 30 minutes';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Préparer les données
    const data = {
        date_debut: formatDateTimeForAPI(dateDebut),
        date_fin: formatDateTimeForAPI(dateFin),
        statut: statut,
        notes: notes || null
    };
    
    // Envoyer la requête POST
    fetch('api/disponibilites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Recharger les événements du calendrier
            window.calendar.refetchEvents();
            closeModal();
        } else {
            errorDiv.textContent = result.error || 'Erreur lors de la création de la disponibilité';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
        errorDiv.style.display = 'block';
    });
}

// Fonction pour formater une date pour l'API (format: YYYY-MM-DD HH:mm:ss)
function formatDateTimeForAPI(dateTimeLocal) {
    const d = new Date(dateTimeLocal);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

