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
