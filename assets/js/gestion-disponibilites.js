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
            openModalEdit(info.event);
        },
        
        // Gérer le déplacement d'un événement (modification de date/heure)
        eventDrop: function(info) {
            updateDisponibilite(info.event);
        },
        
        // Gérer le redimensionnement d'un événement (modification de durée)
        eventResize: function(info) {
            updateDisponibilite(info.event);
        }
    });
    
    // Afficher le calendrier
    calendar.render();
    
    // Variables globales pour le modal et le calendrier
    window.calendar = calendar;
    window.openModalCreate = openModalCreate;
    window.openModalEdit = openModalEdit;
    window.closeModal = closeModal;
    window.submitDisponibilite = submitDisponibilite;
    window.updateDisponibilite = updateDisponibilite;
    window.deleteDisponibilite = deleteDisponibilite;
    
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
    document.getElementById('modal-delete').style.display = 'none';
    
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

// Fonction pour ouvrir le modal de modification
function openModalEdit(event) {
    const modal = document.getElementById('modal-disponibilite');
    const form = document.getElementById('form-disponibilite');
    const title = document.getElementById('modal-title');
    const submitBtn = document.getElementById('modal-submit');
    const deleteBtn = document.getElementById('modal-delete');
    
    // Récupérer les données de l'événement
    const id = event.id;
    const start = event.start;
    const end = event.end || event.start;
    const extendedProps = event.extendedProps || {};
    const statut = extendedProps.statut || 'DISPONIBLE';
    const serviceId = extendedProps.service_id || '';
    const prix = extendedProps.prix || '';
    const notes = extendedProps.notes || '';
    
    // Pré-remplir le formulaire
    form.reset();
    document.getElementById('disponibilite-id').value = id;
    title.textContent = 'Modifier une disponibilité';
    submitBtn.textContent = 'Modifier';
    
    // Afficher le bouton de suppression si le créneau n'est pas réservé
    if (statut !== 'RESERVE') {
        deleteBtn.style.display = 'block';
        deleteBtn.onclick = function() {
            deleteDisponibilite(id);
        };
    } else {
        deleteBtn.style.display = 'none';
    }
    
    // Formater les dates pour datetime-local
    const startStr = formatDateTimeLocal(start);
    const endStr = formatDateTimeLocal(end);
    
    document.getElementById('date-debut').value = startStr;
    document.getElementById('date-fin').value = endStr;
    document.getElementById('service-id').value = serviceId;
    document.getElementById('prix').value = prix;
    document.getElementById('statut').value = statut;
    document.getElementById('notes').value = notes;
    
    // Afficher le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fonction pour soumettre le formulaire (créer ou modifier une disponibilité)
function submitDisponibilite() {
    const form = document.getElementById('form-disponibilite');
    const formData = new FormData(form);
    const errorDiv = document.getElementById('modal-error');
    const id = formData.get('id');
    
    // Récupérer les valeurs du formulaire
    const dateDebut = formData.get('date_debut');
    const dateFin = formData.get('date_fin');
    const serviceId = formData.get('service_id');
    const prix = formData.get('prix');
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
        service_id: serviceId || null,
        prix: prix ? parseFloat(prix) : null,
        statut: statut,
        notes: notes || null
    };
    
    // Déterminer la méthode HTTP et l'URL
    const method = id ? 'PUT' : 'POST';
    if (id) {
        data.id = id;
    }
    
    // Envoyer la requête
    fetch('api/disponibilites.php', {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Afficher message de succès
            const message = id ? 'Disponibilité modifiée avec succès' : 'Disponibilité créée avec succès';
            showNotification(message, 'success');
            // Recharger les événements du calendrier
            window.calendar.refetchEvents();
            closeModal();
        } else {
            errorDiv.textContent = result.error || 'Erreur lors de la sauvegarde de la disponibilité';
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
        errorDiv.style.display = 'block';
    });
}

// Fonction pour mettre à jour une disponibilité après déplacement ou redimensionnement
function updateDisponibilite(event) {
    const id = event.id;
    const start = event.start;
    const end = event.end || event.start;
    const extendedProps = event.extendedProps || {};
    
    // Validation côté client : durée minimum 30 minutes
    const diffMinutes = (end - start) / (1000 * 60);
    
    if (diffMinutes < 30) {
        // Annuler le changement si la durée est inférieure à 30 minutes
        event.revert();
        showNotification('La durée minimum doit être de 30 minutes', 'error');
        return;
    }
    
    // Préparer les données
    const data = {
        id: id,
        date_debut: formatDateForAPI(start),
        date_fin: formatDateForAPI(end),
        statut: extendedProps.statut || null,
        service_id: extendedProps.service_id || null,
        prix: extendedProps.prix || null,
        notes: extendedProps.notes || null
    };
    
    // Envoyer la requête PUT
    fetch('api/disponibilites.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (!result.success) {
            // Annuler le changement en cas d'erreur
            event.revert();
            showNotification(result.error || 'Erreur lors de la modification de la disponibilité', 'error');
        } else {
            showNotification('Disponibilité modifiée avec succès', 'success');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        event.revert();
        showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
    });
}

// Fonction pour formater une date pour l'API (format: YYYY-MM-DD HH:mm:ss)
function formatDateTimeForAPI(dateTimeLocal) {
    const d = new Date(dateTimeLocal);
    return formatDateForAPI(d);
}

// Fonction pour formater un objet Date pour l'API (format: YYYY-MM-DD HH:mm:ss)
function formatDateForAPI(date) {
    const d = date instanceof Date ? date : new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// Fonction pour supprimer une disponibilité
function deleteDisponibilite(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')) {
        return;
    }
    
    fetch('api/disponibilites.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Afficher message de succès
            showNotification('Disponibilité supprimée avec succès', 'success');
            // Recharger les événements du calendrier
            window.calendar.refetchEvents();
            closeModal();
        } else {
            showNotification(result.error || 'Erreur lors de la suppression de la disponibilité', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
    });
}

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    notification.innerHTML = `
        <span class="notification-icon">${icon}</span>
        <span class="notification-message">${message}</span>
        <button type="button" class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(notification);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideInRight 0.3s reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

