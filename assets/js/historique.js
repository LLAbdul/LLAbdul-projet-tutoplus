// === Constantes ===

const RENDEZ_VOUS_API_URL = 'api/rendez-vous.php';

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
    A_VENIR:  'À venir',
    EN_COURS: 'En cours',
    TERMINE:  'Terminé',
    ANNULE:   'Annulé',
    REPORTE:  'Reporté'
};

const STATUT_CLASSES = {
    A_VENIR:  'a-venir',
    EN_COURS: 'en-cours',
    TERMINE:  'termine',
    ANNULE:   'annule',
    REPORTE:  'reporte'
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

// Affiche les rendez-vous dans le DOM
function displayRendezVous(rendezVous) {
    const { rendezVousList } = getHistoriqueElements();
    rendezVousList.innerHTML = '';

    rendezVous.forEach(rv => {
        const card = createRendezVousCard(rv);
        rendezVousList.appendChild(card);
    });
}

// Crée une carte HTML pour un rendez-vous
function createRendezVousCard(rv) {
    const card = document.createElement('div');
    card.className = 'rendez-vous-card';

    const now = new Date();
    const rvDate = toValidDate(rv.date_heure);
    const isPast = rvDate ? rvDate < now : false;

    // Classes CSS selon statut / passé / futur
    if (rv.statut === 'ANNULE') {
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
    const formattedDuration = formatDuration(rv.duree);
    const statutLabel       = formatStatut(rv.statut);
    const statutClass       = formatStatutClass(rv.statut);

    const tuteurNom   = `${escapeHtml(rv.tuteur_prenom || '')} ${escapeHtml(rv.tuteur_nom || '')}`.trim();
    const serviceNom  = escapeHtml(rv.service_nom || 'Non spécifié');
    const prixSection = rv.prix
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
    `;

    return card;
}

// === Chargement des rendez-vous ===

document.addEventListener('DOMContentLoaded', function() {
    loadRendezVous();
});

// Charge les rendez-vous depuis l'API
async function loadRendezVous() {
    const {
        loadingIndicator,
        errorMessage,
        errorText,
        noRendezVous,
        rendezVousList
    } = getHistoriqueElements();

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

        if (data.length === 0) {
            noRendezVous.style.display = 'block';
            return;
        }

        const sortedRendezVous = sortRendezVousByDate(data);
        displayRendezVous(sortedRendezVous);
        rendezVousList.style.display = 'flex';

    } catch (error) {
        console.error('Erreur lors du chargement des rendez-vous:', error);

        loadingIndicator.style.display = 'none';
        errorText.textContent = error.message || 'Une erreur est survenue lors du chargement de vos séances.';
        errorMessage.style.display = 'block';
    }
}
