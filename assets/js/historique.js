// Script pour gérer l'affichage de l'historique des séances

document.addEventListener('DOMContentLoaded', function() {
    loadRendezVous();
});

/**
 * Charge les rendez-vous depuis l'API
 */
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
        
        // TODO: Afficher les rendez-vous (prochain commit)
        console.log('Rendez-vous chargés:', data);
        
    } catch (error) {
        console.error('Erreur lors du chargement des rendez-vous:', error);
        
        // Cacher le chargement
        loadingIndicator.style.display = 'none';
        
        // Afficher l'erreur
        errorText.textContent = error.message || 'Une erreur est survenue lors du chargement de vos séances.';
        errorMessage.style.display = 'block';
    }
}
