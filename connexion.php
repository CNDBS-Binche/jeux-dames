<?php
session_start();
require_once 'config.php';

// Déjà connecté → rediriger directement
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($identifiant === '' || $password === '') {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $req = $bdd->prepare('SELECT id, pseudo, mot_de_passe FROM utilisateurs WHERE email = ? OR pseudo = ? LIMIT 1');
        $req->execute([$identifiant, $identifiant]);
        $user = $req->fetch();

        // Délai constant pour contrer le timing-attack
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            session_regenerate_id(true);            // ← prévention fixation de session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['pseudo']  = $user['pseudo'];
            header('Location: dashboard.php');
            exit();
        } else {
            $erreur = 'Identifiant ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion – Dames</title>
    <style>
       /* ==========================================================================
           1. LE FOND EXACT DU DASHBOARD (EFFET LOSANGES / SANS ANIMATION)
           ========================================================================== */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5; 
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                linear-gradient(45deg, rgba(0,0,0,0.15) 25%, transparent 25%), 
                linear-gradient(-45deg, rgba(0,0,0,0.15) 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, rgba(0,0,0,0.15) 75%), 
                linear-gradient(-45deg, transparent 75%, rgba(0,0,0,0.15) 75%);
            background-size: 100px 100px;
            z-index: 0;
        }

        /* ==========================================================================
           2. CADRE DE CONNEXION SÉCURISÉ
           ========================================================================== */
        .login-container {
            background-color: rgba(43, 29, 18, 0.6); /* Même opacité que le dashboard */
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 1;
            box-sizing: border-box;
        }

        .login-container h1 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 28px;
            color: #fff;
            text-align: center;
        }

        .subtitle {
            margin-top: 0;
            margin-bottom: 30px;
            color: #a8947a;
            text-align: center;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #c4b49c;
            font-weight: 600;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            background-color: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(122, 74, 40, 0.4);
            border-radius: 6px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-group input:focus {
            border-color: #81b64c;
        }

        .alert.error {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.4);
            color: #e74c3c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background-color: #81b64c;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn:hover {
            background-color: #6a9b3c;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .switch-mode {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #c4b49c;
        }

        .switch-mode a {
            color: #81b64c;
            text-decoration: none;
            font-weight: 600;
        }

        .switch-mode a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h1>Connexion</h1>
    <p class="subtitle">Heureux de vous revoir !</p>

    <?php if ($erreur): ?>
        <div class="alert error"><?php echo htmlspecialchars($erreur); ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="input-group">
            <label for="identifiant">Email ou nom d'utilisateur</label>
            <input type="text" id="identifiant" name="identifiant" required
                   placeholder="Votre pseudo ou email"
                   value="<?php echo htmlspecialchars($_POST['identifiant'] ?? ''); ?>">
        </div>
        <div class="input-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn">Se connecter</button>
    </form>

    <div class="switch-mode">
        Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
    </div>
</div>
<?php include 'popup_invitation.php'; ?>
</body>
</html>