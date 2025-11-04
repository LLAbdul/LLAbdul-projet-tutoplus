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

    // Animation pour les sections de catégories
    const categorySections = document.querySelectorAll('.category-section');
    const categoryObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                categoryObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    categorySections.forEach(section => {
        categoryObserver.observe(section);
    });
});

