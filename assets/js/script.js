// Script principal de la page d'accueil (scroll, animations, onglets services)
document.addEventListener('DOMContentLoaded', () => {
    const HEADER_OFFSET = 80;

    // --- Scroll smooth vers la section des services ---
    const heroCta = document.querySelector('.hero-cta');

    if (heroCta) {
        heroCta.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = heroCta.getAttribute('href');
            if (!targetId || !targetId.startsWith('#')) return;

            const targetElement = document.querySelector(targetId);
            if (!targetElement) return;

            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - HEADER_OFFSET;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        });
    }

    // --- Animation au scroll pour les cartes de services ---
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const serviceObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('slide-up');
            observer.unobserve(entry.target);
        });
    }, observerOptions);

    function observeServiceCards(container = document) {
        const cards = container.querySelectorAll('.service-card');
        cards.forEach(card => serviceObserver.observe(card));
    }

    // Observer les cartes déjà visibles au chargement
    observeServiceCards();

    // --- Système d'onglets pour les catégories de services ---
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    function setActiveTab(category) {
        // Retirer active de tous les boutons et panels
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabPanels.forEach(panel => panel.classList.remove('active'));

        // Activer le bouton courant
        const activeButton = document.querySelector(`.tab-btn[data-category="${category}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }

        // Activer le panel correspondant
        const targetPanel = document.querySelector(`.tab-panel[data-category="${category}"]`);
        if (targetPanel) {
            targetPanel.classList.add('active');

            // Réinitialiser les animations des cartes du panel actif
            const cards = targetPanel.querySelectorAll('.service-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                if (!card.classList.contains('slide-up')) {
                    // Laisser l'observer les animer au scroll
                    serviceObserver.observe(card);
                }
            });
        }
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const category = button.getAttribute('data-category');
            if (!category) return;
            setActiveTab(category);
        });
    });

    // Si un panel est déjà actif dans le HTML au chargement
    const activePanel = document.querySelector('.tab-panel.active');
    if (activePanel) {
        const activeCards = activePanel.querySelectorAll('.service-card');
        activeCards.forEach(card => {
            serviceObserver.observe(card);
        });
    }
});
