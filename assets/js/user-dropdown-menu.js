/**
 * Gestion du menu dropdown utilisateur pour mobile
 */

document.addEventListener('DOMContentLoaded', () => {
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (!userMenuBtn || !userDropdownMenu) {
        // Menu dropdown non présent sur cette page
        return;
    }

    const openMenu = () => {
        userDropdownMenu.classList.add('active');
        userMenuBtn.classList.add('active');
    };

    const closeMenu = () => {
        userDropdownMenu.classList.remove('active');
        userMenuBtn.classList.remove('active');
    };

    const toggleMenu = () => {
        const isOpen = userDropdownMenu.classList.contains('active');
        isOpen ? closeMenu() : openMenu();
    };

    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMenu();
    });

    // Fermer le menu en cliquant en dehors
    document.addEventListener('click', (e) => {
        if (!userMenuBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
            closeMenu();
        }
    });

    // Fermer le menu en appuyant sur Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && userDropdownMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // Fermer le menu lors du clic sur un lien
    const dropdownLinks = userDropdownMenu.querySelectorAll('.dropdown-menu-link');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(closeMenu, 100);
        });
    });
});
