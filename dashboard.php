<?php
session_start();
require_once 'config.php';

// Sécurité : si l'utilisateur n'est pas connecté, retour à la page de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

// Récupération des infos fraîches de l'utilisateur depuis la brique de config $bdd
$query = $bdd->prepare('SELECT pseudo, date_inscription FROM utilisateurs WHERE id = ?');
$query->execute([$_SESSION['user_id']]);
$user = $query->fetch();

// Simulation rapide pour les statistiques en attendant les vraies tables
$victoires = 0; 
$defaites = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Jeu de Dames</title>
    <style>
        /* ==========================================================================
           1. LE FOND D'ÉCRAN BRUN BOISÉ ET SON QUADRILLAGE ANIMÉ
           ========================================================================== */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5; /* Écritures crème style échecs/dames */
            overflow-x: hidden;
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
        }

        /* Effet de grille animée en arrière-plan */
        body::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background-image: 
                linear-gradient(45deg, rgba(0,0,0,0.1) 25%, transparent 25%), 
                linear-gradient(-45deg, rgba(0,0,0,0.1) 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, rgba(0,0,0,0.1) 75%), 
                linear-gradient(-45deg, transparent 75%, rgba(0,0,0,0.1) 75%);
            background-size: 100px 100px;
            z-index: 0;
            animation: move 60s linear infinite;
        }

        @keyframes move {
            from { transform: translate(-25%, -25%); }
            to { transform: translate(0, 0); }
        }

        /* ==========================================================================
           2. BARRE LATÉRALE DE NAVIGATION (Marron sombre)
           ========================================================================== */
        .sidebar {
            width: 240px;
            background-color: rgba(27, 18, 11, 0.9);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            padding: 20px 10px;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            position: fixed;
            height: 100vh;
            box-sizing: border-box;
            z-index: 10;
        }

        .sidebar-brand {
            font-size: 22px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 30px;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex-grow: 1;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #c4b49c;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left: 4px solid #81b64c;
            padding-left: 11px;
        }

        /* ==========================================================================
           3. CONTENU PRINCIPAL & CARTES FLOTTANTES MARRONS
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
            z-index: 1;
        }

        /* En-tête Profil */
        .user-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(27, 18, 11, 0.8);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background-color: #5d3a1a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }

        /* Grille principale */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .left-column, .right-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Style des Cartes "Glow & Blur" version bois sombre */
        .card {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
        }

        .card h2 {
            margin-top: 0;
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card p {
            margin: 0 0 20px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #c4b49c;
        }

        /* La carte principale avec un dégradé boisé plus prononcé */
        .card-play {
            background: linear-gradient(135deg, rgba(62, 37, 16, 0.95) 0%, rgba(28, 17, 10, 0.95) 100%);
            border: 1px solid #7a4a28;
        }

        /* ==========================================================================
           4. BOUTONS STYLE CHESS.COM
           ========================================================================== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            border: none;
            transition: filter 0.2s, transform 0.1s;
            cursor: pointer;
            box-shadow: 0 4px 0 rgba(0,0,0,0.2);
        }

        .btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 0 rgba(0,0,0,0.2);
        }

        .btn-primary {
            background-color: #5d3a1a;
            border-bottom: 3px solid #3e2510;
        }
        .btn-primary:hover {
            background-color: #7a4a28; 
        }

        .btn-play {
            background-color: #81b64c;
            border-bottom: 4px solid #68943b;
            font-size: 18px;
            text-transform: uppercase;
            padding: 16px 30px;
        }
        .btn-play:hover { 
            background-color: #95cc5a; 
        }

        .btn-logout {
            background-color: rgba(62, 37, 16, 0.5);
            color: #e74c3c;
            margin-top: auto;
        }
        .sidebar-link.btn-logout:hover { 
            background-color: #c0392b; color: #fff; 
        }

        /* Bloc Statistiques */
        .stat-box {
            display: flex;
            gap: 15px;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.03);
        }

        .stat-item {
            flex: 1;
            text-align: center;
        }
        .stat-val {
            display: block;
            font-size: 20px;
            font-weight: bold;
            color: #fff;
        }
        .stat-lbl {
            font-size: 12px;
            color: #a8947a;
            text-transform: uppercase;
        }

        .empty-state {
            color: #a8947a;
            font-style: italic;
            text-align: center;
            padding: 20px 0;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            ⚪ Jeu de Dames
        </div>
        <div class="sidebar-menu">
            <a href="#" class="sidebar-link active">🏠 Accueil</a>
            <a href="jeu.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="#" class="sidebar-link">💬 Salon Public</a>
            <a href="#" class="sidebar-link">👥 Amis</a>
            <a href="#" class="sidebar-link">🛡️ Clans</a>
            <a href="#" class="sidebar-link">⚙️ Profil</a>
            
            <a href="logout.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="user-header">
            <div class="user-profile-info">
                <div class="user-avatar">👤</div>
                <div>
                    <h1 style="margin:0; font-size: 24px; color:#fff;"><?php echo htmlspecialchars($user['pseudo']); ?></h1>
                </div>
            </div>
            <div style="font-size: 13px; color: #a8947a;">
                Inscrit le <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="left-column">
                <div class="card card-play">
                    <h2>Prêt à en découdre ?</h2>
                    <p style="color: #f0d9b5; opacity: 0.8;">Défiez des joueurs en ligne, peaufinez vos tactiques et grimpez dans le classement du jeu de dames.</p>
                    <a href="jeu.php" class="btn btn-play">Lancer une partie</a>
                </div>

                <div class="card">
                    <h2>💬 Salon Public & Chat</h2>
                    <p>Rejoignez le Hub pour discuter avec les joueurs connectés en temps réel et voir l'activité de la communauté globale.</p>
                    <a href="#" class="btn btn-primary">Accéder au Hub</a>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <h2>📊 Mes Statistiques</h2>
                    <div class="stat-box">
                        <div class="stat-item">
                            <span class="stat-val" style="color: #81b64c;"><?php echo $victoires; ?></span>
                            <span class="stat-lbl">Victoires</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-val" style="color: #e74c3c;"><?php echo $defaites; ?></span>
                            <span class="stat-lbl">Défaites</span>
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary">Détail du profil</a>
                </div>

                <div class="card">
                    <h2>👥 Liste d'amis</h2>
                    <p class="empty-state">Aucun ami en ligne pour le moment.</p>
                    <a href="#" class="btn btn-primary">Gérer mes amis</a>
                </div>

                <div class="card">
                    <h2>🛡️ Mon Clan</h2>
                    <p>Créez une alliance, arborez un tag unique et participez au classement général des clans.</p>
                    <a href="#" class="btn btn-primary">Accéder aux Clans</a>
                </div>
            </div>

        </div>
    </div>
</body>
</html>