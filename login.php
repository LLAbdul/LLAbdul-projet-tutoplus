<?php
/**
 * Page login.php
 * - Page de connexion simulée Étudiant / Tuteur / Administrateur
 * - Bascule via ?type=etudiant|tuteur|admin
 * - Redirige si déjà connecté
 *   - Étudiant -> index.php
 *   - Tuteur   -> gestion_disponibilites.php
 *   - Admin    -> admin.php
 */

session_start();

// Si déjà connecté, rediriger selon le type d'utilisateur
if (isset($_SESSION['etudiant_id'])) {
    header('Location: index.php');
    exit;
}
if (isset($_SESSION['tuteur_id'])) {
    header('Location: gestion_disponibilites.php');
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Déterminer le type de connexion (par défaut: étudiant)
$typeParam = $_GET['type'] ?? 'etudiant';
if ($typeParam === 'admin') {
    $loginType = 'admin';
} elseif ($typeParam === 'tuteur') {
    $loginType = 'tuteur';
} else {
    $loginType = 'etudiant';
}

$logoAhuntsicFull  = 'https://www.collegeahuntsic.qc.ca/assets/logo-ahuntsic@2x-d26df4e07b2c21fcf37f518dd0ddba254ead36b6184274af4a4f6ca3b47bc838.png';
$logoAhuntsicShort = 'assets/images/collegeahuntsiclogoshort.png';
$cacheBuster       = time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TutoPlus</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cacheBuster ?>">
    <link rel="stylesheet" href="assets/css/login.css?v=<?= $cacheBuster ?>">
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <a href="index.php" class="logo-link">
                    <img
                        src="<?= $logoAhuntsicFull ?>"
                        alt="Collège Ahuntsic"
                        class="college-logo college-logo-desktop"
                    >
                    <img
                        src="<?= $logoAhuntsicShort ?>"
                        alt="Collège Ahuntsic"
                        class="college-logo college-logo-mobile"
                    >
                </a>
            </div>
            <div class="header-center">
                <a href="index.php" class="header-title-link">
                    <h1>
                        <span class="logo-text">Tuto</span><span class="logo-accent">Plus</span>
                    </h1>
                </a>
                <p class="subtitle">Système de tutorat pour votre école</p>
            </div>
        </div>
    </div>
</header>

<main>
    <section class="login-section">
        <div class="login-container">
            <div class="login-card">

                <div class="login-type-switch">
                    <button
                        type="button"
                        class="switch-btn <?= $loginType === 'etudiant' ? 'active' : '' ?>"
                        data-type="etudiant"
                    >
                        Étudiant
                    </button>
                    <button
                        type="button"
                        class="switch-btn <?= $loginType === 'tuteur' ? 'active' : '' ?>"
                        data-type="tuteur"
                    >
                        Tuteur
                    </button>
                    <button
                        type="button"
                        class="switch-btn <?= $loginType === 'admin' ? 'active' : '' ?>"
                        data-type="admin"
                    >
                        Admin
                    </button>
                </div>

                <h2 class="login-title" id="login-title">
                    <?= $loginType === 'admin' ? 'Connexion Administrateur' : ($loginType === 'tuteur' ? 'Connexion Tuteur' : 'Connexion') ?>
                </h2>

                <p class="login-subtitle" id="login-subtitle">
                    <?php
                    if ($loginType === 'admin') {
                        echo 'Entrez votre numéro d\'administrateur pour vous connecter';
                    } elseif ($loginType === 'tuteur') {
                        echo 'Entrez votre numéro d\'employé pour vous connecter';
                    } else {
                        echo 'Entrez votre numéro d\'étudiant pour vous connecter';
                    }
                    ?>
                </p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="login-error">
                        <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form
                    action="<?php
                        if ($loginType === 'admin') {
                            echo 'login_admin_process.php';
                        } elseif ($loginType === 'tuteur') {
                            echo 'login_tuteur_process.php';
                        } else {
                            echo 'login_process.php';
                        }
                    ?>"
                    method="POST"
                    class="login-form"
                    id="login-form"
                >
                    <input type="hidden" name="login_type" value="<?= $loginType ?>">

                    <div class="form-group">
                        <label for="numero_input" class="form-label" id="numero-label">
                            <?php
                            if ($loginType === 'admin') {
                                echo 'Numéro d\'administrateur';
                            } elseif ($loginType === 'tuteur') {
                                echo 'Numéro d\'employé';
                            } else {
                                echo 'Numéro d\'étudiant';
                            }
                            ?>
                        </label>

                        <input
                            type="text"
                            id="numero_input"
                            name="<?php
                                if ($loginType === 'admin') {
                                    echo 'numero_admin';
                                } elseif ($loginType === 'tuteur') {
                                    echo 'numero_employe';
                                } else {
                                    echo 'numero_etudiant';
                                }
                            ?>"
                            class="form-input"
                            placeholder="<?php
                                if ($loginType === 'admin') {
                                    echo 'Ex: ADMIN001';
                                } elseif ($loginType === 'tuteur') {
                                    echo 'Ex: T001';
                                } else {
                                    echo 'Ex: E001';
                                }
                            ?>"
                            required
                            autofocus
                            autocomplete="off"
                        >
                    </div>

                    <button type="submit" class="btn-login">Se connecter</button>
                </form>

                <div class="login-info">
                    <p class="info-text" id="login-info">
                        <strong>Connexion simulée :</strong> Aucune validation Omnivox réelle.
                        <?php
                        if ($loginType === 'admin') {
                            echo 'Utilisez un numéro d\'administrateur de test (ex: ADMIN001).';
                        } elseif ($loginType === 'tuteur') {
                            echo 'Utilisez un numéro d\'employé de test (ex: T001, T002, T003, T004, T005, T006).';
                        } else {
                            echo 'Utilisez un numéro d\'étudiant de test (ex: E001, E002, E003, E004, E005).';
                        }
                        ?>
                    </p>
                </div>

            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> TutoPlus - Tous droits réservés</p>
            <img
                src="<?= $logoAhuntsicFull ?>"
                alt="Collège Ahuntsic"
                class="footer-logo"
            >
        </div>
    </div>
</footer>

<script src="assets/js/login.js?v=<?= $cacheBuster ?>"></script>
</body>
</html>
