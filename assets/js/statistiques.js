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
