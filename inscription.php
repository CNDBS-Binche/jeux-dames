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
            $message = 'Compte créé ! <a href="connexion.php">Connectez-vous</a>';
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
    <link rel="stylesheet" href="log.css">
</head>
<body>
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
        <button type="submit" class="btn" style="background: var(--wood);">S'inscrire</button>
    </form>

    <div class="switch-mode">
        Déjà inscrit ? <a href="connexion.php">Se connecter</a>
    </div>
</div>
</body>
</html>
