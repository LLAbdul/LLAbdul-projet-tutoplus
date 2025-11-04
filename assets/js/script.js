// Scroll smooth vers la section des services
document.addEventListener('DOMContentLoaded', () => {
    const heroCta = document.querySelector('.hero-cta');
    if (heroCta) {
        heroCta.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = heroCta.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                // Animation de scroll
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    }

    // Animation au scroll pour les cartes de services
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('slide-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observer les cartes de services
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        observer.observe(card);
    });

    // Système d'onglets pour les catégories de services
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const category = button.getAttribute('data-category');

            // Retirer active de tous les boutons et panels
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));

            // Ajouter active au bouton et panel sélectionnés
            button.classList.add('active');
            const targetPanel = document.querySelector(`.tab-panel[data-category="${category}"]`);
            if (targetPanel) {
                targetPanel.classList.add('active');
                
                // Réinitialiser les animations des cartes
                const cards = targetPanel.querySelectorAll('.service-card');
                cards.forEach((card, index) => {
                    card.style.animationDelay = `${index * 0.1}s`;
                    if (!card.classList.contains('slide-up')) {
                        card.classList.add('slide-up');
                    }
                });
            }
        });
    });

    // Observer les cartes de services dans les panels actifs
    const activePanel = document.querySelector('.tab-panel.active');
    if (activePanel) {
        const activeCards = activePanel.querySelectorAll('.service-card');
        activeCards.forEach(card => {
            observer.observe(card);
        });
    }
});

