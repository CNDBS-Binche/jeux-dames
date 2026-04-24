<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeux de Dames - Menu Principal</title>
    <link rel="stylesheet" href="accueil.css">
</head>
<body>

    <div class="home-container">
        <div class="pawn-icon">
            <div style="width: 20px; height: 20px; background: var(--wood); border-radius: 50%;"></div>
        </div>
        
        <h1 class="main-title">DAMES</h1>

        <div class="divider">
            <div class="line"></div>
            <div class="pion-mini blanc"></div>
            <div class="pion-mini noir"></div>
            <div class="line"></div>
        </div>

        <p class="subtitle">Défiez l'intelligence artificielle ou un ami en ligne.</p>

        <div class="menu-grid">
            <a href="plateau.php" class="btn-home btn-play">
                <span>⚡ Commencer à jouer</span>
            </a>

            <a href="connexion.php" class="btn-home">
                <span>👤 Connexion</span>
            </a>

            <a href="inscription.php" class="btn-home">
                <span>📝 Inscription</span>
            </a>
        </div>

        <div class="switch-mode" style="margin-top: 40px;">
            Version 1.0 &bull; <a href="README.md">Règles du jeu</a>
        </div>
    </div>

</body>
</html>