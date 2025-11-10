// Gestion du switch entre étudiant et tuteur
document.querySelectorAll('.switch-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.dataset.type;
        window.location.href = 'login.php?type=' + type;
    });
});

