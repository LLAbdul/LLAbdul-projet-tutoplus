<?php
/**
 * Page de connexion simulée
 * TutoPlus - Système de tutorat
 */

session_start();

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['etudiant_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TutoPlus</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo-link">
                <h1><span class="logo-text">Tuto</span><span class="logo-accent">Plus</span></h1>
            </a>
            <p class="subtitle">Système de tutorat pour votre école</p>
        </div>
    </header>

    <main>
        <section class="login-section">
            <div class="login-container">
                <div class="login-card">
                    <h2 class="login-title">Connexion</h2>
                    <p class="login-subtitle">Entrez votre numéro d'étudiant pour vous connecter</p>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="login-error">
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="login_process.php" method="POST" class="login-form">
                        <div class="form-group">
                            <label for="numero_etudiant" class="form-label">Numéro d'étudiant</label>
                            <input 
                                type="text" 
                                id="numero_etudiant" 
                                name="numero_etudiant" 
                                class="form-input" 
                                placeholder="Ex: E001"
                                required
                                autofocus
                                autocomplete="off"
                            >
                        </div>
                        
                        <button type="submit" class="btn-login">Se connecter</button>
                    </form>
                    
                    <div class="login-info">
                        <p class="info-text">
                            <strong>Connexion simulée :</strong> Aucune validation Omnivox réelle. 
                            Utilisez un numéro d'étudiant de test (ex: E001, E002, E003, E004, E005).
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> TutoPlus - Tous droits réservés</p>
        </div>
    </footer>
</body>
</html>

