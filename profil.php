<?php
session_start();
require_once 'config.php';

// 1. SÉCURITÉ : Si l'utilisateur n'est pas connecté, retour à la page de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

// Utilisation des sessions pour afficher les messages après redirection (Pattern PRG)
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// 2. TRAITEMENT DU FORMULAIRE DE MISE À POUR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Cas de la mise à jour des paramètres texte
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_pseudo = trim($_POST['pseudo']);
        $new_bio = trim($_POST['biographie']);
        $new_flag = trim($_POST['code_drapeau']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (!empty($new_pseudo)) {
            try {
                if (!empty($password)) {
                    if ($password === $confirm_password) {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $update = $bdd->prepare('UPDATE utilisateurs SET pseudo = ?, biographie = ?, code_drapeau = ?, mot_de_passe = ? WHERE id = ?');
                        $update->execute([$new_pseudo, $new_bio, $new_flag, $hashed_password, $_SESSION['user_id']]);
                        $_SESSION['success_msg'] = "Profil et mot de passe mis à jour !";
                    } else {
                        $_SESSION['error_msg'] = "Les mots de passe ne correspondent pas.";
                    }
                } else {
                    $update = $bdd->prepare('UPDATE utilisateurs SET pseudo = ?, biographie = ?, code_drapeau = ? WHERE id = ?');
                    $update->execute([$new_pseudo, $new_bio, $new_flag, $_SESSION['user_id']]);
                    $_SESSION['success_msg'] = "Profil mis à jour avec succès !";
                }
            } catch (Exception $e) {
                error_log('Profile update: ' . $e->getMessage());
                $_SESSION['error_msg'] = "Erreur lors de la mise à jour. Veuillez réessayer.";
            }
        }
        // Redirection pour éviter le renvoi du formulaire au rafraîchissement
        header('Location: profil.php');
        exit();
    }

    // Cas du changement dynamique de Thème (Fetch)
    if (isset($_POST['action']) && $_POST['action'] === 'update_theme') {
        $light = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['theme_light'] ?? '') ? $_POST['theme_light'] : '#f0d9b5';
        $dark  = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['theme_dark']  ?? '') ? $_POST['theme_dark']  : '#b58863';
        $update = $bdd->prepare('UPDATE utilisateurs SET theme_light = ?, theme_dark = ? WHERE id = ?');
        $update->execute([$light, $dark, $_SESSION['user_id']]);
        echo json_encode(['status' => 'success']);
        exit();
    }

    // Cas du téléversement de la Photo de profil en Base64 (Fetch)
    if (isset($_POST['action']) && $_POST['action'] === 'update_avatar') {
        $avatar_data = $_POST['avatar_base64'];
        $update = $bdd->prepare('UPDATE utilisateurs SET avatar = ? WHERE id = ?');
        $update->execute([$avatar_data, $_SESSION['user_id']]);
        echo json_encode(['status' => 'success']);
        exit();
    }
}

// 3. RÉCUPÉRATION DES INFOS FRAÎCHES DE L'UTILISATEUR
$query = $bdd->prepare('SELECT pseudo, date_inscription, biographie, code_drapeau, avatar, theme_light, theme_dark FROM utilisateurs WHERE id = ?');
$query->execute([$_SESSION['user_id']]);
$user = $query->fetch();

// Valeur par défaut vide pour la biographie
$user_bio = !empty($user['biographie']) ? $user['biographie'] : "";
$user_flag = !empty($user['code_drapeau']) ? $user['code_drapeau'] : "un";

// Validation stricte des couleurs récupérées de la BDD pour le bloc CSS :root
$theme_light = (isset($user['theme_light']) && preg_match('/^#[0-9a-fA-F]{6}$/', $user['theme_light'])) ? $user['theme_light'] : "#f0d9b5";
$theme_dark = (isset($user['theme_dark']) && preg_match('/^#[0-9a-fA-F]{6}$/', $user['theme_dark'])) ? $user['theme_dark'] : "#b58863";

$victoires = 0; 
$defaites = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['pseudo'] ?? 'Mon Profil'); ?> - Profil - Jeu de Dames</title>
    <style>
        :root {
            --light-square: <?php echo $theme_light; ?>;
            --dark-square: <?php echo $theme_dark; ?>;
        }

        body {
            display: flex;
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

        .sidebar-brand {
            font-size: 22px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 30px;
            padding-left: 15px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
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
            transition: background-color 0.2s, color 0.2s, border-left 0.1s;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left: 4px solid #81b64c;
            border-radius: 0 6px 6px 0;
            padding-left: 11px;
        }

        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            height: 100vh;
            overflow-y: auto;
            z-index: 1;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center; /* Centre les conteneurs enfants horizontalement */
        }

        /* Conteneur interne pour limiter la largeur et centrer parfaitement les cadres */
        .main-container {
            max-width: 1200px;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .profile-header {
            background: rgba(27, 18, 11, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 30px;
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .profile-main-info {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .big-avatar {
            width: 120px;
            height: 120px;
            background-color: #e2e2e2;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 70px;
            color: #8b8b8b;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        
        .big-avatar:hover {
            transform: scale(1.02);
        }

        .avatar-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            opacity: 0;
            transition: opacity 0.2s;
            padding: 5px;
            box-sizing: border-box;
        }
        
        .big-avatar:hover .avatar-overlay {
            opacity: 1;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
        }

        .username-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .username-row h1 {
            margin: 0;
            font-size: 26px;
            color: #fff;
        }

        .flag-img {
            width: 24px;
            height: auto;
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            display: inline-block;
            vertical-align: middle;
        }

        .bio-display-line {
            color: #d2c1ab;
            font-size: 13.5px;
            background: rgba(0,0,0,0.15);
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 4px;
            max-width: 600px;
            border-left: 3px solid #81b64c;
            font-style: italic;
            min-height: 18px;
        }

        .meta-info {
            font-size: 13px;
            color: #a8947a;
            display: flex;
            gap: 12px;
            margin-top: 4px;
        }

        .meta-info span strong {
            color: #fff;
        }

        .btn-modifier-profil {
            position: absolute;
            top: 24px;
            right: 24px;
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-modifier-profil:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .profile-tabs {
            display: flex;
            gap: 20px;
            margin-top: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 5px;
        }

        .tab-link {
            color: #a8947a;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 5px 0;
            transition: color 0.2s;
        }

        .tab-link:hover {
            color: #fff;
        }

        .tab-link.active {
            color: #fff;
            border-bottom: 3px solid #81b64c;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            width: 100%;
        }

        .card-left-placeholder {
            background-color: rgba(27, 18, 11, 0.2);
            border: 1px dashed rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            color: #a8947a;
            font-style: italic;
        }

        .card-right-item {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-right-item.clickable {
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        
        .card-right-item.clickable:hover {
            background-color: rgba(53, 34, 21, 0.95);
            border-color: #81b64c;
        }

        .item-title {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mini-board-preview {
            display: grid;
            grid-template-columns: repeat(4, 12px);
            grid-template-rows: repeat(4, 12px);
            width: 48px;
            height: 48px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .mini-board-preview div.light { background-color: var(--light-square); }
        .mini-board-preview div.dark { background-color: var(--dark-square); }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-card {
            background-color: rgba(33, 21, 13, 0.95);
            border: 1px solid #7a4a28;
            border-radius: 8px;
            width: 460px;
            padding: 25px 30px;
            box-shadow: 0 15px 5px rgba(0,0,0,0.5);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-card h2 {
            margin-top: 0;
            color: #fff;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; color: #c4b49c; font-weight: 600; }
        
        .form-control { 
            width: 100%; 
            background: rgba(0,0,0,0.5); 
            border: 1px solid rgba(255,255,255,0.1); 
            padding: 10px; 
            color: #fff; 
            border-radius: 4px; 
            box-sizing: border-box;
            font-family: inherit;
        }
        textarea.form-control { resize: vertical; min-height: 60px; }
        select.form-control option { background-color: #21150d; color: #fff; }

        .password-section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ff6b6b;
            margin: 18px 0 10px 0;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            padding-bottom: 4px;
            font-weight: bold;
        }

        .btn-save { background-color: #81b64c; border: none; border-bottom: 3px solid #68943b; color: #fff; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-close-modal { position: absolute; top: 15px; right: 15px; background: transparent; border: none; color: #a8947a; font-size: 20px; cursor: pointer; }

        .themes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .theme-option { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; padding: 12px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: transform 0.2s, border-color 0.2s; }
        .theme-option:hover { transform: scale(1.03); border-color: #81b64c; }
        .theme-option span { font-size: 13px; font-weight: 600; color: #fff; }
        .theme-showcase { display: grid; grid-template-columns: repeat(2, 16px); grid-template-rows: repeat(2, 16px); width: 32px; height: 32px; border: 1px solid rgba(255,255,255,0.1); }

        .alert-error { background: rgba(255, 107, 107, 0.15); border: 1px solid #ff6b6b; color: #ff8787; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        .alert-success { background: rgba(129, 182, 76, 0.2); border: 1px solid #81b64c; color: #95cc5a; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">⚪ Jeu de Dames</div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link active">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link" style="margin-top: auto; color: #e74c3c;">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        <div class="main-container">
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <div class="profile-header">
                <button class="btn-modifier-profil" onclick="openModal('editModal')">Modifier le profil</button>
                
                <div class="profile-main-info">
                    <input type="file" id="avatarFileInput" accept="image/*" style="display: none;" onchange="uploadAvatar(event)">
                    
                    <div class="big-avatar" id="avatarContainer" onclick="triggerAvatarUpload()" title="Cliquez pour changer votre photo de profil">
                        <?php if(!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="avatar-img" id="avatarImg" alt="Avatar">
                        <?php else: ?>
                            <span id="avatarPlaceholder">♙</span>
                        <?php endif; ?>
                        <div class="avatar-overlay">Changer la photo</div>
                    </div>
                    
                    <div class="user-details">
                        <div class="username-row">
                            <h1 id="displayPseudo"><?php echo htmlspecialchars($user['pseudo'] ?? ''); ?></h1>
                            <span id="flagContainer">
                                <img class="flag-img" src="https://flagcdn.com/w40/<?php echo htmlspecialchars($user_flag); ?>.png" alt="Drapeau">
                            </span>
                        </div>
                        
                        <div class="bio-display-line" id="displayBio">
                            <?php echo !empty($user_bio) ? htmlspecialchars($user_bio) : "Aucune biographie pour le moment."; ?>
                        </div>
                        
                        <div class="meta-info">
                            <span>Inscription le <strong><?php echo htmlspecialchars($user['date_inscription'] ?? 'Inconnue'); ?></strong></span>
                            <span>•</span>
                            <span><strong>2</strong> amis</span>
                            <span>•</span>
                            <span><strong><?php echo $victoires; ?></strong> Victoires / <strong><?php echo $defaites; ?></strong> Défaites</span>
                            <span>•</span>
                            <span style="color: #81b64c;">En ligne maintenant</span>
                        </div>
                    </div>
                </div>

                <div class="profile-tabs">
                    <a href="profil.php" class="tab-link active">Aperçu</a>
                    <a href="parties.php" class="tab-link">Parties</a>
                    <a href="clans.php" class="tab-link">Clans</a>
                    <a href="amis.php" class="tab-link">Amis</a>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="card-left-placeholder">
                    <p>Vous n'avez pas de parties actives.</p>
                    <div style="font-size: 12px; margin-top: 10px;">Historique des affrontements (0)</div>
                </div>

                <div>
                    <div class="card-right-item">
                        <span class="item-title"><span style="color:#ff6b6b;">🔥</span> Série de 54 jours</span>
                    </div>

                    <div class="card-right-item clickable" onclick="openModal('themeModal')">
                        <div>
                            <span class="item-title">Votre thème</span>
                            <div style="font-size:11px; color: #81b64c; margin-top:2px;">Modifier ⚙️</div>
                        </div>
                        <div class="mini-board-preview" id="currentThemePreview">
                            <div class="light"></div><div class="dark"></div><div class="light"></div><div class="dark"></div>
                            <div class="dark"></div><div class="light"></div><div class="dark"></div><div class="light"></div>
                            <div class="light"></div><div class="dark"></div><div class="light"></div><div class="dark"></div>
                            <div class="dark"></div><div class="light"></div><div class="dark"></div><div class="light"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <button class="btn-close-modal" onclick="closeModal('editModal')">×</button>
            <h2>⚙️ Paramètres du Profil</h2>
            <div class="alert-error" id="modalErrorAlert" style="display:none;"></div>
            
            <form action="profil.php" method="POST" onsubmit="return validatePasswordMatch(event)">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="inputPseudo">Votre Pseudo</label>
                    <input type="text" name="pseudo" id="inputPseudo" class="form-control" value="<?php echo htmlspecialchars($user['pseudo'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="inputBio">Biographie / Présentation</label>
                    <textarea name="biographie" id="inputBio" class="form-control" rows="3" placeholder="Parlez-nous de vous..."><?php echo htmlspecialchars($user_bio); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="inputFlag">Nationalité (Drapeau)</label>
                    <select name="code_drapeau" id="inputFlag" class="form-control">
                        <option value="un" <?php echo $user_flag == 'un' ? 'selected' : ''; ?>>🌐 International (Universel)</option>
                        <option value="un" <?php echo $user_flag == 'un' ? 'selected' : ''; ?>>🌐 International (Universel)</option>
<option value="af" <?php echo $user_flag == 'af' ? 'selected' : ''; ?>>🇦🇫 Afghanistan</option>
<option value="za" <?php echo $user_flag == 'za' ? 'selected' : ''; ?>>🇿🇦 Afrique du Sud</option>
<option value="al" <?php echo $user_flag == 'al' ? 'selected' : ''; ?>>🇦🇱 Albanie</option>
<option value="dz" <?php echo $user_flag == 'dz' ? 'selected' : ''; ?>>🇩🇿 Algérie</option>
<option value="de" <?php echo $user_flag == 'de' ? 'selected' : ''; ?>>🇩🇪 Allemagne</option>
<option value="ad" <?php echo $user_flag == 'ad' ? 'selected' : ''; ?>>🇦🇩 Andorre</option>
<option value="ao" <?php echo $user_flag == 'ao' ? 'selected' : ''; ?>>🇦🇴 Angola</option>
<option value="ag" <?php echo $user_flag == 'ag' ? 'selected' : ''; ?>>🇦🇬 Antigua-et-Barbuda</option>
<option value="sa" <?php echo $user_flag == 'sa' ? 'selected' : ''; ?>>🇸🇦 Arabie saoudite</option>
<option value="ar" <?php echo $user_flag == 'ar' ? 'selected' : ''; ?>>🇦🇷 Argentine</option>
<option value="am" <?php echo $user_flag == 'am' ? 'selected' : ''; ?>>🇦🇲 Arménie</option>
<option value="au" <?php echo $user_flag == 'au' ? 'selected' : ''; ?>>🇦🇺 Australie</option>
<option value="at" <?php echo $user_flag == 'at' ? 'selected' : ''; ?>>🇦🇹 Autriche</option>
<option value="az" <?php echo $user_flag == 'az' ? 'selected' : ''; ?>>🇦🇿 Azerbaïdjan</option>
<option value="bs" <?php echo $user_flag == 'bs' ? 'selected' : ''; ?>>🇧🇸 Bahamas</option>
<option value="bh" <?php echo $user_flag == 'bh' ? 'selected' : ''; ?>>🇧🇭 Bahreïn</option>
<option value="bd" <?php echo $user_flag == 'bd' ? 'selected' : ''; ?>>🇧🇩 Bangladesh</option>
<option value="bb" <?php echo $user_flag == 'bb' ? 'selected' : ''; ?>>🇧🇧 Barbade</option>
<option value="be" <?php echo $user_flag == 'be' ? 'selected' : ''; ?>>🇧🇪 Belgique</option>
<option value="bz" <?php echo $user_flag == 'bz' ? 'selected' : ''; ?>>🇧🇿 Belize</option>
<option value="bj" <?php echo $user_flag == 'bj' ? 'selected' : ''; ?>>🇧🇯 Bénin</option>
<option value="bt" <?php echo $user_flag == 'bt' ? 'selected' : ''; ?>>🇧🇹 Bhoutan</option>
<option value="by" <?php echo $user_flag == 'by' ? 'selected' : ''; ?>>🇧🇾 Biélorussie</option>
<option value="mm" <?php echo $user_flag == 'mm' ? 'selected' : ''; ?>>🇲🇲 Birmanie</option>
<option value="bo" <?php echo $user_flag == 'bo' ? 'selected' : ''; ?>>🇧🇴 Bolivie</option>
<option value="ba" <?php echo $user_flag == 'ba' ? 'selected' : ''; ?>>🇧🇦 Bosnie-Herzégovine</option>
<option value="bw" <?php echo $user_flag == 'bw' ? 'selected' : ''; ?>>🇧🇼 Botswana</option>
<option value="br" <?php echo $user_flag == 'br' ? 'selected' : ''; ?>>🇧🇷 Brésil</option>
<option value="bn" <?php echo $user_flag == 'bn' ? 'selected' : ''; ?>>🇧🇳 Brunei</option>
<option value="bg" <?php echo $user_flag == 'bg' ? 'selected' : ''; ?>>🇧🇬 Bulgarie</option>
<option value="bf" <?php echo $user_flag == 'bf' ? 'selected' : ''; ?>>🇧🇫 Burkina Faso</option>
<option value="bi" <?php echo $user_flag == 'bi' ? 'selected' : ''; ?>>🇧🇮 Burundi</option>
<option value="kh" <?php echo $user_flag == 'kh' ? 'selected' : ''; ?>>🇰🇭 Cambodge</option>
<option value="cm" <?php echo $user_flag == 'cm' ? 'selected' : ''; ?>>🇨🇲 Cameroun</option>
<option value="ca" <?php echo $user_flag == 'ca' ? 'selected' : ''; ?>>🇨🇦 Canada</option>
<option value="cv" <?php echo $user_flag == 'cv' ? 'selected' : ''; ?>>🇨🇻 Cap-Vert</option>
<option value="cf" <?php echo $user_flag == 'cf' ? 'selected' : ''; ?>>🇨🇫 République centrafricaine</option>
<option value="cl" <?php echo $user_flag == 'cl' ? 'selected' : ''; ?>>🇨🇱 Chili</option>
<option value="cn" <?php echo $user_flag == 'cn' ? 'selected' : ''; ?>>🇨🇳 Chine</option>
<option value="cy" <?php echo $user_flag == 'cy' ? 'selected' : ''; ?>>🇨🇾 Chypre</option>
<option value="co" <?php echo $user_flag == 'co' ? 'selected' : ''; ?>>🇨🇴 Colombie</option>
<option value="km" <?php echo $user_flag == 'km' ? 'selected' : ''; ?>>🇰🇲 Comores</option>
<option value="cg" <?php echo $user_flag == 'cg' ? 'selected' : ''; ?>>🇨🇬 Congo-Brazzaville</option>
<option value="cd" <?php echo $user_flag == 'cd' ? 'selected' : ''; ?>>🇨🇩 Congo-Kinshasa</option>
<option value="kp" <?php echo $user_flag == 'kp' ? 'selected' : ''; ?>>🇰🇵 Corée du Nord</option>
<option value="kr" <?php echo $user_flag == 'kr' ? 'selected' : ''; ?>>🇰🇷 Corée du Sud</option>
<option value="cr" <?php echo $user_flag == 'cr' ? 'selected' : ''; ?>>🇨🇷 Costa Rica</option>
<option value="ci" <?php echo $user_flag == 'ci' ? 'selected' : ''; ?>>🇨🇮 Côte d'Ivoire</option>
<option value="hr" <?php echo $user_flag == 'hr' ? 'selected' : ''; ?>>🇭🇷 Croatie</option>
<option value="cu" <?php echo $user_flag == 'cu' ? 'selected' : ''; ?>>🇨🇺 Cuba</option>
<option value="dk" <?php echo $user_flag == 'dk' ? 'selected' : ''; ?>>🇩🇰 Danemark</option>
<option value="dj" <?php echo $user_flag == 'dj' ? 'selected' : ''; ?>>🇩🇯 Djibouti</option>
<option value="dm" <?php echo $user_flag == 'dm' ? 'selected' : ''; ?>>🇩🇲 Dominique</option>
<option value="eg" <?php echo $user_flag == 'eg' ? 'selected' : ''; ?>>🇪🇬 Égypte</option>
<option value="ae" <?php echo $user_flag == 'ae' ? 'selected' : ''; ?>>🇦🇪 Émirats arabes unis</option>
<option value="ec" <?php echo $user_flag == 'ec' ? 'selected' : ''; ?>>🇪🇨 Équateur</option>
<option value="er" <?php echo $user_flag == 'er' ? 'selected' : ''; ?>>🇪🇷 Érythrée</option>
<option value="es" <?php echo $user_flag == 'es' ? 'selected' : ''; ?>>🇪🇸 Espagne</option>
<option value="ee" <?php echo $user_flag == 'ee' ? 'selected' : ''; ?>>🇪🇪 Estonie</option>
<option value="us" <?php echo $user_flag == 'us' ? 'selected' : ''; ?>>🇺🇸 États-Unis</option>
<option value="et" <?php echo $user_flag == 'et' ? 'selected' : ''; ?>>🇪🇹 Éthiopie</option>
<option value="fj" <?php echo $user_flag == 'fj' ? 'selected' : ''; ?>>🇫🇯 Fidji</option>
<option value="fi" <?php echo $user_flag == 'fi' ? 'selected' : ''; ?>>🇫🇮 Finlande</option>
<option value="fr" <?php echo $user_flag == 'fr' ? 'selected' : ''; ?>>🇫🇷 France</option>
<option value="ga" <?php echo $user_flag == 'ga' ? 'selected' : ''; ?>>🇬🇦 Gabon</option>
<option value="gm" <?php echo $user_flag == 'gm' ? 'selected' : ''; ?>>🇬🇲 Gambie</option>
<option value="ge" <?php echo $user_flag == 'ge' ? 'selected' : ''; ?>>🇬🇪 Géorgie</option>
<option value="gh" <?php echo $user_flag == 'gh' ? 'selected' : ''; ?>>🇬🇭 Ghana</option>
<option value="gi" <?php echo $user_flag == 'gi' ? 'selected' : ''; ?>>🇬🇮 Gibraltar</option>
<option value="gr" <?php echo $user_flag == 'gr' ? 'selected' : ''; ?>>🇬🇷 Grèce</option>
<option value="gd" <?php echo $user_flag == 'gd' ? 'selected' : ''; ?>>🇬🇩 Grenade</option>
<option value="gt" <?php echo $user_flag == 'gt' ? 'selected' : ''; ?>>🇬🇹 Guatemala</option>
<option value="gn" <?php echo $user_flag == 'gn' ? 'selected' : ''; ?>>🇬🇳 Guinée</option>
<option value="gq" <?php echo $user_flag == 'gq' ? 'selected' : ''; ?>>🇬🇶 Guinée équatoriale</option>
<option value="gw" <?php echo $user_flag == 'gw' ? 'selected' : ''; ?>>🇬🇼 Guinée-Bissau</option>
<option value="gy" <?php echo $user_flag == 'gy' ? 'selected' : ''; ?>>🇬🇾 Guyane</option>
<option value="ht" <?php echo $user_flag == 'ht' ? 'selected' : ''; ?>>🇭🇹 Haïti</option>
<option value="hn" <?php echo $user_flag == 'hn' ? 'selected' : ''; ?>>🇭🇳 Honduras</option>
<option value="hu" <?php echo $user_flag == 'hu' ? 'selected' : ''; ?>>🇭🇺 Hongrie</option>
<option value="in" <?php echo $user_flag == 'in' ? 'selected' : ''; ?>>🇮🇳 Inde</option>
<option value="id" <?php echo $user_flag == 'id' ? 'selected' : ''; ?>>🇮🇩 Indonésie</option>
<option value="iq" <?php echo $user_flag == 'iq' ? 'selected' : ''; ?>>🇮🇶 Irak</option>
<option value="ir" <?php echo $user_flag == 'ir' ? 'selected' : ''; ?>>🇮🇷 Iran</option>
<option value="ie" <?php echo $user_flag == 'ie' ? 'selected' : ''; ?>>🇮🇪 Irlande</option>
<option value="is" <?php echo $user_flag == 'is' ? 'selected' : ''; ?>>🇮🇸 Islande</option>
<option value="il" <?php echo $user_flag == 'il' ? 'selected' : ''; ?>>🇮🇱 Israël</option>
<option value="it" <?php echo $user_flag == 'it' ? 'selected' : ''; ?>>🇮🇹 Italie</option>
<option value="jm" <?php echo $user_flag == 'jm' ? 'selected' : ''; ?>>🇯🇲 Jamaïque</option>
<option value="jp" <?php echo $user_flag == 'jp' ? 'selected' : ''; ?>>🇯🇵 Japon</option>
<option value="jo" <?php echo $user_flag == 'jo' ? 'selected' : ''; ?>>🇯🇴 Jordanie</option>
<option value="kz" <?php echo $user_flag == 'kz' ? 'selected' : ''; ?>>🇰🇿 Kazakhstan</option>
<option value="ke" <?php echo $user_flag == 'ke' ? 'selected' : ''; ?>>🇰🇪 Kenya</option>
<option value="kg" <?php echo $user_flag == 'kg' ? 'selected' : ''; ?>>🇰🇬 Kirghizistan</option>
<option value="ki" <?php echo $user_flag == 'ki' ? 'selected' : ''; ?>>🇰🇮 Kiribati</option>
<option value="kw" <?php echo $user_flag == 'kw' ? 'selected' : ''; ?>>🇰🇼 Koweït</option>
<option value="la" <?php echo $user_flag == 'la' ? 'selected' : ''; ?>>🇱🇦 Laos</option>
<option value="ls" <?php echo $user_flag == 'ls' ? 'selected' : ''; ?>>🇱🇸 Lesotho</option>
<option value="lv" <?php echo $user_flag == 'lv' ? 'selected' : ''; ?>>🇱🇻 Lettonie</option>
<option value="lb" <?php echo $user_flag == 'lb' ? 'selected' : ''; ?>>🇱🇧 Liban</option>
<option value="lr" <?php echo $user_flag == 'lr' ? 'selected' : ''; ?>>🇱🇷 Liberia</option>
<option value="ly" <?php echo $user_flag == 'ly' ? 'selected' : ''; ?>>🇱🇾 Libye</option>
<option value="li" <?php echo $user_flag == 'li' ? 'selected' : ''; ?>>🇱🇮 Liechtenstein</option>
<option value="lt" <?php echo $user_flag == 'lt' ? 'selected' : ''; ?>>🇱🇹 Lituanie</option>
<option value="lu" <?php echo $user_flag == 'lu' ? 'selected' : ''; ?>>🇱🇺 Luxembourg</option>
<option value="mk" <?php echo $user_flag == 'mk' ? 'selected' : ''; ?>>🇲🇰 Macédoine du Nord</option>
<option value="mg" <?php echo $user_flag == 'mg' ? 'selected' : ''; ?>>🇲🇬 Madagascar</option>
<option value="my" <?php echo $user_flag == 'my' ? 'selected' : ''; ?>>🇲🇾 Malaisie</option>
<option value="mw" <?php echo $user_flag == 'mw' ? 'selected' : ''; ?>>🇲🇼 Malawi</option>
<option value="mv" <?php echo $user_flag == 'mv' ? 'selected' : ''; ?>>🇲🇻 Maldives</option>
<option value="ml" <?php echo $user_flag == 'ml' ? 'selected' : ''; ?>>🇲🇱 Mali</option>
<option value="mt" <?php echo $user_flag == 'mt' ? 'selected' : ''; ?>>🇲🇹 Malte</option>
<option value="ma" <?php echo $user_flag == 'ma' ? 'selected' : ''; ?>>🇲🇦 Maroc</option>
<option value="mh" <?php echo $user_flag == 'mh' ? 'selected' : ''; ?>>🇲🇭 Îles Marshall</option>
<option value="mu" <?php echo $user_flag == 'mu' ? 'selected' : ''; ?>>🇲🇺 Maurice</option>
<option value="mr" <?php echo $user_flag == 'mr' ? 'selected' : ''; ?>>🇲🇷 Mauritanie</option>
<option value="mx" <?php echo $user_flag == 'mx' ? 'selected' : ''; ?>>🇲🇽 Mexique</option>
<option value="fm" <?php echo $user_flag == 'fm' ? 'selected' : ''; ?>>🇫🇲 Micronésie</option>
<option value="md" <?php echo $user_flag == 'md' ? 'selected' : ''; ?>>🇲🇩 Moldavie</option>
<option value="mc" <?php echo $user_flag == 'mc' ? 'selected' : ''; ?>>🇲🇨 Monaco</option>
<option value="mn" <?php echo $user_flag == 'mn' ? 'selected' : ''; ?>>🇲🇳 Mongolie</option>
<option value="me" <?php echo $user_flag == 'me' ? 'selected' : ''; ?>>🇲🇪 Monténégro</option>
<option value="mz" <?php echo $user_flag == 'mz' ? 'selected' : ''; ?>>🇲🇿 Mozambique</option>
<option value="na" <?php echo $user_flag == 'na' ? 'selected' : ''; ?>>🇳🇦 Namibie</option>
<option value="nr" <?php echo $user_flag == 'nr' ? 'selected' : ''; ?>>🇳🇷 Nauru</option>
<option value="np" <?php echo $user_flag == 'np' ? 'selected' : ''; ?>>🇳🇵 Népal</option>
<option value="ni" <?php echo $user_flag == 'ni' ? 'selected' : ''; ?>>🇳🇮 Nicaragua</option>
<option value="ne" <?php echo $user_flag == 'ne' ? 'selected' : ''; ?>>🇳🇪 Niger</option>
<option value="ng" <?php echo $user_flag == 'ng' ? 'selected' : ''; ?>>🇳🇬 Nigeria</option>
<option value="no" <?php echo $user_flag == 'no' ? 'selected' : ''; ?>>🇳🇴 Norvège</option>
<option value="nz" <?php echo $user_flag == 'nz' ? 'selected' : ''; ?>>🇳🇿 Nouvelle-Zélande</option>
<option value="om" <?php echo $user_flag == 'om' ? 'selected' : ''; ?>>🇴🇲 Oman</option>
<option value="ug" <?php echo $user_flag == 'ug' ? 'selected' : ''; ?>>🇺🇬 Ouganda</option>
<option value="uz" <?php echo $user_flag == 'uz' ? 'selected' : ''; ?>>🇺🇿 Ouzbékistan</option>
<option value="pk" <?php echo $user_flag == 'pk' ? 'selected' : ''; ?>>🇵🇰 Pakistan</option>
<option value="pw" <?php echo $user_flag == 'pw' ? 'selected' : ''; ?>>🇵🇼 Palaos</option>
<option value="ps" <?php echo $user_flag == 'ps' ? 'selected' : ''; ?>>🇵🇸 Palestine</option>
<option value="pa" <?php echo $user_flag == 'pa' ? 'selected' : ''; ?>>🇵🇦 Panama</option>
<option value="pg" <?php echo $user_flag == 'pg' ? 'selected' : ''; ?>>🇵🇬 Papouasie-Nouvelle-Guinée</option>
<option value="py" <?php echo $user_flag == 'py' ? 'selected' : ''; ?>>🇵🇾 Paraguay</option>
<option value="nl" <?php echo $user_flag == 'nl' ? 'selected' : ''; ?>>🇳🇱 Pays-Bas</option>
<option value="pe" <?php echo $user_flag == 'pe' ? 'selected' : ''; ?>>🇵🇪 Pérou</option>
<option value="ph" <?php echo $user_flag == 'ph' ? 'selected' : ''; ?>>🇵🇭 Philippines</option>
<option value="pl" <?php echo $user_flag == 'pl' ? 'selected' : ''; ?>>🇵🇱 Pologne</option>
<option value="pt" <?php echo $user_flag == 'pt' ? 'selected' : ''; ?>>🇵🇹 Portugal</option>
<option value="qa" <?php echo $user_flag == 'qa' ? 'selected' : ''; ?>>🇶🇦 Qatar</option>
<option value="cf" <?php echo $user_flag == 'cf' ? 'selected' : ''; ?>>🇨🇫 République centrafricaine</option>
<option value="do" <?php echo $user_flag == 'do' ? 'selected' : ''; ?>>🇩🇴 République dominicaine</option>
<option value="cz" <?php echo $user_flag == 'cz' ? 'selected' : ''; ?>>🇨🇿 République tchèque</option>
<option value="ro" <?php echo $user_flag == 'ro' ? 'selected' : ''; ?>>🇷🇴 Roumanie</option>
<option value="gb" <?php echo $user_flag == 'gb' ? 'selected' : ''; ?>>🇬🇧 Royaume-Uni</option>
<option value="ru" <?php echo $user_flag == 'ru' ? 'selected' : ''; ?>>🇷🇺 Russie</option>
<option value="rw" <?php echo $user_flag == 'rw' ? 'selected' : ''; ?>>🇷🇼 Rwanda</option>
<option value="kn" <?php echo $user_flag == 'kn' ? 'selected' : ''; ?>>🇰🇳 Saint-Christophe-et-Niévès</option>
<option value="sm" <?php echo $user_flag == 'sm' ? 'selected' : ''; ?>>🇸🇲 Saint-Marin</option>
<option value="vc" <?php echo $user_flag == 'vc' ? 'selected' : ''; ?>>🇻🇨 Saint-Vincent-et-les-Grenadines</option>
<option value="lc" <?php echo $user_flag == 'lc' ? 'selected' : ''; ?>>🇱🇨 Sainte-Lucie</option>
<option value="sb" <?php echo $user_flag == 'sb' ? 'selected' : ''; ?>>🇸🇧 Îles Salomon</option>
<option value="ws" <?php echo $user_flag == 'ws' ? 'selected' : ''; ?>>🇼🇸 Samoa</option>
<option value="st" <?php echo $user_flag == 'st' ? 'selected' : ''; ?>>🇸🇹 Sao Tomé-et-Principe</option>
<option value="sn" <?php echo $user_flag == 'sn' ? 'selected' : ''; ?>>🇸🇳 Sénégal</option>
<option value="rs" <?php echo $user_flag == 'rs' ? 'selected' : ''; ?>>🇷🇸 Serbie</option>
<option value="sc" <?php echo $user_flag == 'sc' ? 'selected' : ''; ?>>🇸🇨 Seychelles</option>
<option value="sl" <?php echo $user_flag == 'sl' ? 'selected' : ''; ?>>🇸🇱 Sierra Leone</option>
<option value="sg" <?php echo $user_flag == 'sg' ? 'selected' : ''; ?>>🇸🇬 Singapour</option>
<option value="sk" <?php echo $user_flag == 'sk' ? 'selected' : ''; ?>>🇸🇰 Slovaquie</option>
<option value="si" <?php echo $user_flag == 'si' ? 'selected' : ''; ?>>🇸🇮 Slovénie</option>
<option value="so" <?php echo $user_flag == 'so' ? 'selected' : ''; ?>>🇸🇴 Somalie</option>
<option value="sd" <?php echo $user_flag == 'sd' ? 'selected' : ''; ?>>🇸🇩 Soudan</option>
<option value="ss" <?php echo $user_flag == 'ss' ? 'selected' : ''; ?>>🇸🇸 Soudan du Sud</option>
<option value="lk" <?php echo $user_flag == 'lk' ? 'selected' : ''; ?>>🇱🇰 Sri Lanka</option>
<option value="se" <?php echo $user_flag == 'se' ? 'selected' : ''; ?>>🇸🇪 Suède</option>
<option value="ch" <?php echo $user_flag == 'ch' ? 'selected' : ''; ?>>🇨🇭 Suisse</option>
<option value="sr" <?php echo $user_flag == 'sr' ? 'selected' : ''; ?>>🇸🇷 Suriname</option>
<option value="sz" <?php echo $user_flag == 'sz' ? 'selected' : ''; ?>>🇸🇿 Eswatini</option>
<option value="sy" <?php echo $user_flag == 'sy' ? 'selected' : ''; ?>>🇸🇾 Syrie</option>
<option value="tj" <?php echo $user_flag == 'tj' ? 'selected' : ''; ?>>🇹🇯 Tadjikistan</option>
<option value="tw" <?php echo $user_flag == 'tw' ? 'selected' : ''; ?>>🇹🇼 Taïwan</option>
<option value="tz" <?php echo $user_flag == 'tz' ? 'selected' : ''; ?>>🇹🇿 Tanzanie</option>
<option value="td" <?php echo $user_flag == 'td' ? 'selected' : ''; ?>>🇹🇩 Tchad</option>
<option value="th" <?php echo $user_flag == 'th' ? 'selected' : ''; ?>>🇹🇭 Thaïlande</option>
<option value="tl" <?php echo $user_flag == 'tl' ? 'selected' : ''; ?>>🇹🇱 Timor oriental</option>
<option value="tg" <?php echo $user_flag == 'tg' ? 'selected' : ''; ?>>🇹🇬 Togo</option>
<option value="to" <?php echo $user_flag == 'to' ? 'selected' : ''; ?>>🇹🇴 Tonga</option>
<option value="tt" <?php echo $user_flag == 'tt' ? 'selected' : ''; ?>>🇹🇹 Trinité-et-Tobago</option>
<option value="tn" <?php echo $user_flag == 'tn' ? 'selected' : ''; ?>>🇹🇳 Tunisie</option>
<option value="tm" <?php echo $user_flag == 'tm' ? 'selected' : ''; ?>>🇹🇲 Turkménistan</option>
<option value="tr" <?php echo $user_flag == 'tr' ? 'selected' : ''; ?>>🇹🇷 Turquie</option>
<option value="tv" <?php echo $user_flag == 'tv' ? 'selected' : ''; ?>>🇹🇻 Tuvalu</option>
<option value="ua" <?php echo $user_flag == 'ua' ? 'selected' : ''; ?>>🇺🇦 Ukraine</option>
<option value="uy" <?php echo $user_flag == 'uy' ? 'selected' : ''; ?>>🇺🇾 Uruguay</option>
<option value="va" <?php echo $user_flag == 'va' ? 'selected' : ''; ?>>🇻🇦 Vatican</option>
<option value="ve" <?php echo $user_flag == 've' ? 'selected' : ''; ?>>🇻🇪 Venezuela</option>
<option value="vn" <?php echo $user_flag == 'vn' ? 'selected' : ''; ?>>🇻🇳 Viêt Nam</option>
<option value="ye" <?php echo $user_flag == 'ye' ? 'selected' : ''; ?>>🇾🇪 Yémen</option>
<option value="zm" <?php echo $user_flag == 'zm' ? 'selected' : ''; ?>>🇿🇲 Zambie</option>
<option value="zw" <?php echo $user_flag == 'zw' ? 'selected' : ''; ?>>🇿🇼 Zimbabwe</option>
                    </select>
                </div>

                <div class="password-section-title">Sécurisation (Mot de passe)</div>
                
                <div class="form-group">
                    <label for="inputPassword">Nouveau mot de passe</label>
                    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Laisser vide si inchangé">
                </div>

                <div class="form-group">
                    <label for="inputConfirmPassword">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" id="inputConfirmPassword" class="form-control" placeholder="Ressaisir le mot de passe">
                </div>

                <button type="submit" class="btn-save">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="themeModal">
        <div class="modal-card" style="width: 500px;">
            <button class="btn-close-modal" onclick="closeModal('themeModal')">×</button>
            <h2>🎨 Choisir un style de Damier</h2>
            
            <div class="themes-grid">
                <div class="theme-option" onclick="saveTheme('#f0d9b5', '#b58863')">
                    <div class="theme-showcase"><div style="background:#f0d9b5;"></div><div style="background:#b58863;"></div><div style="background:#b58863;"></div><div style="background:#f0d9b5;"></div></div>
                    <span>🪵 Bois Classique</span>
                </div>
                <div class="theme-option" onclick="saveTheme('#eeeed2', '#769656')">
                    <div class="theme-showcase"><div style="background:#eeeed2;"></div><div style="background:#769656;"></div><div style="background:#769656;"></div><div style="background:#eeeed2;"></div></div>
                    <span>🌿 Forêt Émeraude</span>
                </div>
                <div class="theme-option" onclick="saveTheme('#e0e4ec', '#00a8ff')">
                    <div class="theme-showcase"><div style="background:#e0e4ec;"></div><div style="background:#00a8ff;"></div><div style="background:#00a8ff;"></div><div style="background:#e0e4ec;"></div></div>
                    <span>⚡ Néon Cyber</span>
                </div>
                <div class="theme-option" onclick="saveTheme('#70a1ff', '#2f3542')">
                    <div class="theme-showcase"><div style="background:#70a1ff;"></div><div style="background:#2f3542;"></div><div style="background:#2f3542;"></div><div style="background:#70a1ff;"></div></div>
                    <span>🌑 Midnight Noir</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function triggerAvatarUpload() {
            document.getElementById('avatarFileInput').click();
        }

        function uploadAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64Data = e.target.result;

                    const formData = new FormData();
                    formData.append('action', 'update_avatar');
                    formData.append('avatar_base64', base64Data);

                    fetch('profil.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            const container = document.getElementById('avatarContainer');
                            const placeholder = document.getElementById('avatarPlaceholder');
                            if (placeholder) placeholder.remove();

                            let img = document.getElementById('avatarImg');
                            if (!img) {
                                img = document.createElement('img');
                                img.id = 'avatarImg';
                                img.classList.add('avatar-img');
                                img.alt = "Avatar";
                                container.insertBefore(img, container.firstChild);
                            }
                            img.src = base64Data;
                        }
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        function validatePasswordMatch(event) {
            const password = document.getElementById('inputPassword').value;
            const confirmPassword = document.getElementById('inputConfirmPassword').value;
            const errorAlert = document.getElementById('modalErrorAlert');

            if (password !== "" && password !== confirmPassword) {
                event.preventDefault();
                errorAlert.style.display = 'block';
                errorAlert.innerText = "❌ Les deux mots de passe ne correspondent pas !";
                return false;
            }
            return true;
        }

        function saveTheme(lightColor, darkColor) {
            const formData = new FormData();
            formData.append('action', 'update_theme');
            formData.append('theme_light', lightColor);
            formData.append('theme_dark', darkColor);

            fetch('profil.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    document.documentElement.style.setProperty('--light-square', lightColor);
                    document.documentElement.style.setProperty('--dark-square', darkColor);
                    closeModal('themeModal');
                }
            });
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = "none";
            }
        }
    </script>
    <?php include 'popup_invitation.php'; ?>
</body>
</html>