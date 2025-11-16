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
        // Configuration des vues
        views: {
            dayGridMonth: {
                // Éviter les chevauchements d'événements en mode mois
                eventMaxStack: 3,
                eventOrder: 'start,-duration,title',
                // Format du titre pour la vue mois : "Novembre 2025"
                titleFormat: function(arg) {
                    try {
                        if (!arg) {
                            return '';
                        }
                        // FullCalendar passe des objets DateMarker, utiliser marker ou toDate()
                        let dateObj = null;
                        if (arg.start) {
                            dateObj = arg.start.marker || (typeof arg.start.toDate === 'function' ? arg.start.toDate() : arg.start);
                        } else if (arg.date) {
                            dateObj = arg.date.marker || (typeof arg.date.toDate === 'function' ? arg.date.toDate() : arg.date);
                        }
                        
                        if (!dateObj) {
                            return '';
                        }
                        
                        const date = new Date(dateObj);
                        if (isNaN(date.getTime())) {
                            return '';
                        }
                        
                        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                                      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                        const month = months[date.getMonth()];
                        const year = date.getFullYear();
                        return month + ' ' + year;
                    } catch (e) {
                        console.error('Erreur titleFormat mois:', e);
                        return '';
                    }
                },
                // Format des en-têtes de colonnes pour la vue mois
                columnHeaderFormat: { weekday: 'short' }
            },
            timeGridWeek: {
                // Format du titre pour la vue semaine : "16 – 22 novembre 2025"
                titleFormat: function(arg) {
                    try {
                        if (!arg) {
                            return '';
                        }
                        // FullCalendar passe des objets DateMarker, utiliser marker ou toDate()
                        let startObj = null;
                        let endObj = null;
                        
                        if (arg.start) {
                            startObj = arg.start.marker || (typeof arg.start.toDate === 'function' ? arg.start.toDate() : arg.start);
                        }
                        if (arg.end) {
                            endObj = arg.end.marker || (typeof arg.end.toDate === 'function' ? arg.end.toDate() : arg.end);
                        }
                        
                        if (!startObj || !endObj) {
                            return '';
                        }
                        
                        const startDate = new Date(startObj);
                        const endDate = new Date(endObj);
                        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                            return '';
                        }
                        
                        const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                                      'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                        const startDay = startDate.getDate();
                        const endDay = endDate.getDate();
                        const month = months[startDate.getMonth()];
                        const year = startDate.getFullYear();
                        return startDay + ' – ' + endDay + ' ' + month + ' ' + year;
                    } catch (e) {
                        console.error('Erreur titleFormat semaine:', e);
                        return '';
                    }
                },
                // Format des en-têtes de colonnes pour la vue semaine
                columnHeaderFormat: { weekday: 'short', day: 'numeric' }
            },
            timeGridDay: {
                // Format du titre pour la vue jour : "16 novembre 2025"
                titleFormat: function(arg) {
                    try {
                        if (!arg) {
                            return '';
                        }
                        // FullCalendar passe des objets DateMarker, utiliser marker ou toDate()
                        let dateObj = null;
                        if (arg.start) {
                            dateObj = arg.start.marker || (typeof arg.start.toDate === 'function' ? arg.start.toDate() : arg.start);
                        } else if (arg.date) {
                            dateObj = arg.date.marker || (typeof arg.date.toDate === 'function' ? arg.date.toDate() : arg.date);
                        }
                        
                        if (!dateObj) {
                            return '';
                        }
                        
                        const date = new Date(dateObj);
                        if (isNaN(date.getTime())) {
                            return '';
                        }
                        
                        const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                                      'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                        const day = date.getDate();
                        const month = months[date.getMonth()];
                        const year = date.getFullYear();
                        return day + ' ' + month + ' ' + year;
                    } catch (e) {
                        console.error('Erreur titleFormat jour:', e);
                        return '';
                    }
                },
                // Format des en-têtes de colonnes pour la vue jour
                columnHeaderFormat: { weekday: 'short', day: 'numeric' }
            },
            listWeek: {
                // Vue liste pour voir les réservations
                // Format du titre pour la vue liste : "16 – 22 novembre 2025"
                titleFormat: function(arg) {
                    try {
                        if (!arg) {
                            return '';
                        }
                        // FullCalendar passe des objets DateMarker, utiliser marker ou toDate()
                        let startObj = null;
                        let endObj = null;
                        
                        if (arg.start) {
                            startObj = arg.start.marker || (typeof arg.start.toDate === 'function' ? arg.start.toDate() : arg.start);
                        }
                        if (arg.end) {
                            endObj = arg.end.marker || (typeof arg.end.toDate === 'function' ? arg.end.toDate() : arg.end);
                        }
                        
                        if (!startObj || !endObj) {
                            return '';
                        }
                        
                        const startDate = new Date(startObj);
                        const endDate = new Date(endObj);
                        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                            return '';
                        }
                        
                        const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                                      'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                        const startDay = startDate.getDate();
                        const endDay = endDate.getDate();
                        const month = months[startDate.getMonth()];
                        const year = startDate.getFullYear();
                        return startDay + ' – ' + endDay + ' ' + month + ' ' + year;
                    } catch (e) {
                        console.error('Erreur titleFormat liste:', e);
                        return '';
                    }
                },
                listDayFormat: { weekday: 'long', day: 'numeric', month: 'long' },
                listDaySideFormat: false
            }
        },
        
        // Empêcher la sélection sur plusieurs jours
        selectAllow: function(selectInfo) {
            const startDay = selectInfo.start.toDateString();
            const endDay = selectInfo.end.toDateString();
            return startDay === endDay;
        },
        
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
                    // S'assurer que les couleurs sont bien définies pour tous les événements
                    const events = data.map(event => {
                        // Utiliser la couleur de l'API ou déterminer selon le statut
                        let color = event.color;
                        if (!color) {
                            const statut = event.extendedProps?.statut;
                            if (statut === 'RESERVE') {
                                color = '#dc3545'; // Rouge
                            } else if (statut === 'BLOQUE') {
                                color = '#6c757d'; // Gris
                            } else {
                                color = '#28a745'; // Vert
                            }
                        }
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
            // La validation est déjà faite par selectAllow, mais on vérifie quand même par sécurité
            const startDay = selectInfo.start.toDateString();
            const endDay = selectInfo.end.toDateString();
            
            if (startDay !== endDay) {
                calendar.unselect();
                return;
            }
            
            openModalCreate(selectInfo.start, selectInfo.end);
            calendar.unselect();
        },
        
        // Gérer le clic sur une date/heure (pour mobile/tablette)
        dateClick: function(info) {
            // Sur mobile/tablette, ouvrir le modal avec une durée par défaut (1 heure)
            if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                const start = info.date;
                const end = new Date(start.getTime() + 60 * 60 * 1000); // +1 heure
                openModalCreate(start, end);
            }
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
        },
        
        // Forcer l'application des couleurs après le rendu des événements (uniquement en mode mois)
        eventDidMount: function(info) {
            // Appliquer les couleurs uniquement en mode mois (dayGridMonth)
            if (info.view.type === 'dayGridMonth') {
                const event = info.event;
                let color = event.backgroundColor || event.borderColor || event.extendedProps?.color;
                if (!color) {
                    // Fallback selon le statut
                    const statut = event.extendedProps?.statut;
                    if (statut === 'RESERVE') {
                        color = '#dc3545'; // Rouge
                    } else if (statut === 'BLOQUE') {
                        color = '#6c757d'; // Gris
                    } else {
                        color = '#28a745'; // Vert
                    }
                }
                if (color && info.el) {
                    // Forcer l'application des couleurs via les styles inline
                    info.el.style.setProperty('background-color', color, 'important');
                    info.el.style.setProperty('border-color', color, 'important');
                    info.el.style.setProperty('color', '#ffffff', 'important');
                    // S'assurer que l'élément est visible
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
    
    // Définir le statut par défaut et gérer l'affichage des champs
    document.getElementById('statut').value = 'DISPONIBLE';
    toggleFieldsByStatut();
    
    // Pré-remplir le service et le prix par défaut
    updatePrixFromService();
    
    // Afficher le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fonction pour fermer le modal
function closeModal() {
    const modal = document.getElementById('modal-disponibilite');
    const form = document.getElementById('form-disponibilite');
    
    modal.classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('modal-error').style.display = 'none';
    
    // Réinitialiser le formulaire et réafficher tous les champs
    form.reset();
    document.getElementById('disponibilite-id').value = '';
    const serviceGroup = document.getElementById('service-id').closest('.form-group');
    const prixGroup = document.getElementById('prix').closest('.form-group');
    serviceGroup.style.display = 'block';
    prixGroup.style.display = 'block';
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

// Fonction pour gérer l'affichage des champs selon le statut
function toggleFieldsByStatut() {
    const statut = document.getElementById('statut').value;
    const serviceGroup = document.getElementById('service-id').closest('.form-group');
    const prixGroup = document.getElementById('prix').closest('.form-group');
    
    if (statut === 'BLOQUE') {
        // Cacher et désactiver les champs service et prix
        serviceGroup.style.display = 'none';
        prixGroup.style.display = 'none';
        document.getElementById('service-id').value = '';
        document.getElementById('prix').value = '';
    } else {
        // Afficher les champs service et prix
        serviceGroup.style.display = 'block';
        prixGroup.style.display = 'block';
        // Si le service est vide, réinitialiser avec le service par défaut
        const serviceSelect = document.getElementById('service-id');
        if (!serviceSelect.value) {
            // Sélectionner le premier service (service par défaut)
            const firstOption = serviceSelect.options[1]; // Index 0 est "Aucun service spécifique"
            if (firstOption) {
                serviceSelect.value = firstOption.value;
                updatePrixFromService();
            }
        } else {
            // Mettre à jour le prix selon le service sélectionné
            updatePrixFromService();
        }
    }
}

// Fonction pour mettre à jour le prix selon le service sélectionné
function updatePrixFromService() {
    const serviceSelect = document.getElementById('service-id');
    const prixInput = document.getElementById('prix');
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const prix = selectedOption.getAttribute('data-prix');
        if (prix) {
            prixInput.value = parseFloat(prix).toFixed(2);
        }
    } else {
        prixInput.value = '';
    }
}

// Fonction pour initialiser les événements du modal
function initModal() {
    const modal = document.getElementById('modal-disponibilite');
    const closeBtn = document.getElementById('modal-close');
    const cancelBtn = document.getElementById('modal-cancel');
    const overlay = modal.querySelector('.creneaux-modal-overlay');
    const form = document.getElementById('form-disponibilite');
    const statutSelect = document.getElementById('statut');
    const serviceSelect = document.getElementById('service-id');
    
    // Fermer le modal
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    // Gérer l'affichage des champs selon le statut
    statutSelect.addEventListener('change', toggleFieldsByStatut);
    
    // Mettre à jour le prix quand le service change
    serviceSelect.addEventListener('change', updatePrixFromService);
    
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
    
    // Gérer l'affichage des champs selon le statut
    toggleFieldsByStatut();
    
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
    
    // Validation : vérifier que les dates sont dans la même journée
    const startDay = debut.toDateString();
    const endDay = fin.toDateString();
    
    if (startDay !== endDay) {
        errorDiv.textContent = 'Vous ne pouvez créer une disponibilité que dans la même journée';
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
    
    // Validation : vérifier que les dates sont dans la même journée
    const startDay = start.toDateString();
    const endDay = end.toDateString();
    
    if (startDay !== endDay) {
        // Annuler le changement si les dates sont sur plusieurs jours
        event.revert();
        showNotification('Vous ne pouvez créer une disponibilité que dans la même journée', 'error');
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


