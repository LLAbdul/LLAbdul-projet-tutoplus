/**
 * JavaScript pour gestion_demandes.php
 * - Charge les demandes depuis l'API
 * - Affiche les demandes dans la liste
 * - Gère les actions d'acceptation et de refus
 */

// Constantes
const DEMANDES_API_URL = 'api/demandes.php';

// Maps pour les statuts
const STATUT_LABELS = {
    EN_ATTENTE: 'En attente',
    ACCEPTEE: 'Acceptée',
    REFUSEE: 'Refusée',
    EXPIRED: 'Expirée'
};

const STATUT_CLASSES = {
    EN_ATTENTE: 'en-attente',
    ACCEPTEE: 'acceptee',
    REFUSEE: 'refusee',
    EXPIRED: 'expired'
};

// Maps pour les statuts de rendez-vous
const RENDEZ_VOUS_STATUT_LABELS = {
    A_VENIR: 'À venir',
    EN_COURS: 'En cours',
    TERMINE: 'Terminé',
    ANNULE: 'Annulé',
    REPORTE: 'Reporté'
};

const RENDEZ_VOUS_STATUT_CLASSES = {
    A_VENIR: 'rendez-vous-a-venir',
    EN_COURS: 'rendez-vous-en-cours',
    TERMINE: 'rendez-vous-termine',
    ANNULE: 'rendez-vous-annule',
    REPORTE: 'rendez-vous-reporte'
};

// État global pour le filtrage
let allDemandes = [];
let currentFilter = 'all';

// Éléments DOM
const loadingIndicator = document.getElementById('loadingIndicator');
const errorMessage = document.getElementById('errorMessage');
const errorText = document.getElementById('errorText');
const noDemandes = document.getElementById('noDemandes');
const demandesList = document.getElementById('demandesList');

// Fonction utilitaire pour échapper le HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fonction pour formater la date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
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
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleTimeString('fr-CA', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonction pour obtenir le libellé du statut
function getStatutLabel(statut) {
    return STATUT_LABELS[statut] || statut || 'N/A';
}

// Fonction pour obtenir la classe CSS du statut
function getStatutClass(statut) {
    return STATUT_CLASSES[statut] || '';
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
        toast.classList.add('toast-notification-out');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Fonction pour afficher une erreur
function showError(message) {
    if (!errorMessage || !errorText) return;
    errorText.textContent = message;
    errorMessage.style.display = 'block';
    if (loadingIndicator) loadingIndicator.style.display = 'none';
    if (noDemandes) noDemandes.style.display = 'none';
    if (demandesList) demandesList.style.display = 'none';
}

// Fonction pour cacher l'erreur
function hideError() {
    if (!errorMessage) return;
    errorMessage.style.display = 'none';
}

// Fonction pour filtrer les demandes selon le statut sélectionné
function filterDemandes(demandes, filter) {
    if (filter === 'all') {
        return demandes;
    }
    return demandes.filter(demande => demande.statut === filter);
}

// Fonction pour afficher les demandes avec filtrage
function displayDemandes(demandes) {
    if (!noDemandes || !demandesList) return;

    // Filtrer selon le filtre actif
    const filteredDemandes = filterDemandes(demandes, currentFilter);

    if (filteredDemandes.length === 0) {
        // Afficher un message si aucun résultat après filtrage
        noDemandes.style.display = 'block';

        const title = noDemandes.querySelector('h3');
        const text = noDemandes.querySelector('p');

        if (title) title.textContent = 'Aucune demande trouvée';
        if (text) text.textContent = 'Aucune demande avec le statut sélectionné.';

        demandesList.style.display = 'none';
        return;
    }

    // Cacher le message "aucune demande"
    noDemandes.style.display = 'none';

    // Afficher les demandes
    demandesList.innerHTML = filteredDemandes.map(createDemandeCard).join('');
    demandesList.style.display = 'flex';
}

// Fonction pour obtenir le libellé du statut de rendez-vous
function getRendezVousStatutLabel(statut) {
    return RENDEZ_VOUS_STATUT_LABELS[statut] || statut || 'N/A';
}

// Fonction pour obtenir la classe CSS du statut de rendez-vous
function getRendezVousStatutClass(statut) {
    return RENDEZ_VOUS_STATUT_CLASSES[statut] || '';
}

// Fonction pour créer le HTML d'une carte de demande
function createDemandeCard(demande) {
    const statutClass = getStatutClass(demande.statut);
    const statutLabel = getStatutLabel(demande.statut);
    const dateFormatted = formatDate(demande.date_heure_demande);
    const timeFormatted = formatTime(demande.date_heure_demande);

    const etudiantNomComplet = `${demande.etudiant_prenom || ''} ${demande.etudiant_nom || ''}`.trim() || 'N/A';

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
                    onclick="openRefusModal('${demande.id}')"
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

    // Statut du rendez-vous si présent
    let rendezVousStatutHTML = '';
    if (demande.rendez_vous_id && demande.rendez_vous_statut) {
        const rvStatutClass = getRendezVousStatutClass(demande.rendez_vous_statut);
        const rvStatutLabel = getRendezVousStatutLabel(demande.rendez_vous_statut);
        rendezVousStatutHTML = `
            <div class="demande-detail-item">
                <div class="demande-detail-label">Statut du rendez-vous</div>
                <div class="demande-detail-value">
                    <span class="rendez-vous-status-badge ${rvStatutClass}">${rvStatutLabel}</span>
                </div>
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
                            ${escapeHtml(etudiantNomComplet)}
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
                        <div class="demande-detail-value">${escapeHtml(etudiantNomComplet)}</div>
                    </div>
                    <div class="demande-detail-item">
                        <div class="demande-detail-label">Email</div>
                        <div class="demande-detail-value">${escapeHtml(demande.etudiant_email || 'N/A')}</div>
                    </div>
                    ${rendezVousStatutHTML}
                </div>
                ${motifHTML}
            </div>
            ${actionsHTML}
        </div>
    `;
}

// Utilitaire : désactiver/réactiver les boutons d'une demande
function setDemandeButtonsDisabled(demandeId, disabled) {
    const buttons = document.querySelectorAll(`[onclick*="${demandeId}"]`);
    buttons.forEach(btn => {
        btn.disabled = disabled;
    });
}

// Fonction pour charger les demandes depuis l'API
async function loadDemandes() {
    try {
        hideError();
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        if (noDemandes) noDemandes.style.display = 'none';
        if (demandesList) demandesList.style.display = 'none';

        const response = await fetch(DEMANDES_API_URL);

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const demandes = await response.json();

        if (loadingIndicator) loadingIndicator.style.display = 'none';

        // Normaliser les données dans allDemandes
        allDemandes = Array.isArray(demandes) ? demandes : [];

        if (allDemandes.length === 0) {
            if (noDemandes) noDemandes.style.display = 'block';
            if (demandesList) demandesList.style.display = 'none';

            // Cacher les filtres s'il n'y a pas de données
            const filters = document.getElementById('demandesFilters');
            if (filters) filters.style.display = 'none';
            return;
        }

        // Afficher les filtres
        const filters = document.getElementById('demandesFilters');
        if (filters) filters.style.display = 'flex';

        // Afficher les demandes avec filtrage
        displayDemandes(allDemandes);

    } catch (error) {
        console.error('Erreur lors du chargement des demandes :', error);
        showError('Erreur lors du chargement des demandes. Veuillez réessayer plus tard.');
    }
}

// Fonction pour accepter une demande
async function accepterDemande(demandeId) {
    try {
        setDemandeButtonsDisabled(demandeId, true);

        const response = await fetch(DEMANDES_API_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: demandeId,
                action: 'accepter'
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Erreur lors de l\'acceptation de la demande');
        }

        showToast(data.message || 'Demande acceptée avec succès', 'success');

        // Recharger les demandes après un court délai
        setTimeout(() => {
            loadDemandes();
        }, 1000);

    } catch (error) {
        console.error('Erreur lors de l\'acceptation de la demande :', error);
        showToast(error.message || 'Erreur lors de l\'acceptation de la demande', 'error');
        setDemandeButtonsDisabled(demandeId, false);
    }
}

// Variables pour le modal de refus
let currentRefusDemandeId = null;

// Fonction pour ouvrir le modal de refus (accessible globalement)
function openRefusModal(demandeId) {
    currentRefusDemandeId = demandeId;
    const modal = document.getElementById('refusModal');
    const raisonTextarea = document.getElementById('refus-raison');
    const charCount = document.getElementById('refus-char-count');
    
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Réinitialiser le formulaire
        if (raisonTextarea) {
            raisonTextarea.value = '';
            if (charCount) charCount.textContent = '0';
        }
    }
}

// Rendre la fonction accessible globalement (si nécessaire)
window.openRefusModal = openRefusModal;

// Fonction pour fermer le modal de refus
function closeRefusModal() {
    const modal = document.getElementById('refusModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        currentRefusDemandeId = null;
    }
}

// Fonction pour mettre à jour le compteur de caractères
function updateRefusCharCount() {
    const raisonTextarea = document.getElementById('refus-raison');
    const charCount = document.getElementById('refus-char-count');
    
    if (raisonTextarea && charCount) {
        const count = raisonTextarea.value.length;
        charCount.textContent = count;
        
        if (count > 500) {
            charCount.classList.add('char-counter-error');
        } else {
            charCount.classList.remove('char-counter-error');
        }
    }
}

// Fonction pour refuser une demande
async function confirmRefusDemande() {
    if (!currentRefusDemandeId) return;

    const raisonTextarea = document.getElementById('refus-raison');
    const raison = raisonTextarea ? raisonTextarea.value.trim() : '';

    if (raison.length > 500) {
        showToast('La raison ne peut pas dépasser 500 caractères', 'error');
        return;
    }

    try {
        setDemandeButtonsDisabled(currentRefusDemandeId, true);

        const body = {
            id: currentRefusDemandeId,
            action: 'refuser'
        };

        if (raison) {
            body.raison = raison;
        }

        const response = await fetch(DEMANDES_API_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Erreur lors du refus de la demande');
        }

        closeRefusModal();
        showToast(data.message || 'Demande refusée avec succès', 'success');

        setTimeout(() => {
            loadDemandes();
        }, 1000);

    } catch (error) {
        console.error('Erreur lors du refus de la demande :', error);
        showToast(error.message || 'Erreur lors du refus de la demande', 'error');
        setDemandeButtonsDisabled(currentRefusDemandeId, false);
    }
}

// Fonction pour initialiser les filtres
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
            
            // Réafficher les demandes avec le nouveau filtre
            displayDemandes(allDemandes);
        });
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    if (!loadingIndicator || !demandesList) return;

    initFilters();
    loadDemandes();

    // Gestion du modal de refus
    const refusModal = document.getElementById('refusModal');
    const refusModalClose = document.getElementById('refusModalClose');
    const refusModalOverlay = refusModal ? refusModal.querySelector('.refus-modal-overlay') : null;
    const btnRefusCancel = document.getElementById('btnRefusCancel');
    const btnRefusConfirm = document.getElementById('btnRefusConfirm');
    const raisonTextarea = document.getElementById('refus-raison');

    if (refusModalClose) {
        refusModalClose.addEventListener('click', closeRefusModal);
    }

    if (refusModalOverlay) {
        refusModalOverlay.addEventListener('click', closeRefusModal);
    }

    if (btnRefusCancel) {
        btnRefusCancel.addEventListener('click', closeRefusModal);
    }

    if (btnRefusConfirm) {
        btnRefusConfirm.addEventListener('click', confirmRefusDemande);
    }

    if (raisonTextarea) {
        raisonTextarea.addEventListener('input', updateRefusCharCount);
    }

    // Fermer le modal avec la touche Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && refusModal && refusModal.classList.contains('active')) {
            closeRefusModal();
        }
    });
});
