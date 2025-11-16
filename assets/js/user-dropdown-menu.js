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

/**
 * Gestion du menu burger desktop pour les tuteurs
 */
document.addEventListener('DOMContentLoaded', () => {
    const burgerMenuBtnDesktop = document.getElementById('burgerMenuBtnDesktop');
    const burgerMenuDesktop = document.getElementById('burgerMenuDesktop');

    if (!burgerMenuBtnDesktop || !burgerMenuDesktop) {
        // Menu burger desktop non présent sur cette page
        return;
    }

    const openBurgerMenu = () => {
        burgerMenuDesktop.classList.add('active');
        burgerMenuBtnDesktop.classList.add('active');
    };

    const closeBurgerMenu = () => {
        burgerMenuDesktop.classList.remove('active');
        burgerMenuBtnDesktop.classList.remove('active');
    };

    const toggleBurgerMenu = () => {
        const isOpen = burgerMenuDesktop.classList.contains('active');
        isOpen ? closeBurgerMenu() : openBurgerMenu();
    };

    burgerMenuBtnDesktop.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleBurgerMenu();
    });

    // Fermer le menu en cliquant en dehors
    document.addEventListener('click', (e) => {
        if (!burgerMenuBtnDesktop.contains(e.target) && !burgerMenuDesktop.contains(e.target)) {
            closeBurgerMenu();
        }
    });

    // Fermer le menu en appuyant sur Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && burgerMenuDesktop.classList.contains('active')) {
            closeBurgerMenu();
        }
    });

    // Fermer le menu lors du clic sur un lien
    const burgerLinks = burgerMenuDesktop.querySelectorAll('.burger-menu-link');
    burgerLinks.forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(closeBurgerMenu, 100);
        });
    });
});
