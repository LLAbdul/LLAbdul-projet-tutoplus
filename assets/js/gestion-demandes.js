/**
 * JavaScript pour gestion_demandes.php
 * - Charge les demandes depuis l'API
 * - Affiche les demandes dans la liste
 * - Gère les actions d'acceptation et de refus
 */

// Éléments DOM
const loadingIndicator = document.getElementById('loadingIndicator');
const errorMessage = document.getElementById('errorMessage');
const errorText = document.getElementById('errorText');
const noDemandes = document.getElementById('noDemandes');
const demandesList = document.getElementById('demandesList');

// Fonction utilitaire pour échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fonction pour formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-CA', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Fonction pour formater l'heure
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('fr-CA', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonction pour obtenir le libellé du statut
function getStatutLabel(statut) {
    const labels = {
        'EN_ATTENTE': 'En attente',
        'ACCEPTEE': 'Acceptée',
        'REFUSEE': 'Refusée',
        'EXPIRED': 'Expirée'
    };
    return labels[statut] || statut;
}

// Fonction pour obtenir la classe CSS du statut
function getStatutClass(statut) {
    const classes = {
        'EN_ATTENTE': 'en-attente',
        'ACCEPTEE': 'acceptee',
        'REFUSEE': 'refusee',
        'EXPIRED': 'expired'
    };
    return classes[statut] || '';
}

// Fonction pour afficher une notification toast
function showToast(message, type = 'success') {
    // Supprimer les notifications existantes
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());

    // Créer la notification
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <div class="toast-notification-message">${escapeHtml(message)}</div>
    `;

    document.body.appendChild(toast);

    // Supprimer après 5 secondes
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Fonction pour afficher une erreur
function showError(message) {
    errorText.textContent = message;
    errorMessage.style.display = 'block';
    loadingIndicator.style.display = 'none';
    noDemandes.style.display = 'none';
    demandesList.style.display = 'none';
}

// Fonction pour cacher l'erreur
function hideError() {
    errorMessage.style.display = 'none';
}

// Fonction pour créer le HTML d'une carte de demande
function createDemandeCard(demande) {
    const statutClass = getStatutClass(demande.statut);
    const statutLabel = getStatutLabel(demande.statut);
    const dateFormatted = formatDate(demande.date_heure_demande);
    const timeFormatted = formatTime(demande.date_heure_demande);

    // Boutons d'action (seulement si EN_ATTENTE)
    let actionsHTML = '';
    if (demande.statut === 'EN_ATTENTE') {
        actionsHTML = `
            <div class="demande-card-actions">
                <button 
                    class="btn-accepter" 
                    onclick="accepterDemande('${demande.id}')"
                    type="button"
                >
                    Accepter
                </button>
                <button 
                    class="btn-refuser" 
                    onclick="refuserDemande('${demande.id}')"
                    type="button"
                >
                    Refuser
                </button>
            </div>
        `;
    }

    // Motif si présent
    let motifHTML = '';
    if (demande.motif) {
        motifHTML = `
            <div class="demande-card-motif">
                <div class="demande-card-motif-label">Motif :</div>
                <div class="demande-card-motif-text">${escapeHtml(demande.motif)}</div>
            </div>
        `;
    }

    return `
        <div class="demande-card">
            <div class="demande-card-header">
                <div class="demande-card-info">
                    <div class="demande-card-title">
                        ${escapeHtml(demande.service_nom || 'Service')}
                    </div>
                    <div class="demande-card-meta">
                        <span>
                            <strong>Étudiant :</strong> 
                            ${escapeHtml((demande.etudiant_prenom || '') + ' ' + (demande.etudiant_nom || ''))}
                        </span>
                        <span>
                            <strong>Date de demande :</strong> 
                            ${dateFormatted} à ${timeFormatted}
                        </span>
                    </div>
                </div>
                <div class="demande-card-status ${statutClass}">
                    ${statutLabel}
                </div>
            </div>
            <div class="demande-card-body">
                <div class="demande-card-details">
                    <div class="demande-detail-item">
                        <div class="demande-detail-label">Service</div>
                        <div class="demande-detail-value">${escapeHtml(demande.service_nom || 'N/A')}</div>
                    </div>
                    <div class="demande-detail-item">
                        <div class="demande-detail-label">Catégorie</div>
                        <div class="demande-detail-value">${escapeHtml(demande.service_categorie || 'N/A')}</div>
                    </div>
                    <div class="demande-detail-item">
                        <div class="demande-detail-label">Étudiant</div>
                        <div class="demande-detail-value">${escapeHtml((demande.etudiant_prenom || '') + ' ' + (demande.etudiant_nom || ''))}</div>
                    </div>
                    <div class="demande-detail-item">
                        <div class="demande-detail-label">Email</div>
                        <div class="demande-detail-value">${escapeHtml(demande.etudiant_email || 'N/A')}</div>
                    </div>
                </div>
                ${motifHTML}
            </div>
            ${actionsHTML}
        </div>
    `;
}

// Fonction pour charger les demandes depuis l'API
async function loadDemandes() {
    try {
        hideError();
        loadingIndicator.style.display = 'block';
        noDemandes.style.display = 'none';
        demandesList.style.display = 'none';

        const response = await fetch('api/demandes.php');
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const demandes = await response.json();

        loadingIndicator.style.display = 'none';

        if (!demandes || demandes.length === 0) {
            noDemandes.style.display = 'block';
            demandesList.style.display = 'none';
            return;
        }

        // Afficher les demandes
        demandesList.innerHTML = demandes.map(demande => createDemandeCard(demande)).join('');
        demandesList.style.display = 'flex';
        noDemandes.style.display = 'none';

    } catch (error) {
        console.error('Erreur lors du chargement des demandes:', error);
        showError('Erreur lors du chargement des demandes. Veuillez réessayer plus tard.');
    }
}
