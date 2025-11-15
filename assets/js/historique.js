// Script pour gérer l'affichage de l'historique des séances

document.addEventListener('DOMContentLoaded', function() {
    loadRendezVous();
});

// Charge les rendez-vous depuis l'API
async function loadRendezVous() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const noRendezVous = document.getElementById('noRendezVous');
    const rendezVousList = document.getElementById('rendezVousList');
    
    // Afficher le chargement
    loadingIndicator.style.display = 'block';
    errorMessage.style.display = 'none';
    noRendezVous.style.display = 'none';
    rendezVousList.style.display = 'none';
    
    try {
        const response = await fetch('api/rendez-vous.php');
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Cacher le chargement
        loadingIndicator.style.display = 'none';
        
        // Vérifier si c'est une erreur
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Vérifier si la réponse est un tableau
        if (!Array.isArray(data)) {
            throw new Error('Format de réponse invalide');
        }
        
        // Si aucun rendez-vous
        if (data.length === 0) {
            noRendezVous.style.display = 'block';
            return;
        }
        
        // Trier par date (plus récent en premier)
        const sortedRendezVous = sortRendezVousByDate(data);
        
        // Afficher les rendez-vous
        displayRendezVous(sortedRendezVous);
        rendezVousList.style.display = 'flex';
        
    } catch (error) {
        console.error('Erreur lors du chargement des rendez-vous:', error);
        
        // Cacher le chargement
        loadingIndicator.style.display = 'none';
        
        // Afficher l'erreur
        errorText.textContent = error.message || 'Une erreur est survenue lors du chargement de vos séances.';
        errorMessage.style.display = 'block';
    }
}

// Formate une date pour l'affichage dans l'historique
// dateString : Date au format ISO
// Retourne : Date formatée (ex: "15 janvier 2025")
function formatDateForHistorique(dateString) {
    if (!dateString) return '-';
    
    try {
        const date = new Date(dateString);
        
        if (isNaN(date.getTime())) {
            console.error('Date invalide:', dateString);
            return dateString;
        }
        
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    } catch (error) {
        console.error('Erreur lors du formatage de la date:', error);
        return dateString;
    }
}

// Formate une heure pour l'affichage dans l'historique
// dateString : Date/heure au format ISO
// Retourne : Heure formatée (ex: "14:30")
function formatTimeForHistorique(dateString) {
    if (!dateString) return '-';
    
    try {
        const date = new Date(dateString);
        
        if (isNaN(date.getTime())) {
            console.error('Date invalide:', dateString);
            return '-';
        }
        
        return date.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: false 
        });
    } catch (error) {
        console.error('Erreur lors du formatage de l\'heure:', error);
        return '-';
    }
}

// Formate une durée en minutes en format lisible
// minutes : Durée en minutes
// Retourne : Durée formatée (ex: "1h 30min")
function formatDuration(minutes) {
    if (!minutes || minutes < 0) return '-';
    
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours === 0) {
        return `${mins}min`;
    } else if (mins === 0) {
        return `${hours}h`;
    } else {
        return `${hours}h ${mins}min`;
    }
}

// Formate le statut pour l'affichage
// statut : Statut du rendez-vous
// Retourne : Libellé formaté
function formatStatut(statut) {
    const statuts = {
        'A_VENIR': 'À venir',
        'EN_COURS': 'En cours',
        'TERMINE': 'Terminé',
        'ANNULE': 'Annulé',
        'REPORTE': 'Reporté'
    };
    
    return statuts[statut] || statut;
}

// Retourne la classe CSS pour le statut
// statut : Statut du rendez-vous
// Retourne : Classe CSS
function formatStatutClass(statut) {
    const classes = {
        'A_VENIR': 'a-venir',
        'EN_COURS': 'en-cours',
        'TERMINE': 'termine',
        'ANNULE': 'annule',
        'REPORTE': 'reporte'
    };
    
    return classes[statut] || '';
}

// Formate un prix pour l'affichage
// price : Prix (number ou string)
// Retourne : Prix formaté (ex: "25,00 $")
function formatPrice(price) {
    if (!price) return '-';
    
    const numPrice = typeof price === 'string' ? parseFloat(price) : price;
    
    if (isNaN(numPrice)) {
        return price;
    }
    
    return numPrice.toFixed(2).replace('.', ',') + ' $';
}

// Échappe les caractères HTML pour éviter les injections XSS
// text : Texte à échapper
// Retourne : Texte échappé
function escapeHtml(text) {
    if (!text) return '';
    
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Trie les rendez-vous par date (plus récent en premier)
// rendezVous : Liste des rendez-vous
// Retourne : Liste triée
function sortRendezVousByDate(rendezVous) {
    return [...rendezVous].sort((a, b) => {
        const dateA = new Date(a.date_heure);
        const dateB = new Date(b.date_heure);
        return dateB - dateA; // Plus récent en premier
    });
}

// Affiche les rendez-vous dans le DOM
// rendezVous : Liste des rendez-vous à afficher
function displayRendezVous(rendezVous) {
    const rendezVousList = document.getElementById('rendezVousList');
    rendezVousList.innerHTML = ''; // Vider la liste
    
    rendezVous.forEach(rv => {
        const card = createRendezVousCard(rv);
        rendezVousList.appendChild(card);
    });
}

// Crée une carte HTML pour un rendez-vous
// rv : Données du rendez-vous
// Retourne : Élément HTML de la carte
function createRendezVousCard(rv) {
    const card = document.createElement('div');
    card.className = 'rendez-vous-card';
    
    // Déterminer si le rendez-vous est passé ou à venir
    const now = new Date();
    const rvDate = new Date(rv.date_heure);
    const isPast = rvDate < now;
    
    // Ajouter les classes CSS appropriées
    if (rv.statut === 'ANNULE') {
        card.classList.add('cancelled');
    } else if (rv.statut === 'TERMINE') {
        card.classList.add('completed');
    } else if (isPast) {
        card.classList.add('past');
    } else {
        card.classList.add('upcoming');
    }
    
    // Formater la date et l'heure
    const formattedDate = formatDateForHistorique(rv.date_heure);
    const formattedTime = formatTimeForHistorique(rv.date_heure);
    const formattedDuration = formatDuration(rv.duree);
    
    // Formater le statut
    const statutLabel = formatStatut(rv.statut);
    const statutClass = formatStatutClass(rv.statut);
    
    // Construire le HTML
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
                <span class="rendez-vous-info-value">${escapeHtml(rv.tuteur_prenom || '')} ${escapeHtml(rv.tuteur_nom || '')}</span>
            </div>
            
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Service</span>
                <span class="rendez-vous-info-value">${escapeHtml(rv.service_nom || 'Non spécifié')}</span>
            </div>
            
            ${rv.prix ? `
            <div class="rendez-vous-info">
                <span class="rendez-vous-info-label">Prix</span>
                <span class="rendez-vous-info-value">${formatPrice(rv.prix)}</span>
            </div>
            ` : ''}
        </div>
        
        ${rv.notes ? `
        <div class="rendez-vous-notes">
            <div class="rendez-vous-notes-label">Notes</div>
            <div class="rendez-vous-notes-content">${escapeHtml(rv.notes)}</div>
        </div>
        ` : ''}
    `;
    
    return card;
}
