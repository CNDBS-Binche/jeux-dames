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
        /* --- Styles Généraux & Thème Bois Sombre --- */
        body {
            /* Effet texture bois subtil généré en CSS pour coller à la capture */
            background-color: #2b1d12;
            background-image: radial-gradient(rgba(255,255,255,0.05) 15%, transparent 16%),
                              radial-gradient(rgba(0,0,0,0.3) 50%, transparent 52%);
            background-size: 60px 60px;
            background-position: 0 0, 30px 30px;
            color: #bababa;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* --- Barre Latérale Gauche (Style Chess.com) --- */
        .sidebar {
            width: 240px;
            background-color: #21150d;
            display: flex;
            flex-direction: column;
            padding: 20px 10px;
            border-right: 1px solid #332216;
            position: fixed;
            height: 100vh;
            box-sizing: border-box;
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
            color: #bababa;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-link:hover {
            background-color: #2c1d13;
            color: #fff;
        }

        .sidebar-link.active {
            background-color: #312116;
            color: #fff;
            border-left: 4px solid #81b64c; /* Ligne verte comme Chess.com */
            padding-left: 11px;
        }

        /* --- Conteneur Principal --- */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
        }

        /* --- En-tête Profil --- */
        .user-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background-color: #4a3525;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }

        .user-stats-summary {
            display: flex;
            gap: 20px;
            margin-top: 5px;
            font-size: 14px;
        }

        .streak-badge {
            background-color: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* --- Grille des fonctionnalités --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Structure asymétrique style Chess.com */
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

        /* --- Cartes et Panneaux --- */
        .card {
            background-color: rgba(33, 21, 13, 0.5);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            backdrop-filter: blur(5px);
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
            color: #a3a3a3;
        }

        /* Zone Jouer (Mise en valeur) */
        .card-play {
            background: linear-gradient(135deg, #2d1e13 0%, #1c110a 100%);
            border: 1px solid #4a321f;
        }

        /* --- Boutons Style Chess.com --- */
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
            background-color: #453124;
            border-bottom: 3px solid #2d1f16;
        }
        .btn-primary:hover { filter: brightness(1.2); }

        .btn-play {
            background-color: #81b64c; /* Vert Chess.com */
            border-bottom: 4px solid #68943b;
            font-size: 18px;
            text-transform: uppercase;
            padding: 16px 30px;
        }
        .btn-play:hover { background-color: #95cc5a; }

        .btn-logout {
            background-color: #312116;
            color: #c0392b;
            margin-top: auto;
        }
        .btn-logout:hover { background-color: #c0392b; color: #fff; }

        /* Bloc des statistiques */
        .stat-box {
            display: flex;
            gap: 15px;
            background-color: #1a1009;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.02);
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
            color: #757575;
            text-transform: uppercase;
        }

        /* Liste d'amis vide */
        .empty-state {
            color: #635246;
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
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link">🛡️ Clans</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            
            <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="user-header">
            <div class="user-profile-info">
                <div class="user-avatar">👤</div>
                <div>
                    <h1 style="margin:0; font-size: 24px; color:#fff;"><?php echo htmlspecialchars($user['pseudo']); ?></h1>
                    <span class="streak-badge">🔥 54 Jours de série</span>
                </div>
            </div>
            <div style="font-size: 13px; color: #757575;">
                Inscrit le <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="left-column">
                <div class="card card-play">
                    <h2>Prêt à en découdre ?</h2>
                    <p>Défiez des joueurs en ligne, peaufinez vos tactiques et grimpez dans le classement du jeu de dames.</p>
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

    <div id="pop-up-defi" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#21150d; border:2px solid #81b64c; padding:20px; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.6); z-index:9999; color:white; min-width: 250px;">
        <p id="texte-defi" style="margin:0 0 15px 0; font-weight:bold;"></p>
        <div style="display:flex; gap:10px;">
            <button id="btn-accepter-defi" class="btn" style="background-color:#81b64c; flex:1; padding: 8px;">Accepter</button>
            <button id="btn-refuser-defi" class="btn" style="background-color:#c0392b; flex:1; padding: 8px;">Refuser</button>
        </div>
    </div>

    <script>
    let ID_MATCH_ACTUEL = null;

    function ecouterDefis() {
        fetch('./jcj_ajax.php?action=verifier_defis')
        .then(r => r.json())
        .then(data => {
            if(data.type === 'recu') {
                ID_MATCH_ACTUEL = data.match_id;
                document.getElementById('texte-defi').innerText = `⚔️ ${data.adversaire} vous défie !`;
                document.getElementById('pop-up-defi').style.display = 'block';
            }
        });
    }

    document.getElementById('btn-accepter-defi').addEventListener('click', () => { repondreDefi('accepte'); });
    document.getElementById('btn-refuser-defi').addEventListener('click', () => { repondreDefi('refuse'); });

    function repondreDefi(decision) {
        const data = new FormData();
        data.append('match_id', ID_MATCH_ACTUEL);
        data.append('decision', decision);

        fetch('./jcj_ajax.php?action=repondre', { method: 'POST', body: data })
        .then(() => {
            document.getElementById('pop-up-defi').style.display = 'none';
            if(decision === 'accepte') {
                window.location.href = './plateau.php?match_id=' + ID_MATCH_ACTUEL;
            }
        });
    }

    setInterval(ecouterDefis, 3000);
    </script>
</body>
</html>