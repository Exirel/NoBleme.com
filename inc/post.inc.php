<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                 CETTE PAGE NE PEUT S'OUVRIR QUE SI ELLE EST INCLUDE PAR UNE AUTRE PAGE                                */
/*                                                                                                                                       */
// Include only /*************************************************************************************************************************/
if(substr(dirname(__FILE__),-8).basename(__FILE__) == str_replace("/","\\",substr(dirname($_SERVER['PHP_SELF']),-8).basename($_SERVER['PHP_SELF'])))
  exit('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>Vous n\'êtes pas censé accéder à cette page, dehors!</body></html>');




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Fonction de traitement du postdata avant de l'envoyer dans une requête
//
// Paramètres :
// $data est le postdata qui doit être assaini par la fonction
// $type  (optionnel) est le type de données auquel on a affaire
// $min   (optionnel) est la valeur minimum des données
// $max   (optionnel) est la valeur maximum des données
//
// Types de données qui peuvent être traitées :
// "double" est un nombre à virgule, $min et $max sont les valeurs minimum et maximum
// "int" est un nombre entier, $min et $max sont les valeurs minimum et maximum
// "string" est une chaine de caractères, $min et $max sont la longueur minimum et maximum de la chaine
//
// Exemple d'utilisation:
// $assainissement = postdata($_POST["mon_postdata"]);

function postdata($data, $type=NULL, $min=NULL, $max=NULL)
{
  // Traitement des types de données
  if($type == "double")
  {
    if(!is_float($data))
      $data = floatval($data);
    if($min && $data < $min)
      $data = $min;
    if($max && $data > $max)
      $data = $max;
  }
  else if($type == "int")
  {
    if(!is_int($data))
      $data = intval($data);
    if($min && $data < $min)
      $data = $min;
    if($max && $data > $max)
      $data = $max;
  }
  else if($type == "string")
  {
    if(!is_string($data))
      $data = strval($data);
    if($min && strlen($data) < $min)
      $data = str_pad($data, $min, '_');
    if($max && strlen($data) > $max)
      $data = mb_substr($data, 0, $max);
  }

  // On transforme le postdata et on le renvoie
  $output = trim(mysqli_real_escape_string($GLOBALS['db'],$data));
  return $output;
}




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Fonction permettant de faire un postdata sur une valeur qui n'existe peut-être pas
//
// Paramètres:
// $postdata        est le nom du $_POST[] à récupérer
// $type            est le type de variable ('int', 'string', etc.)
// $defaut          (optionnel) est la valeur par défaut à mettre si le postdata n'existe pas
// $longueur_max    (optionnel) est la longueur maximum des chaines de caractères parsées par postdata_vide
// $caracteres_max  (optionnel) est la chaine de caractères à appliquer à la fin si $longueur_max est dépassé
//
// Utilisation: postdata_vide('mon_postdata', 'int', 'rien');

function postdata_vide($postdata, $type, $defaut=NULL, $longueur_max=NULL, $caracteres_max='')
{
  if(!$longueur_max)
    return (isset($_POST[$postdata])) ? postdata($_POST[$postdata], $type) : $defaut;
  else
    return (isset($_POST[$postdata])) ? postdata(tronquer_chaine($_POST[$postdata], $longueur_max), $type, $caracteres_max) : $defaut;
}




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Fonction de traitement du html avant de l'afficher publiquement
//
// Paramètres :
// $data est le contenu qui doit être préparé par la fonction
// $breaks  (optionnel) préserve les retours à la ligne
// $nostrip (optionnel) demande à ne pas faire de stripslashes
//
// Exemple d'utilisation :
// $preparation = predata("string<hr>HTML");

function predata($data,$breaks=NULL,$nostrip=NULL)
{
  $data = ($nostrip) ? htmlentities($data, ENT_QUOTES, 'utf-8') : stripslashes(htmlentities($data, ENT_QUOTES, 'utf-8'));
  return ($breaks) ? nl2br($data) : $data;
}