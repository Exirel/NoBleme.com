<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                             INITIALISATION                                                            */
/*                                                                                                                                       */
// Inclusions /***************************************************************************************************************************/
include './../../inc/includes.inc.php'; // Inclusions communes

// Menus du header
$header_menu      = 'NoBleme';
$header_sidemenu  = 'ListeDesMembres';

// Identification
$page_nom = "Parcourt la liste des membres";
$page_url = "pages/nobleme/membres";

// Langages disponibles
$langage_page = array('FR','EN');

// Titre et description
$page_titre = ($lang == 'FR') ? "Liste des membres" : "User list";
$page_desc  = "Liste des utilisateurs inscrits sur NoBleme";

// JS
$js = array('dynamique');




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                        PRÉPARATION DES DONNÉES                                                        */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

// On va chercher tous les membres
$qmembres = "       SELECT    membres.id            ,
                              membres.pseudonyme    ,
                              membres.admin         ,
                              membres.sysop         ,
                              membres.moderateur    ,
                              membres.date_creation ,
                              membres.derniere_visite
                    FROM      membres               ";

// Recherche
if(isset($_POST['search_pseudo']))
{
  $search_pseudo = postdata($_POST['search_pseudo'], "string");
  $qmembres .= "    WHERE     membres.pseudonyme LIKE '%$search_pseudo%' ";
}

// Tri
if(isset($_POST['search_sort']))
{
  $search_sort = postdata($_POST['search_sort'], "string");
  if($search_sort == 'pseudo')
    $qmembres .= "  ORDER BY  membres.pseudonyme ASC ";
  else if($search_sort == 'visite')
    $qmembres .= "  ORDER BY  membres.derniere_visite DESC ";
  else
    $qmembres .= "  ORDER BY  membres.date_creation DESC ";
}
else
  $qmembres .= "    ORDER BY  membres.date_creation DESC ";

// Balançons la requête
$qmembres = query($qmembres);

// Préparation des données
for($nmembres = 0 ; $dmembres = mysqli_fetch_array($qmembres) ; $nmembres++)
{
  $m_id[$nmembres]      = $dmembres['id'];
  $m_pseudo[$nmembres]  = predata($dmembres['pseudonyme']);
  $m_inscrit[$nmembres] = predata(ilya($dmembres['date_creation'], $lang));
  $m_visite[$nmembres]  = predata(ilya($dmembres['derniere_visite'], $lang));
  $m_css[$nmembres]     = ((time() - $dmembres['derniere_visite']) < 864000) ? ' class="gras"' : '';
  $m_css[$nmembres]     = ($dmembres['moderateur']) ? ' class="vert_background gras"' : $m_css[$nmembres];
  $m_css[$nmembres]     = ($dmembres['sysop']) ? ' class="neutre gras"' : $m_css[$nmembres];
  $m_css[$nmembres]     = ($dmembres['admin']) ? ' class="negatif gras"' : $m_css[$nmembres];
  $m_csshref[$nmembres] = ($dmembres['moderateur']) ? 'texte_nobleme_fonce' : 'texte_noir';
  $m_csshref[$nmembres] = ($dmembres['sysop'] || $dmembres['admin']) ? 'texte_blanc' : $m_csshref[$nmembres];
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                   TRADUTION DU CONTENU MULTILINGUE                                                    */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

$traduction['titre']        = ($lang == 'FR') ? "Liste des membres" : "Registered user list";
$traduction['labelpseudo']  = ($lang == 'FR') ? "Recherche d'un membre:" : "Search by nickname:";
$traduction['mb_pseudo']    = ($lang == 'FR') ? "PSEUDONYME" : "NICKNAME";
$traduction['mb_inscrit']   = ($lang == 'FR') ? "INSCRIPTION" : "REGISTERED";
$traduction['mb_visite']    = ($lang == 'FR') ? "DERNIÈRE CONNEXION" : "LAST LOGIN";
$traduction['mb_vide']      = ($lang == 'FR') ? "AUCUN MEMBRE TROUVÉ" : "NO USERS FOUND";

if($lang == 'FR')
  $traduction['description'] = "<p>
  Le tableau ci-dessous recense tous les utilisateurs inscrits sur NoBleme, du plus récent au plus ancien. Vous pouvez cliquer sur le titre d'une colonne pour changer l'ordre de tri. Afin de les distinguer du reste, certains membres apparaissent dans des styles ou des couleurs différents:
</p>
<p>
  Les utilisateurs qui se sont <a href=\"".$chemin."pages/nobleme/online?noguest\">connectés récemment</a> à leur compte apparaissent en <span class=\"gras texte_noir\">gras</span><br>
  Les membres de <a class=\"gras\" href=\"".$chemin."pages/nobleme/admins\">l'équipe administrative</a> apparaissent dans leurs couleurs respectives.<br>
</p>
<p>
  Cliquez sur un membre dans le tableau pour voir sa page de profil. Si vous cherchez un membre en particulier, entrez son pseudonyme dans le formulaire de recherche ci-dessous:
</p>";
else
  $traduction['description'] = "<p>
  The table below lists all users that have registered on NoBleme, from the most recent to the oldest. You can click on the title of a column to sort the table by that column's contents. In order to tell them apart from the others, some users appear in the list with different formatting or colors:
</p>
<p>
  Users that have <a href=\"".$chemin."pages/nobleme/online\">recently logged into their account</a> will appear in <span class=\"gras texte_noir\">bold</span><br>
  Members of the <a class=\"gras\" href=\"".$chemin."pages/nobleme/admins\">administrative team</a> will appear each in their respective formatting<br>
</p>
<p>
  Click on a user in the list to see its profile page, which contains more info on said user. If you are looking for a specific user, enter part or all of his nickname in the search box below:
</p>";




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                         AFFICHAGE DES DONNÉES                                                         */
/*                                                                                                                                       */
if(!isset($_GET['dynamique'])){ /* Ne pas afficher toute la page si elle est invoquée par du XHR */ include './../../inc/header.inc.php';?>

      <div class="texte">

        <h1><?=$traduction['titre']?></h1>

        <?=$traduction['description']?>

        <br>

      </div>

      <div class="minitexte2">

        <fieldset>
          <label for="pseudoMembre"><?=$traduction['labelpseudo']?></label>
          <input id="pseudoMembre" name="pseudoMembr" class="indiv" type="text"
                 onkeyup="dynamique('<?=$chemin?>', 'membres?dynamique', 'membres_tableau',
                          'search_pseudo='+dynamique_prepare('pseudoMembre') ,1);">
        </fieldset>

      </div>

      <br>
      <br>
      <hr class="separateur_contenu">
      <br>

      <div class="texte">

        <table class="titresnoirs">
          <thead>
            <tr>
              <th class="pointeur" onclick="dynamique('<?=$chemin?>', 'membres?dynamique', 'membres_tableau',
                  'search_pseudo='+dynamique_prepare('pseudoMembre')+'&search_sort=pseudo', 1);">
                <?=$traduction['mb_pseudo']?>
              </th>
              <th class="pointeur" onclick="dynamique('<?=$chemin?>', 'membres?dynamique', 'membres_tableau',
                  'search_pseudo='+dynamique_prepare('pseudoMembre')+'&search_sort=inscrit', 1);">
                <?=$traduction['mb_inscrit']?>
              </th>
              <th class="pointeur" onclick="dynamique('<?=$chemin?>', 'membres?dynamique', 'membres_tableau',
                  'search_pseudo='+dynamique_prepare('pseudoMembre')+'&search_sort=visite', 1);">
                <?=$traduction['mb_visite']?>
              </th>
            </tr>
          </thead>
          <tbody class="align_center" id="membres_tableau">
            <?php } for($i=0;$i<$nmembres;$i++) { ?>
            <tr>
              <td<?=$m_css[$i]?>>
                <a class="<?=$m_csshref[$i]?>" href="<?=$chemin?>pages/user/user?id=<?=$m_id[$i]?>">
                  <?=$m_pseudo[$i]?>
                </a>
              </td>
              <td<?=$m_css[$i]?>>
                <a class="<?=$m_csshref[$i]?>" href="<?=$chemin?>pages/user/user?id=<?=$m_id[$i]?>">
                  <?=$m_inscrit[$i]?>
                </a>
              </td>
              <td<?=$m_css[$i]?>>
                <a class="<?=$m_csshref[$i]?>" href="<?=$chemin?>pages/user/user?id=<?=$m_id[$i]?>">
                  <?=$m_visite[$i]?>
                </a>
              </td>
            </tr>
            <?php } if(!$nmembres) { ?>
            <tr>
              <td colspan="3" class="negatif texte_blanc gras">
                <?=$traduction['mb_vide']?>
              </td>
            </tr>
            <?php } if(!isset($_GET['dynamique'])) { ?>
          </tbody>
        </table>

      </div>

<?php include './../../inc/footer.inc.php'; /*********************************************************************************************/
/*                                                                                                                                       */
/*                                                              FIN DU HTML                                                              */
/*                                                                                                                                       */
/***************************************************************************************************************************************/ }