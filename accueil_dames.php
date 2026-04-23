<?php
session_start();
require_once('config.php');
$erreur = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifiant = $_POST['identifiant'];
    $password = $_POST['password'];

    $req = $bdd->prepare("SELECT * FROM utilisateurs WHERE email = ? OR pseudo = ?");
    $req->execute([$identifiant, $identifiant]);
    $user = $req->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        header('Location: index.php');
        exit();
    } else {
        $erreur = "Identifiant ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page de connection - Dames</title>
    <link rel="stylesheet" href="accueil.css">
</head>
<body>
    <div class="login-container">
        <h1>Connexion</h1>
        <p class="subtitle">Heureux de vous revoir !</p>

        <?php if($erreur): ?>
            <div class="alert error"><?php echo $erreur; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Email ou nom d'utilisateur</label>
                <input type="text" name="identifiant" required placeholder="Votre pseudo ou email">
            </div>
            <div class="input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Se connecter</button>
        </form>

        <div class="switch-mode">
            Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
        </div>
    </div>
</body>
</html>