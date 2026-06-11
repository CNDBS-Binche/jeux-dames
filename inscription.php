<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$status  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo   = trim($_POST['pseudo']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    // --- Validation minimale ---
    $errors = [];
    if (strlen($pseudo) < 3 || strlen($pseudo) > 30) {
        $errors[] = 'Le pseudo doit faire entre 3 et 30 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
    }

    if ($errors) {
        $message = implode('<br>', array_map('htmlspecialchars', $errors));
        $status  = 'error';
    } else {
        $mdp_hache = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            $ins = $bdd->prepare('INSERT INTO utilisateurs (pseudo, email, mot_de_passe) VALUES (?, ?, ?)');
            $ins->execute([$pseudo, $email, $mdp_hache]);
            $message = 'Compte créé ! <a href="dashboard.php">Connectez-vous</a>';
            $status  = 'success';
        } catch (PDOException $e) {
            $message = 'Ce nom d\'utilisateur ou cet email est déjà pris.';
            $status  = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription – Dames</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            overflow: hidden;
            position: relative;
        }

        .background-dashboard {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            z-index: 1;
            pointer-events: none;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px); /* Ajusté à 5px pour correspondre à ton dashboard */
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: rgba(43, 29, 18, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            box-sizing: border-box;
            color: #f0d9b5;
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
        
        .input-group label small {
            color: #a8947a;
            font-weight: normal;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(122, 74, 40, 0.6);
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

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert.error {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }

        .alert.success {
            background-color: rgba(129, 182, 76, 0.2);
            border: 1px solid #81b64c;
            color: #81b64c;
        }
        
        .alert.success a {
            color: #fff;
            text-decoration: underline;
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
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #6a9b3c;
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

    <iframe src="dashboard.php?hide_overlay=1" class="background-dashboard"></iframe>

    <div class="overlay">
        <div class="login-container">
            <h1>Inscription</h1>
            <p class="subtitle">Rejoignez l'élite des joueurs.</p>

            <?php if ($message): ?>
                <div class="alert <?php echo htmlspecialchars($status); ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="input-group">
                    <label for="pseudo">Nom d'utilisateur</label>
                    <input type="text" id="pseudo" name="pseudo" required minlength="3" maxlength="30"
                           placeholder="Ex: PionMagique"
                           value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>">
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                           placeholder="joueur@mail.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="input-group">
                    <label for="password">Mot de passe <small>(8 caractères min.)</small></label>
                    <input type="password" id="password" name="password" required minlength="8"
                           placeholder="••••••••">
                </div>
                <button type="submit" class="btn">S'inscrire</button>
            </form>
            
            <div class="switch-mode">
                Déjà inscrit ? <a href="dashboard.php">Se connecter</a>
            </div>
        </div>
    </div>
</body>
</html>