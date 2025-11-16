/**
 * Script admin.js
 * - Gestion de l'affichage des comptes (étudiants et tuteurs)
 * - Filtrage des comptes
 * - Activation/désactivation des comptes
 * - Gestion de l'affichage des rendez-vous
 * - Filtrage des rendez-vous par statut
 */

// === Constantes ===

const ADMIN_API_URL = 'api/admin.php';

// === Variables globales ===

let allComptes = [];
let currentFilter = 'all';

let allRendezVous = [];
let currentRendezVousFilter = 'all';

// === Fonctions utilitaires ===

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toValidDate(dateString) {
    if (!dateString) return null;
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? null : date;
}

function formatDate(dateString) {
    const date = toValidDate(dateString);
    if (!date) return 'N/A';
    return date.toLocaleDateString('fr-CA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Gardé si tu veux l'utiliser plus tard
function formatDateTime(dateString) {
    const date = toValidDate(dateString);
    if (!date) return 'N/A';
    return date.toLocaleString('fr-CA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatTime(dateString) {
    const date = toValidDate(dateString);
    if (!date) return 'N/A';
    return date.toLocaleTimeString('fr-CA', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
}

function formatDuration(minutes) {
    if (minutes == null || minutes < 0) return '-';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours === 0) return `${mins}min`;
    if (mins === 0) return `${hours}h`;
    return `${hours}h ${mins}min`;
}

function formatPrice(price) {
    if (price == null || price === '') return '-';
    const numPrice = typeof price === 'string' ? parseFloat(price) : price;
    if (isNaN(numPrice)) return String(price);
    return numPrice.toFixed(2).replace('.', ',') + ' $';
}

const STATUT_LABELS = {
    A_VENIR: 'À venir',
    EN_COURS: 'En cours',
    TERMINE: 'Terminé',
    ANNULE: 'Annulé',
    REPORTE: 'Reporté'
};

const STATUT_CLASSES = {
    A_VENIR: 'a-venir',
    EN_COURS: 'en-cours',
    TERMINE: 'termine',
    ANNULE: 'annule',
    REPORTE: 'reporte'
};

function formatStatut(statut) {
    return STATUT_LABELS[statut] || statut || '';
}

function formatStatutClass(statut) {
    return STATUT_CLASSES[statut] || '';
}

// === Fonctions d'affichage et de notification ===

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    if (errorDiv && errorText) {
        errorText.textContent = message;
        errorDiv.style.display = 'block';
    }
}

function hideError() {
    const errorDiv = document.getElementById('errorMessage');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

function showErrorRendezVous(message) {
    const errorDiv = document.getElementById('errorMessageRendezVous');
    const errorText = document.getElementById('errorTextRendezVous');
    if (errorDiv && errorText) {
        errorText.textContent = message;
        errorDiv.style.display = 'block';
    }
}

function hideErrorRendezVous() {
    const errorDiv = document.getElementById('errorMessageRendezVous');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

function showToast(message, type = 'success') {
    // Optionnel : nettoyer les anciens toasts pour éviter l’empilement
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(t => t.remove());

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('toast-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// === Création des cartes de compte ===

function createCompteCard(compte) {
    const isEtudiant = compte.type === 'etudiant';
    const isActif = compte.actif === true || compte.actif === 1;

    const numero = isEtudiant ? compte.numero_etudiant : compte.numero_employe;
    const numeroAffiche = numero ? String(numero) : 'N/A';

    const card = document.createElement('div');
    card.className = `compte-card ${isActif ? 'actif' : 'inactif'}`;
    card.innerHTML = `
        <div class="compte-card-header">
            <div class="compte-type-badge ${isEtudiant ? 'etudiant' : 'tuteur'}">
                ${isEtudiant ? 'Étudiant' : 'Tuteur'}
            </div>
            <div class="compte-statut-badge ${isActif ? 'actif' : 'inactif'}">
                ${isActif ? 'Actif' : 'Inactif'}
            </div>
        </div>
        <div class="compte-card-body">
            <h3 class="compte-name">
                ${escapeHtml((compte.prenom || '') + ' ' + (compte.nom || ''))}
            </h3>
            <div class="compte-info">
                <div class="compte-info-item">
                    <span class="compte-info-label">Numéro:</span>
                    <span class="compte-info-value">${escapeHtml(numeroAffiche)}</span>
                </div>
                <div class="compte-info-item">
                    <span class="compte-info-label">Email:</span>
                    <span class="compte-info-value">${escapeHtml(compte.email || '')}</span>
                </div>
                ${compte.telephone ? `
                <div class="compte-info-item">
                    <span class="compte-info-label">Téléphone:</span>
                    <span class="compte-info-value">${escapeHtml(compte.telephone)}</span>
                </div>
                ` : ''}
                ${isEtudiant ? `
                <div class="compte-info-item">
                    <span class="compte-info-label">Niveau:</span>
                    <span class="compte-info-value">${escapeHtml(compte.niveau || 'N/A')}</span>
                </div>
                <div class="compte-info-item">
                    <span class="compte-info-label">Spécialité:</span>
                    <span class="compte-info-value">${escapeHtml(compte.specialite || 'N/A')}</span>
                </div>
                ` : `
                <div class="compte-info-item">
                    <span class="compte-info-label">Département:</span>
                    <span class="compte-info-value">${escapeHtml(compte.departement || 'N/A')}</span>
                </div>
                <div class="compte-info-item">
                    <span class="compte-info-label">Tarif horaire:</span>
                    <span class="compte-info-value">
                        $${parseFloat(compte.tarif_horaire || 0).toFixed(2)}
                    </span>
                </div>
                `}
                ${compte.date_creation ? `
                <div class="compte-info-item">
                    <span class="compte-info-label">Date de création:</span>
                    <span class="compte-info-value">${formatDate(compte.date_creation)}</span>
                </div>
                ` : ''}
            </div>
        </div>
        <div class="compte-card-actions">
            <button 
                class="btn-compte-toggle ${isActif ? 'btn-deactivate' : 'btn-activate'}"
                data-compte-id="${escapeHtml(String(compte.id))}"
                data-compte-type="${escapeHtml(compte.type || '')}"
                data-compte-actif="${isActif ? 'true' : 'false'}"
                type="button"
            >
                ${isActif ? 'Désactiver' : 'Activer'}
            </button>
        </div>
    `;
    
    return card;
}

// === Chargement des comptes depuis l'API ===

async function loadComptes() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage     = document.getElementById('errorMessage');
    const noComptes        = document.getElementById('noComptes');
    const comptesList      = document.getElementById('comptesList');
    
    // Afficher le chargement
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (errorMessage)     errorMessage.style.display     = 'none';
    if (noComptes)        noComptes.style.display        = 'none';
    if (comptesList)      comptesList.style.display      = 'none';
    
    try {
        const response = await fetch(ADMIN_API_URL);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        allComptes = Array.isArray(data) ? data : [];
        
        // Masquer le chargement
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        // Afficher les comptes filtrés
        displayComptes();
        
    } catch (error) {
        console.error('Erreur lors du chargement des comptes:', error);
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        showError('Erreur lors du chargement des comptes: ' + error.message);
    }
}

// === Filtrage des comptes ===

function filterComptes(filter) {
    currentFilter = filter;
    displayComptes();
}

// Afficher les comptes filtrés
function displayComptes() {
    const comptesList = document.getElementById('comptesList');
    const noComptes   = document.getElementById('noComptes');
    
    if (!comptesList) return;
    
    // Filtrer les comptes
    let filteredComptes = allComptes;
    
    switch (currentFilter) {
        case 'etudiants':
            filteredComptes = allComptes.filter(c => c.type === 'etudiant');
            break;
        case 'tuteurs':
            filteredComptes = allComptes.filter(c => c.type === 'tuteur');
            break;
        case 'actifs':
            filteredComptes = allComptes.filter(c => c.actif === true || c.actif === 1);
            break;
        case 'inactifs':
            filteredComptes = allComptes.filter(c => c.actif === false || c.actif === 0);
            break;
        default:
            filteredComptes = allComptes;
    }
    
    // Vider la liste
    comptesList.innerHTML = '';
    
    // Afficher le message si aucun compte
    if (filteredComptes.length === 0) {
        if (noComptes) noComptes.style.display = 'block';
        comptesList.style.display = 'none';
        return;
    }
    
    // Masquer le message "aucun compte"
    if (noComptes) noComptes.style.display = 'none';
    comptesList.style.display = 'grid';
    
    // Créer et ajouter les cartes
    filteredComptes.forEach(compte => {
        const card = createCompteCard(compte);
        comptesList.appendChild(card);
    });
    
    // Ajouter les event listeners pour les boutons
    attachToggleListeners();
}

// === Activation / désactivation ===

function attachToggleListeners() {
    const toggleButtons = document.querySelectorAll('.btn-compte-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const compteId    = button.getAttribute('data-compte-id');
            const compteType  = button.getAttribute('data-compte-type');
            const currentActif = button.getAttribute('data-compte-actif') === 'true';
            const newActif     = !currentActif;
            
            await toggleCompteActif(compteId, compteType, newActif);
        });
    });
}

// Activer/désactiver un compte
async function toggleCompteActif(compteId, compteType, actif) {
    try {
        const response = await fetch(ADMIN_API_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: compteId,
                type: compteType,
                actif: actif
            })
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Mettre à jour le compte dans allComptes
        const compteIndex = allComptes.findIndex(
            c => String(c.id) === String(compteId) && c.type === compteType
        );
        if (compteIndex !== -1 && data.compte) {
            allComptes[compteIndex] = data.compte;
        }
        
        // Réafficher les comptes
        displayComptes();
        
        // Afficher un message de succès
        showToast(
            `Compte ${actif ? 'activé' : 'désactivé'} avec succès`,
            'success'
        );
        
    } catch (error) {
        console.error('Erreur lors de la modification du compte:', error);
        showToast(
            'Erreur lors de la modification du compte: ' + error.message,
            'error'
        );
    }
}

// === Initialiser les filtres ===

function initFilters() {
    const tabComptes = document.getElementById('tab-comptes');
    if (!tabComptes) return;
    
    const filterButtons = tabComptes.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Retirer la classe active de tous les boutons dans cette section
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            button.classList.add('active');
            // Filtrer les comptes
            const filter = button.getAttribute('data-filter');
            filterComptes(filter);
        });
    });
}

// === Gestion des rendez-vous ===

function createRendezVousCard(rv) {
    const card = document.createElement('div');
    card.className = 'rendez-vous-card';
    
    const statutLabel = formatStatut(rv.statut);
    const statutClass = formatStatutClass(rv.statut);
    
    const etudiantNom = `${escapeHtml(rv.etudiant_prenom || '')} ${escapeHtml(rv.etudiant_nom || '')}`.trim() || 'N/A';
    const tuteurNom = `${escapeHtml(rv.tuteur_prenom || '')} ${escapeHtml(rv.tuteur_nom || '')}`.trim() || 'N/A';
    const serviceNom = escapeHtml(rv.service_nom || 'Non spécifié');
    
    card.innerHTML = `
        <div class="rendez-vous-card-header">
            <div class="rendez-vous-date-time">
                <div class="rendez-vous-date">${formatDate(rv.date_heure)}</div>
                <div class="rendez-vous-time">${formatTime(rv.date_heure)} (${formatDuration(rv.duree)})</div>
            </div>
            <span class="rendez-vous-statut ${statutClass}">${statutLabel}</span>
        </div>
        <div class="rendez-vous-card-body">
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Étudiant:</span>
                <span class="rendez-vous-info-value">${etudiantNom}</span>
            </div>
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Tuteur:</span>
                <span class="rendez-vous-info-value">${tuteurNom}</span>
            </div>
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Service:</span>
                <span class="rendez-vous-info-value">${serviceNom}</span>
            </div>
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Prix:</span>
                <span class="rendez-vous-info-value">${formatPrice(rv.prix)}</span>
            </div>
            ${rv.notes ? `
            <div class="rendez-vous-notes">
                <div class="rendez-vous-notes-label">Notes:</div>
                <div class="rendez-vous-notes-content">${escapeHtml(rv.notes)}</div>
            </div>
            ` : ''}
        </div>
    `;
    
    return card;
}

async function loadRendezVous() {
    const loadingIndicator = document.getElementById('loadingIndicatorRendezVous');
    const errorMessage = document.getElementById('errorMessageRendezVous');
    const noRendezVous = document.getElementById('noRendezVous');
    const rendezVousList = document.getElementById('rendezVousList');
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (errorMessage) errorMessage.style.display = 'none';
    if (noRendezVous) noRendezVous.style.display = 'none';
    if (rendezVousList) rendezVousList.style.display = 'none';
    
    try {
        const response = await fetch(`${ADMIN_API_URL}?resource=rendez-vous`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        allRendezVous = Array.isArray(data) ? data : [];
        
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        displayRendezVous();
        
    } catch (error) {
        console.error('Erreur lors du chargement des rendez-vous:', error);
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        showErrorRendezVous('Erreur lors du chargement des rendez-vous: ' + error.message);
    }
}

function filterRendezVous(filter) {
    currentRendezVousFilter = filter;
    displayRendezVous();
}

function displayRendezVous() {
    const rendezVousList = document.getElementById('rendezVousList');
    const noRendezVous = document.getElementById('noRendezVous');
    
    if (!rendezVousList) return;
    
    let filteredRendezVous = allRendezVous;
    
    if (currentRendezVousFilter !== 'all') {
        filteredRendezVous = allRendezVous.filter(rv => rv.statut === currentRendezVousFilter);
    }
    
    rendezVousList.innerHTML = '';
    
    if (filteredRendezVous.length === 0) {
        if (noRendezVous) noRendezVous.style.display = 'block';
        rendezVousList.style.display = 'none';
        return;
    }
    
    if (noRendezVous) noRendezVous.style.display = 'none';
    rendezVousList.style.display = 'flex';
    
    filteredRendezVous.forEach(rv => {
        const card = createRendezVousCard(rv);
        rendezVousList.appendChild(card);
    });
}

function initRendezVousFilters() {
    const tabRendezVous = document.getElementById('tab-rendez-vous');
    if (!tabRendezVous) return;
    
    const filterButtons = tabRendezVous.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const filter = button.getAttribute('data-filter');
            filterRendezVous(filter);
        });
    });
}

// === Initialiser les onglets ===

function initTabs() {
    const tabButtons  = document.querySelectorAll('.admin-tab');
    const tabContents = document.querySelectorAll('.admin-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');
            
            // Retirer la classe active de tous les onglets et contenus
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Ajouter la classe active à l'onglet et au contenu sélectionnés
            button.classList.add('active');
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
                
                // Charger les rendez-vous si on clique sur l'onglet rendez-vous
                if (targetTab === 'rendez-vous' && allRendezVous.length === 0) {
                    loadRendezVous();
                }
            }
        });
    });
}

// === Initialisation au chargement de la page ===

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initFilters();
    initRendezVousFilters();
    loadComptes();
});
