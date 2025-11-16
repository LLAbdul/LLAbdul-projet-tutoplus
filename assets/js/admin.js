/**
 * Script admin.js
 * - Gestion de l'affichage des comptes (étudiants et tuteurs)
 * - Filtrage des comptes
 * - Activation/désactivation des comptes
 */

// Variables globales
let allComptes = [];
let currentFilter = 'all';

// Fonctions utilitaires
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-CA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('fr-CA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonctions d'affichage et de notification
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

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Créer une carte de compte
function createCompteCard(compte) {
    const isEtudiant = compte.type === 'etudiant';
    const isActif = compte.actif === true || compte.actif === 1;
    
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
            <h3 class="compte-name">${escapeHtml(compte.prenom + ' ' + compte.nom)}</h3>
            <div class="compte-info">
                <div class="compte-info-item">
                    <span class="compte-info-label">Numéro:</span>
                    <span class="compte-info-value">${escapeHtml(isEtudiant ? compte.numero_etudiant : compte.numero_employe)}</span>
                </div>
                <div class="compte-info-item">
                    <span class="compte-info-label">Email:</span>
                    <span class="compte-info-value">${escapeHtml(compte.email)}</span>
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
                    <span class="compte-info-value">$${parseFloat(compte.tarif_horaire || 0).toFixed(2)}</span>
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
                data-compte-id="${escapeHtml(compte.id)}"
                data-compte-type="${escapeHtml(compte.type)}"
                data-compte-actif="${isActif ? 'true' : 'false'}"
                type="button"
            >
                ${isActif ? 'Désactiver' : 'Activer'}
            </button>
        </div>
    `;
    
    return card;
}

// Charger les comptes depuis l'API
async function loadComptes() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const noComptes = document.getElementById('noComptes');
    const comptesList = document.getElementById('comptesList');
    
    // Afficher le chargement
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (errorMessage) errorMessage.style.display = 'none';
    if (noComptes) noComptes.style.display = 'none';
    if (comptesList) comptesList.style.display = 'none';
    
    try {
        const response = await fetch('api/admin.php');
        
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

// Filtrer les comptes
function filterComptes(filter) {
    currentFilter = filter;
    displayComptes();
}

// Afficher les comptes filtrés
function displayComptes() {
    const comptesList = document.getElementById('comptesList');
    const noComptes = document.getElementById('noComptes');
    
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
        if (comptesList) comptesList.style.display = 'none';
        return;
    }
    
    // Masquer le message "aucun compte"
    if (noComptes) noComptes.style.display = 'none';
    if (comptesList) comptesList.style.display = 'grid';
    
    // Créer et ajouter les cartes
    filteredComptes.forEach(compte => {
        const card = createCompteCard(compte);
        comptesList.appendChild(card);
    });
    
    // Ajouter les event listeners pour les boutons
    attachToggleListeners();
}

