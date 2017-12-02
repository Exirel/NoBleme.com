<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                 CETTE PAGE NE PEUT S'OUVRIR QUE SI ELLE EST INCLUDE PAR UNE AUTRE PAGE                                */
/*                                                                                                                                       */
// Include only /*************************************************************************************************************************/
if(substr(dirname(__FILE__),-8).basename(__FILE__) == str_replace("/","\\",substr(dirname($_SERVER['PHP_SELF']),-8).basename($_SERVER['PHP_SELF'])))
  exit('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>Vous n\'êtes pas censé accéder à cette page, dehors!</body></html>');




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Renvoie le nom de l'option d'un sujet de discussion
//
// $option              est le nom de l'option sur laquelle on veut des infos
// $lang    (optionnel) est la langue dans laquelle renvoyer l'option
//
// Utilisation: forum_option_info('Anonyme', 'FR');

function forum_option_info($option, $lang="FR")
{
  // Apparence
  if($option == 'Fil')
    $return = ($lang == 'FR') ? 'Fil de discussion' : 'Linear thread';
  if($option == 'Anonyme')
    $return = ($lang == 'FR') ? 'Fil de discussion anonyme' : 'Anonymous thread';

  // Classification
  if($option == 'Standard')
    $return = ($lang == 'FR') ? 'Sujet standard' : 'Standard topic';
  if($option == 'Sérieux')
    $return = ($lang == 'FR') ? 'Sujet sérieux' : 'Serious topic';
  if($option == 'Débat')
    $return = ($lang == 'FR') ? 'Débat d\'opinion' : 'Debate';
  if($option == 'Jeu')
    $return = ($lang == 'FR') ? 'Jeu de forum' : 'Forum game';

  // Catégorie
  if($option == 'Aucune')
    $return = ($lang == 'FR') ? 'Aucune catégorie' : 'Uncategorized';
  if($option == 'Politique')
    $return = ($lang == 'FR') ? 'Politique' : 'Political';
  if($option == 'Informatique')
    $return = ($lang == 'FR') ? 'Informatique' : 'Computer science';
  if($option == 'NoBleme')
    $return = 'NoBleme.com';

  // On renvoie la valeur demandée
  return $return;
}




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Recompte le nombre de messages postés par l'user
//
// $id est l'id du membre dont on veut recompter les messages
//
// Utilisation forum_recompter_messages_membre(1);

function forum_recompter_messages_membre($id=0)
{
  // On va chercher tous les messages qu'on peut recompter
  $qcompte = mysqli_fetch_array(query(" SELECT    COUNT(*) AS 'count_messages'
                                        FROM      forum_message
                                        LEFT JOIN forum_sujet ON forum_message.FKforum_sujet = forum_sujet.id
                                        WHERE     forum_message.FKmembres         =         '$id'
                                        AND       forum_message.message_supprime  =         0
                                        AND       forum_sujet.public              =         1
                                        AND       forum_sujet.apparence           NOT LIKE  'Anonyme'
                                        AND       forum_sujet.classification      NOT LIKE  'Jeu' "));

  // Et on met à jour le compte de messages de l'user
  $compte_messages = $qcompte['count_messages'];
  query(" UPDATE membres SET forum_messages = '$compte_messages' WHERE membres.id = '$id' ");

  // Au cas où ça pourrait servir, on renvoie le nouveau postcount
  return $compte_messages;
}