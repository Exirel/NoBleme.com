<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                             INITIALISATION                                                            */
/*                                                                                                                                       */
// Inclusions /***************************************************************************************************************************/
include './../../inc/includes.inc.php'; // Inclusions communes
include './../../inc/requetes.inc.php'; // Requêtes SQL en attente d'exécution

// Permissions
adminonly($lang);

// Menus du header
$header_menu      = 'Dev';
$header_sidemenu  = 'MajRequetes';

// Titre et description
$page_titre = "Dev: Requêtes SQL";

// Identification
$page_nom = "Administre secrètement le site";




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                         AFFICHAGE DES DONNÉES                                                         */
/*                                                                                                                                       */
/************************************************************************************************/ include './../../inc/header.inc.php'; ?>

      <br>
      <br>
      <br>
      <br>
      <br>
      <br>

      <div class="texte">

        <h1 class="positif texte_blanc align_center">LES REQUÊTES ONT ÉTÉ EFFECTUÉES AVEC SUCCÈS</h1>

      </div>

      <br>
      <br>
      <br>
      <br>
      <br>


<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                              FIN DU HTML                                                              */
/*                                                                                                                                       */
/***************************************************************************************************/ include './../../inc/footer.inc.php';