<?php
session_start();
require_once 'config.php';

// 1. SÉCURITÉ : Si l'utilisateur n'est pas connecté, retour à la page de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$success_msg = "";
$error_msg = "";

// 2. TRAITEMENT DU FORMULAIRE DE MISE À JOUR (POST AJAX ou Standard)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cas de la mise à jour des paramètres texte (Pseudo, Bio, Drapeau, Mot de passe)
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_pseudo = trim($_POST['pseudo']);
        $new_bio = trim($_POST['biographie']);
        $new_flag = trim($_POST['code_drapeau']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (!empty($new_pseudo)) {
            try {
                // Gestion du mot de passe si rempli
                if (!empty($password)) {
                    if ($password === $confirm_password) {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $update = $bdd->prepare('UPDATE utilisateurs SET pseudo = ?, biographie = ?, code_drapeau = ?, mot_de_passe = ? WHERE id = ?');
                        $update->execute([$new_pseudo, $new_bio, $new_flag, $hashed_password, $_SESSION['user_id']]);
                        $success_msg = "Profil et mot de passe mis à jour !";
                    } else {
                        $error_msg = "Les mots de passe ne correspondent pas.";
                    }
                } else {
                    // Mise à jour sans toucher au mot de passe
                    $update = $bdd->prepare('UPDATE utilisateurs SET pseudo = ?, biographie = ?, code_drapeau = ? WHERE id = ?');
                    $update->execute([$new_pseudo, $new_bio, $new_flag, $_SESSION['user_id']]);
                    $success_msg = "Profil mis à jour avec succès !";
                }
            } catch (Exception $e) {
                $error_msg = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        }
    }

    // Cas du changement dynamique de Thème (via JavaScript/Fetch)
    if (isset($_POST['action']) && $_POST['action'] === 'update_theme') {
        $light = $_POST['theme_light'];
        $dark = $_POST['theme_dark'];
        $update = $bdd->prepare('UPDATE utilisateurs SET theme_light = ?, theme_dark = ? WHERE id = ?');
        $update->execute([$light, $dark, $_SESSION['user_id']]);
        echo json_encode(['status' => 'success']);
        exit();
    }

    // Cas du téléversement de la Photo de profil en Base64
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

// Valeurs par défaut si les champs de la BDD sont vides
$user_bio = !empty($user['biographie']) ? $user['biographie'] : "Bienvenue sur mon profil ! Passionné de jeu de dames et de stratégie.";
$user_flag = !empty($user['code_drapeau']) ? $user['code_drapeau'] : "un";
$theme_light = !empty($user['theme_light']) ? $user['theme_light'] : "#f0d9b5";
$theme_dark = !empty($user['theme_dark']) ? $user['theme_dark'] : "#b58863";

// Simulation rapide pour les statistiques
$victoires = 0; 
$defaites = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['pseudo']); ?> - Profil - Jeu de Dames</title>
    <style>
        /* Déclaration des variables dynamiques pour le thème du damier */
        :root {
            --light-square: <?php echo $theme_light; ?>;
            --dark-square: <?php echo $theme_dark; ?>;
        }

        /* ==========================================================================
           1. FOND BOISÉ D'ORIGINE ET STYLE GÉNÉRAL
           ========================================================================== */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5;
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
            overflow-x: hidden;
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

        /* ==========================================================================
           2. BARRE LATÉRALE DE NAVIGATION
           ========================================================================== */
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
        }

        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* ==========================================================================
           3. ZONE PRINCIPALE ET EN-TÊTE DU PROFIL
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
            z-index: 1;
            box-sizing: border-box;
        }

        .profile-header {
            background: rgba(27, 18, 11, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 30px;
            position: relative;
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

        .badge-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #c4b49c;
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 4px;
            cursor: pointer;
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
        }

        .tab-link.active {
            color: #fff;
            border-bottom: 3px solid #81b64c;
        }

        /* ==========================================================================
           4. DISPOSITION DU BAS (GRILLE)
           ========================================================================== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
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

        .card-right-item.vertical {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
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

        /* ==========================================================================
           5. MODALES ET COMPOSANTS DE FORMULAIRE
           ========================================================================== */
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
            <a href="index.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link active">⚙️ Profil</a>
        </div>
    </div>

    <div class="main-content">
        
        <?php if(!empty($success_msg)): ?>
            <div class="alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert-error" style="display:block;"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <button class="btn-modifier-profil" onclick="openModal('editModal')">Modifier le profil</button>
            
            <div class="profile-main-info">
                
                <input type="file" id="avatarFileInput" accept="image/*" style="display: none;" onchange="uploadAvatar(event)">
                
                <div class="big-avatar" id="avatarContainer" onclick="triggerAvatarUpload()" title="Cliquez pour changer votre photo de profil">
                    <?php if(!empty($user['avatar'])): ?>
                        <img src="<?php echo $user['avatar']; ?>" class="avatar-img" id="avatarImg">
                    <?php else: ?>
                        <span id="avatarPlaceholder">♙</span>
                    <?php endif; ?>
                    <div class="avatar-overlay">Changer la photo</div>
                </div>
                
                <div class="user-details">
                    <div class="username-row">
                        <h1 id="displayPseudo"><?php echo htmlspecialchars($user['pseudo']); ?></h1>
                        <span id="flagContainer">
                            <img class="flag-img" src="https://flagcdn.com/w40/<?php echo $user_flag; ?>.png" alt="Drapeau">
                        </span>
                        <button class="badge-btn">Ajouter un badge</button>
                    </div>
                    
                    <div class="bio-display-line" id="displayBio">
                        <?php echo htmlspecialchars($user_bio); ?>
                    </div>
                    
                    <div class="meta-info">
                        <span>Inscription le <strong><?php echo htmlspecialchars($user['date_inscription']); ?></strong></span>
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
                <a href="#" class="tab-link active">Aperçu</a>
                <a href="#" class="tab-link">Parties</a>
                <a href="#" class="tab-link">Statistiques</a>
                <a href="#" class="tab-link">Amis</a>
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

    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <button class="btn-close-modal" onclick="closeModal('editModal')">×</button>
            <h2>⚙️ Paramètres du Profil</h2>
            
            <div class="alert-error" id="modalErrorAlert" style="display:none;"></div>
            
            <form action="profil.php" method="POST" onsubmit="return validatePasswordMatch(event)">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label>Votre Pseudo</label>
                    <input type="text" name="pseudo" id="inputPseudo" class="form-control" value="<?php echo htmlspecialchars($user['pseudo']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Biographie / Présentation</label>
                    <textarea name="biographie" id="inputBio" class="form-control" rows="3"><?php echo htmlspecialchars($user_bio); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="inputFlag">Nationalité (Drapeau)</label>
                    <select name="code_drapeau" id="inputFlag" class="form-control">
                        <option value="un" <?php echo $user_flag == 'un' ? 'selected' : ''; ?>>🌐 International (Universel)</option>
                        <option value="fr" <?php echo $user_flag == 'fr' ? 'selected' : ''; ?>>🇫🇷 France</option>
                        <option value="be" <?php echo $user_flag == 'be' ? 'selected' : ''; ?>>🇧🇪 Belgique</option>
                        <option value="ca" <?php echo $user_flag == 'ca' ? 'selected' : ''; ?>>🇨🇦 Canada</option>
                        <option value="ch" <?php echo $user_flag == 'ch' ? 'selected' : ''; ?>>🇨🇭 Suisse</option>
                        <option value="us" <?php echo $user_flag == 'us' ? 'selected' : ''; ?>>🇺🇸 États-Unis</option>
                        <option value="ma" <?php echo $user_flag == 'ma' ? 'selected' : ''; ?>>🇲🇦 Maroc</option>
                        <option value="dz" <?php echo $user_flag == 'dz' ? 'selected' : ''; ?>>🇩🇿 Algérie</option>
                        <option value="tn" <?php echo $user_flag == 'tn' ? 'selected' : ''; ?>>🇹🇳 Tunisie</option>
                    </select>
                </div>

                <div class="password-section-title">Sécurisation (Mot de passe)</div>
                
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Laisser vide si inchangé">
                </div>

                <div class="form-group">
                    <label>Confirmer le nouveau mot de passe</label>
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

        // 1. Envoi asynchrone de la photo de profil (Base64) en BDD
        function uploadAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64Data = e.target.result;

                    // Requête AJAX vers profil.php
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
                                container.insertBefore(img, container.firstChild);
                            }
                            img.src = base64Data;
                        }
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        // 2. Vérification JavaScript avant soumission du formulaire
        function validatePasswordMatch(event) {
            const password = document.getElementById('inputPassword').value;
            const confirmPassword = document.getElementById('inputConfirmPassword').value;
            const errorAlert = document.getElementById('modalErrorAlert');

            if (password !== "" && password !== confirmPassword) {
                event.preventDefault(); // Annule l'envoi du formulaire
                errorAlert.style.display = 'block';
                errorAlert.innerText = "❌ Les deux mots de passe ne correspondent pas !";
                return false;
            }
            return true;
        }

        // 3. Envoi asynchrone et persistance du Thème choisi
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
</body>
</html>