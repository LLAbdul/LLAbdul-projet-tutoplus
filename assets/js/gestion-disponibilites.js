/* Testé par Diane Devi le 21/11/2025 Réussi */
const DISPONIBILITES_API_URL = 'api/disponibilites.php';
const MIN_DURATION_MINUTES = 30;

// === Helpers génériques ===

// Différence en minutes entre 2 dates
function getDiffMinutes(start, end) {
    return (end - start) / (1000 * 60);
}

// Vérifie si deux dates sont le même jour (en fonction locale)
function isSameDay(a, b) {
    return a.toDateString() === b.toDateString();
}

// Couleurs selon statut
const STATUT_COLORS = {
    RESERVE: '#dc3545',   // Rouge
    BLOQUE:  '#6c757d',   // Gris
    DISPONIBLE: '#28a745' // Vert
};

// Récupérer la couleur d'un statut
function getStatutColor(statut) {
    if (!statut) return STATUT_COLORS.DISPONIBLE;
    return STATUT_COLORS[statut] || STATUT_COLORS.DISPONIBLE;
}

// Récupérer la couleur d'un événement (API + fallback statut)
function getEventColor(event) {
    if (event.color) return event.color;

    const statut = event.extendedProps?.statut || event.statut;
    return getStatutColor(statut);
}

// Formater une date pour <input type="datetime-local">
function formatDateTimeLocal(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Formater un objet Date pour l'API (format: YYYY-MM-DD HH:mm:ss)
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

// Formater une string datetime-local pour l'API
function formatDateTimeForAPI(dateTimeLocal) {
    const d = new Date(dateTimeLocal);
    return formatDateForAPI(d);
}

// === Initialisation du calendrier ===

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendrier-disponibilites');
    
    if (!calendarEl) {
        console.error('Élément calendrier non trouvé');
        return;
    }
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
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
        
        // Empêcher le drag & drop et resize pour les créneaux réservés avec rendez-vous
        eventStartEditable: function(event) {
            const extendedProps = event.extendedProps || {};
            const hasRendezVous = extendedProps.hasRendezVous === true;
            return !hasRendezVous; // Non éditable si un rendez-vous est lié
        },
        eventDurationEditable: function(event) {
            const extendedProps = event.extendedProps || {};
            const hasRendezVous = extendedProps.hasRendezVous === true;
            return !hasRendezVous; // Non redimensionnable si un rendez-vous est lié
        },

        // Configuration des vues
        views: {
            dayGridMonth: {
                // "Novembre 2025"
                titleFormat: {
                    year: 'numeric',
                    month: 'long'
                }
            },
            timeGridWeek: {
                // "11 – 16 novembre 2025"
                titleFormat: {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }
            },
            timeGridDay: {
                // "16 novembre 2025"
                titleFormat: {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }
            },
            listWeek: {
                // "11 – 16 novembre 2025"
                titleFormat: {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                },
                listDayFormat: { weekday: 'long', day: 'numeric', month: 'long' },
                listDaySideFormat: false
            }
        },
        
        // Empêcher la sélection sur plusieurs jours
        selectAllow: function(selectInfo) {
            return isSameDay(selectInfo.start, selectInfo.end);
        },
        
        // Charger les disponibilités existantes
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(DISPONIBILITES_API_URL)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur lors du chargement des disponibilités');
                    }
                    return response.json();
                })
                .then(data => {
                    const events = data.map(event => {
                        const color = getEventColor(event);
                        return {
                            ...event,
                            backgroundColor: color,
                            borderColor: color,
                            textColor: '#ffffff'
                        };
                    });
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    failureCallback(error);
                });
        },
        
        // Gérer la sélection d'une plage horaire (création de créneau)
        select: function(selectInfo) {
            if (!isSameDay(selectInfo.start, selectInfo.end)) {
                calendar.unselect();
                return;
            }
            openModalCreate(selectInfo.start, selectInfo.end);
            calendar.unselect();
        },
        
        // Gérer le clic sur une date/heure (pour mobile/tablette)
        dateClick: function(info) {
            if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                const start = info.date;
                const end = new Date(start.getTime() + 30 * 60 * 1000); // +30 minutes
                openModalCreate(start, end);
            }
        },
        
        
        // Gérer le clic sur un événement existant (modification ou consultation)
        eventClick: function(info) {
            // Toujours ouvrir le modal, même pour les créneaux réservés (en lecture seule)
            openModalEdit(info.event);
        },
        
        // Gérer le déplacement d'un événement (modification de date/heure)
        eventDrop: function(info) {
            const extendedProps = info.event.extendedProps || {};
            const hasRendezVous = extendedProps.hasRendezVous === true;
            
            // Si le créneau a un rendez-vous lié, annuler le déplacement
            if (hasRendezVous) {
                info.revert();
                showNotification('Impossible de modifier un créneau réservé avec un rendez-vous', 'error');
                return;
            }
            
            updateDisponibilite(info.event);
        },
        
        // Gérer le redimensionnement d'un événement (modification de durée)
        eventResize: function(info) {
            const extendedProps = info.event.extendedProps || {};
            const hasRendezVous = extendedProps.hasRendezVous === true;
            
            // Si le créneau a un rendez-vous lié, annuler le redimensionnement
            if (hasRendezVous) {
                info.revert();
                showNotification('Impossible de modifier un créneau réservé avec un rendez-vous', 'error');
                return;
            }
            
            updateDisponibilite(info.event);
        },
        
        // Forcer l'application des couleurs en mode mois
        eventDidMount: function(info) {
            if (info.view.type === 'dayGridMonth') {
                const event = info.event;
                let color = event.backgroundColor || event.borderColor || event.extendedProps?.color;
                if (!color) {
                    const statut = event.extendedProps?.statut || event.statut;
                    color = getStatutColor(statut);
                }
                if (color && info.el) {
                    info.el.style.setProperty('background-color', color, 'important');
                    info.el.style.setProperty('border-color', color, 'important');
                    info.el.style.setProperty('color', '#ffffff', 'important');
                    info.el.style.setProperty('display', 'block', 'important');
                    info.el.style.setProperty('opacity', '1', 'important');
                    info.el.style.setProperty('visibility', 'visible', 'important');
                }
            }
        }
    });
    
    // Afficher le calendrier
    try {
        calendar.render();
    } catch (error) {
        console.error('Erreur lors du rendu du calendrier:', error);
    }
    
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
    initSuppressionModal();
});

// === Gestion des modals ===

function openModalCreate(start, end) {
    const modal = document.getElementById('modal-disponibilite');
    const form = document.getElementById('form-disponibilite');
    const title = document.getElementById('modal-title');
    const submitBtn = document.getElementById('modal-submit');
    
    // Reset du formulaire
    form.reset();
    document.getElementById('disponibilite-id').value = '';
    title.textContent = 'Créer une disponibilité';
    submitBtn.textContent = 'Créer';
    document.getElementById('modal-delete').style.display = 'none';

    // On enlève les données originales (utilisées pour détecter les modifications en édition)
    delete form.dataset.originalDisponibilite;
    
    // FullCalendar nous donne déjà un start / end cohérents avec slotDuration = 30 min
    document.getElementById('date-debut').value = formatDateTimeLocal(start);
    document.getElementById('date-fin').value = formatDateTimeLocal(end);

    // Par défaut, une nouvelle dispo est "DISPONIBLE"
    document.getElementById('statut').value = 'DISPONIBLE';
    toggleFieldsByStatut();
    updatePrixFromService();
    
    // Afficher le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}



function closeModal() {
    const modal = document.getElementById('modal-disponibilite');
    const form = document.getElementById('form-disponibilite');
    const submitBtn = document.getElementById('modal-submit');
    
    modal.classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('modal-error').style.display = 'none';
    
    form.reset();
    document.getElementById('disponibilite-id').value = '';
    
    // Réactiver tous les champs du formulaire (au cas où ils étaient désactivés)
    const formInputs = form.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.disabled = false;
    });
    
    // Réafficher le bouton de soumission
    if (submitBtn) {
        submitBtn.style.display = 'block';
    }
    
    const serviceGroup = document.getElementById('service-id').closest('.form-group');
    const prixGroup = document.getElementById('prix').closest('.form-group');
    serviceGroup.style.display = 'block';
    prixGroup.style.display = 'block';
}

// Affichage des champs selon statut
function toggleFieldsByStatut(preservePrix = false) {
    const statut = document.getElementById('statut').value;
    const serviceGroup = document.getElementById('service-id').closest('.form-group');
    const prixGroup = document.getElementById('prix').closest('.form-group');
    
    if (statut === 'BLOQUE') {
        serviceGroup.style.display = 'none';
        prixGroup.style.display = 'none';
        document.getElementById('service-id').value = '';
        document.getElementById('prix').value = '';
    } else {
        serviceGroup.style.display = 'block';
        prixGroup.style.display = 'block';
        const serviceSelect = document.getElementById('service-id');
        if (!serviceSelect.value) {
            const firstOption = serviceSelect.options[1]; // 0 = "Aucun service spécifique"
            if (firstOption) {
                serviceSelect.value = firstOption.value;
                if (!preservePrix) {
                    updatePrixFromService();
                }
            }
        } else {
            // Ne mettre à jour le prix que si on ne doit pas le préserver (mode création)
            if (!preservePrix) {
                updatePrixFromService();
            }
        }
    }
}

// Mettre à jour le prix selon le service sélectionné
function updatePrixFromService() {
    const serviceSelect = document.getElementById('service-id');
    const prixInput = document.getElementById('prix');
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const prix = selectedOption.getAttribute('data-prix');
        prixInput.value = prix ? parseFloat(prix).toFixed(2) : '';
    } else {
        prixInput.value = '';
    }
}

// Initialiser les événements du modal
function initModal() {
    const modal = document.getElementById('modal-disponibilite');
    const closeBtn = document.getElementById('modal-close');
    const cancelBtn = document.getElementById('modal-cancel');
    const overlay = modal.querySelector('.creneaux-modal-overlay');
    const form = document.getElementById('form-disponibilite');
    const statutSelect = document.getElementById('statut');
    const serviceSelect = document.getElementById('service-id');
    
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    statutSelect.addEventListener('change', toggleFieldsByStatut);
    serviceSelect.addEventListener('change', updatePrixFromService);
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitDisponibilite();
    });
}

// === CRUD Disponibilités ===

function openModalEdit(event) {
    const modal = document.getElementById('modal-disponibilite');
    const form = document.getElementById('form-disponibilite');
    const title = document.getElementById('modal-title');
    const submitBtn = document.getElementById('modal-submit');
    const deleteBtn = document.getElementById('modal-delete');
    
    const id = event.id;
    const start = event.start;
    const end = event.end || event.start;
    const extendedProps = event.extendedProps || {};
    const statut = extendedProps.statut || 'DISPONIBLE';
    const serviceId = extendedProps.service_id || '';
    const prix = extendedProps.prix ?? '';
    const notes = extendedProps.notes || '';
    const hasRendezVous = extendedProps.hasRendezVous === true;
    
    form.reset();
    document.getElementById('disponibilite-id').value = id;
    
    // Si le créneau a un rendez-vous lié, afficher en mode lecture seule
    if (hasRendezVous) {
        title.textContent = 'Détails de la disponibilité (Réservé)';
        submitBtn.style.display = 'none';
        deleteBtn.style.display = 'none';
        
        // Désactiver tous les champs du formulaire
        const formInputs = form.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.disabled = true;
        });
    } else {
        title.textContent = 'Modifier une disponibilité';
        submitBtn.textContent = 'Modifier';
        submitBtn.style.display = 'block';
        
        // Réactiver tous les champs du formulaire
        const formInputs = form.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.disabled = false;
        });
        
        if (statut !== 'RESERVE') {
            deleteBtn.style.display = 'block';
            deleteBtn.onclick = function() {
                deleteDisponibilite(id);
            };
        } else {
            deleteBtn.style.display = 'none';
        }
    }

    const startStr = formatDateTimeLocal(start);
    const endStr = formatDateTimeLocal(end);

    document.getElementById('date-debut').value = startStr;
    document.getElementById('date-fin').value = endStr;
    document.getElementById('service-id').value = serviceId;
    document.getElementById('prix').value = prix !== null && prix !== undefined ? String(prix) : '';
    document.getElementById('statut').value = statut;
    document.getElementById('notes').value = notes;

    // En mode édition, préserver le prix personnalisé (ne pas l'écraser avec le prix du service)
    toggleFieldsByStatut(true);

    const original = {
        date_debut: document.getElementById('date-debut').value || '',
        date_fin: document.getElementById('date-fin').value || '',
        service_id: document.getElementById('service-id').value || '',
        prix: document.getElementById('prix').value || '',
        statut: document.getElementById('statut').value || '',
        notes: document.getElementById('notes').value || ''
    };
    form.dataset.originalDisponibilite = JSON.stringify(original);
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}




function submitDisponibilite() {
    const form = document.getElementById('form-disponibilite');
    const formData = new FormData(form);
    const errorDiv = document.getElementById('modal-error');
    const id = formData.get('id');
    
    const dateDebut = formData.get('date_debut') || '';
    const dateFin = formData.get('date_fin') || '';
    const serviceId = formData.get('service_id') || '';
    const prix = formData.get('prix') || '';
    const statut = formData.get('statut') || '';
    const notes = formData.get('notes') || '';

    // 🔍 Normalisation simple pour la comparaison
    const normalize = (v) => (v ?? '').toString().trim();

    if (id && form.dataset.originalDisponibilite) {
        try {
            const original = JSON.parse(form.dataset.originalDisponibilite);

            const noChange =
                normalize(original.date_debut) === normalize(dateDebut) &&
                normalize(original.date_fin) === normalize(dateFin) &&
                normalize(original.service_id) === normalize(serviceId) &&
                normalize(original.prix) === normalize(prix) &&
                normalize(original.statut) === normalize(statut) &&
                normalize(original.notes) === normalize(notes);

            if (noChange) {
                // Comme si on avait cliqué sur "Fermer"
                errorDiv.style.display = 'none';
                closeModal();
                return;
            }
        } catch (e) {
            console.warn('Impossible de parser les données originales de la disponibilité', e);
        }
    }
    
    const debut = new Date(dateDebut);
    const fin = new Date(dateFin);
    const diffMinutes = getDiffMinutes(debut, fin);
    
    if (diffMinutes < MIN_DURATION_MINUTES) {
        errorDiv.textContent = `La durée minimum doit être de ${MIN_DURATION_MINUTES} minutes`;
        errorDiv.style.display = 'block';
        return;
    }
    
    if (!isSameDay(debut, fin)) {
        errorDiv.textContent = 'Vous ne pouvez créer une disponibilité que dans la même journée';
        errorDiv.style.display = 'block';
        return;
    }
    
    const data = {
        date_debut: formatDateTimeForAPI(dateDebut),
        date_fin: formatDateTimeForAPI(dateFin),
        service_id: serviceId || null,
        prix: prix ? parseFloat(prix) : null,
        statut: statut,
        notes: notes || null
    };
    
    const method = id ? 'PUT' : 'POST';
    if (id) {
        data.id = id;
    }
    
    fetch(DISPONIBILITES_API_URL, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const message = id ? 'Disponibilité modifiée avec succès' : 'Disponibilité créée avec succès';
            showNotification(message, 'success');
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



function updateDisponibilite(event) {
    const id = event.id;
    const start = event.start;
    const end = event.end || event.start;
    const extendedProps = event.extendedProps || {};
    
    const diffMinutes = getDiffMinutes(start, end);
    
    if (diffMinutes < MIN_DURATION_MINUTES) {
        event.revert();
        showNotification(`La durée minimum doit être de ${MIN_DURATION_MINUTES} minutes`, 'error');
        return;
    }
    
    if (!isSameDay(start, end)) {
        event.revert();
        showNotification('Vous ne pouvez créer une disponibilité que dans la même journée', 'error');
        return;
    }
    
    const data = {
        id: id,
        date_debut: formatDateForAPI(start),
        date_fin: formatDateForAPI(end),
        statut: extendedProps.statut || null,
        service_id: extendedProps.service_id || null,
        prix: extendedProps.prix || null,
        notes: extendedProps.notes || null
    };
    
    fetch(DISPONIBILITES_API_URL, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (!result.success) {
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

// Variable globale pour stocker l'ID de la disponibilité à supprimer
let pendingSuppressionDisponibiliteId = null;

function deleteDisponibilite(id) {
    // Récupérer les informations de la disponibilité depuis le calendrier
    const event = window.calendar.getEventById(id);
    if (!event) {
        showNotification('Impossible de trouver la disponibilité', 'error');
        return;
    }

    const extendedProps = event.extendedProps || {};
    const start = event.start;
    const end = event.end || event.start;

    // Formater les dates
    const dateDebut = formatDateTimeLocal(start);
    const dateFin = formatDateTimeLocal(end);
    const serviceNom = extendedProps.service_nom || 'Aucun service spécifique';

    // Ouvrir le modal de confirmation
    openSuppressionModal(id, dateDebut, dateFin, serviceNom);
}

// Formater une date pour l'affichage lisible (ex: "15 janvier 2025 à 14:30")
function formatDateForDisplay(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        const optionsDate = { day: 'numeric', month: 'long', year: 'numeric' };
        const optionsTime = { hour: '2-digit', minute: '2-digit', hour12: false };
        
        const dateStr = date.toLocaleDateString('fr-FR', optionsDate);
        const timeStr = date.toLocaleTimeString('fr-FR', optionsTime);
        
        return `${dateStr} à ${timeStr}`;
    } catch (error) {
        console.error('Erreur lors du formatage de la date:', error);
        return dateString;
    }
}

function openSuppressionModal(id, dateDebut, dateFin, serviceNom) {
    const modal = document.getElementById('suppressionModal');
    if (!modal) {
        console.error('Modal de suppression non trouvé');
        return;
    }

    // Remplir les informations avec formatage lisible
    document.getElementById('suppressionDateDebut').textContent = formatDateForDisplay(dateDebut) || '-';
    document.getElementById('suppressionDateFin').textContent = formatDateForDisplay(dateFin) || '-';
    document.getElementById('suppressionService').textContent = serviceNom || '-';

    // Stocker l'ID de la disponibilité à supprimer
    pendingSuppressionDisponibiliteId = id;

    // Ouvrir le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSuppressionModal() {
    const modal = document.getElementById('suppressionModal');
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';
    pendingSuppressionDisponibiliteId = null;
}

function confirmSuppression() {
    if (!pendingSuppressionDisponibiliteId) return;

    const id = pendingSuppressionDisponibiliteId;
    
    fetch(DISPONIBILITES_API_URL, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Disponibilité supprimée avec succès', 'success');
            window.calendar.refetchEvents();
            closeModal();
            closeSuppressionModal();
        } else {
            showNotification(result.error || 'Erreur lors de la suppression de la disponibilité', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
    });
}

// Initialiser les événements du modal de suppression
function initSuppressionModal() {
    const modal = document.getElementById('suppressionModal');
    if (!modal) return;

    const cancelBtn = document.getElementById('suppressionCancelBtn');
    const confirmBtn = document.getElementById('suppressionConfirmBtn');
    const overlay = modal.querySelector('.confirmation-modal-overlay');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeSuppressionModal);
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmSuppression);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSuppressionModal);
    }

    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeSuppressionModal();
        }
    });
}

// === Notifications ===

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
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('notification-out');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// === Gestion du modal de service ===

const TUTEURS_API_URL = 'api/tuteurs.php';


function closeServiceModal() {
    const modal = document.getElementById('modal-service');
    if (modal) {
        modal.classList.remove('active');
    }
    const form = document.getElementById('form-service');
    if (form) {
        form.reset();
    }
    const errorDiv = document.getElementById('modal-service-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

async function submitServiceForm(e) {
    e.preventDefault();
    
    const form = document.getElementById('form-service');
    const submitBtn = document.getElementById('modal-service-submit');
    const serviceId = document.getElementById('service-id-edit').value;
    const errorDiv = document.getElementById('modal-service-error');
    
    if (!serviceId) {
        if (errorDiv) {
            errorDiv.textContent = 'ID du service manquant';
            errorDiv.style.display = 'block';
        }
        return;
    }
    
    const data = {
        resource: 'service',
        id: serviceId,
        description: document.getElementById('service-description-edit').value.trim(),
        nom: document.getElementById('service-nom-edit').value.trim(),
        prix: parseFloat(document.getElementById('service-prix-edit').value) || null,
        duree_minute: parseInt(document.getElementById('service-duree-edit').value) || null
    };
    
    // Désactiver le bouton pendant l'envoi
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enregistrement...';
    }
    
    try {
        const response = await fetch(TUTEURS_API_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const text = await response.text();
        if (!text || text.trim() === '') {
            throw new Error('Réponse vide du serveur');
        }
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            throw new Error('Réponse invalide du serveur');
        }
        
        if (!response.ok) {
            throw new Error(result.error || 'Erreur lors de l\'enregistrement');
        }
        
        // Fermer le modal
        closeServiceModal();
        
        // Recharger la page pour mettre à jour les informations
        window.location.reload();
        
    } catch (error) {
        if (errorDiv) {
            errorDiv.textContent = error.message || 'Erreur lors de l\'enregistrement du service';
            errorDiv.style.display = 'block';
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enregistrer';
        }
    }
}

// Initialiser le modal de service
function initServiceModal() {
    const btnModifier = document.getElementById('btnModifierService');
    const modal = document.getElementById('modal-service');
    const form = document.getElementById('form-service');
    const closeBtn = document.getElementById('modal-service-close');
    const cancelBtn = document.getElementById('modal-service-cancel');
    
    if (!btnModifier || !modal || !form) return;
    
    // Ouvrir le modal
    btnModifier.addEventListener('click', () => {
        // Récupérer les données du service depuis les data attributes
        const serviceId = btnModifier.getAttribute('data-service-id');
        const serviceNom = btnModifier.getAttribute('data-service-nom');
        const serviceDescription = btnModifier.getAttribute('data-service-description');
        const servicePrix = btnModifier.getAttribute('data-service-prix');
        const serviceDuree = btnModifier.getAttribute('data-service-duree');
        
        if (!serviceId) {
            showNotification('Aucun service trouvé', 'error');
            return;
        }
        
        // Remplir le formulaire
        document.getElementById('service-id-edit').value = serviceId;
        document.getElementById('service-nom-edit').value = serviceNom || '';
        document.getElementById('service-description-edit').value = serviceDescription || '';
        document.getElementById('service-prix-edit').value = servicePrix || '';
        document.getElementById('service-duree-edit').value = serviceDuree || 60;
        
        // Ouvrir le modal
        modal.classList.add('active');
    });
    
    // Gérer la soumission du formulaire
    form.addEventListener('submit', submitServiceForm);
    
    // Gérer la fermeture du modal
    if (closeBtn) {
        closeBtn.addEventListener('click', closeServiceModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeServiceModal);
    }
    
    // Fermer en cliquant sur l'overlay
    const overlay = modal.querySelector('.creneaux-modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeServiceModal);
    }
}

// Initialiser le modal de service au chargement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initServiceModal);
} else {
    initServiceModal();
}