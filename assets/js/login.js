// Gestion du switch entre étudiant et tuteur
document.addEventListener('DOMContentLoaded', () => {
    const switchButtons = document.querySelectorAll('.switch-btn');

    if (!switchButtons.length) return;

    switchButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            if (!type) return;

            const url = new URL('login.php', window.location.origin);
            url.searchParams.set('type', type);
            window.location.href = url.toString();
        });
    });
});
