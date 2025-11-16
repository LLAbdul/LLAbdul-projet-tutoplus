// Script pour gérer le modal de sélection de créneaux

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('creneauxModal');
    const modalBody = document.getElementById('creneauxModalBody');
    const modalClose = document.querySelector('.creneaux-modal-close');
    const modalOverlay = document.querySelector('.creneaux-modal-overlay');
    const btnPlusCreneaux = document.querySelectorAll('.btn-plus-creneaux');

    if (!modal || !modalBody) return;

    let currentServiceId = null;

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    window.closeCreneauxModal = closeModal;

    btnPlusCreneaux.forEach(btn => {
        btn.addEventListener('click', () => {
            const serviceId = btn.getAttribute('data-service-id');
            if (!serviceId) {
                console.error('Service ID manquant sur le bouton');
                return;
            }
            openModal(serviceId);
        });
    });

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    async function openModal(serviceId) {
        currentServiceId = serviceId;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        modalBody.innerHTML = '<div style="text-align: center; padding: 3rem;">Chargement...</div>';

        try {
            const data = await fetchCreneaux(serviceId);

            if (data.error) {
                modalBody.innerHTML = `<div style="text-align: center; padding: 3rem; color: var(--accent-color);">${data.error}</div>`;
                return;
            }

            renderCreneaux(data.service, data.creneaux);
        } catch (error) {
            console.error('Erreur lors du chargement des créneaux:', error);
            modalBody.innerHTML = '<div style="text-align: center; padding: 3rem; color: var(--accent-color);">Erreur lors du chargement des créneaux</div>';
        }
    }

    async function fetchCreneaux(serviceId) {
        const response = await fetch(`api/creneaux.php?service_id=${encodeURIComponent(serviceId)}`);
        const data = await response.json();
        return data;
    }

    window.refreshCreneaux = async function () {
        if (!currentServiceId || !modal.classList.contains('active')) return;

        try {
            const data = await fetchCreneaux(currentServiceId);

            if (data.error) {
                console.error('Erreur lors du rafraîchissement:', data.error);
                return;
            }

            renderCreneaux(data.service, data.creneaux);
        } catch (error) {
            console.error('Erreur lors du rafraîchissement des créneaux:', error);
        }
    };

    function renderCreneaux(service, creneauxParDate) {
        const dates = Object.keys(creneauxParDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const content = `
            <div class="creneaux-container">
                <div class="creneaux-left">
                    <div class="calendar-widget">
                        <div class="calendar-header">
                            <button class="calendar-nav-btn" id="prevMonth">‹</button>
                            <h2 class="calendar-month-year" id="currentMonthYear">${getMonthYear(today)}</h2>
                            <button class="calendar-nav-btn" id="nextMonth">›</button>
                        </div>
                        <div class="calendar-weekdays">
                            <span>Lu</span>
                            <span>Ma</span>
                            <span>Me</span>
                            <span>Je</span>
                            <span>Ve</span>
                            <span>Sa</span>
                            <span>Di</span>
                        </div>
                        <div class="calendar-days" id="calendarDays"></div>
                    </div>

                    <div class="scheduling-summary">
                        <h3 class="summary-title">Réservation</h3>
                        <div class="summary-selected" id="summarySelected" style="display: none;">
                            <span class="selected-date-time" id="selectedDateTime"></span>
                            <button class="remove-selection" id="removeSelection">×</button>
                        </div>
                        <div class="summary-empty" id="summaryEmpty">
                            Aucun créneau sélectionné
                        </div>
                        <div class="summary-notification">
                            <div class="notification-info">
                                <span class="notification-text">Me notifier 1 jour avant</span>
                            </div>
                            <label class="notification-toggle">
                                <input type="checkbox" id="notificationToggle">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="creneaux-right">
                    <div class="time-slots-section">
                        <h2 class="time-slots-title">Choisir une heure</h2>
                        <div class="time-slots-list" id="timeSlotsList">
                            ${
                                dates.length === 0
                                    ? '<div class="no-slots"><p>Aucun créneau disponible pour ce service.</p></div>'
                                    : generateTimeSlotsHTML(creneauxParDate)
                            }
                        </div>
                    </div>

                    <div class="creneaux-navigation">
                        <button class="btn-back" onclick="closeCreneauxModal()">Retour</button>
                        <button class="btn-next" id="btnNext" disabled>Étape suivante</button>
                    </div>
                </div>
            </div>
        `;

        modalBody.innerHTML = content;

        initCalendar(dates, today);
        initTimeSlots();
    }

    function generateTimeSlotsHTML(creneauxParDate) {
        let html = '';

        Object.keys(creneauxParDate).sort().forEach(date => {
            const creneaux = creneauxParDate[date];
            const dateObj = new Date(date);
            const dateFormatted = dateObj.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });

            html += `
                <div class="date-group" data-date="${date}">
                    <h3 class="date-group-title">${dateFormatted}</h3>
                    <div class="time-slots-grid">
                        ${creneaux
                            .map(creneau => {
                                const heureDebut = formatTime(creneau.heure_debut);
                                const heureFin = formatTime(creneau.heure_fin);
                                return `
                                    <label class="time-slot-item" 
                                           data-creneau-id="${creneau.id}" 
                                           data-date="${date}">
                                        <input type="radio" 
                                               name="creneau" 
                                               value="${creneau.id}" 
                                               class="time-slot-radio" 
                                               data-date-debut="${creneau.date_debut}"
                                               data-date-fin="${creneau.date_fin}">
                                        <span class="time-slot-text">${heureDebut} - ${heureFin}</span>
                                    </label>
                                `;
                            })
                            .join('')}
                    </div>
                </div>
            `;
        });

        return html;
    }

    function formatTime(time24) {
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }

    function getMonthYear(date) {
        const months = [
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ];
        return `${months[date.getMonth()]} ${date.getFullYear()}`;
    }

    function initCalendar(availableDates, currentDate) {
        const calendarDays = document.getElementById('calendarDays');
        const currentMonthYear = document.getElementById('currentMonthYear');
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');

        let currentMonth = new Date(currentDate);

        function renderCalendar() {
            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();

            currentMonthYear.textContent = getMonthYear(currentMonth);

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const firstDayOfWeek = firstDay.getDay() === 0 ? 7 : firstDay.getDay();
            const daysInMonth = lastDay.getDate();

            calendarDays.innerHTML = '';

            for (let i = 1; i < firstDayOfWeek; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'calendar-day';
                calendarDays.appendChild(emptyDay);
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            for (let day = 1; day <= daysInMonth; day++) {
                const dayDate = new Date(year, month, day);
                const dayDateStr = formatDateForComparison(dayDate);
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = day;
                dayElement.dataset.date = dayDateStr;

                if (dayDateStr === formatDateForComparison(today)) {
                    dayElement.classList.add('calendar-day-today');
                }

                if (dayDate < today) {
                    dayElement.classList.add('calendar-day-disabled');
                } else if (availableDates.includes(dayDateStr)) {
                    dayElement.addEventListener('click', () => {
                        selectDateInCalendar(dayDateStr, dayElement);
                        filterTimeSlotsByDate(dayDateStr);
                    });
                } else {
                    dayElement.classList.add('calendar-day-disabled');
                }

                calendarDays.appendChild(dayElement);
            }
        }

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', () => {
                currentMonth.setMonth(currentMonth.getMonth() - 1);
                renderCalendar();
            });
        }

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', () => {
                currentMonth.setMonth(currentMonth.getMonth() + 1);
                renderCalendar();
            });
        }

        renderCalendar();
    }

    function formatDateForComparison(date) {
        const year = date.getFullYear();
        const month = `${date.getMonth() + 1}`.padStart(2, '0');
        const day = `${date.getDate()}`.padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function selectDateInCalendar(dateStr, dayElement) {
        document
            .querySelectorAll('.calendar-day-selected')
            .forEach(el => el.classList.remove('calendar-day-selected'));

        dayElement.classList.add('calendar-day-selected');
    }

    function filterTimeSlotsByDate(dateStr) {
        document.querySelectorAll('.date-group').forEach(group => {
            const groupDate = group.getAttribute('data-date');
            group.style.display = groupDate === dateStr ? 'block' : 'none';
        });
    }

    function initTimeSlots() {
        const timeSlotRadios = document.querySelectorAll('.time-slot-radio');
        const summarySelected = document.getElementById('summarySelected');
        const summaryEmpty = document.getElementById('summaryEmpty');
        const selectedDateTime = document.getElementById('selectedDateTime');
        const removeSelection = document.getElementById('removeSelection');
        const btnNext = document.getElementById('btnNext');

        let selectedCreneau = null;

        timeSlotRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (!radio.checked) return;
                const timeSlotItem = radio.closest('.time-slot-item');
                if (!timeSlotItem) return;
                selectCreneau(timeSlotItem, radio);
            });
        });

        function selectCreneau(item, radio) {
            document
                .querySelectorAll('.time-slot-item')
                .forEach(el => el.classList.remove('selected'));

            item.classList.add('selected');
            selectedCreneau = item;

            const dateDebut = radio.getAttribute('data-date-debut');
            const dateFin = radio.getAttribute('data-date-fin');

            updateSummary(dateDebut, dateFin);

            if (btnNext) {
                btnNext.disabled = false;
            }

            const creneauDate = item.getAttribute('data-date');
            if (creneauDate) {
                const dayElement = document.querySelector(`.calendar-day[data-date="${creneauDate}"]`);
                if (dayElement && !dayElement.classList.contains('calendar-day-disabled')) {
                    selectDateInCalendar(creneauDate, dayElement);
                }
            }
        }

        function updateSummary(dateDebut, dateFin) {
            const dateDebutObj = new Date(dateDebut);
            const dateFinObj = new Date(dateFin);

            const dateStr = dateDebutObj.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            const heureDebut = dateDebutObj.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            const heureFin = dateFinObj.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            if (selectedDateTime) {
                selectedDateTime.textContent = `${dateStr} ${heureDebut} - ${heureFin}`;
            }

            if (summarySelected && summaryEmpty) {
                summarySelected.style.display = 'flex';
                summaryEmpty.style.display = 'block';
                summaryEmpty.style.display = 'none';
            }
        }

        if (removeSelection) {
            removeSelection.addEventListener('click', () => {
                document
                    .querySelectorAll('.time-slot-item')
                    .forEach(el => {
                        el.classList.remove('selected');
                        const radio = el.querySelector('.time-slot-radio');
                        if (radio) radio.checked = false;
                    });

                document
                    .querySelectorAll('.calendar-day-selected')
                    .forEach(el => el.classList.remove('calendar-day-selected'));

                selectedCreneau = null;

                if (summarySelected && summaryEmpty) {
                    summarySelected.style.display = 'none';
                    summaryEmpty.style.display = 'block';
                }

                if (btnNext) {
                    btnNext.disabled = true;
                }

                document
                    .querySelectorAll('.date-group')
                    .forEach(group => {
                        group.style.display = 'block';
                    });
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                if (!selectedCreneau) {
                    alert('Veuillez sélectionner un créneau avant de continuer');
                    return;
                }

                if (btnNext.disabled) return;

                const radio = selectedCreneau.querySelector('.time-slot-radio');
                const creneauId = radio?.value;

                if (!creneauId) {
                    alert('Erreur : Aucun créneau sélectionné');
                    return;
                }

                reserverCreneau(creneauId);
            });
        }
    }
});

/*
    Réserve un créneau en appelant l'API
*/
async function reserverCreneau(creneauId) {
    if (!creneauId || creneauId.trim() === '') {
        showReservationError('Aucun créneau sélectionné');
        return;
    }

    const btnNext = document.getElementById('btnNext');
    let originalText = '';

    if (btnNext) {
        originalText = btnNext.textContent || 'Étape suivante';
        btnNext.disabled = true;
        btnNext.textContent = 'Réservation en cours...';
    }

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);

        const notificationToggle = document.getElementById('notificationToggle');
        const notificationEnabled = notificationToggle ? notificationToggle.checked : false;

        const response = await fetch('api/reservations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                disponibilite_id: creneauId,
                notification_enabled: notificationEnabled
            }),
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        let data;
        try {
            data = await response.json();
        } catch {
            throw new Error('Erreur lors de la lecture de la réponse du serveur');
        }

        if (!response.ok) {
            throw new Error(data.error || 'Erreur lors de la réservation');
        }

        if (!data.disponibilite) {
            throw new Error('Données de réservation manquantes dans la réponse');
        }

        const dispo = data.disponibilite;

        if (!dispo.date_debut || !dispo.date_fin) {
            throw new Error('Données de réservation incomplètes');
        }

        const dateDebut = new Date(dispo.date_debut);
        const dateFin = new Date(dispo.date_fin);

        if (isNaN(dateDebut.getTime()) || isNaN(dateFin.getTime())) {
            throw new Error('Format de date invalide');
        }

        const heureDebut = dateDebut.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });

        const heureFin = dateFin.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });

        const confirmationData = {
            date: formatDateForConfirmation(dispo.date_debut.split(' ')[0]),
            heure: `${heureDebut} - ${heureFin}`,
            tuteur: `${dispo.tuteur_prenom || ''} ${dispo.tuteur_nom || ''}`.trim() || 'Tuteur non spécifié',
            service: dispo.service_nom || 'Service général',
            notificationEnabled
        };

        fillConfirmationData(confirmationData);

        if (typeof window.refreshCreneaux === 'function') {
            await window.refreshCreneaux();
        }

        if (typeof window.closeCreneauxModal === 'function') {
            window.closeCreneauxModal();
        }

        openConfirmationModal();
    } catch (error) {
        console.error('Erreur lors de la réservation:', error);

        let errorMessage = error.message;

        if (error.name === 'AbortError') {
            errorMessage = 'La requête a pris trop de temps. Veuillez réessayer.';
        } else if (
            error.message.includes('Failed to fetch') ||
            error.message.includes('NetworkError')
        ) {
            errorMessage = 'Erreur de connexion. Vérifiez votre connexion internet et réessayez.';
        } else if (!errorMessage || errorMessage === 'Error') {
            errorMessage = 'Une erreur inattendue s\'est produite. Veuillez réessayer.';
        }

        showReservationError(errorMessage);
    } finally {
        const btnNextStillExists = document.getElementById('btnNext');

        if (btnNextStillExists && btnNextStillExists.parentElement) {
            const modal = document.getElementById('creneauxModal');
            if (modal && modal.classList.contains('active')) {
                btnNextStillExists.disabled = false;
                btnNextStillExists.textContent = originalText || 'Étape suivante';
            }
        }
    }
}

/*
    Affiche un message d'erreur pour la réservation
*/
function showReservationError(message) {
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.className = 'notification notification-error';
    notification.style.cssText = `
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 1rem;
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        animation: slideInRight 0.3s ease;
    `;

    notification.innerHTML = `
        <span style="font-size: 1.5rem; flex-shrink: 0;">✕</span>
        <span style="flex: 1; font-size: 0.9375rem; font-weight: 500;">${message}</span>
        <button type="button" style="
            background: transparent;
            border: none;
            color: inherit;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
        ">&times;</button>
    `;

    const closeBtn = notification.querySelector('button');
    closeBtn.addEventListener('click', () => notification.remove());

    container.appendChild(notification);

    setTimeout(() => {
        if (!notification.parentElement) return;
        notification.style.animation = 'slideInRight 0.3s reverse';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
