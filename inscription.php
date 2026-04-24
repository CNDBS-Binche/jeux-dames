<?php
require_once('config.php');
$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pseudo = $_POST['pseudo'];
    $email = $_POST['email'];
    $mdp_hache = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $ins = $bdd->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe) VALUES (?, ?, ?)");
        $ins->execute([$pseudo, $email, $mdp_hache]);
        $message = "Compte créé ! <a href='accueil_dames.php'>Connectez-vous</a>";
        $status = "success";
    } catch (Exception $e) {
        $message = "Erreur : Ce nom d'utilisateur ou email est déjà pris.";
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page d'inscription - Dames</title>
    <link rel="stylesheet" href="log.css">
</head>
<body>
    <div class="login-container">
        <h1>Inscription</h1>
        <p class="subtitle">Rejoignez l'élite des joueurs.</p>

        <?php if($message): ?>
            <div class="alert <?php echo $status; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="pseudo" required placeholder="Ex: PionMagique">
            </div>
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="joueur@mail.com">
            </div>
            <div class="input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn" style="background: var(--wood);">S'inscrire</button>
        </form>

        <div class="switch-mode">
            Déjà inscrit ? <a href="index.php">Se connecter</a>
        </div>
    </div>
</body>
</html>