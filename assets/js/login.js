// Gestion du switch entre étudiant et tuteur
document.addEventListener('DOMContentLoaded', () => {
    const switchButtons = document.querySelectorAll('.switch-btn');
    const baseLoginUrl = 'login.php?type='; // Base de redirection
    if (!switchButtons.length) return;

    switchButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            if (!type) return;

            window.location.href = baseLoginUrl + encodeURIComponent(type);
        });
    });
});
