/* Testé par Diane Devi le 26/11/2025 Réussi */
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

// Afficher les statistiques générales
function displayStatistiquesGenerales(stats) {
    document.getElementById('totalRendezVous').textContent = stats.total_rendez_vous || 0;
    document.getElementById('totalTuteurs').textContent = stats.total_tuteurs || 0;
    document.getElementById('totalEtudiants').textContent = stats.total_etudiants || 0;
    document.getElementById('rendezVousTermines').textContent = stats.rendez_vous_termines || 0;
}

// Créer le graphique des rendez-vous par statut
function createRendezVousChart(data, chartType = 'bar') {
    const ctx = document.getElementById('rendezVousChart').getContext('2d');

    // Détruire le graphique existant s'il existe
    if (rendezVousChart) {
        rendezVousChart.destroy();
    }

    // Labels en français
    const labels = {
        'A_VENIR': 'À venir',
        'EN_COURS': 'En cours',
        'TERMINE': 'Terminé',
        'ANNULE': 'Annulé',
        'REPORTE': 'Reporté'
    };

    const chartLabels = Object.keys(data).map(key => labels[key] || key);
    const chartData = Object.values(data);

    // Couleurs pour chaque statut
    const colors = {
        'A_VENIR': '#2196F3',    // Bleu
        'EN_COURS': '#FF9800',   // Orange
        'TERMINE': '#4CAF50',    // Vert
        'ANNULE': '#F44336',     // Rouge
        'REPORTE': '#9C27B0'     // Violet
    };

    const backgroundColors = Object.keys(data).map(key => colors[key] || '#757575');

    // Configuration de base
    const baseConfig = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: chartType === 'pie' || chartType === 'doughnut'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (chartType === 'pie' || chartType === 'doughnut') {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                        return `Nombre: ${context.parsed.y || context.parsed}`;
                    }
                }
            }
        }
    };

    // Configuration spécifique selon le type
    if (chartType === 'bar' || chartType === 'line') {
        baseConfig.scales = {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        };
    }

    // Préparer les données selon le type
    let chartConfig;
    if (chartType === 'pie' || chartType === 'doughnut') {
        chartConfig = {
            type: chartType,
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Nombre de rendez-vous',
                    data: chartData,
                    backgroundColor: backgroundColors,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: baseConfig
        };
    } else {
        chartConfig = {
            type: chartType,
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Nombre de rendez-vous',
                    data: chartData,
                    backgroundColor: chartType === 'line' ? backgroundColors[0] : backgroundColors,
                    borderColor: chartType === 'line' ? backgroundColors[0] : backgroundColors,
                    borderWidth: chartType === 'line' ? 3 : 1,
                    fill: chartType === 'line' ? false : undefined,
                    tension: chartType === 'line' ? 0.4 : undefined
                }]
            },
            options: baseConfig
        };
    }

    rendezVousChart = new Chart(ctx, chartConfig);
    currentChartType = chartType;
}

// Initialiser le sélecteur de type de graphique
function initChartTypeSelector() {
    const chartTypeSelect = document.getElementById('chartType');
    if (!chartTypeSelect) return;

    chartTypeSelect.value = currentChartType;

    chartTypeSelect.addEventListener('change', (e) => {
        const newType = e.target.value;
        if (currentChartData) {
            createRendezVousChart(currentChartData, newType);
        }
    });
}
