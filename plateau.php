<?php

declare(strict_types=1);

session_start();
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'statut' => 'erreur',
        'message' => 'Accès refusé'
    ]);

    exit;
}

$userId = (int)$_SESSION['user_id'];
$matchId = (int)($_GET['match_id'] ?? 0);
$action = $_GET['action'] ?? '';

$monRole = 'spectateur'; 

if ($matchId > 0) {
    $reqPartie = $bdd->prepare('SELECT id_challengeur, id_defie FROM parties_jcj WHERE id = ?');
    $reqPartie->execute([$matchId]);
    $partie = $reqPartie->fetch();

    if ($partie) {
        // CORRECTION : Attribution aléatoire automatique au chargement de la page
        if ($partie['id_challengeur'] == $userId || $partie['id_defie'] == $userId) {
            $monRole = (rand(0, 1) === 0) ? 'blanc' : 'noir';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Damier de Dames 10x10</title>
</head>
<body>

<div id="info-partie">
    <a href="dashboard.php" class="btn-ctrl btn-accueil">
        <span class="icon">🏠</span> Accueil
    </a>
    
    <hr class="separateur-controle">

    <div class="statut-header">
        Tour : <span id="status-tour" class="tour-blanc">Blancs</span>
    </div>
    <div id="statut-jeu">Préparation...</div>
    
    <hr class="separateur-controle">
    
    <div class="panneau-controles">
        <button id="btn-abandon" class="btn-ctrl btn-danger" disabled>
            <span class="icon">🏳</span> Abandonner
        </button>
        <button id="btn-nulle" class="btn-ctrl btn-warning" disabled>
            <span class="icon">🤝</span> Proposer nulle
        </button>
    </div>
</div>

<div class="zone-jeu-centrale">

    <div id="timer-noir" class="timer-global">
        <span class="label-joueur" id="nom-noir">NOIRS</span>
        <span class="temps-affichage" id="temps-noir">05:00</span>
        <div class="badge-victoire">🏆 GAGNANT !</div>
    </div>

    <div id="overlay-preparation">
        <div class="boite-preparation">
            <h2>La partie va commencer</h2>
            <p>Attribution de votre couleur aléatoire effectuée (Vous jouez les : <strong><?php echo strtoupper($monRole); ?></strong>)</p>
            <div id="compte-a-rebours" style="font-size: 3rem; font-weight: bold; color: #e74c3c; margin-top: 15px;">3</div>
        </div>
    </div>

    <table id="table-damier" class="jeu-verrouille">
        <thead>
            <tr>
                <td class="coord"></td><td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td><td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td><td class="coord"></td>
            </tr>
        </thead>
        <tbody>
            <?php
            $plateau = [];
            for ($l = 1; $l <= 10; $l++) {
                for ($c = 1; $c <= 10; $c++) {
                    $plateau[$l][$c] = 0;
                    if (($l + $c) % 2 != 0) {
                        if ($l <= 4) $plateau[$l][$c] = 1; 
                        if ($l >= 7) $plateau[$l][$c] = 2; 
                    }
                }
            }

            for ($ligne = 10; $ligne >= 1; $ligne--) {
                echo "<tr>";
                echo "<td class='coord'>$ligne</td>";
                for ($col = 1; $col <= 10; $col++) {
                    $typeCase = ($ligne + $col) % 2 == 0 ? 'white' : 'black';
                    $contentsCase = "";
                    if ($plateau[$ligne][$col] == 1) $contentsCase = '<div class="pion blanc"></div>';
                    elseif ($plateau[$ligne][$col] == 2) $contentsCase = '<div class="pion noir"></div>';

                    echo "<td class='$typeCase' data-ligne='$ligne' data-col='$col'>$contentsCase</td>";
                }
                echo "<td class='coord'>$ligne</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="coord"></td><td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td><td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td><td class="coord"></td>
            </tr>
        </tfoot>
    </table>

    <div id="timer-blanc" class="timer-global">
        <span class="label-joueur" id="nom-blanc">BLANCS</span>
        <span class="temps-affichage" id="temps-blanc">05:00</span>
        <div class="badge-victoire">🏆 GAGNANT !</div>
    </div>

</div>

<script>
    const MATCH_ID = <?php echo isset($matchId) ? $matchId : 0; ?>;
    const MON_ROLE = "<?php echo isset($monRole) ? $monRole : 'spectateur'; ?>"; 
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let pionSelectionne = null;
    let tourActuel = 'blanc'; 
    let coupsPossiblesCalcules = []; 
    
    const statusEl = document.getElementById('status-tour');
    const statutJeuEl = document.getElementById('statut-jeu');
    const casesNoires = document.querySelectorAll('.black');
    const damierTable = document.getElementById('table-damier');
    let partieTerminee = false;
    let partieCommencee = false;

    let dernierCoupCompteur = 0;

    const overlayPrep = document.getElementById('overlay-preparation');
    const btnAbandon = document.getElementById('btn-abandon');
    const btnNulle = document.getElementById('btn-nulle');
    const compteAReboursEl = document.getElementById('compte-a-rebours');

    // --- MODIFICATION : COMPTE À REBOURS DE 3 SECONDES AUTOMATIQUE ---
    let tempsRestant = 3;
    let indexCompteARebours = setInterval(() => {
        tempsRestant--;
        if (tempsRestant > 0) {
            compteAReboursEl.innerText = tempsRestant;
        } else {
            clearInterval(indexCompteARebours);
            demarrerLaPartie();
        }
    }, 1000);

    // Fonction qui lance la partie visuellement après les 3 secondes
    function demarrerLaPartie() {
        overlayPrep.style.opacity = "0";
        setTimeout(() => { overlayPrep.style.display = "none"; }, 400);

        partieCommencee = true;
        damierTable.classList.remove('jeu-verrouille');
        btnAbandon.disabled = false;
        btnNulle.disabled = false;
        statutJeuEl.innerText = "Partie en cours";
        
        elTimerBlancBox.classList.add('actif');
        lancerTimer();
    }

    // --- GESTION DES CHRONOMÈTRES & ANIMATION VICTOIRE ---
    const TEMPS_INITIAL = 5 * 60; 
    let tempsBlanc = TEMPS_INITIAL;
    let tempsNoir = TEMPS_INITIAL;
    let intervalleTimer = null;

    const elTempsBlanc = document.getElementById('temps-blanc');
    const elTempsNoir = document.getElementById('temps-noir');
    const elTimerBlancBox = document.getElementById('timer-blanc');
    const elTimerNoirBox = document.getElementById('timer-noir');

    function formaterTemps(secondes) {
        const min = Math.floor(secondes / 60);
        const sec = secondes % 60;
        return `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
    }

    function lancerTimer() {
        if (intervalleTimer) clearInterval(intervalleTimer);
        
        intervalleTimer = setInterval(() => {
            if (partieTerminee || !partieCommencee) {
                clearInterval(intervalleTimer);
                return;
            }

            if (tourActuel === 'blanc') {
                tempsBlanc--;
                elTempsBlanc.innerText = formaterTemps(tempsBlanc);
                if (tempsBlanc <= 0) finirParTemps('blanc');
            } else {
                tempsNoir--;
                elTempsNoir.innerText = formaterTemps(tempsNoir);
                if (tempsNoir <= 0) finirParTemps('noir');
            }
        }, 1000);
    }

    function finirParTemps(perdant) {
        const vainqueur = perdant === 'blanc' ? 'noir' : 'blanc';
        if (perdant === 'blanc') elTimerBlancBox.classList.add('temps-depasse');
        else elTimerNoirBox.classList.add('temps-depasse');
        
        declencherVictoire(vainqueur, "Temps écoulé !");
    }

    function declencherVictoire(couleurGagnante, raison = "") {
        partieTerminee = true;
        clearInterval(intervalleTimer);
        
        const nomGagnant = document.getElementById(`nom-${couleurGagnante}`).innerText;
        statutJeuEl.innerText = raison ? `${raison} Victoire de ${nomGagnant} !` : `Victoire de ${nomGagnant} !`;
        statutJeuEl.style.color = "#2ecc71";
        
        const blocGagnant = couleurGagnante === 'blanc' ? elTimerBlancBox : elTimerNoirBox;
        blocGagnant.classList.add('victoire-animation');
        
        creerExplosionConfettis(blocGagnant);
        verrouillerJeu();
    }

    function creerExplosionConfettis(elementCible) {
        const couleurs = ['#f1c40f', '#2ecc71', '#e74c3c', '#3498db', '#9b59b6', '#e67e22', '#1abc9c'];
        const rect = elementCible.getBoundingClientRect();
        const estTimerNoir = elementCible.id === 'timer-noir';
        
        for (let i = 0; i < 100; i++) {
            const conf = document.createElement('div');
            conf.className = 'particle-confetti';
            
            const large = Math.random() * 6 + 6;
            const haut = Math.random() * 10 + 6;
            conf.style.width = `${large}px`;
            conf.style.height = `${haut}px`;
            conf.style.backgroundColor = couleurs[Math.floor(Math.random() * couleurs.length)];
            
            const depX = rect.left + (Math.random() * rect.width);
            const depY = rect.top + (Math.random() * rect.height);
            conf.style.left = `${depX}px`;
            conf.style.top = `${depY}px`;
            
            const trajetX = (Math.random() - 0.5) * 350; 
            const forceY = Math.random() * 200 + 150;
            const trajetY = estTimerNoir ? forceY : -forceY; 
            
            const rotation = `${Math.random() * 720 - 360}deg`;
            
            conf.style.setProperty('--x', `${trajetX}px`);
            conf.style.setProperty('--y', `${trajetY}px`);
            conf.style.setProperty('--r', rotation);
            conf.style.animationDelay = `${Math.random() * 0.4}s`;
            
            document.body.appendChild(conf);
            setTimeout(() => conf.remove(), 3000);
        }
    }

    function majVisuelleTimers() {
        if (tourActuel === 'blanc') {
            elTimerBlancBox.classList.add('actif');
            elTimerNoirBox.classList.remove('actif');
        } else {
            elTimerNoirBox.classList.add('actif');
            elTimerBlancBox.classList.remove('actif');
        }
    }

    btnAbandon.addEventListener('click', function() {
        if (partieTerminee || !partieCommencee) return;
        if (confirm(`Souhaitez-vous vraiment abandonner ?`)) {
            const vainqueur = tourActuel === 'blanc' ? 'noir' : 'blanc';
            declencherVictoire(vainqueur, "Abandon.");
        }
    });

    btnNulle.addEventListener('click', function() {
        if (partieTerminee || !partieCommencee) return;
        const adversaire = tourActuel === 'blanc' ? 'Noirs' : 'Blancs';
        if (confirm(`Les ${tourActuel === 'blanc' ? 'Blancs' : 'Noirs'} proposent le nul. Équipe des ${adversaire}, acceptez-vous ?`)) {
            partieTerminee = true;
            clearInterval(intervalleTimer);
            statutJeuEl.innerText = "Match nul consenti !";
            statutJeuEl.style.color = "#3498db";
            verrouillerJeu();
        }
    });

    function verrouillerJeu() {
        damierTable.classList.add('jeu-verrouille');
        btnAbandon.disabled = true;
        btnNulle.disabled = true;
        if(!document.querySelector('.victoire-animation')) {
            elTimerBlancBox.classList.remove('actif');
            elTimerNoirBox.classList.remove('actif');
        }
    }

    // --- LOGIQUE SÉLECTION ET VÉRIFICATION BLOCAGES ---
    let historiquePositions = {}; 
    let compteurCoupsSansPriseNiPion = 0; 
    let decompte16Coups = -1; 

    function nettoyerAide() {
        document.querySelectorAll('.aide-coup').forEach(el => el.remove());
        document.querySelectorAll('.case-etape-intermediaire').forEach(el => el.classList.remove('case-etape-intermediaire'));
        coupsPossiblesCalcules = [];
    }

    function ajouterPoint(caseCible, estIntermediaire = false) {
        if (!caseCible.querySelector('.aide-coup')) { 
            const point = document.createElement('div');
            if (estIntermediaire) {
                point.className = 'aide-coup etape-intermediaire';
                caseCible.classList.add('case-etape-intermediaire');
            } else {
                point.className = 'aide-coup';
            }
            caseCible.appendChild(point);
        }
    }

    function marquerCheminHistorique(caseDepartLigne, caseDepartCol, coupApplique, caseArriveeElement) {
        document.querySelectorAll('.derniere-case-depart, .case-etape-historique, .derniere-case-arrivee').forEach(el => {
            el.classList.remove('derniere-case-depart', 'case-etape-historique', 'derniere-case-arrivee');
        });
        const caseDepart = document.querySelector(`[data-ligne="${caseDepartLigne}"][data-col="${caseDepartCol}"]`);
        if (caseDepart) caseDepart.classList.add('derniere-case-depart');
        if (coupApplique.etapes && coupApplique.etapes.length > 1) {
            for (let i = 0; i < coupApplique.etapes.length - 1; i++) {
                const etape = coupApplique.etapes[i];
                const caseEtape = document.querySelector(`[data-ligne="${etape.l}"][data-col="${etape.c}"]`);
                if (caseEtape) caseEtape.classList.add('case-etape-historique');
            }
        }
        caseArriveeElement.classList.add('derniere-case-arrivee');
    }

    function calculerTrajectoires(ligne, col, couleur, estDame, pionsCaptures = [], cheminEtapes = [], estAuMilieuDuneRafale = false) {
        let trajectories = [];
        const directions = [{l: 1, c: -1}, {l: 1, c: 1}, {l: -1, c: -1}, {l: -1, c: 1}];
        directions.forEach(dir => {
            let i = 1; let pionAAdverse = null; let casePionAdverse = null;
            while (true) {
                const cL = ligne + (dir.l * i); const cC = col + (dir.c * i);
                if (cL < 1 || cL > 10 || cC < 1 || cC > 10) break;
                if (!estDame && i > 2) break;
                const caseCible = document.querySelector(`[data-ligne="${cL}"][data-col="${cC}"].black`);
                if (!caseCible) break;
                const pionCible = caseCible.querySelector('.pion');
                if (!pionAAdverse) {
                    if (pionCible) {
                        const identifiantPion = `${cL},${cC}`;
                        if (!pionCible.classList.contains(couleur) && !pionsCaptures.includes(identifiantPion)) {
                            pionAAdverse = pionCible; casePionAdverse = caseCible;
                        } else break;
                    }
                } else {
                    if (!pionCible) {
                        let nouveauxCaptures = [...pionsCaptures, `${casePionAdverse.dataset.ligne},${casePionAdverse.dataset.col}`];
                        let nouvellesEtapes = [...cheminEtapes, { l: cL, c: cC }];
                        let suites = calculerTrajectoires(cL, cC, couleur, estDame, nouveauxCaptures, nouvellesEtapes, true);
                        let suitesAvecPrises = suites.filter(s => s.captures.length > nouveauxCaptures.length);
                        if (suitesAvecPrises.length > 0) trajectories.push(...suitesAvecPrises);
                        else trajectories.push({ destLigne: cL, destCol: cC, captures: nouveauxCaptures, etapes: nouvellesEtapes });
                        if (!estDame) break;
                    } else break;
                }
                i++;
            }
        });
        if (!estAuMilieuDuneRafale && trajectories.length === 0) {
            if (estDame) {
                directions.forEach(dir => {
                    let i = 1;
                    while (true) {
                        const cL = ligne + (dir.l * i); const cC = col + (dir.c * i);
                        if (cL < 1 || cL > 10 || cC < 1 || cC > 10) break;
                        const caseCible = document.querySelector(`[data-ligne="${cL}"][data-col="${cC}"].black`);
                        if (!caseCible || caseCible.querySelector('.pion')) break;
                        trajectories.push({ destLigne: cL, destCol: cC, captures: [], etapes: [{ l: cL, c: cC }] });
                        i++;
                    }
                });
            } else {
                const dirMouvement = couleur === 'blanc' ? [{l: 1, c: -1}, {l: 1, c: 1}] : [{l: -1, c: -1}, {l: -1, c: 1}];
                dirMouvement.forEach(dir => {
                    const cL = ligne + dir.l; const cC = col + dir.c;
                    const caseCible = document.querySelector(`[data-ligne="${cL}"][data-col="${cC}"].black`);
                    if (caseCible && !caseCible.querySelector('.pion')) {
                        trajectories.push({ destLigne: cL, destCol: cC, captures: [], etapes: [{ l: cL, c: cC }] });
                    }
                });
            }
        }
        return trajectories;
    }

    function verifierPrisesObligatoiresDuPlateau() {
        let maxCapturesPossiblesSurPlateau = 0;
        const pionsDuJoueur = document.querySelectorAll(`.pion.${tourActuel}`);
        pionsDuJoueur.forEach(pion => {
            const casePion = pion.parentElement;
            const coups = calculerTrajectoires(parseInt(casePion.dataset.ligne), parseInt(casePion.dataset.col), tourActuel, pion.classList.contains('dame'));
            coups.forEach(c => { if (c.captures.length > maxCapturesPossiblesSurPlateau) maxCapturesPossiblesSurPlateau = c.captures.length; });
        });
        return maxCapturesPossiblesSurPlateau;
    }

    function verifierBloquageJoueur(couleurAdverse) {
        const pionsAdverses = document.querySelectorAll(`.pion.${couleurAdverse}`);
        if(pionsAdverses.length === 0) return true; 

        let aAuMoinsUnCoup = false;
        pionsAdverses.forEach(pion => {
            const casePion = pion.parentElement;
            const coups = calculerTrajectoires(parseInt(casePion.dataset.ligne), parseInt(casePion.dataset.col), couleurAdverse, pion.classList.contains('dame'));
            if(coups.length > 0) aAuMoinsUnCoup = true;
        });
        return !aAuMoinsUnCoup; 
    }

    function montrerCoupsPossibles(pionData) {
        nettoyerAide();
        let tousLesCoupsDuPion = calculerTrajectoires(pionData.ligne, pionData.col, pionData.couleur, pionData.element.classList.contains('dame'));
        let maxPlateau = verifierPrisesObligatoiresDuPlateau();
        coupsPossiblesCalcules = maxPlateau > 0 ? tousLesCoupsDuPion.filter(c => c.captures.length === maxPlateau) : tousLesCoupsDuPion;
        coupsPossiblesCalcules.forEach(coup => {
            coup.etapes.forEach((etape, index) => {
                const caseEtape = document.querySelector(`[data-ligne="${etape.l}"][data-col="${etape.c}"].black`);
                if (caseEtape) ajouterPoint(caseEtape, index !== coup.etapes.length - 1);
            });
        });
    }

    function obtenirSnapshotPlateau() {
        let snapshot = "";
        document.querySelectorAll('.black').forEach(c => {
            const pion = c.querySelector('.pion');
            if (pion) snapshot += `${c.dataset.ligne},${c.dataset.col}:${pion.classList.contains('blanc') ? 'B' : 'N'}${pion.classList.contains('dame') ? 'D' : 'P'};`;
        });
        return snapshot + `Tour:${tourActuel}`;
    }

    function verifierReglesFinDePartie(aBougeUnPion, aFaitUnePrise) {
        const snapshotActuel = obtenirSnapshotPlateau();
        historiquePositions[snapshotActuel] = (historiquePositions[snapshotActuel] || 0) + 1;
        if (historiquePositions[snapshotActuel] >= 3) { declarerEgalite("Égalité : 3ème répétition."); return; }
        compteurCoupsSansPriseNiPion = (!aBougeUnPion && !aFaitUnePrise) ? compteurCoupsSansPriseNiPion + 1 : 0;
        if (compteurCoupsSansPriseNiPion >= 25) { declarerEgalite("Égalité : 25 coups sans action."); return; }

        let damesBlanches = 0, pionsBlancs = 0, damesNoires = 0, pionsNoirs = 0;
        document.querySelectorAll('.pion').forEach(p => {
            if (p.classList.contains('blanc')) { if (p.classList.contains('dame')) damesBlanches++; else pionsBlancs++; }
            else { if (p.classList.contains('dame')) damesNoires++; else pionsNoirs++; }
        });
        if (pionsBlancs + pionsNoirs === 0 && ((damesBlanches === 2 && damesNoires === 1) || (damesBlanches === 1 && damesNoires === 2) || (damesBlanches === 1 && damesNoires === 1))) {
            declarerEgalite("Égalité : Fin réglementaire (2v1 ou 1v1)."); return;
        }
        let condition16CoupsRemplie = (pionsBlancs+pionsNoirs+damesBlanches+damesNoires <= 4) && ((damesBlanches === 1 && pionsBlancs === 0 && damesNoires > 0) || (damesNoires === 1 && pionsNoirs === 0 && damesBlanches > 0));
        if (condition16CoupsRemplie) {
            decompte16Coups = decompte16Coups === -1 ? 32 : decompte16Coups - 1;
            if (decompte16Coups === 0) { declarerEgalite("Égalité : Limite des 16 coups."); return; }
            else statutJeuEl.innerText = `Fin de partie : ${Math.ceil(decompte16Coups / 2)} coups restants`;
        } else { decompte16Coups = -1; statutJeuEl.innerText = "Partie en cours"; }
    }

    function declarerEgalite(message) {
        partieTerminee = true;
        clearInterval(intervalleTimer);
        statutJeuEl.innerText = message;
        statutJeuEl.style.color = "#e67e22";
        verrouillerJeu();
    }

    function executerDeplacementGraphique(departLigne, departCol, destLigne, destCol) {
        const caseDepart = document.querySelector(`[data-ligne="${departLigne}"][data-col="${departCol}"].black`);
        const caseArrivee = document.querySelector(`[data-ligne="${destLigne}"][data-col="${destCol}"].black`);
        
        if (!caseDepart || !caseArrivee) return;
        const pion = caseDepart.querySelector('.pion');
        if (!pion) return;

        const estDameAuDepart = pion.classList.contains('dame');
        const couleurPion = pion.classList.contains('blanc') ? 'blanc' : 'noir';

        let trajectories = calculerTrajectoires(departLigne, departCol, couleurPion, estDameAuDepart);
        let coupApplique = trajectories.find(c => c.destLigne === destLigne && c.destCol === destCol);

        if (!coupApplique) {
            coupApplique = { captures: [], etapes: [{ l: destLigne, c: destCol }] };
        }

        let aFaitUnePrise = coupApplique.captures.length > 0;
        let aBougeUnPion = !estDameAuDepart;

        coupApplique.captures.forEach(coordStr => {
            const [pL, pC] = coordStr.split(',');
            const caseEnnemi = document.querySelector(`[data-ligne="${pL}"][data-col="${pC}"]`);
            if (caseEnnemi) {
                const pionVictime = caseEnnemi.querySelector('.pion');
                if (pionVictime) pionVictime.remove();
            }
        });

        caseArrivee.appendChild(pion);
        marquerCheminHistorique(departLigne, departCol, coupApplique, caseArrivee);
        nettoyerAide();

        if (!pion.classList.contains('dame')) {
            if ((couleurPion === 'blanc' && destLigne === 10) || (couleurPion === 'noir' && destLigne === 1)) {
                pion.classList.add('dame');
            }
        }

        verifierReglesFinDePartie(aBougeUnPion, aFaitUnePrise);

        if (!partieTerminee) {
            const prochainTour = tourActuel === 'blanc' ? 'noir' : 'blanc';
            if (verifierBloquageJoueur(prochainTour)) {
                declencherVictoire(tourActuel, "Plus aucun coup possible pour l'adversaire !");
            } else {
                tourActuel = prochainTour;
                statusEl.innerText = tourActuel === 'blanc' ? 'Blancs' : 'Noirs';
                statusEl.className = 'tour-' + tourActuel;
                document.body.className = 'tour-actif-' + tourActuel;
                majVisuelleTimers();
            }
        }
    }

    casesNoires.forEach(caseNoire => {
        caseNoire.addEventListener('click', function() {
            if (partieTerminee || !partieCommencee) return;
            if (MATCH_ID > 0 && tourActuel !== MON_ROLE) return;

            let pion = this.querySelector('.pion');
            if (pion) {
                if ((pion.classList.contains('blanc') ? 'blanc' : 'noir') !== tourActuel) return;
                let coupsDuPion = calculerTrajectoires(parseInt(this.dataset.ligne), parseInt(this.dataset.col), tourActuel, pion.classList.contains('dame'));
                let maxPlateau = verifierPrisesObligatoiresDuPlateau();
                if (maxPlateau > 0 && !coupsDuPion.some(c => c.captures.length === maxPlateau)) return;

                document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
                pion.classList.add('selected');
                pionSelectionne = { element: pion, ligne: parseInt(this.dataset.ligne), col: parseInt(this.dataset.col), couleur: tourActuel };
                montrerCoupsPossibles(pionSelectionne);
            } 
            else if (pionSelectionne) {
                if (!this.querySelector('.aide-coup')) return;
                const destLigne = parseInt(this.dataset.ligne); const destCol = parseInt(this.dataset.col);
                const coupsValibles = coupsPossiblesCalcules.filter(c => c.destLigne === destLigne && c.destCol === destCol);
                if (coupsValibles.length === 0) return;
                const coupApplique = coupsValibles[0];

                if (coupApplique) {
                    const departLigne = pionSelectionne.ligne;
                    const departCol = pionSelectionne.col;

                    let aFaitUnePrise = coupApplique.captures.length > 0;
                    let aBougeUnPion = !pionSelectionne.element.classList.contains('dame');
                    
                    coupApplique.captures.forEach(coordStr => {
                        const [pL, pC] = coordStr.split(',');
                        const caseEnnemi = document.querySelector(`[data-ligne="${pL}"][data-col="${pC}"]`);
                        if (caseEnnemi && caseEnnemi.querySelector('.pion')) caseEnnemi.querySelector('.pion').remove();
                    });

                    this.appendChild(pionSelectionne.element);

                    if (MATCH_ID > 0) {
                        const formData = new FormData();
                        formData.append('match_id', MATCH_ID);
                        formData.append('depart', `${departLigne},${departCol}`);
                        formData.append('arrivee', `${destLigne},${destCol}`);
                        formData.append('couleur', MON_ROLE);

                        fetch('jcj_ajax.php?action=jouer', { method: 'POST', body: formData });
                        dernierCoupCompteur++;
                    }

                    pionSelectionne.element.classList.remove('selected');
                    marquerCheminHistorique(departLigne, departCol, coupApplique, this);
                    nettoyerAide();

                    if (!pionSelectionne.element.classList.contains('dame') && ((pionSelectionne.couleur === 'blanc' && destLigne === 10) || (pionSelectionne.couleur === 'noir' && destLigne === 1))) {
                        pionSelectionne.element.classList.add('dame');
                    }
                    
                    verifierReglesFinDePartie(aBougeUnPion, aFaitUnePrise);
                    
                    if (!partieTerminee) {
                        const prochainTour = tourActuel === 'blanc' ? 'noir' : 'blanc';
                        
                        if (verifierBloquageJoueur(prochainTour)) {
                            declencherVictoire(tourActuel, "Plus aucun coup possible pour l'adversaire !");
                        } else {
                            tourActuel = prochainTour;
                            statusEl.innerText = tourActuel === 'blanc' ? 'Blancs' : 'Noirs';
                            statusEl.className = 'tour-' + tourActuel;
                            document.body.className = 'tour-actif-' + tourActuel;
                            majVisuelleTimers();
                        }
                    }
                    pionSelectionne = null;
                }
            }
        });
    });

    // --- SYNCHRONISATION PAR POLLING (PULL) ---
    if (MATCH_ID > 0) {
        setInterval(function() {
            if (tourActuel === MON_ROLE) return;

            fetch(`jcj_ajax.php?action=charger_dernier_coup&match_id=${MATCH_ID}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.num_coup > dernierCoupCompteur) {
                        console.log("Nouveau coup détecté de l'adversaire :", data);
                        
                        dernierCoupCompteur = data.num_coup;
                        
                        const [depL, depC] = data.case_depart.split(',').map(Number);
                        const [arrL, arrC] = data.case_arrivee.split(',').map(Number);
                        
                        console.log(`Exécution du déplacement : de (${depL},${depC}) vers (${arrL},${arrC})`);
                        
                        executerDeplacementGraphique(depL, depC, arrL, arrC);
                    }
                })
                .catch(err => console.error("Erreur polling coups :", err));
        }, 2000);
    }
});
</script>
</body>
</html>