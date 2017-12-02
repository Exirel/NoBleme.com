<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                             INITIALISATION                                                            */
/*                                                                                                                                       */
// Inclusions /***************************************************************************************************************************/
include './../../inc/includes.inc.php'; // Inclusions communes

// Permissions
if(isset($_GET['mod']))
  sysoponly($lang);

// Menus du header
$header_menu      = (!isset($_GET['mod'])) ? 'NoBleme' : 'Admin';
$header_sidemenu  = (!isset($_GET['mod'])) ? 'ActiviteRecente' : 'ModLogs';

// Identification
$page_nom = "Consulte l'activité récente";
$page_url = "pages/nobleme/activite";

// Lien court
$shorturl = "a";

// Langages disponibles
$langage_page = (!isset($_GET['mod'])) ? array('FR','EN') : array('FR');

// Titre et description
$page_titre = ($lang == 'FR') ? "Activité récente" : "Recent activity";
$page_titre = (isset($_GET['mod'])) ? "Logs de modération" : $page_titre;
$page_desc  = "Liste chronologique des évènements qui ont eu lieu récemment";

// CSS & JS
$js  = array('dynamique', 'toggle');




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                        TRAITEMENT DU POST-DATA                                                        */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Préparation de l'URL dynamique selon si c'est les logs de modération ou la liste des tâches
$activite_dynamique_url = (!isset($_GET['mod'])) ? "activite" : "activite?mod";




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Suppression d'une entrée dans la liste

if(isset($_POST['activite_delete']) && getadmin())
{
  $activite_delete = postdata($_POST['activite_delete']);
  query(" DELETE FROM activite      WHERE activite.id               = '$activite_delete' ");
  query(" DELETE FROM activite_diff WHERE activite_diff.FKactivite  = '$activite_delete' ");
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                        PRÉPARATION DES DONNÉES                                                        */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Préparation du tableau, dans les variables suivantes :
// $nactrec                         - Nombre de lignes au tableau renvoyé
// $activite_id[$nactrec]           - ID dans la table activite
// $activite_date[$nactrec]         - Ancienneté de l'activité (format texte)
// $activite_desc[$nactrec][$lang]  - Description de l'activité dans le langage spécifié
// $activite_href[$nactrec]         - Lien vers lequel l'activité pointe
// $activite_css[$nactrec]          - CSS à appliquer à l'activité
// $activite_raison[$nactrec]       - (optionnel) Justification du log
// $activite_diff[$nactrec]         - (optionnel) Différences stockées dans le log

// On commence par aller chercher toute l'activité récente
$qactrec = "    SELECT    activite.id           ,
                          activite.timestamp    ,
                          activite.pseudonyme   ,
                          activite.FKmembres    ,
                          activite.action_type  ,
                          activite.action_id    ,
                          activite.action_titre ,
                          activite.parent       ,
                          activite.justification
                FROM      activite              ";

// Activité récente ou log de modération
if(isset($_GET['mod']) && getsysop())
  $qactrec .= " WHERE     activite.log_moderation = 1 ";
else
  $qactrec .= " WHERE     activite.log_moderation = 0 ";

// On rajoute la recherche si y'en a une
if(isset($_POST['activite_type']))
{
  $activite_type = postdata($_POST['activite_type']);
  if($activite_type == 'membres')
    $qactrec .= " AND     ( activite.action_type LIKE 'register'
                  OR        activite.action_type LIKE 'profil'
                  OR        activite.action_type LIKE 'profil_%'
                  OR        activite.action_type LIKE 'droits_%'
                  OR        activite.action_type LIKE 'ban'
                  OR        activite.action_type LIKE 'deban'
                  OR        activite.action_type LIKE 'editpass' ) ";
  else if($activite_type == 'irl')
    $qactrec .= " AND       activite.action_type LIKE 'irl_%' ";
  else if($activite_type == 'dev')
    $qactrec .= " AND     ( activite.action_type LIKE 'version'
                  OR        activite.action_type LIKE 'devblog'
                  OR        activite.action_type LIKE 'todo_%' )";
  else if($activite_type == 'misc')
    $qactrec .= " AND       activite.action_type LIKE 'quote' ";
}

// On trie
$qactrec .= "   ORDER BY  activite.timestamp DESC ";

// On décide combien on en select
if(isset($_POST['activite_num']))
{
  $activite_limit = postdata($_POST['activite_num']);
  $activite_limit = ($activite_limit > 1000) ? 1000 : $activite_limit;
  $qactrec .= " LIMIT     ".$activite_limit;
}
else
  $qactrec .= " LIMIT     100 ";

// On balance la requête
$qactrec = query($qactrec);

// Et on prépare les données comme il se doit
for($nactrec = 0 ; $dactrec = mysqli_fetch_array($qactrec) ; $nactrec++)
{
  // On va avoir besoin de l'ID pour la suppression, ainsi que de la date de l'action
  $activite_id[$nactrec]      = $dactrec['id'];
  $activite_date[$nactrec]    = ilya($dactrec['timestamp']);

  // Par défaut on met toutes les variables à zéro
  $activite_css[$nactrec]         = "";
  $activite_href[$nactrec]        = "";
  $activite_desc[$nactrec]['FR']  = "";
  $activite_desc[$nactrec]['EN']  = "";
  $activite_raison[$nactrec]      = ($dactrec['justification']) ? predata($dactrec['justification']) : "";
  $activite_diff[$nactrec]        = "";

  // On va chercher les diffs s'il y en a
  $qdiff = query("  SELECT    activite_diff.titre_diff  ,
                              activite_diff.diff_avant  ,
                              activite_diff.diff_apres
                    FROM      activite_diff
                    WHERE     activite_diff.FKactivite = '".$dactrec['id']."'
                    ORDER BY  activite_diff.id ASC ");
  while($ddiff = mysqli_fetch_array($qdiff))
  {
    if($ddiff['titre_diff'])
    {
      if($ddiff['diff_apres'])
        $activite_diff[$nactrec] .= '<span class="gras">'.predata($ddiff['titre_diff']).' :</span> '.bbcode(diff(predata($ddiff['diff_avant'], 1), predata($ddiff['diff_apres'], 1))).'<br>';
      else
        $activite_diff[$nactrec] .= '<span class="gras">'.predata($ddiff['titre_diff']).' :</span> '.bbcode(predata($ddiff['diff_avant'], 1)).'<br>';
    }
    else
      $activite_diff[$nactrec] .= bbcode(predata($ddiff['diff_avant'])).'<br>';
  }

  // Puis on passe au traitement au cas par cas des divers types d'activité...


  //*************************************************************************************************************************************//
  //                                                               MEMBRES                                                               //
  //*************************************************************************************************************************************//
  // Nouvel utilisateur

  if($dactrec['action_type'] === 'register')
  {
    $activite_css[$nactrec]         = 'texte_blanc nobleme_clair';
    $activite_href[$nactrec]        = $chemin.'pages/user/user?id='.$dactrec['FKmembres'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme'])." s'est inscrit(e) sur NoBleme !";
    $activite_desc[$nactrec]['EN']  = predata($dactrec['pseudonyme'])." registered on NoBleme!";
  }

  //***************************************************************************************************************************************
  // Utilisateur modifie son profil

  else if($dactrec['action_type'] === 'profil')
  {
    $activite_href[$nactrec]        = $chemin.'pages/user/user?id='.$dactrec['FKmembres'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a modifié son profil public';
  }

  //***************************************************************************************************************************************
  // Profil d'un user modifié par un admin

  else if($dactrec['action_type'] === 'profil_edit')
  {
    $activite_css[$nactrec]         = 'neutre texte_blanc';
    $activite_href[$nactrec]        = $chemin.'pages/user/user?id='.$dactrec['FKmembres'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a modifié le profil public de '.predata($dactrec['pseudonyme']);
  }

  //***************************************************************************************************************************************
  // Mot de passe d'un user modifié par un admin

  else if($dactrec['action_type'] === 'editpass')
  {
    $activite_css[$nactrec]         = 'neutre texte_blanc';
    $activite_href[$nactrec]        = $chemin.'pages/user/user?id='.$dactrec['FKmembres'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a modifié le mot de passe de '.predata($dactrec['pseudonyme']);
  }

  //***************************************************************************************************************************************
  // Utilisateur banni

  else if($dactrec['action_type'] === 'ban' && !isset($_GET['mod']))
  {
    $activite_css[$nactrec]         = 'negatif texte_blanc gras';
    $activite_href[$nactrec]        = $chemin.'pages/user/user?id='.$dactrec['FKmembres'];
    $temp                           = ($dactrec['action_id'] > 1) ? 's' : '';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a été banni(e) pendant '.$dactrec['action_id'].' jour'.$temp;
    $activite_desc[$nactrec]['EN']  = predata($dactrec['pseudonyme']).' has been banned for '.$dactrec['action_id'].' day'.$temp;
  }
  else if($dactrec['action_type'] == 'ban')
  {
    $activite_css[$nactrec]         = 'negatif texte_blanc gras';
    $activite_href[$nactrec]        = $chemin.'pages/sysop/pilori';
    $temp                           = ($dactrec['action_id'] > 1) ? 's' : '';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a banni '.predata($dactrec['pseudonyme']).' pendant '.$dactrec['action_id'].' jour'.$temp;
  }

  //***************************************************************************************************************************************
  // Utilisateur débanni

  else if($dactrec['action_type'] === 'deban' && !isset($_GET['mod']))
  {
    $activite_css[$nactrec]         = 'positif texte_blanc gras';
    $activite_href[$nactrec]        = $chemin.'pages/user/user?id='.$dactrec['FKmembres'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a été débanni(e)';
    $activite_desc[$nactrec]['EN']  = predata($dactrec['pseudonyme']).' has been unbanned';
  }
  else if($dactrec['action_type'] == 'deban')
  {
    $activite_css[$nactrec]         = 'positif texte_blanc gras';
    $activite_href[$nactrec]        = $chemin.'pages/sysop/pilori';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a débanni '.predata($dactrec['pseudonyme']);
  }

  //***************************************************************************************************************************************
  // Permissions: Plus aucun droit

  else if($dactrec['action_type'] === 'droits_delete')
  {
    $activite_css[$nactrec]         = 'negatif texte_blanc gras';
    $activite_href[$nactrec]        = $chemin.'pages/nobleme/admins';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme'])." ne fait plus partie de l'équipe administrative";
  }

  //***************************************************************************************************************************************
  // Permissions: Plus aucun droit

  else if($dactrec['action_type'] === 'droits_mod')
  {
    $activite_css[$nactrec]         = 'vert_background texte_noir';
    $activite_href[$nactrec]        = $chemin.'pages/nobleme/admins';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme'])." a rejoint l'équipe administrative en tant que modérateur";
  }

  //***************************************************************************************************************************************
  // Permissions: Plus aucun droit

  else if($dactrec['action_type'] === 'droits_sysop')
  {
    $activite_css[$nactrec]         = 'neutre texte_blanc gras';
    $activite_href[$nactrec]        = $chemin.'pages/nobleme/admins';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme'])." a rejoint l'équipe administrative en tant que sysop";
  }




  //*************************************************************************************************************************************//
  //                                                                FORUM                                                                //
  //*************************************************************************************************************************************//
  // Nouveau sujet

  if($dactrec['action_type'] === 'forum_new' && !isset($_GET['mod']))
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background_clair';
    $activite_href[$nactrec]        = $chemin.'pages/forum/sujet?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = $dactrec['pseudonyme'].' a ouvert un sujet sur le forum : '.tronquer_chaine(predata($dactrec['action_titre']), 55, '...');
    $activite_desc[$nactrec]['EN']  = $dactrec['pseudonyme'].' opened a forum topic: '.tronquer_chaine(predata($dactrec['action_titre']), 55, '...');
  }
  else if($dactrec['action_type'] === 'forum_new')
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background_clair';
    $activite_href[$nactrec]        = $chemin.'pages/forum/sujet?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = $dactrec['pseudonyme'].' a ouvert un sujet privé sur le forum : '.tronquer_chaine(predata($dactrec['action_titre']), 50, '...');
    $activite_desc[$nactrec]['EN']  = $dactrec['pseudonyme'].' opened a private forum topic: '.tronquer_chaine(predata($dactrec['action_titre']), 50, '...');
  }




  //*************************************************************************************************************************************//
  //                                                                 IRL                                                                 //
  //*************************************************************************************************************************************//
  // Nouvelle IRL

  else if($dactrec['action_type'] === 'irl_new' && !isset($_GET['mod']))
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background_clair';
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = 'Nouvelle rencontre IRL planifiée le '.predata($dactrec['action_titre']);
    $activite_desc[$nactrec]['EN']  = 'New real life meetup planned';
  }
  else if($dactrec['action_type'] === 'irl_new')
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background_clair';
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a crée une nouvelle IRL le '.predata($dactrec['action_titre']);
  }

  //***************************************************************************************************************************************
  // IRL modifiée

  else if($dactrec['action_type'] === 'irl_edit')
  {
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a modifié l\'IRL du '.predata($dactrec['action_titre']);
  }

  //***************************************************************************************************************************************
  // Suppression d'une IRL

  else if($dactrec['action_type'] === 'irl_delete')
  {
    $activite_css[$nactrec]         = 'mise_a_jour texte_blanc';
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme'])." a supprimé l'IRL du ".predata($dactrec['action_titre']);
  }

  //***************************************************************************************************************************************
  // Nouveau participant à une IRL

  else if($dactrec['action_type'] === 'irl_add_participant' && !isset($_GET['mod']))
  {
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a rejoint l\'IRL du '.predata($dactrec['action_titre']);
    $activite_desc[$nactrec]['EN']  = predata($dactrec['pseudonyme']).' joined a real life meetup';
  }
  else if($dactrec['action_type'] === 'irl_add_participant')
  {
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a ajouté '.predata($dactrec['pseudonyme']).' à l\'IRL du '.predata($dactrec['action_titre']);
  }

  //***************************************************************************************************************************************
  // Participant modifié dans une IRL

  else if($dactrec['action_type'] === 'irl_edit_participant')
  {
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a modifié les infos de '.predata($dactrec['pseudonyme']).' dans l\'IRL du '.predata($dactrec['action_titre']);
  }

  //***************************************************************************************************************************************
  // Participant supprimé d'une IRL

  else if($dactrec['action_type'] === 'irl_del_participant' && !isset($_GET['mod']))
  {
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme']).' a quitté l\'IRL du '.predata($dactrec['action_titre']);
    $activite_desc[$nactrec]['EN']  = predata($dactrec['pseudonyme']).' left a real life meetup';
  }
  else if($dactrec['action_type'] === 'irl_del_participant')
  {
    $activite_href[$nactrec]        = $chemin.'pages/irl/irl?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['parent']).' a supprimé '.predata($dactrec['pseudonyme']).' de l\'IRL du '.predata($dactrec['action_titre']);
  }




  //*************************************************************************************************************************************//
  //                                                            MISCELLANÉES                                                             //
  //*************************************************************************************************************************************//
  // Nouvelle miscellanée

  else if($dactrec['action_type'] === 'quote')
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background_clair';
    $activite_href[$nactrec]        = $chemin.'pages/quotes/quote?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = 'Miscellanée #'.$dactrec['action_id'].' ajoutée à la collection';
  }




  //*************************************************************************************************************************************//
  //                                                            DÉVELOPPEMENT                                                            //
  //*************************************************************************************************************************************//
  // Nouvelle version

  else if($dactrec['action_type'] === 'version')
  {
    $activite_css[$nactrec]         = 'gras texte_blanc positif';
    $activite_href[$nactrec]        = $chemin.'pages/todo/roadmap';
    $activite_desc[$nactrec]['FR']  = "Nouvelle version de NoBleme.com : ".predata($dactrec['action_titre']);
    $activite_desc[$nactrec]['EN']  = "New version of NoBleme.com: ".predata($dactrec['action_titre']);
  }

  //***************************************************************************************************************************************
  // Nouveau devblog

  else if($dactrec['action_type'] === 'devblog')
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background';
    $activite_href[$nactrec]        = $chemin.'pages/devblog/devblog?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = "Nouveau devblog publié : ".predata(tronquer_chaine($dactrec['action_titre'], 50, '...'));
  }

  //***************************************************************************************************************************************
  // Nouvelle tâche

  else if($dactrec['action_type'] === 'todo_new')
  {
    $activite_href[$nactrec]        = $chemin.'pages/todo/index?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = predata($dactrec['pseudonyme'])." a ouvert une tâche : ".predata(tronquer_chaine($dactrec['action_titre'], 50, '...'));
  }

  //***************************************************************************************************************************************
  // Tâche résolue

  else if($dactrec['action_type'] === 'todo_fini')
  {
    $activite_css[$nactrec]         = 'texte_noir vert_background_clair';
    $activite_href[$nactrec]        = $chemin.'pages/todo/index?id='.$dactrec['action_id'];
    $activite_desc[$nactrec]['FR']  = "Tache résolue : ".predata(tronquer_chaine($dactrec['action_titre'], 70, '...'));
  }




  //*************************************************************************************************************************************//
  //                                                            DÉVELOPPEMENT                                                            //
  //*************************************************************************************************************************************//
  // Cas par défaut

  else
  {
    $activite_desc[$nactrec]['FR']  = "Ceci ne devrait pas apparaitre ici, oups (".$dactrec['action_type'].")";
    $activite_desc[$nactrec]['EN']  = "This should not appear here, oops (".$dactrec['action_type'].")";
  }
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                   TRADUCTION DU CONTENU MULTILINGUE                                                   */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

if($lang == 'FR')
{
  $trad['titre']      = "Activité récente";
  $trad['soustitre']  = "Pour ceux qui ne veulent rien rater et tout traquer";
  $trad['titre_mod']  = "Logs de modération";
  $trad['titretable'] = "DERNIÈRES ACTIONS";
  $trad['ar_tout']    = "Voir tout";
  $trad['ar_user']    = "Membres";
  $trad['ar_irl']     = "IRL";
  $trad['ar_dev']     = "Développement";
  $trad['ar_misc']    = "Miscellanées";
}


/*****************************************************************************************************************************************/

else if($lang == 'EN')
{
  $trad['titre']      = "Recent activity";
  $trad['soustitre']  = "For those of us who don't want to miss a thing";
  $trad['titre_mod']  = "Mod logs";
  $trad['titretable'] = "LATEST ACTIONS";
  $trad['ar_tout']    = "Everything";
  $trad['ar_user']    = "Users";
  $trad['ar_irl']     = "Meetups";
  $trad['ar_dev']     = "Internals";
  $trad['ar_misc']    = "Quotes";
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                         AFFICHAGE DES DONNÉES                                                         */
/*                                                                                                                                       */
if(!getxhr()) { /*********************************************************************************/ include './../../inc/header.inc.php';?>

      <div class="texte2">

        <?php if(!isset($_GET['mod'])) { ?>
        <h1 class="indiv align_center"><?=$trad['titre']?></h1>
        <h6 class="indiv align_center texte_nobleme_clair"><?=$trad['soustitre']?></h6>
        <?php } else { ?>
        <h1 class="indiv align_center"><?=$trad['titre_mod']?></h1>
        <br>
        <p>
          Certains logs de modération ont des icônes à droite de la ligne ( <img height="20" width="20" class="valign_bottom" src="<?=$chemin?>img/icones/pourquoi.png" alt="?"> et <img height="20" width="20" class="valign_bottom" src="<?=$chemin?>img/icones/details.png" alt="?"> ).<br>
          Vous pouvez cliquer dessus pour afficher la justification de l'action et/ou le contenu qui a été modifié/supprimé.
        </p>
        <?php } ?>

        <br>

        <p class="indiv align_center">
          <select id="activite_num"
                  onchange="dynamique('<?=$chemin?>', '<?=$activite_dynamique_url?>', 'activite_table',
                  'activite_num='+dynamique_prepare('activite_num')+
                  '&activite_type='+dynamique_prepare('activite_type'), 1);">
            <option value="100">100</option>
            <option value="250">250</option>
            <option value="1000">1000</option>
          </select>
          <span class="gros gras spaced valign_bottom"><?=$trad['titretable']?></span>
          <select id="activite_type"
                  onchange="dynamique('<?=$chemin?>', '<?=$activite_dynamique_url?>', 'activite_table',
                  'activite_num='+dynamique_prepare('activite_num')+
                  '&activite_type='+dynamique_prepare('activite_type'), 1);">
            <option value="tout"><?=$trad['ar_tout']?></option>
            <option value="membres"><?=$trad['ar_user']?></option>
            <option value="irl"><?=$trad['ar_irl']?></option>
            <option value="misc"><?=$trad['ar_misc']?></option>
            <option value="dev"><?=$trad['ar_dev']?></option>
          </select>
        </p>

        <br>

        <table class="titresnoirs" id="activite_table">
          <?php } ?>
          <thead>
            <tr>
              <th colspan="3">
                &nbsp;
              </th>
            </tr>
          </thead>
          <tbody class="align_center">
            <?php for($i=0;$i<$nactrec;$i++) { ?>
            <?php if($activite_desc[$i][$lang]) { ?>
            <tr>
              <?php if($activite_href[$i]) { ?>
              <td class="pointeur nowrap <?=$activite_css[$i]?>" onclick="window.open('<?=$activite_href[$i]?>','_blank');">
              <?php } else { ?>
              <td class="nowrap <?=$activite_css[$i]?>">
              <?php } ?>
                <?=$activite_date[$i]?>
              </td>
              <?php if($activite_href[$i]) { ?>
              <td class="pointeur nowrap <?=$activite_css[$i]?>" onclick="window.open('<?=$activite_href[$i]?>','_blank');">
              <?php } else { ?>
              <td class="nowrap <?=$activite_css[$i]?>">
              <?php } ?>
                <?=$activite_desc[$i][$lang]?>
              </td>
              <td class="pointeur nowrap <?=$activite_css[$i]?>">
                <?php if(isset($_GET['mod']) && $activite_raison[$i]) { ?>
                <img  height="17" width="17" class="valign_center" src="<?=$chemin?>img/icones/pourquoi.png" alt="?"
                      onclick="toggle_row('activite_hidden<?=$i?>',1);">
                <?php } if(isset($_GET['mod']) && $activite_diff[$i]) { ?>
                <img height="17" width="17" class="valign_center" src="<?=$chemin?>img/icones/details.png" alt="?"
                      onclick="toggle_row('activite_hidden<?=$i?>',1);">
                <?php } if(loggedin()) { ?>
                <img height="17" width="17" class="valign_center" src="<?=$chemin?>img/icones/delete.png" alt="X"
                      onclick="var ok = confirm('Confirmation'); if(ok == true) {
                      dynamique('<?=$chemin?>', '<?=$activite_dynamique_url?>', 'activite_table',
                      'activite_num='+dynamique_prepare('activite_num')+
                      '&activite_type='+dynamique_prepare('activite_type')+
                      '&activite_delete='+<?=$activite_id[$i]?>, 1); }">
                <?php } ?>
              </td>
            </tr>
            <?php if(isset($_GET['mod'])) { ?>
            <tr class="hidden texte_noir" id="activite_hidden<?=$i?>">
              <td colspan="3" class="align_left">
                <?php if($activite_raison[$i]) { ?>
                <span class="alinea gras souligne">Justification de l'action:</span> <?=$activite_raison[$i]?><br>
                <br>
                <?php } if($activite_raison[$i] && $activite_diff[$i]) { ?>
                <hr>
                <br>
                <?php } if($activite_diff[$i]) { ?>
                <span class="alinea gras souligne">Différence(s) avant/après l'action:</span><br>
                <br>
                <?=$activite_diff[$i]?><br>
                <br>
                <?php } ?>
              </td>
            </tr>
            <?php } } } ?>
          </tbody>
          <?php if(!getxhr()) { ?>
        </table>

      </div>

<?php include './../../inc/footer.inc.php'; /*********************************************************************************************/
/*                                                                                                                                       */
/*                                                              FIN DU HTML                                                              */
/*                                                                                                                                       */
/***************************************************************************************************************************************/ }