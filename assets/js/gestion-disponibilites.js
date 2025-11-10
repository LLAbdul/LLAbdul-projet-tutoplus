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
            // TODO: Ouvrir modal pour créer une disponibilité
            console.log('Sélection:', selectInfo);
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
});

