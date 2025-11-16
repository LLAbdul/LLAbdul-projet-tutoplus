/**
 * Gestion du menu dropdown utilisateur pour mobile
 */

document.addEventListener('DOMContentLoaded', function() {
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (!userMenuBtn || !userDropdownMenu) {
        return; // Menu dropdown non présent sur cette page
    }

    function toggleMenu() {
        const isOpen = userDropdownMenu.classList.contains('active');
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    function openMenu() {
        userDropdownMenu.classList.add('active');
        userMenuBtn.classList.add('active');
    }

    function closeMenu() {
        userDropdownMenu.classList.remove('active');
        userMenuBtn.classList.remove('active');
    }

    userMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });

    // Fermer le menu en cliquant en dehors
    document.addEventListener('click', function(e) {
        if (!userMenuBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
            closeMenu();
        }
    });

    // Fermer le menu en appuyant sur Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && userDropdownMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // Fermer le menu lors du clic sur un lien
    const dropdownLinks = userDropdownMenu.querySelectorAll('.dropdown-menu-link');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(closeMenu, 100);
        });
    });
});

