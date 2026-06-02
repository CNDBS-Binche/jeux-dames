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
        body {
            background-color: #2c3e50;
            color: #f0d9b5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        h1 {
            color: #fff;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        /* Grille du Dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            width: 100%;
            max-width: 1200px;
        }

        /* Design Boisé des Panneaux (Identique à ta DA) */
        .card {
            background-color: #5d3a1a;
            border: 4px solid #4a2e14;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card h2 {
            margin-top: 0;
            color: #fff;
            border-bottom: 2px solid #7a4a28;
            padding-bottom: 8px;
            font-size: 20px;
        }

        .card p {
            line-height: 1.5;
        }

        /* Zone de lancement rapide (Mise en valeur) */
        .card-play {
            grid-column: 1 / -1; /* Prend toute la largeur sur grand écran */
            background: linear-gradient(135deg, #7a4a28 0%, #5d3a1a 100%);
            text-align: center;
            align-items: center;
        }

        /* Boutons */
        .btn {
            display: inline-block;
            background-color: #7a4a28;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #4a2e14;
            transition: background 0.2s;
            margin-top: 10px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #8c5630;
        }

        .btn-play {
            background-color: #27ae60;
            border-color: #1e7e43;
            font-size: 18px;
            padding: 15px 40px;
            box-shadow: 0 4px 0 #1e7e43;
        }

        .btn-play:hover {
            background-color: #2ecc71;
        }

        .btn-logout {
            background-color: #c0392b;
            border-color: #962d22;
            position: absolute;
            top: 20px;
            right: 20px;
            margin-top: 0;
        }
        .btn-logout:hover { background-color: #e74c3c; }

        .stat-box {
            display: flex;
            justify-content: space-around;
            background-color: #3e2510;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <a href="deconnexion.php" class="btn btn-logout">Déconnexion</a>

    <h1>Tableau de Bord</h1>

    <div class="dashboard-grid">

        <div class="card card-play">
            <h2>Prêt à en découdre ?</h2>
            <p>Lancez une partie locale ou rejoignez l'arène de jeu de dames.</p>
            <a href="plateau.php" class="btn btn-play">LANCER UNE PARTIE</a>
        </div>

        <div class="card">
            <div>
                <h2>Mon Profil</h2>
                <p><strong>Pseudo :</strong> <?php echo htmlspecialchars($user['pseudo']); ?></p>
                <p><strong>Inscrit le :</strong> <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></p>
                <div class="stat-box">
                    <span>🏆 Victoires : <?php echo $victoires; ?></span>
                    <span>💀 Défaites : <?php echo $defaites; ?></span>
                </div>
            </div>
            <a href="profil.php" class="btn">Modifier le profil</a>
        </div>

        <div class="card">
            <div>
                <h2>Salon Public & Chat</h2>
                <p>Rejoignez le Hub pour discuter avec les joueurs connectés en temps réel et voir la communauté globale.</p>
            </div>
            <a href="hub.php" class="btn" style="background-color: #2980b9; border-color: #1f618d;">Accéder au Hub</a>
        </div>

        <div class="card">
            <div>
                <h2>Liste d'amis</h2>
                <p style="color: #a1887f; font-style: italic;">Aucun ami en ligne pour le moment.</p>
            </div>
            <a href="amis.php" class="btn">Gérer mes amis</a>
        </div>

        <div class="card">
            <div>
                <h2>Mon Clan</h2>
                <p>Créez une alliance, arborez un tag unique devant votre pseudo et participez au classement général des clans.</p>
            </div>
            <a href="clans.php" class="btn">Accéder aux Clans</a>
        </div>

    </div>
<div id="pop-up-defi" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#d35400; border:4px solid #a04000; padding:20px; border-radius:8px; box-shadow:0 10px 20px rgba(0,0,0,0.5); z-index:9999; color:white;">
    <p id="texte-defi" style="margin:0 0 15px 0; font-weight:bold;"></p>
    <div style="display:flex; gap:10px;">
        <button id="btn-accepter-defi" class="btn" style="background-color:#27ae60; margin:0;">Accepter</button>
        <button id="btn-refuser-defi" class="btn" style="background-color:#c0392b; margin:0;">Refuser</button>
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
            document.getElementById('texte-defi').innerText = `⚔️ ${data.adversaire} vous défie en duel !`;
            document.getElementById('pop-up-defi').style.display = 'block';
        }
    });
}

document.getElementById('btn-accepter-defi').addEventListener('click', () => {
    repondreDefi('accepte');
});

document.getElementById('btn-refuser-defi').addEventListener('click', () => {
    repondreDefi('refuse');
});

function repondreDefi(decision) {
    const data = new FormData();
    data.append('match_id', ID_MATCH_ACTUEL);
    data.append('decision', decision);

    fetch('./jcj_ajax.php?action=repondre', { method: 'POST', body: data })
    .then(() => {
        document.getElementById('pop-up-defi').style.display = 'none';
        if(decision === 'accepte') {
            // Redirection immédiate vers le plateau de jeu en transmettant l'ID de la partie !
            window.location.href = './plateau.php?match_id=' + ID_MATCH_ACTUEL;
        }
    });
}

// On vérifie toutes les 3 secondes si quelqu'un nous défie
setInterval(ecouterDefis, 3000);
</script>
</body>
</html>