/**
 * Script pour la page de statistiques
 */

let rendezVousChart = null;

// Charger les statistiques au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    loadStatistiques();
});

// Charger les statistiques depuis l'API
async function loadStatistiques() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const statistiquesContent = document.getElementById('statistiquesContent');

    try {
        loadingIndicator.style.display = 'block';
        errorMessage.style.display = 'none';
        statistiquesContent.style.display = 'none';

        const response = await fetch('api/statistiques.php');
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        // Afficher les statistiques générales
        displayStatistiquesGenerales(data.generales);

        // TODO: Créer le graphique
        // createRendezVousChart(data.rendez_vous_par_statut);

        // Afficher le contenu
        loadingIndicator.style.display = 'none';
        statistiquesContent.style.display = 'block';
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
        loadingIndicator.style.display = 'none';
        errorText.textContent = 'Erreur lors du chargement des statistiques. Veuillez réessayer.';
        errorMessage.style.display = 'block';
    }
}

// Afficher les statistiques générales
function displayStatistiquesGenerales(stats) {
    document.getElementById('totalRendezVous').textContent = stats.total_rendez_vous || 0;
    document.getElementById('totalTuteurs').textContent = stats.total_tuteurs || 0;
    document.getElementById('totalEtudiants').textContent = stats.total_etudiants || 0;
    document.getElementById('rendezVousTermines').textContent = stats.rendez_vous_termines || 0;
}
