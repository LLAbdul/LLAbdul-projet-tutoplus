// === Constantes ===

const RENDEZ_VOUS_API_URL = 'api/rendez-vous.php';

// État global pour le filtrage
let allRendezVous = [];
let currentFilter = 'all';

// === Helpers génériques ===

// Retourne un objet Date valide ou null
function toValidDate(dateString) {
    if (!dateString) return null;
    const d = new Date(dateString);
    return isNaN(d.getTime()) ? null : d;
}

// Différence en ms (utile si besoin d'étendre)
function compareDatesDesc(a, b) {
    return b - a; // plus récent en premier
}

// Formate une date pour l'affichage (ex: "15 janvier 2025")
function formatDateForHistorique(dateString) {
    const date = toValidDate(dateString);
    if (!date) return '-';

    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

// Formate une heure pour l'affichage (ex: "14:30")
function formatTimeForHistorique(dateString) {
    const date = toValidDate(dateString);
    if (!date) return '-';

    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
}

// Formate une durée en minutes (ex: "1h 30min")
function formatDuration(minutes) {
    if (minutes == null || minutes < 0) return '-';

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hours === 0) return `${mins}min`;
    if (mins === 0)  return `${hours}h`;
    return `${hours}h ${mins}min`;
}

// Mappings statut -> label / classe CSS
const STATUT_LABELS = {
    A_VENIR:    'À venir',
    EN_COURS:   'En cours',
    TERMINE:    'Terminé',
    ANNULE:     'Annulé',
    REPORTE:    'Reporté',
    EN_ATTENTE: 'En attente',
    REFUSEE:    'Refusée'
};

const STATUT_CLASSES = {
    A_VENIR:    'a-venir',
    EN_COURS:   'en-cours',
    TERMINE:    'termine',
    ANNULE:     'annule',
    REPORTE:    'reporte',
    EN_ATTENTE: 'en-attente',
    REFUSEE:    'refusee'
};

function formatStatut(statut) {
    return STATUT_LABELS[statut] || statut || '';
}

function formatStatutClass(statut) {
    return STATUT_CLASSES[statut] || '';
}

// Formate un prix (ex: "25,00 $")
function formatPrice(price) {
    if (price == null || price === '') return '-';

    const numPrice = typeof price === 'string' ? parseFloat(price) : price;
    if (isNaN(numPrice)) return String(price);

    return numPrice.toFixed(2).replace('.', ',') + ' $';
}

// Échappe les caractères HTML pour éviter les injections XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Trie les rendez-vous par date (plus récent en premier)
function sortRendezVousByDate(rendezVous) {
    return [...rendezVous].sort((a, b) => {
        const dateA = toValidDate(a.date_heure) || 0;
        const dateB = toValidDate(b.date_heure) || 0;
        return compareDatesDesc(dateA, dateB);
    });
}

// === DOM & affichage ===

// Récupère tous les éléments de l'UI liés à l'historique
function getHistoriqueElements() {
    return {
        loadingIndicator: document.getElementById('loadingIndicator'),
        errorMessage:     document.getElementById('errorMessage'),
        errorText:        document.getElementById('errorText'),
        noRendezVous:     document.getElementById('noRendezVous'),
        rendezVousList:   document.getElementById('rendezVousList')
    };
}

// Filtre les rendez-vous selon le statut sélectionné
function filterRendezVous(rendezVous, filter) {
    if (filter === 'all') {
        return rendezVous;
    }

    return rendezVous.filter(rv => {
        if (filter === 'A_VENIR') {
            // Pour "À venir", on veut les rendez-vous avec statut A_VENIR
            // ou EN_COURS qui sont dans le futur
            const rvDate = toValidDate(rv.date_heure);
            const now = new Date();

            if (rv.statut === 'A_VENIR') {
                return true;
            }
            if (rv.statut === 'EN_COURS' && rvDate && rvDate > now) {
                return true;
            }
            return false;
        }

        return rv.statut === filter;
    });
}

// Affiche les rendez-vous dans le DOM
function displayRendezVous(rendezVous) {
    const { rendezVousList, noRendezVous } = getHistoriqueElements();
    if (!rendezVousList || !noRendezVous) return;

    rendezVousList.innerHTML = '';

    // Filtrer selon le filtre actif
    const filteredRendezVous = filterRendezVous(rendezVous, currentFilter);

    if (filteredRendezVous.length === 0) {
        // Afficher un message si aucun résultat après filtrage
        noRendezVous.style.display = 'block';

        const title = noRendezVous.querySelector('h3');
        const text  = noRendezVous.querySelector('p');

        if (title) title.textContent = 'Aucune séance trouvée';
        if (text)  text.innerHTML   = 'Aucune séance avec le statut sélectionné.';

        rendezVousList.style.display = 'none';
        return;
    }

    // Cacher le message "aucun rendez-vous"
    noRendezVous.style.display = 'none';

    filteredRendezVous.forEach(rv => {
        const card = createRendezVousCard(rv);
        rendezVousList.appendChild(card);
    });

    rendezVousList.style.display = 'flex';
}

// Crée une carte HTML pour un rendez-vous
function createRendezVousCard(rv) {
    const card = document.createElement('div');
    card.className = 'rendez-vous-card';

    const now    = new Date();
    const rvDate = toValidDate(rv.date_heure);
    const isPast = rvDate ? rvDate < now : false;

    // Classes CSS selon statut / passé / futur
    if (rv.statut === 'EN_ATTENTE') {
        card.classList.add('pending');
    } else if (rv.statut === 'REFUSEE') {
        card.classList.add('refused');
    } else if (rv.statut === 'ANNULE') {
        card.classList.add('cancelled');
    } else if (rv.statut === 'TERMINE') {
        card.classList.add('completed');
    } else if (isPast) {
        card.classList.add('past');
    } else {
        card.classList.add('upcoming');
    }

    const formattedDate     = formatDateForHistorique(rv.date_heure);
    const formattedTime     = formatTimeForHistorique(rv.date_heure);
    // Pour les demandes en attente ou refusées, la durée peut être null
    const formattedDuration = (rv.statut === 'EN_ATTENTE' || rv.statut === 'REFUSEE')
        ? 'À confirmer'
        : formatDuration(rv.duree);

    const statutLabel = formatStatut(rv.statut);
    const statutClass = formatStatutClass(rv.statut);

    const tuteurNom  = `${escapeHtml(rv.tuteur_prenom || '')} ${escapeHtml(rv.tuteur_nom || '')}`.trim();
    const serviceNom = escapeHtml(rv.service_nom || 'Non spécifié');

    // Ne pas afficher le prix pour les demandes en attente ou refusées
    const prixSection = (rv.prix && rv.statut !== 'EN_ATTENTE' && rv.statut !== 'REFUSEE')
        ? `
        <div class="rendez-vous-info">
            <span class="rendez-vous-info-label">Prix</span>
            <span class="rendez-vous-info-value">${formatPrice(rv.prix)}</span>
        </div>
        `
        : '';

    const notesSection = rv.notes
        ? `
        <div class="rendez-vous-notes">
            <div class="rendez-vous-notes-label">Notes</div>
            <div class="rendez-vous-notes-content">${escapeHtml(rv.notes)}</div>
        </div>
        `
        : '';

    // Bouton d'annulation (seulement pour les rendez-vous qui peuvent être annulés)
    const canCancel = rv.id && (rv.statut === 'A_VENIR' || rv.statut === 'EN_COURS' || rv.statut === 'EN_ATTENTE');
    const cancelButton = canCancel
        ? `
        <div class="rendez-vous-card-actions">
            <button 
                type="button" 
                class="btn-annuler-rendez-vous" 
                data-rendez-vous-id="${escapeHtml(rv.id)}"
            >
                Annuler le rendez-vous
            </button>
        </div>
        `
        : '';

    card.innerHTML = `
        <div class="rendez-vous-card-header">
            <div class="rendez-vous-date-time">
                <div class="rendez-vous-date">${formattedDate}</div>
                <div class="rendez-vous-time">${formattedTime} (${formattedDuration})</div>
            </div>
            <span class="rendez-vous-statut ${statutClass}">${statutLabel}</span>
        </div>
        
        <div class="rendez-vous-card-body">
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Tuteur</span>
                <span class="rendez-vous-info-value">${tuteurNom || '-'}</span>
            </div>
            
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Service</span>
                <span class="rendez-vous-info-value">${serviceNom}</span>
            </div>
            
            ${prixSection}
        </div>
        
        ${notesSection}
        ${cancelButton}
    `;

    // Ajouter l'événement de clic sur le bouton d'annulation
    if (canCancel) {
        const cancelBtn = card.querySelector('.btn-annuler-rendez-vous');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                const rendezVousId = this.getAttribute('data-rendez-vous-id');
                openAnnulationModal(rv, rendezVousId);
            });
        }
    }

    return card;
}

// === Gestion des filtres ===

function initFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Retirer la classe active de tous les boutons
            filterButtons.forEach(b => b.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            btn.classList.add('active');
            
            // Mettre à jour le filtre actif
            currentFilter = btn.getAttribute('data-filter');
            
            // Réafficher les rendez-vous avec le nouveau filtre
            displayRendezVous(allRendezVous);
        });
    });
}

// === Chargement des rendez-vous ===

// Charge les rendez-vous depuis l'API
async function loadRendezVous() {
    const {
        loadingIndicator,
        errorMessage,
        errorText,
        noRendezVous,
        rendezVousList
    } = getHistoriqueElements();

    // Si des éléments critiques manquent, on arrête proprement
    if (!loadingIndicator || !errorMessage || !errorText || !noRendezVous || !rendezVousList) {
        console.error('Certains éléments de l’interface historique sont manquants.');
        return;
    }

    // État initial : chargement
    loadingIndicator.style.display = 'block';
    errorMessage.style.display     = 'none';
    noRendezVous.style.display     = 'none';
    rendezVousList.style.display   = 'none';

    try {
        const response = await fetch(RENDEZ_VOUS_API_URL);

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        loadingIndicator.style.display = 'none';

        if (data.error) {
            throw new Error(data.error);
        }

        if (!Array.isArray(data)) {
            throw new Error('Format de réponse invalide');
        }

        // Stocker tous les rendez-vous pour le filtrage
        allRendezVous = data;

        if (data.length === 0) {
            noRendezVous.style.display = 'block';

            const filters = document.getElementById('historiqueFilters');
            if (filters) filters.style.display = 'none';

            return;
        }

        // Afficher les filtres
        const filters = document.getElementById('historiqueFilters');
        if (filters) filters.style.display = 'flex';

        const sortedRendezVous = sortRendezVousByDate(data);
        displayRendezVous(sortedRendezVous);

    } catch (error) {
        console.error('Erreur lors du chargement des rendez-vous:', error);

        loadingIndicator.style.display = 'none';
        errorText.textContent = error.message || 'Une erreur est survenue lors du chargement de vos séances.';
        errorMessage.style.display = 'block';
    }
}

// === Gestion du modal d'annulation ===

let pendingAnnulationRendezVousId = null;

function openAnnulationModal(rendezVous, rendezVousId) {
    const modal = document.getElementById('annulationModal');
    if (!modal) {
        console.error('Modal d\'annulation non trouvé');
        return;
    }

    // Remplir les informations du rendez-vous
    const dateHeure = formatDateForHistorique(rendezVous.date_heure) + ' à ' + formatTimeForHistorique(rendezVous.date_heure);
    const tuteurNom = `${rendezVous.tuteur_prenom || ''} ${rendezVous.tuteur_nom || ''}`.trim() || '-';
    const serviceNom = rendezVous.service_nom || '-';

    document.getElementById('annulationDateHeure').textContent = dateHeure;
    document.getElementById('annulationTuteur').textContent = tuteurNom;
    document.getElementById('annulationService').textContent = serviceNom;

    // Stocker l'ID du rendez-vous à annuler
    pendingAnnulationRendezVousId = rendezVousId;

    // Ouvrir le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAnnulationModal() {
    const modal = document.getElementById('annulationModal');
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';
    pendingAnnulationRendezVousId = null;
}

async function confirmAnnulation() {
    if (!pendingAnnulationRendezVousId) return;

    try {
        const response = await fetch(RENDEZ_VOUS_API_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: pendingAnnulationRendezVousId,
                action: 'annuler'
            })
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Erreur lors de l\'annulation du rendez-vous');
        }

        closeAnnulationModal();

        // Recharger les rendez-vous
        await loadRendezVous();

        // Afficher un message de succès (si vous avez une fonction showNotification)
        if (typeof showNotification === 'function') {
            showNotification('Rendez-vous annulé avec succès', 'success');
        } else {
            alert('Rendez-vous annulé avec succès');
        }

    } catch (error) {
        console.error('Erreur lors de l\'annulation:', error);
        if (typeof showNotification === 'function') {
            showNotification(error.message || 'Erreur lors de l\'annulation du rendez-vous', 'error');
        } else {
            alert(error.message || 'Erreur lors de l\'annulation du rendez-vous');
        }
    }
}

// Initialiser les événements du modal d'annulation
function initAnnulationModal() {
    const modal = document.getElementById('annulationModal');
    if (!modal) return;

    const cancelBtn = document.getElementById('annulationCancelBtn');
    const confirmBtn = document.getElementById('annulationConfirmBtn');
    const overlay = modal.querySelector('.confirmation-modal-overlay');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeAnnulationModal);
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmAnnulation);
    }

    if (overlay) {
        overlay.addEventListener('click', closeAnnulationModal);
    }

    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeAnnulationModal();
        }
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    initAnnulationModal();
    loadRendezVous();
});
