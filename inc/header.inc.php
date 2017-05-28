<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                 CETTE PAGE NE PEUT S'OUVRIR QUE SI ELLE EST INCLUDE PAR UNE AUTRE PAGE                                */
/*                                                                                                                                       */
// Include only /*************************************************************************************************************************/
if(substr(dirname(__FILE__),-8).basename(__FILE__) == str_replace("/","\\",substr(dirname($_SERVER['PHP_SELF']),-8).basename($_SERVER['PHP_SELF'])))
  exit('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>Vous n\'êtes pas censé accéder à cette page, dehors!</body></html>');




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                     REDIRECTION DES SOUS-DOMAINES                                                     */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

// On récupère le sous-domaine en cours
$le_subd = explode('.',$_SERVER['HTTP_HOST']);

// Et on redirige vers le lieu approprié
if($_SERVER["SERVER_NAME"] != "localhost" && $_SERVER["SERVER_NAME"] != "127.0.0.1"  && $le_subd[0] != 'nobleme' && $le_subd[1] != 'com')
  header("Location: "."http://".$le_subd[1].".".$le_subd[2].$_SERVER['REQUEST_URI']);




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                            GESTION DU LOGIN                                                           */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

// Préparation de l'url complète de la page
$url_complete = ($_SERVER['QUERY_STRING']) ? substr(basename($_SERVER['PHP_SELF']),0,-4).'?'.$_SERVER['QUERY_STRING'] : substr(basename($_SERVER['PHP_SELF']),0,-4);
$url_logout   = ($_SERVER['QUERY_STRING']) ? substr(basename($_SERVER['PHP_SELF']),0,-4).'?'.$_SERVER['QUERY_STRING'].'&amp;logout' : substr(basename($_SERVER['PHP_SELF']),0,-4).'?logout';
$url_complete = destroy_html($url_complete);
$url_logout   = destroy_html($url_logout);

// Déconnexion
if(isset($_GET['logout']))
{
  // Déconnexion & redirection
  logout();
  header("location: ".substr($url_complete,0,-7));
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                       CHECK MISE À JOUR EN COURS                                                      */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

// Récupération de l'état de maj
$checkmaj = query(" SELECT vars_globales.mise_a_jour FROM vars_globales ");
$majcheck = mysqli_fetch_array($checkmaj);

// Si maj, on ferme la machine (sauf pour les admins)
if($majcheck['mise_a_jour'] && @getadmin($_SESSION['user']) == 0)
  exit('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>Une mise à jour est en cours, NoBleme est temporairement fermé.<br><br>Revenez dans quelques minutes.</body></html>');

// CSS spécial pendant les mises à jour
if(!$majcheck['mise_a_jour'])
  $css_body_maj = "";
else
  $css_body_maj = ' class="mise_a_jour"';




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                              GESTION DES PAGEVIEWS ET DES STATS REFERER                                               */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Récupération puis création ou incrémentation du pageview count de la page liée

if(isset($page_nom) && isset($page_id) && !isset($error_mode))
{
  // Réparation des erreurs au cas où
  $page_nom = postdata($page_nom);
  $page_id  = postdata($page_id);

  // Requête pour récupérer les pageviews sur la page courante
  $view_query = query(" SELECT  stats_pageviews.vues
                        FROM    stats_pageviews
                        WHERE   stats_pageviews.nom_page  = '$page_nom'
                        AND     stats_pageviews.id_page   = '$page_id' ");

  // Si la requête renvoie un résultat, reste plus qu'à incrémenter les pageviews
  if (mysqli_num_rows($view_query) != 0)
  {
    // On définit le nombre de pageviews
    $pageviews_array = mysqli_fetch_array($view_query);
    $pageviews = $pageviews_array["vues"] + 1;

    // On update la BDD si l'user n'est pas un admin
    if(((loggedin() && !getadmin($_SESSION['user'])) || !loggedin()))
      query(" UPDATE  stats_pageviews
              SET     stats_pageviews.vues      = stats_pageviews.vues + 1
              WHERE   stats_pageviews.nom_page  = '$page_nom'
              AND     stats_pageviews.id_page   = '$page_id' ");
  }

  // Sinon, il faut créer l'entrée de la page et lui donner son premier pageview
  else
  {
    // On définit le nombre de pageviews
    $pageviews = 1;

    // On update la BDD
    query(" INSERT INTO stats_pageviews
            SET         stats_pageviews.nom_page  = '$page_nom' ,
                        stats_pageviews.id_page   = '$page_id'  ,
                        stats_pageviews.vues      = 1           ");
  }
}




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Traitement de l'activité récente / dernière page visitée

// On laisse pages.inc.php faire le travail préparatoire
include $chemin."./inc/pages.inc.php";

// On s'assure que l'user soit connecté
if(loggedin())
{
  // On prépare la date et l'user
  $visite_timestamp = time();
  $visite_user      = $_SESSION['user'];

  // Nettoyage des données au cas où
  $visite_page  = postdata($visite_page);
  $visite_url   = postdata($visite_url);

  // IP de l'user
  $visite_ip    = postdata($_SERVER["REMOTE_ADDR"]);

  // Puis on fait la requête d'update
  query(" UPDATE  membres
          SET     membres.derniere_visite       = '$visite_timestamp' ,
                  membres.derniere_visite_page  = '$visite_page'      ,
                  membres.derniere_visite_url   = '$visite_url'       ,
                  membres.derniere_visite_ip    = '$visite_ip'
          WHERE   membres.id                    = '$visite_user' ");
}
// Sinon on a affaire a un invité
else
{
  // On nettoie les vieilles infos
  $guest_limit = time() - 86400;
  query(" DELETE FROM invites WHERE invites.derniere_visite < '$guest_limit' ");

  // On va chercher s'il existe
  $guest_ip = postdata($_SERVER["REMOTE_ADDR"]);
  $qguest   = query(" SELECT invites.ip FROM invites WHERE invites.ip = '$guest_ip' ");

  // On crée l'invité si nécessaire
  if(!mysqli_num_rows($qguest))
  {
    $guest_nom = postdata(surnom_mignon());
    query(" INSERT INTO invites SET invites.ip = '$guest_ip', invites.surnom = '$guest_nom' ");
  }

  // Nettoyage des données au cas où
  $visite_page  = postdata($visite_page);
  $visite_url   = postdata($visite_url);

  // Et on met à jour les données
  $guest_timestamp = time();
  query(" UPDATE  invites
          SET     invites.derniere_visite       = '$guest_timestamp'  ,
                  invites.derniere_visite_page  = '$visite_page'      ,
                  invites.derniere_visite_url   = '$visite_url'
          WHERE   invites.ip                    = '$guest_ip'         ");
}




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Si l'user est connecté, mise à jour de son referer

// On ne s'en occupe que si la page a un nom
if(isset($_SERVER['HTTP_REFERER']))
{
  // Abort si le visiteur se balade sur nobleme et ne vient pas de l'extérieur
  $referer = postdata($_SERVER['HTTP_REFERER']);
  $origine = parse_url($referer);
  if($origine['host'] != "www.nobleme.com" AND $origine['host'] != "nobleme.com" AND $origine['host'] != "localhost" AND $origine['host'] != "127.0.0.1")
  {
    // Requête pour récupérer le nombre de visiteurs venus par ce referrer
    $referer_query = query(" SELECT stats_referer.nombre FROM stats_referer WHERE stats_referer.source = '$referer' ");

    // Si la requête renvoie un résultat, reste plus qu'à incrémenter le compte de visiteurs
    if (mysqli_num_rows($referer_query) != 0)
      query(" UPDATE  stats_referer
              SET     stats_referer.nombre  = stats_referer.nombre + 1
              WHERE   stats_referer.source = '$referer'             ");

    // Sinon, il faut créer l'entrée de la source et lui donner son premier visiteur
    else
      query(" INSERT INTO stats_referer
              SET         stats_referer.source  = '$referer'  ,
                          stats_referer.nombre  = 1           ");
  }
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                        PRÉPARATION DES DONNÉES                                                        */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Récupération des notifications

// Si l'user est connecté
if (loggedin())
{
  // Requête
  $qnotif = query(" SELECT  notifications.id
                    FROM    notifications
                    WHERE   notifications.date_consultation       = 0
                    AND     notifications.FKmembres_destinataire  = ".$_SESSION['user']."
                    LIMIT   1 " );

  // Préparation des données pour l'affichage
  $notifications  = mysqli_num_rows($qnotif);
  $notifs_texte   = ($notifications) ? ' (!)' : '';
  $notifs_css     = ($notifications) ? ' class="nouveaux_messages"' : '';
}




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Préparation de la liste des feuilles de style
//
// Les retours à la ligne bizarres sont pour préserver l'indentation du HTML

if (isset($css))
{
  // Si un CSS perso est défini, on inclut déjà reset.css, header.css, et nobleme.css
  $stylesheets = '<link rel="stylesheet" href="'.$chemin.'css/reset.css" type="text/css">
    <link rel="stylesheet" href="'.$chemin.'css/header.css" type="text/css">
    <link rel="stylesheet" href="'.$chemin.'css/nobleme.css" type="text/css">';

  // Puis on loope les éléments de $css et on les ajoute aux css à inclure
  for($i=0;$i<count($css);$i++)
    $stylesheets .= '
    <link rel="stylesheet" href="'.$chemin.'css/'.$css[$i].'.css" type="text/css">';

  // Pour préserver l'indentation
  if (!isset($js))
    $stylesheets .= '
';
}

// Sinon, on se contente d'inclure reset.css, header.css, et nobleme.css
else
  $stylesheets = '<link rel="stylesheet" href="'.$chemin.'css/reset.css" type="text/css">
    <link rel="stylesheet" href="'.$chemin.'css/header.css" type="text/css">
    <link rel="stylesheet" href="'.$chemin.'css/nobleme.css" type="text/css">
';




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Préparation de la liste des .js
//
// Les retours à la ligne bizarres sont pour préserver l'indentation du HTML

// Préparation de la liste des js
if (isset($js))
{
  $javascripts = '';

  // On loope les éléments de $js et on prépare le paquet de .js à inclure
  for($i=0;$i<count($js);$i++)
  {
    $javascripts .= '
    <script type="text/javascript" src="'.$chemin.'js/'.$js[$i].'.js"> </script>';
  }

  // Pour préserver l'indentation
  $javascripts .= '
';
}
else
  $javascripts = '
';

// Pluie de bites tournantes le premier avril
if(date('d-m') == '01-04' && ($_SERVER["SERVER_NAME"] != "localhost" || $_SERVER["SERVER_NAME"] != "127.0.0.1") && substr($_SERVER["PHP_SELF"],-6) != 'cv.php')
  $javascripts .= '
    <script type="text/javascript" src="'.$chemin.'js/festif.js"> </script>
';




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Préparation du titre de la page, sera uniquement NoBleme si aucun titre n'est précisé, et précédé d'un @ en localhost

if (!isset($page_titre))
  $page_titre = 'NoBleme';
else
  $page_titre = 'NoBleme - '.$page_titre;

if($_SERVER["SERVER_NAME"] == "localhost" || $_SERVER["SERVER_NAME"] == "127.0.0.1")
  $page_titre = "@ ".$page_titre;




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Préparation de la meta description de la page

// Si pas de description, truc générique par défaut
if (!isset($page_desc))
  $page_desc = "NoBleme, la communauté web qui n'apporte rien mais a réponse à tout";

// On remplace les caractères interdits pour éviter les conneries
$page_desc = meta_fix($page_desc);

// Si admin, alerte si la longueur est > les 158 caractères max ou < les 25 caractères min
if(loggedin() && getadmin($_SESSION['user']))
{
  if(strlen($page_desc) > 25 && strlen($page_desc) < 155)
    $alerte_meta = "";
  else if (strlen($page_desc) <= 25)
  {
    $alerte_meta  = "Description meta trop courte (".strlen($page_desc)." <= 25)";
    $css_maj      = "maj";
    $css_body_maj = ' class="maj"';
  }
  else
  {
    $alerte_meta  = "Description meta trop longue (".strlen($page_desc)." >= 155)";
    $css_maj      = "maj";
    $css_body_maj = ' class="maj"';
  }
}
else
  $alerte_meta = "";




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Remise à zéro des variables de menu si jamais elles sont vides

if(!isset($header_menu) || $header_menu == 'nobleme')
  $header_menu = '';
if(!isset($header_submenu))
  $header_submenu = '';
if(!isset($header_sidemenu))
  $header_sidemenu = '';




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                         AFFICHAGE DES DONNÉES                                                         */
/*                                                                                                                                       */
/**************************************************************************************************************************************/ ?>

<!DOCTYPE html>
<html<?=$css_body_maj?>>
  <head>
    <title><?=$page_titre?></title>
    <link rel="shortcut icon" href="<?=$chemin?>img/divers/favicon.ico">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="description" content="<?=$page_desc?>">
    <?=$stylesheets?>
    <?=$javascripts?>
  </head>

  <?php if(isset($cette_page_est_404)) { ?>
<body id="body" onLoad="ecrire_404();">
  <?php } else { ?>
<body id="body">
  <?php }

  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  //                                                                                                                                     //
  //                                                           MENU  PRINCIPAL                                                           //
  //                                                                                                                                     //
  ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////?>

    <?php if(!isset($_GET["popup"]) && !isset($_GET["popout"])) { ?>

    <div class="header_topmenu">
      <div id="header_titres" class="header_topmenu_zone">

        <div class="header_topmenu_titre header_topmenu_selected"
             onclick="window.location.href('<?=$chemin?>index.php');">
                                   <a href="<?=$chemin?>index.php">NOBLEME</a>
        </div>
        <div class="header_topmenu_titre"><a href="<?=$chemin?>index.php">DISCUTER</a></div>
        <div class="header_topmenu_titre"><a href="<?=$chemin?>index.php">JOUER</a></div>
        <div class="header_topmenu_titre">LIRE</div>

      </div>
      <div class="header_topmenu_zone">
        <img src="<?=$chemin?>img/icones/lang_en.png" alt="English">
      </div>
    </div>

    <!-- -->

    <div class="menu_sub">
      <div class="header_topmenu_zone">
        MonPseudonymeIci, vous avez reçu un nouveau message !
      </div>
      <div class="header_topmenu_zone">
        Déconnexion
      </div>
    </div>

    <!-- -->

    <div class="containermenu">
      <div class="header_side_nomenu" id="header_nomenu" onclick="document.getElementById('header_sidemenu').style.display = 'flex'; document.getElementById('header_nomenu').style.display = 'none';">
        Afficher le menu latéral
      </div>
      <nav id="header_sidemenu" class="header_sidemenu_mobile">
        <div class="header_sidemenu">
          <div>
            <div class="header_sidemenu_item header_sidemenu_desktop" onclick="document.getElementById('header_nomenu').style.display = 'flex'; document.getElementById('header_sidemenu').style.display = 'none';">
              <a>Masquer le menu latéral</a>
            </div>
            <hr class="header_sidemenu_hr header_sidemenu_desktop">
            <div class="header_sidemenu_item"
                 onclick="window.location.href('<?=$chemin?>index.php');">
                                       <a href="<?=$chemin?>index.php">Page d'accueil</a>
            </div>
            <div class="header_sidemenu_item"><a href="<?=$chemin?>index.php">Élément du menu latéral</a></div>
            <div class="header_sidemenu_item"><a href="<?=$chemin?>index.php">Élément du menu latéral</a></div>
            <hr class="header_sidemenu_hr">
            <div class="header_sidemenu_titre">Titre du menu latéral</div>
            <div class="header_sidemenu_item">Élément du menu latéral</div>
            <div class="header_sidemenu_item header_sidemenu_selected">Élément du menu latéral</div>
            <div class="header_sidemenu_item">Élément du menu latéral</div>
          </div>
        </div>
      </nav>

  <?php ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  //                                                                                                                                     //
  //                                                            FIN DES MENUS                                                            //
  //                                                                                                                                     //
  ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////?>

    <div class="flex_element contenu_page">

      <?php } if($alerte_meta != "") { ?>

      <br>
      <br>

      <div class="gros gras texte_erreur align_center monospace">
        <?=$alerte_meta?>
      </div>

      <?php } ?>