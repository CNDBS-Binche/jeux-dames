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
    <link rel="stylesheet" href="log.css">
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
