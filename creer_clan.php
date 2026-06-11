<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

// TRAITEMENT DE LA CRÉATION DU CLAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Erreur de sécurité.";
        $messageType = "danger";
    } else {
        $nomClan = trim($_POST['nom_clan']);
        $drapeau = trim($_POST['drapeau']);
        $langue  = trim($_POST['langue']);
        $bio     = trim($_POST['bio_clan']);

        if (strlen($nomClan) < 4 || strlen($nomClan) > 30) {
            $message = "Le nom du clan doit contenir entre 4 et 30 caractères.";
            $messageType = "danger";
        } elseif (empty($bio)) {
            $message = "Veuillez insérer une description pour votre clan.";
            $messageType = "danger";
        } else {
            // Exemple d'insertion SQL mis à jour avec la langue :
            /*
            $req = $bdd->prepare('INSERT INTO clans (nom, drapeau, langue, bio, chef_id, trophees) VALUES (?, ?, ?, ?, ?, 0)');
            $req->execute([$nomClan, $drapeau, $langue, $bio, $userId]);
            */
            
            $message = "Félicitations ! Votre clan a été fondé avec succès.";
            $messageType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un Clan - Jeu de Dames</title>
    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5;
            overflow: hidden;
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
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

        .sidebar-brand { font-size: 22px; font-weight: bold; color: #fff; margin-bottom: 30px; padding-left: 15px; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 5px; flex-grow: 1; }
        .sidebar-link { display: flex; align-items: center; gap: 15px; color: #c4b49c; text-decoration: none; padding: 12px 15px; border-radius: 6px; font-weight: 600; transition: background 0.2s, color 0.2s; }
        .sidebar-link:hover { background-color: rgba(255, 255, 255, 0.05); color: #fff; }
        .sidebar-link.active { background-color: rgba(255, 255, 255, 0.1); color: #fff; border-left: 4px solid #81b64c; padding-left: 11px; }
        .btn-logout { background-color: rgba(62, 37, 16, 0.5); color: #e74c3c; margin-top: auto; }

        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            width: calc(100% - 240px);
            height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
        }

        .dashboard-inner {
            width: 100%;
            max-width: 1300px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .panel {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 35px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            box-sizing: border-box;
            max-width: 650px;
            width: 100%;
            margin: 0 auto;
        }

        .panel h2 {
            margin-top: 0;
            color: #fff;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 2px solid #7a4a28;
            padding-bottom: 10px;
            text-align: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 18px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #c4b49c;
        }

        .form-group input, .form-group select, .form-group textarea {
            padding: 12px;
            border: 1px solid #5d3a1a;
            border-radius: 6px;
            background-color: rgba(0, 0, 0, 0.4);
            color: #fff;
            font-size: 14.5px;
            outline: none;
            font-family: inherit;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #81b64c;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            padding: 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            border: none;
            transition: filter 0.2s, transform 0.1s;
            cursor: pointer;
            box-shadow: 0 4px 0 rgba(0,0,0,0.2);
            text-transform: uppercase;
        }
        .btn:active { transform: translateY(2px); box-shadow: 0 2px 0 rgba(0,0,0,0.2); }
        .btn-play { background-color: #81b64c; border-bottom: 4px solid #68943b; }
        .btn-play:hover { background-color: #95cc5a; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: center;}
        .alert-success { background-color: rgba(129, 182, 76, 0.2); color: #81b64c; border: 1px solid rgba(129, 182, 76, 0.4); }
        .alert-danger { background-color: rgba(192, 57, 43, 0.2); color: #e74c3c; border: 1px solid rgba(192, 57, 43, 0.4); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">⚪ Jeu de Dames</div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link active">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        <div class="dashboard-inner">
            
            <div class="panel">
                <h2>✨ Fonder une Faction</h2>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label for="nom_clan">Nom de la Faction</label>
                        <input type="text" id="nom_clan" name="nom_clan" placeholder="Ex: Les Empereurs du Damier" required autocomplete="off" max-length="30">
                    </div>

                    <div class="form-group">
                        <label for="drapeau">Drapeau principal</label>
                        <select id="drapeau" name="drapeau">
                            <option value="🇫🇷">France (🇫🇷)</option>
                            <option value="🇧🇪">Belgique (🇧🇪)</option>
                            <option value="🇨🇭">Suisse (🇨🇭)</option>
                            <option value="🇨🇦">Canada (🇨🇦)</option>
                            <option value="🌍">International (🌍)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="langue">Langue Principale du Clan</label>
                        <select id="langue" name="langue">
                            <option value="Français">Français</option>
                            <option value="Anglais">Anglais</option>
                            <option value="Espagnol">Espagnol</option>
                            <option value="Allemand">Allemand</option>
                            <option value="Multilingue">Multilingue (Toutes langues)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="bio_clan">Description / Devise du Clan</label>
                        <textarea id="bio_clan" name="bio_clan" rows="4" placeholder="Décrivez l'objectif de votre clan ou ses règles d'entrée..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-play" style="width: 100%; margin-top: 10px;">Créer le clan</button>
                </form>
            </div>

        </div>
    </div>

</body>
</html>