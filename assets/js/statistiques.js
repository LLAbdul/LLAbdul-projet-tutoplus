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

        // Créer le graphique
        createRendezVousChart(data.rendez_vous_par_statut);

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
function createRendezVousChart(data) {
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

    rendezVousChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Nombre de rendez-vous',
                data: chartData,
                backgroundColor: backgroundColors,
                borderColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Nombre: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}
