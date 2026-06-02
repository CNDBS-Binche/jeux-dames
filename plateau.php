<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header('Location: connexion.php'); exit(); }

$userId = $_SESSION['user_id'];
$matchId = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

$monRole = 'spectateur'; 

if ($matchId > 0) {
    // On regarde qui est qui dans cette partie
    $reqPartie = $bdd->prepare('SELECT id_challengeur, id_defie FROM parties_jcj WHERE id = ?');
    $reqPartie->execute([$matchId]);
    $partie = $reqPartie->fetch();

    if ($partie) {
        if ($partie['id_challengeur'] == $userId) {
            $monRole = 'blanc'; // Le créateur du défi a les Blancs
        } elseif ($partie['id_defie'] == $userId) {
            $monRole = 'noir';  // Le défié a les Noirs
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

<div id="info-partie" style="position: absolute; top: 20px; left: 20px; color: #f0d9b5; font-family: Arial, sans-serif; background: #5d3a1a; padding: 10px; border-radius: 6px; border: 2px solid #7a4a28;">
    Tour : <span id="status-tour" class="tour-blanc">Blancs</span>
    <div id="statut-jeu" style="margin-top: 5px; color: #2ecc71; font-weight: bold;">Partie en cours</div>
</div>

<table>
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

<script>
    // Variables PHP transmises au JavaScript
    const MATCH_ID = <?php echo $matchId; ?>;
    const MON_ROLE = "<?php echo $monRole; ?>"; // Contient 'blanc', 'noir' ou 'spectateur'
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let pionSelectionne = null;
    let tourActuel = 'blanc'; 
    let coupsPossiblesCalcules = []; 
    
    // Éléments d'affichage manquants dans le code d'origine
    const statusEl = document.getElementById('status-tour');
    const statutJeuEl = document.getElementById('statut-jeu');
    let partieTerminee = false;

    // --- VARIABLES DE SUIVI DES REGLES DE FIN DE PARTIE ---
    let historiquePositions = {}; // Pour la règle des 3 positions identiques
    let compteurCoupsSansPriseNiPion = 0; // Pour la règle des 25 coups
    let decompte16Coups = -1; // -1 signifie non activé, sinon compte à rebours de 32 demi-coups

    // Compteur de coups local pour la synchronisation JcJ
    let dernierCoupCompteur = 0;

    console.log("Moteur de dames (Historique complet de la rafle) prêt.");

    function nettoyerAide() {
        document.querySelectorAll('.aide-coup').forEach(el => el.remove());
        document.querySelectorAll('.case-etape-intermediaire').forEach(el => {
            el.classList.remove('case-etape-intermediaire');
        });
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
                if (caseEtape) {
                    caseEtape.classList.add('case-etape-historique');
                }
            }
        }

        caseArriveeElement.classList.add('derniere-case-arrivee');
    }

    // --- MOTEUR DE RECHERCHE DES COUPS (RECURSIF) ---
    function calculerTrajectoires(ligne, col, couleur, estDame, pionsCaptures = [], cheminEtapes = [], estAuMilieuDuneRafale = false) {
        let trajectories = [];
        const directions = [{l: 1, c: -1}, {l: 1, c: 1}, {l: -1, c: -1}, {l: -1, c: 1}];

        // 1. RECHERCHE DES PRISES
        directions.forEach(dir => {
            let i = 1;
            let pionAAdverse = null;
            let casePionAdverse = null;

            while (true) {
                const cL = ligne + (dir.l * i);
                const cC = col + (dir.c * i);

                if (cL < 1 || cL > 10 || cC < 1 || cC > 10) break;
                if (!estDame && i > 2) break;

                const caseCible = document.querySelector(`[data-ligne="${cL}"][data-col="${cC}"].black`);
                if (!caseCible) break;

                const pionCible = caseCible.querySelector('.pion');

                if (!pionAAdverse) {
                    if (pionCible) {
                        const identifiantPion = `${cL},${cC}`;
                        if (!pionCible.classList.contains(couleur) && !pionsCaptures.includes(identifiantPion)) {
                            pionAAdverse = pionCible;
                            casePionAdverse = caseCible;
                        } else {
                            break; 
                        }
                    }
                } else {
                    if (!pionCible) {
                        let nouveauxCaptures = [...pionsCaptures, `${casePionAdverse.dataset.ligne},${casePionAdverse.dataset.col}`];
                        let nouvellesEtapes = [...cheminEtapes, { l: cL, c: cC }];
                        
                        let suites = calculerTrajectoires(cL, cC, couleur, estDame, nouveauxCaptures, nouvellesEtapes, true);
                        let suitesAvecPrises = suites.filter(s => s.captures.length > nouveauxCaptures.length);

                        if (suitesAvecPrises.length > 0) {
                            trajectories.push(...suitesAvecPrises);
                        } else {
                            trajectories.push({
                                destLigne: cL,
                                destCol: cC,
                                captures: nouveauxCaptures,
                                etapes: nouvellesEtapes
                            });
                        }

                        if (!estDame) break;
                    } else {
                        break;
                    }
                }
                i++;
            }
        });

        // 2. DEPLACEMENTS SIMPLES
        if (!estAuMilieuDuneRafale && trajectories.length === 0) {
            if (estDame) {
                directions.forEach(dir => {
                    let i = 1;
                    while (true) {
                        const cL = ligne + (dir.l * i);
                        const cC = col + (dir.c * i);
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
                    const cL = ligne + dir.l;
                    const cC = col + dir.c;
                    const caseCible = document.querySelector(`[data-ligne="${cL}"][data-col="${cC}"].black`);
                    if (caseCible && !caseCible.querySelector('.pion')) {
                        trajectories.push({ destLigne: cL, destCol: cC, captures: [], etapes: [{ l: cL, c: cC }] });
                    }
                });
            }
        }

        return trajectories;
    }

    // --- ANALYSE GLOBALE DU PLATEAU ---
    function verifierPrisesObligatoiresDuPlateau() {
        let maxCapturesPossiblesSurPlateau = 0;
        const pionsDuJoueur = document.querySelectorAll(`.pion.${tourActuel}`);

        pionsDuJoueur.forEach(pion => {
            const casePion = pion.parentElement;
            const ligne = parseInt(casePion.dataset.ligne);
            const col = parseInt(casePion.dataset.col);
            const estDame = pion.classList.contains('dame');

            const coups = calculerTrajectoires(ligne, col, tourActuel, estDame);
            coups.forEach(c => {
                if (c.captures.length > maxCapturesPossiblesSurPlateau) {
                    maxCapturesPossiblesSurPlateau = c.captures.length;
                }
            });
        });

        return maxCapturesPossiblesSurPlateau;
    }

    function montrerCoupsPossibles(pionData) {
        nettoyerAide();
        const estDame = pionData.element.classList.contains('dame');
        
        let tousLesCoupsDuPion = calculerTrajectoires(pionData.ligne, pionData.col, pionData.couleur, estDame);
        let maxPlateau = verifierPrisesObligatoiresDuPlateau();

        if (maxPlateau > 0) {
            coupsPossiblesCalcules = tousLesCoupsDuPion.filter(c => c.captures.length === maxPlateau);
        } else {
            coupsPossiblesCalcules = tousLesCoupsDuPion;
        }

        coupsPossiblesCalcules.forEach(coup => {
            coup.etapes.forEach((etape, index) => {
                const caseEtape = document.querySelector(`[data-ligne="${etape.l}"][data-col="${etape.c}"].black`);
                if (caseEtape) {
                    let estDerniereEtape = (index === coup.etapes.length - 1);
                    ajouterPoint(caseEtape, !estDerniereEtape);
                }
            });
        });
    }

    // --- FONCTIONS LOGIQUES POUR LES REGLES DE FIN DE PARTIE ---
    function obtenirSnapshotPlateau() {
        let snapshot = "";
        document.querySelectorAll('.black').forEach(c => {
            const pion = c.querySelector('.pion');
            if (pion) {
                const couleur = pion.classList.contains('blanc') ? 'B' : 'N';
                const type = pion.classList.contains('dame') ? 'D' : 'P';
                snapshot += `${c.dataset.ligne},${c.dataset.col}:${couleur}${type};`;
            }
        });
        return snapshot + `Tour:${tourActuel}`;
    }

    function verifierReglesFinDePartie(aBougeUnPion, aFaitUnePrise) {
        const snapshotActuel = obtenirSnapshotPlateau();
        historiquePositions[snapshotActuel] = (historiquePositions[snapshotActuel] || 0) + 1;
        if (historiquePositions[snapshotActuel] >= 3) {
            declarerEgalite("Égalité : 3ème répétition de la même position.");
            return;
        }

        if (!aBougeUnPion && !aFaitUnePrise) {
            compteurCoupsSansPriseNiPion++;
        } else {
            compteurCoupsSansPriseNiPion = 0; 
        }
        if (compteurCoupsSansPriseNiPion >= 25) {
            declarerEgalite("Égalité : 25 coups sans mouvement de pion ni prise.");
            return;
        }

        let damesBlanches = 0, pionsBlancs = 0;
        let damesNoires = 0, pionsNoirs = 0;

        document.querySelectorAll('.pion').forEach(p => {
            if (p.classList.contains('blanc')) {
                if (p.classList.contains('dame')) damesBlanches++; else pionsBlancs++;
            } else {
                if (p.classList.contains('dame')) damesNoires++; else pionsNoirs++;
            }
        });

        let totalBlancs = damesBlanches + pionsBlancs;
        let totalNoirs = damesNoires + pionsNoirs;
        let totalDames = damesBlanches + damesNoires;
        let totalPions = pionsBlancs + pionsNoirs;

        if (totalPions === 0) {
            if ((damesBlanches === 2 && damesNoires === 1) || 
                (damesBlanches === 1 && damesNoires === 2) || 
                (damesBlanches === 1 && damesNoires === 1)) {
                declarerEgalite("Égalité : Fin de partie réglementaire (2v1 ou 1v1 de dames).");
                return;
            }
        }

        let condition16CoupsRemplie = false;
        
        if (totalPions + totalDames <= 4) { 
            if (damesBlanches === 1 && pionsBlancs === 0) {
                if ((damesNoires === 3 && pionsNoirs === 0) || 
                    (damesNoires === 2 && pionsNoirs === 1) || 
                    (damesNoires === 1 && pionsNoirs === 2)) {
                    condition16CoupsRemplie = true;
                }
            }
            if (damesNoires === 1 && pionsNoirs === 0) {
                if ((damesBlanches === 3 && pionsBlancs === 0) || 
                    (damesBlanches === 2 && pionsBlancs === 1) || 
                    (damesBlanches === 1 && pionsBlancs === 2)) {
                    condition16CoupsRemplie = true;
                }
            }
        }

        if (condition16CoupsRemplie) {
            if (decompte16Coups === -1) {
                decompte16Coups = 32; 
                console.log("Règle des 16 coups activée.");
            } else {
                decompte16Coups--;
            }

            if (decompte16Coups === 0) {
                declarerEgalite("Égalité : Limite des 16 coups atteinte.");
                return;
            } else {
                statutJeuEl.innerText = `Fin de partie : ${Math.ceil(decompte16Coups / 2)} coups restants`;
            }
        } else {
            decompte16Coups = -1; 
            statutJeuEl.innerText = "Partie en cours";
        }
    }

    function declarerEgalite(message) {
        partieTerminee = true;
        statutJeuEl.innerText = message;
        statutJeuEl.style.color = "#e67e22";
        console.log(message);
    }

    // --- EXECUTION TECHNIQUE D'UN DEPLACEMENT REPLICABLE (POUR LE PULL) ---
    function executerDeplacementGraphique(departLigne, departCol, destLigne, destCol) {
        const caseDepart = document.querySelector(`[data-ligne="${departLigne}"][data-col="${departCol}"].black`);
        const caseArrivee = document.querySelector(`[data-ligne="${destLigne}"][data-col="${destCol}"].black`);
        
        if (!caseDepart || !caseArrivee) return;
        const pion = caseDepart.querySelector('.pion');
        if (!pion) return;

        const estDameAuDepart = pion.classList.contains('dame');
        const couleurPion = pion.classList.contains('blanc') ? 'blanc' : 'noir';

        // Simulation/calcul temporaire pour générer la trajectoire de l'adversaire
        let trajectoires = calculerTrajectoires(departLigne, departCol, couleurPion, estDameAuDepart);
        let coupApplique = trajectoires.find(c => c.destLigne === destLigne && c.destCol === destCol);

        if (!coupApplique) {
            // Fallback de secours si structure complexe
            coupApplique = { captures: [], etapes: [{ l: destLigne, c: destCol }] };
        }

        let aFaitUnePrise = coupApplique.captures.length > 0;
        let aBougeUnPion = !estDameAuDepart;

        // Suppression des victimes
        coupApplique.captures.forEach(coordStr => {
            const [pL, pC] = coordStr.split(',');
            const caseEnnemi = document.querySelector(`[data-ligne="${pL}"][data-col="${pC}"]`);
            if (caseEnnemi) {
                const pionVictime = caseEnnemi.querySelector('.pion');
                if (pionVictime) pionVictime.remove();
            }
        });

        // Déplacement effectif dans le DOM
        caseArrivee.appendChild(pion);

        marquerCheminHistorique(departLigne, departCol, coupApplique, caseArrivee);
        nettoyerAide();

        // Promotion potentielle
        if (!pion.classList.contains('dame')) {
            if ((couleurPion === 'blanc' && destLigne === 10) || (couleurPion === 'noir' && destLigne === 1)) {
                pion.classList.add('dame');
            }
        }

        // Cycle des règles de fin de partie et bascule du tour
        verifierReglesFinDePartie(aBougeUnPion, aFaitUnePrise);

        if (!partieTerminee) {
            tourActuel = (tourActuel === 'blanc') ? 'noir' : 'blanc';
            statusEl.innerText = (tourActuel === 'blanc') ? 'Blancs' : 'Noirs';
            statusEl.className = 'tour-' + tourActuel;

            if (tourActuel === 'blanc') {
                document.body.classList.add('tour-actif-blanc');
                document.body.classList.remove('tour-actif-noir');
            } else {
                document.body.classList.add('tour-actif-noir');
                document.body.classList.remove('tour-actif-blanc');
            }
        }
    }

    // --- SELECTION ET DEPLACEMENT ---
    const casesNoires = document.querySelectorAll('.black');
    casesNoires.forEach(caseNoire => {
        caseNoire.addEventListener('click', function() {
            if (partieTerminee) return; // Bloque le jeu si égalité déclarée

            // Barrière de contrôle du tour JcJ (Consigne 2.2)
            if (MATCH_ID > 0 && tourActuel !== MON_ROLE) {
                console.log("Ce n'est pas votre tour !");
                return;
            }

            let pion = this.querySelector('.pion');
            
            if (pion) {
                const couleurPion = pion.classList.contains('blanc') ? 'blanc' : 'noir';
                if (couleurPion !== tourActuel) return; 

                const ligne = parseInt(this.dataset.ligne);
                const col = parseInt(this.dataset.col);
                const estDame = pion.classList.contains('dame');
                let coupsDuPion = calculerTrajectoires(ligne, col, tourActuel, estDame);
                let maxPlateau = verifierPrisesObligatoiresDuPlateau();

                if (maxPlateau > 0 && !coupsDuPion.some(c => c.captures.length === maxPlateau)) {
                    console.log("Prise obligatoire.");
                    return; 
                }

                document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
                pion.classList.add('selected');
                
                pionSelectionne = {
                    element: pion,
                    ligne: ligne,
                    col: col,
                    couleur: couleurPion
                };
                
                montrerCoupsPossibles(pionSelectionne);
            } 
            else if (pionSelectionne) {
                if (!this.querySelector('.aide-coup')) return;

                const destLigne = parseInt(this.dataset.ligne);
                const destCol = parseInt(this.dataset.col);

                const coupsValibles = coupsPossiblesCalcules.filter(c => c.destLigne === destLigne && c.destCol === destCol);
                if (coupsValibles.length === 0) return; 

                const coupApplique = coupsValibles[0];

                if (coupApplique) {
                    const departLigne = pionSelectionne.ligne;
                    const departCol = pionSelectionne.col;
                    const estUneDameAuDepart = pionSelectionne.element.classList.contains('dame');

                    let aFaitUnePrise = coupApplique.captures.length > 0;
                    let aBougeUnPion = !estUneDameAuDepart; 

                    // Supprimer les victimes
                    coupApplique.captures.forEach(coordStr => {
                        const [pL, pC] = coordStr.split(',');
                        const caseEnnemi = document.querySelector(`[data-ligne="${pL}"][data-col="${pC}"]`);
                        if (caseEnnemi) {
                            const pionVictime = caseEnnemi.querySelector('.pion');
                            if (pionVictime) pionVictime.remove();
                        }
                    });

                    // Déplacement
                    this.appendChild(pionSelectionne.element);

                    // --- EMISSION DU MOUVEMENT (PUSH - Consigne 3.1) ---
                    if (MATCH_ID > 0) {
                        const formData = new FormData();
                        formData.append('match_id', MATCH_ID);
                        formData.append('depart', `${departLigne},${departCol}`);
                        formData.append('arrivee', `${destLigne},${destCol}`);
                        formData.append('couleur', MON_ROLE);

                        fetch('./jeu_ajax.php?action=jouer', {
                            method: 'POST',
                            body: formData
                        })
                        // Incrémentation locale immédiate pour rester en phase
                        dernierCoupCompteur++;
                    }

                    pionSelectionne.element.classList.remove('selected');
                    
                    marquerCheminHistorique(departLigne, departCol, coupApplique, this);
                    nettoyerAide();
                    
                    if (!pionSelectionne.element.classList.contains('dame')) {
                        if ((pionSelectionne.couleur === 'blanc' && destLigne === 10) || 
                            (pionSelectionne.couleur === 'noir' && destLigne === 1)) {
                            pionSelectionne.element.classList.add('dame');
                        }
                    }
                
                    verifierReglesFinDePartie(aBougeUnPion, aFaitUnePrise);

                    if (!partieTerminee) {
                        tourActuel = (tourActuel === 'blanc') ? 'noir' : 'blanc';
                        statusEl.innerText = (tourActuel === 'blanc') ? 'Blancs' : 'Noirs';
                        statusEl.className = 'tour-' + tourActuel;

                        if (tourActuel === 'blanc') {
                            document.body.classList.add('tour-actif-blanc');
                            document.body.classList.remove('tour-actif-noir');
                        } else {
                            document.body.classList.add('tour-actif-noir');
                            document.body.classList.remove('tour-actif-blanc');
                        }
                    }

                    pionSelectionne = null;
                }
            }
        });
    });

    // --- SYNCHRONISATION PAR POLLING (Consigne 3.2, 3.3 & 3.4) ---
    if (MATCH_ID > 0) {
        setInterval(function() {
            // Filtrage du rafraîchissement : inutile de requêter pendant notre tour
            if (tourActuel === MON_ROLE) return;

            // Consommation du flux adverse (Pull)
            fetch(`./jeu_ajax.php?action=charger_dernier_coup&match_id=${MATCH_ID}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.num_coup > dernierCoupCompteur) {
                        dernierCoupCompteur = data.num_coup;

                        // Extraction des coordonnées du coup adverse (Format attendu : "X,Y")
                        const [depL, depC] = data.depart.split(',').map(Number);
                        const [arrL, arrC] = data.arrivee.split(',').map(Number);

                        // Réplication dynamique sur le damier
                        executerDeplacementGraphique(depL, depC, arrL, arrC);
                    }
                })
                .catch(err => console.error("Erreur lors du polling :", err));
        }, 2000);
    }
});
</script>
</body>
</html>