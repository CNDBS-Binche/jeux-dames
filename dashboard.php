<?php
session_start();
require_once 'config.php';

// 1. SÉCURITÉ : Si l'utilisateur n'est pas connecté, redirection
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

// 2. RÉCUPÉRATION DES INFOS EN DIRECT DE LA BDD
$query = $bdd->prepare('SELECT pseudo, date_inscription, avatar FROM utilisateurs WHERE id = ?');
$query->execute([$_SESSION['user_id']]);
$user = $query->fetch();

// Si les statistiques ne sont pas encore calculées, on initialise à 0
$victoires = isset($victoires) ? $victoires : 0;
$defaites = isset($defaites) ? $defaites : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Jeu de Dames</title>
    <style>
       /* ==========================================================================
           1. LE FOND D'ÉCRAN BRUN BOISÉ ET SON QUADRILLAGE ANIMÉ (FIXE)
           ========================================================================== */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5; 
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
            overflow-x: hidden; /* Supprime la barre horizontale du bas due au fond animé sans bloquer le scroll vertical si nécessaire */
        }

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

        .btn-logout {
            background-color: rgba(62, 37, 16, 0.5);
            color: #e74c3c;
            margin-top: auto;
        }
        .sidebar-link.btn-logout:hover { 
            background-color: #c0392b; color: #fff; 
        }

        /* ==========================================================================
           3. CONTENU PRINCIPAL CADRÉ & CLASSES RESTAURÉES
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
            width: calc(100% - 240px);
            box-sizing: border-box;
            z-index: 1;
        }

        /* En-tête Utilisateur (Pseudo + Avatar + Date) */
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(43, 29, 18, 0.6);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar img, .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            font-size: 24px;
        }

        /* Grille du Dashboard (2 colonnes) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 30px;
        }

        .left-column, .right-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* Style des cadres (Cartes) */
        .card {
            background-color: rgba(43, 29, 18, 0.6);
            border-radius: 10px;
            padding: 25px;
            box-sizing: border-box;
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 20px;
            color: #fff;
        }

        .card p {
            margin-top: 0;
            margin-bottom: 20px;
            color: #c4b49c;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Boutons standardisés */
        .btn {
            display: block;
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-play {
            background-color: #81b64c;
            color: #fff;
            text-transform: uppercase;
        }

        .btn-primary {
            background-color: #5c3d24;
            color: #fff;
        }

        /* Section Statistiques spécifiques */
        .stat-box {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            background-color: rgba(0, 0, 0, 0.15);
            padding: 15px;
            border-radius: 6px;
            justify-content: space-around;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-val {
            font-size: 24px;
            font-weight: bold;
        }

        .stat-lbl {
            font-size: 12px;
            color: #a8947a;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .empty-state {
            font-style: italic;
            text-align: center;
            opacity: 0.6;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            ⚪ Jeu de Dames
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link active">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        
        <div class="user-header">
            <div class="user-profile-info">
                <div class="user-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Photo de profil">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
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
                    <a href="plateau.php" class="btn btn-play">Lancer une partie</a>
                </div>

                <div class="card">
                    <h2>💬 Salon Public & Chat</h2>
                    <p>Rejoignez le Hub pour discuter avec les joueurs connectés en temps réel et voir l'activité de la communauté globale.</p>
                    <a href="hub.php" class="btn btn-primary">Accéder au Hub</a>
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
                    <a href="profil.php" class="btn btn-primary">Détail du profil</a>
                </div>

                <div class="card">
                    <h2>👥 Liste d'amis</h2>
                    <p class="empty-state">Aucun ami en ligne pour le moment.</p>
                    <a href="amis.php" class="btn btn-primary">Gérer mes amis</a>
                </div>

                <div class="card">
                    <h2>🛡️ Mon Clan</h2>
                    <p>Créez une alliance, arborez un tag unique et participez au classement général des clans.</p>
                    <a href="clans.php" class="btn btn-primary">Accéder aux Clans</a>
                </div>
            </div>

        </div>
    </div>
    <?php include 'popup_invitation.php'; ?>
</body>
</html>