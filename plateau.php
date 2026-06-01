<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?erreur=acces_refuse');
    exit();
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
document.addEventListener('DOMContentLoaded', function() {
    let pionSelectionne = null;
    let tourActuel = 'blanc'; 
    let coupsPossiblesCalcules = []; 

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

    // MODIFICATION : Marque tout le chemin emprunté (Départ -> Escales -> Arrivée)
    function marquerCheminHistorique(caseDepartLigne, caseDepartCol, coupApplique, caseArriveeElement) {
        // 1. Nettoyage complet de l'ancien historique
        document.querySelectorAll('.derniere-case-depart, .case-etape-historique, .derniere-case-arrivee').forEach(el => {
            el.classList.remove('derniere-case-depart', 'case-etape-historique', 'derniere-case-arrivee');
        });

        // 2. Marquer la case de départ originelle
        const caseDepart = document.querySelector(`[data-ligne="${caseDepartLigne}"][data-col="${caseDepartCol}"]`);
        if (caseDepart) caseDepart.classList.add('derniere-case-depart');

        // 3. Marquer toutes les cases intermédiaires de la rafle
        if (coupApplique.etapes && coupApplique.etapes.length > 1) {
            // On prend toutes les étapes sauf la dernière (qui est la case d'arrivée)
            for (let i = 0; i < coupApplique.etapes.length - 1; i++) {
                const etape = coupApplique.etapes[i];
                const caseEtape = document.querySelector(`[data-ligne="${etape.l}"][data-col="${etape.c}"]`);
                if (caseEtape) {
                    caseEtape.classList.add('case-etape-historique');
                }
            }
        }

        // 4. Marquer la case d'arrivée finale
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

    // --- SELECTION ET DEPLACEMENT ---
    const casesNoires = document.querySelectorAll('.black');
    casesNoires.forEach(caseNoire => {
        caseNoire.addEventListener('click', function() {
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
                    pionSelectionne.element.classList.remove('selected');
                    
                    // MODIFICATION : On enregistre tout l'historique du chemin complet
                    marquerCheminHistorique(departLigne, departCol, coupApplique, this);

                    nettoyerAide();
                    
                    if (!pionSelectionne.element.classList.contains('dame')) {
                        if ((pionSelectionne.couleur === 'blanc' && destLigne === 10) || 
                            (pionSelectionne.couleur === 'noir' && destLigne === 1)) {
                            pionSelectionne.element.classList.add('dame');
                        }
                    }
                
                tourActuel = (tourActuel === 'blanc') ? 'noir' : 'blanc';
                statusEl.innerText = (tourActuel === 'blanc') ? 'Blancs' : 'Noirs';
                statusEl.className = 'tour-' + tourActuel;

                // Gestion dynamique du dégradé de l'arrière-plan
                // Gestion dynamique du dégradé de l'arrière-plan (Haut / Bas)
                if (tourActuel === 'blanc') {
                        document.body.classList.add('tour-actif-blanc');
                        document.body.classList.remove('tour-actif-noir');
                } else {
                    document.body.classList.add('tour-actif-noir');
                    document.body.classList.remove('tour-actif-blanc');
                }

                pionSelectionne = null;
                }
            }
        });
    });
});
</script>
</body>
</html>