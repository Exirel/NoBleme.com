<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                     CETTE PAGE NE PEUT S'OUVRIR QUE SI ELLE EST APPELÉE PAR DU XHR                                    */
/*                                                                                                                                       */
// Include only /*************************************************************************************************************************/
if(!isset($_POST['xhr']))
  exit('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>Cette page n\'est pas censée être chargée toute seule, dehors !</body></html>');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Inclusions communes + nbrpg, protection des permissions, et correction du chemin vu qu'on se situe un dossier plus haut que d'habitude
include './../../../inc/includes.inc.php';
include './../../../inc/nbrpg.inc.php';
$chemin_fixed   = substr($chemin,0,-3);
$id_personnage  = nbrpg();
if(!$id_personnage)
  exit();

/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                        PRÉPARATION DES DONNÉES                                                        */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Infos sur la session en cours

// On va chercher le contenu de la session
$qsession = query(" SELECT    nbrpg_session.id              AS 's_id'       ,
                              nbrpg_persos.couleur_chat     AS 'p_couleur'  ,
                              nbrpg_persos.nom              AS 'p_nom'      ,
                              membres.pseudonyme            AS 'p_pseudo'   ,
                              nbrpg_session.monstre_niveau  AS 'm_niveau'   ,
                              nbrpg_persos.niveau           AS 'p_niveau'   ,
                              nbrpg_monstres.nom            AS 'm_nom'      ,
                              nbrpg_session.vie             AS 's_vie'      ,
                              nbrpg_persos.max_vie          AS 'p_viemax'   ,
                              nbrpg_monstres.max_vie        AS 'm_viemax'   ,
                              nbrpg_session.danger          AS 's_danger'   ,
                              nbrpg_session.physique        AS 's_physique' ,
                              nbrpg_session.mental          AS 's_mental'
                    FROM      nbrpg_session
                    LEFT JOIN nbrpg_persos    ON nbrpg_session.FKnbrpg_persos   = nbrpg_persos.id
                    LEFT JOIN membres         ON nbrpg_persos.FKmembres         = membres.id
                    LEFT JOIN nbrpg_monstres  ON nbrpg_session.FKnbrpg_monstres = nbrpg_monstres.id
                    ORDER BY  nbrpg_session.danger_initial  DESC ,
                              nbrpg_persos.nom              DESC ,
                              nbrpg_monstres.nom            DESC ");

// Puis on prépare tout ça pour l'affichage
for($nsession = 0 ; $dsession = mysqli_fetch_array($qsession) ; $nsession++)
{
  // On a d'abord besoin de chercher certains effets, on prépare le terrain pour ça
  $session_id                   = $dsession['s_id'];
  $session_effets[$nsession]    = '';
  $buff_hpmax                   = 0;
  $buff_hpmax_p                 = 0;
  $buff_danger                  = 0;
  $buff_danger_p                = 0;
  $buff_physique                = 0;
  $buff_physique_p              = 0;
  $buff_mental                  = 0;
  $buff_mental_p                = 0;

  // Puis on va chercher les effets
  $qeffets                      = query(" SELECT    nbrpg_session_effets.FKnbrpg_effets             AS 'e_id'           ,
                                                    nbrpg_session_effets.duree_restante             AS 'e_duree'        ,
                                                    nbrpg_effets.duree                              AS 'e_dureemax'     ,
                                                    nbrpg_effets.reduction_effet_par_tour           AS 'e_reduction'    ,
                                                    nbrpg_effets.reduction_effet_par_tour_pourcent  AS 'e_reduction_p'  ,
                                                    nbrpg_effets.buff_hpmax                         AS 'e_hpmax'        ,
                                                    nbrpg_effets.buff_hpmax_pourcent                AS 'e_hpmax_p'      ,
                                                    nbrpg_effets.buff_danger                        AS 'e_danger'       ,
                                                    nbrpg_effets.buff_danger_pourcent               AS 'e_danger_p'     ,
                                                    nbrpg_effets.buff_physique                      AS 'e_physique'     ,
                                                    nbrpg_effets.buff_physique_pourcent             AS 'e_physique_p'   ,
                                                    nbrpg_effets.buff_mental                        AS 'e_mental'       ,
                                                    nbrpg_effets.buff_mental_pourcent               AS 'e_mental_p'
                                          FROM      nbrpg_session_effets
                                          LEFT JOIN nbrpg_effets ON nbrpg_session_effets.FKnbrpg_effets = nbrpg_effets.id
                                          WHERE     nbrpg_session_effets.FKnbrpg_session = '$session_id'
                                          ORDER BY  nbrpg_session_effets.duree_restante DESC ");

  // Et on fait des calculs sur ces effets
  while($deffets = mysqli_fetch_array($qeffets))
  {
    $session_effets[$nsession]  .= nbrpg_format_effet($deffets['e_id'],$deffets['e_duree']);
    $duree_max                  = $deffets['e_dureemax'];
    $duree                      = $deffets['e_duree'];
    $reduction                  = $deffets['e_reduction'];
    $reduction_p                = $deffets['e_reduction_p'];
    $buff_hpmax                 += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_hpmax'],$reduction,$reduction_p);
    $buff_hpmax_p               += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_hpmax_p'],$reduction,$reduction_p);
    $buff_danger                += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_danger'],$reduction,$reduction_p);
    $buff_danger_p              += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_danger_p'],$reduction,$reduction_p);
    $buff_physique              += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_physique'],$reduction,$reduction_p);
    $buff_physique_p            += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_physique_p'],$reduction,$reduction_p);
    $buff_mental                += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_mental'],$reduction,$reduction_p);
    $buff_mental_p              += nbrpg_reduction_effet($duree_max,$duree,$deffets['e_mental_p'],$reduction,$reduction_p);
  }

  // Et reste plus qu'à faire les calculs finaux et formater pour l'affichage
  $session_perso                = ($dsession['p_nom']) ? 1 : 0;
  $session_couleur[$nsession]   = ($session_perso) ? $dsession['p_couleur'] : '#133742';
  $session_joueur[$nsession]    = ($session_perso) ? $dsession['p_pseudo'] : '';
  $session_nom[$nsession]       = ($session_perso) ? $dsession['p_nom'] : $dsession['m_nom'];
  $session_niveau[$nsession]    = ($session_perso) ? $dsession['p_niveau'] : $dsession['m_niveau'];
  $session_vie[$nsession]       = nbrpg_vierestante($dsession['s_vie'],($session_perso) ? nbrpg_application_effet($dsession['p_viemax'],$buff_physique,$buff_physique_p,1) : nbrpg_multiplicateur(nbrpg_application_effet($dsession['m_viemax'],$buff_physique,$buff_physique_p,1), $dsession['m_niveau']));
  $session_danger[$nsession]    = nbrpg_application_effet($dsession['s_danger'],$buff_physique,$buff_physique_p,1);
  $session_physique[$nsession]  = nbrpg_application_effet($dsession['s_physique'],$buff_physique,$buff_physique_p,1);
  $session_mental[$nsession]    = nbrpg_application_effet($dsession['s_mental'],$buff_physique,$buff_physique_p,1);
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                         AFFICHAGE DES DONNÉES                                                         */
/*                                                                                                                                       */
/**************************************************************************************************************************************/ ?>

<br>
<br>
<div class="align_center gras">
  <p class="plusgros">Session en cours</p>
</div>
<br>
<br>

<div class="nbrpg_table_session">
  <table class="cadre_gris indiv">
    <thead>
      <tr>
        <td class="cadre_gris_sous_titre vspaced moinsgros">
          JOUEUR
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros">
          NOM
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros pointeur tooltip">
          NIV.
          <div>
            Le niveau du personnage.<br>
            <br>
            Plus il est élevé, plus le personnage est fort.<br>
            C'est aussi con que ça.
          </div>
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros pointeur tooltip">
          HP
          <div>
            Les points de vie du personnage.<br>
            <br>
            S'ils tombent à 0, le personnage meurt et est exclu de la session.<br>
          </div>
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros pointeur tooltip">
          DAN.
          <div class="grandtooltip">
            Le niveau de danger du personnage.<br>
            <br>
            Pour les joueurs, plus le niveau de danger est élevé, plus le joueur a de chances de se faire attaquer par les adversaires.<br>
            <br>
            Pour les adversaires, plus le niveau de danger est élevé, plus il s'agit d'un adversaire difficile à vaincre (à quelques exceptions près).<br>
            <br>
            Lors d'un combat, c'est le personnage le plus dangereux qui a l'honneur de faire la première action. Les personnages prennent ensuite chacun leur tour, par ordre décroissant de dangerosité.<br>
            <br>
            Augmenter votre niveau de danger vous permet d'agir plus tôt lors des combats, mais vous expose également à plus de chances de subir les attaques adverses.
          </div>
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros pointeur tooltip">
          PHY.
          <div class="grandtooltip">
            La forme physique du personnage.<br>
            <br>
            Plus le physique du personnage est élevé, plus les attaques physiques du personnage seront douloureuses.<br>
            <br>
            Un physique élevé offre également plus de chances de survivre aux effets qui affectent le physique du personnage (poison, lenteur, etc.)
          </div>
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros pointeur tooltip">
          MEN.
          <div class="grandtooltip">
            La capacité mentale du personnage.<br>
            <br>
            Plus le mental du personnage est élevé, plus les attaques magiques du personnage seront douloureuses.<br>
            <br>
            Un mental élevé offre également plus de chances de survivre aux effets qui affectent le mental du personnage (contrôle, silence, etc.)
          </div>
        </td>
        <td class="cadre_gris_sous_titre vspaced moinsgros pointeur tooltip">
          EFFETS
          <div class="petittooltip">
            La liste des afflictions, bénédictions, ou autres effets qui sont affectent actuellement le personnage.<br>
            <br>
            Pour voir les détails d'un effet spécifique, passez votre souris dessus et une infobulle s'ouvrira.
          </div>
        </td>
      </tr>
    </thead>
    <tbody class="cadre_gris_altc">
      <?php for($i=0;$i<$nsession;$i++) { ?>
      <tr>
        <td class="cadre_gris align_center vspaced gras" style="color:<?=$session_couleur[$i]?>">
          <?=$session_joueur[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced gras" style="color:<?=$session_couleur[$i]?>">
          <?=$session_nom[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced gras">
          <?=$session_niveau[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced gras">
          <?=$session_vie[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced gras">
          <?=$session_danger[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced gras">
          <?=$session_physique[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced gras">
          <?=$session_mental[$i]?>
        </td>
        <td class="cadre_gris align_center vspaced">
          <?=$session_effets[$i]?>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>