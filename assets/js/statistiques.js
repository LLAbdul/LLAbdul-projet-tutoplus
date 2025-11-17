/**
 * Script pour la page de statistiques
 */

let rendezVousChart = null;
let currentChartData = null;
let currentChartType = 'bar';

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

        // Stocker les données pour le changement de type
        currentChartData = data.rendez_vous_par_statut;

        // Créer le graphique
        createRendezVousChart(currentChartData, currentChartType);

        // Initialiser le sélecteur de type
        initChartTypeSelector();

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
