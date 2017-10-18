<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                             INITIALISATION                                                            */
/*                                                                                                                                       */
// Inclusions /***************************************************************************************************************************/
include './../../inc/includes.inc.php'; // Inclusions communes

// Permissions
guestonly($lang);

// Identification
$page_nom = "Se crée un compte";
$page_url = "pages/user/register";

// Langages disponibles
$langage_page = array('FR','EN');

// Titre et description
$page_titre = ($lang == 'FR') ? "Créer un compte" : "Register";
$page_desc  = "Rejoindre la communauté NoBleme en se créant un compte";

// JS
$js = array('dynamique', '/user/register');




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                        TRAITEMENT DU POST-DATA                                                        */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

// Vérification et validation des données
if (isset($_POST["register_pseudo"]))
{
  // Assainissement du postdata
  $register_pseudo  = postdata($_POST["register_pseudo"],"string");
  $register_pass_1  = postdata($_POST["register_pass_1"],"string");
  $register_pass_2  = postdata($_POST["register_pass_2"],"string");
  $register_email   = postdata($_POST["register_email"],"string");
  $register_captcha = postdata($_POST["register_captcha"],"string");
  $register_erreur  = "";

  // On remet les questions de sécurité là où elles étaient
  $register_q1      = isset($_POST["register_question_1"]) ? postdata($_POST["register_question_1"]) : 0;
  $register_q2      = isset($_POST["register_question_2"]) ? postdata($_POST["register_question_2"]) : 0;
  $register_q3      = isset($_POST["register_question_3"]) ? postdata($_POST["register_question_3"]) : 0;
  $register_q4      = isset($_POST["register_question_4"]) ? postdata($_POST["register_question_4"]) : 0;
  for($i=1;$i<=3;$i++)
  {
    $register_check_q1[$i] = ($register_q1 == $i) ? " checked" : "";
    $register_check_q2[$i] = ($register_q2 == $i) ? " checked" : "";
    $register_check_q3[$i] = ($register_q3 == $i) ? " checked" : "";
    $register_check_q4[$i] = ($register_q4 == $i) ? " checked" : "";
  }

  // On vérifie si le pseudo est valide
  if($register_erreur == "" && strlen($_POST["register_pseudo"]) < 3)
    $register_erreur = ($lang == 'FR') ? "Pseudonyme trop court" : "Nickname is too short";
  else if($register_erreur == "" && strlen($_POST["register_pseudo"]) > 18)
    $register_erreur = ($lang == 'FR') ? "Pseudonyme trop long" : "Nickname is too long";
  else if($register_erreur == "" && strlen($_POST["register_pass_1"]) < 6)
    $register_erreur = ($lang == 'FR') ? "Mot de passe trop court" : "Password is too short";

  // On vérifie l'originalité du pseudo
  $qallnick = query(" SELECT pseudonyme FROM membres ");
  while($dallnick = mysqli_fetch_array($qallnick))
  {
    if(changer_casse($register_pseudo, 'maj') == changer_casse($dallnick['pseudonyme'], 'maj'))
      $register_erreur = "Le pseudonyme choisi existe déjà, merci d'en utiliser un autre";
  }

  // On vérifie que tout soit bien rempli
  if($register_erreur == "" && ($register_pseudo == "" || $register_pass_1 == "" || $register_pass_2 == "" || $register_email == "" || $register_captcha == ""))
    $register_erreur = ($lang == 'FR') ? "Tous les champs sont obligatoires" : "All fields are mandatory";

  // On check si le captcha est bien rempli
  if($register_erreur == "" && ($register_captcha != $_SESSION['captcha']))
    $register_erreur = ($lang == 'FR') ? "Mauvaise réponse au test anti-robot" : "You failed the anti-robot test, try again";

  // Si pas d'erreur, on peut créer le compte
  if($register_erreur == "")
  {
    $register_pass  = postdata(salage($register_pass_1));
    $date_creation  = time();

    // Création du compte
    query(" INSERT INTO membres
            SET         pseudonyme    = '$register_pseudo'  ,
                        pass          = '$register_pass'    ,
                        admin         = '0'                 ,
                        sysop         = '0'                 ,
                        email         = '$register_email'   ,
                        date_creation = '$date_creation'    ");

    // Ajout du register dans l'activité
    $new_user = mysqli_insert_id($db);
    query(" INSERT INTO activite
            SET         timestamp   = '$date_creation'    ,
                        pseudonyme  = '$register_pseudo'  ,
                        FKmembres   = '$new_user'         ,
                        action_type = 'register'          ");

    // Bot IRC NoBleme
    ircbot($chemin,"Nouveau membre enregistré sur le site : ".$_POST["register_pseudo"]." - http://www.nobleme.com/pages/user/user?id=".$new_user,"#NoBleme");

    // Envoi d'un message de bienvenue
    if($lang == 'FR')
      envoyer_notif($new_user,"Bienvenue sur NoBleme !",postdata("[size=1.3][b]Bienvenue sur NoBleme ![/b][/size]\r\n\r\nMaintenant que vous êtes inscrit, pourquoi pas rejoindre la communauté là où elle est active :\r\n- Princiaplement [url=".$chemin."pages/irc/index][color=#2F4456][b]sur le serveur IRC[/b][/color][/url], où l'on discute en temps réel\r\n- Parfois sur [url=".$chemin."pages/forum/index][color=#2F4456][b]le forum[/b][/color][/url], où l'on discute en différé\r\n- Et dans tous les endroits actifs dans [url=".$chemin."pages/nobleme/activite][color=#2F4456][b]l'activité récente[/b][/color][/url], où vous aurez une idée de ce qui se passe sur le site\r\n\r\n\r\nBon séjour sur NoBleme,\r\nSi vous avez la moindre question, n'hésitez pas à répondre à ce message.\r\n\r\nVotre administrateur,\r\n[url=".$chemin."pages/user/user?id=1][color=#2F4456][b]Bad[/b][/color][/url]"));
    else
      envoyer_notif($new_user,"Welcome to NoBleme!",postdata("[size=1.3][b]Welcome to NoBleme![/b][/size]\r\n\r\nNow that you have registered, why not join the community where it is most active :\r\n- Mainly on [url=".$chemin."pages/irc/index][color=#2F4456][b]the IRC server[/b][/color][/url], where we chat in real time\r\n- Sometimes on [url=".$chemin."pages/forum/index][color=#2F4456][b]the forum[/b][/color][/url], on which we share things every now and then\r\n- And everywhere that's active in the [url=".$chemin."pages/nobleme/activite][color=#2F4456][b]recent activity[/b][/color][/url], which should show you what's going on on the website\r\n\r\n\r\nEnjoy your stay on NoBleme,\r\nIf you have any questions, feel free to reply to this message.\r\n\r\nYour admin,\r\n[url=".$chemin."pages/user/user?id=1][color=#2F4456][b]Bad[/b][/color][/url]"));

    // Redirection vers la page de bienvenue
    header('Location: ./login?bienvenue');
  }
  // Si y'a une erreur, on fixe l'affichage
  else
  {
    $register_pseudo  = predata(stripslashes($register_pseudo));
    $register_email   = predata(stripslashes($register_email));
    $register_pass_1  = predata(stripslashes($register_pass_1));
    $register_pass_2  = predata(stripslashes($register_pass_2));
  }
}

// Sinon on remet tout à zéro
else
{
  $register_pseudo  = "";
  $register_pass_1  = "";
  $register_pass_2  = "";
  $register_email   = "";
  $register_erreur  = "";
  for($i=1;$i<=3;$i++)
  {
    $register_check_q1[$i]  = "";
    $register_check_q2[$i]  = "";
    $register_check_q3[$i]  = "";
    $register_check_q4[$i]  = "";
  }
}




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                   TRADUTION DU CONTENU MULTILINGUE                                                    */
/*                                                                                                                                       */
/*****************************************************************************************************************************************/

// Titre et intro
$traduction['titre']      = ($lang == 'FR') ? "Créer un compte" : "Register an account";
$traduction['soustitre']  = ($lang == 'FR') ? "Code de conduite à respecter sur NoBleme" : "Code of conduct to follow on NoBleme";

// Formulaire d'inscription
$traduction['reg_pseudo'] = ($lang == 'FR') ? "Choisissez un pseudonyme (3 à 18 caractères)" : "Choose a nickname (3 to 18 characters long)";
$traduction['reg_pass']   = ($lang == 'FR') ? "Mot de passe (6 caractères minimum)" : "Your password (at least 6 characters long)";
$traduction['reg_pass2']  = ($lang == 'FR') ? "Entrez à nouveau votre mot de passe" : "Confirm your password by typing it again";
$traduction['reg_email']  = ($lang == 'FR') ? "Adresse e-mail (utile si vous oubliez votre mot de passe)" : "E-mail address (useful if you forget your password)";
$traduction['reg_humain'] = ($lang == 'FR') ? "Prouvez que vous êtes humain en recopiant ce nombre" : "Prove you are human by copying this number";
$traduction['reg_capalt'] = ($lang == 'FR') ? "Vous devez désactiver votre bloqueur d'image pour voir ce captcha !" : "You must turn off your image blocker to see this captcha !";
$traduction['reg_creer']  = ($lang == 'FR') ? "Créer mon compte" : "Create my account";

// Questionnaire sur le code de conduite
$traduction['reg_quest1'] = ($lang == 'FR') ? "La pornographie est-elle autorisée ?" : "Is pornography allowed?";
$traduction['reg_repq11'] = ($lang == 'FR') ? "Oui" : "Yes";
$traduction['reg_repq12'] = ($lang == 'FR') ? "Non" : "No";
$traduction['reg_repq13'] = ($lang == 'FR') ? "Ça dépend des cas" : "It depends";
$traduction['reg_quest2'] = ($lang == 'FR') ? "Les images gores sont-elle tolérées ?" : "Can I post gore images?";
$traduction['reg_repq21'] = ($lang == 'FR') ? "Oui" : "Yes";
$traduction['reg_repq22'] = ($lang == 'FR') ? "Non" : "No";
$traduction['reg_repq23'] = ($lang == 'FR') ? "J'en sais rien, j'ai pas lu" : "Didn't read, don't know lol";
$traduction['reg_quest3'] = ($lang == 'FR') ? "Si je m'engueule avec quelqu'un, je fais quoi ?" : "If I'm arguing with someone, what should I do?";
$traduction['reg_repq31'] = ($lang == 'FR') ? "J'étale ça en public" : "Spread it publicly";
$traduction['reg_repq32'] = ($lang == 'FR') ? "Je résous ça en privé" : "Solve it privately";
$traduction['reg_quest4'] = ($lang == 'FR') ? "Si je suis aggressif avec les autres, qu'est-ce qui se passe ?" : "If I'm being aggressive towards others, what will happen to me?";
$traduction['reg_repq41'] = ($lang == 'FR') ? "Je me fais bannir" : "I will get banned";
$traduction['reg_repq42'] = ($lang == 'FR') ? "Rien, on est dans un pays libre !" : "Nothing, this is a free country!";

// Code de conduite
if($lang == 'FR')
  $traduction['reg_coc']  = "
<p>
  NoBleme est un site cool où les gens sont relax. Il n'y a pas de restriction d'âge, et peu de restrictions de contenu. Il y a juste un code de conduite minimaliste à respecter, afin de tous cohabiter paisiblement. Pour s'assurer que tout le monde lise le code de conduite (il est court), vous devez répondre à des questions à son sujet lors de la création de votre compte.
</p>
<p>
  <ul>
    <li>
      Vu qu'il n'y a pas de restriction d'âge, les <span class=\"gras\">images pornographiques</span> ou suggestives <span class=\"gras\">sont interdites</span>.
    </li>
    <li>
      Les <span class=\"gras\">images gores</span> ou à tendance dégueulasse sont <span class=\"gras\">également interdites</span>. NoBleme n'est pas le lieu pour ça.
    </li>
    <li>
      Tout <span class=\"gras\">contenu illégal</span> sera immédiatement <span class=\"gras\">envoyé à la police</span>. Ne jouez pas avec le feu, ce n'est pas le bon site pour en discuter.
    </li>
    <li>
      Si vous pouvez régler une situation tendue en privé plutôt qu'en public, faites l'effort, sinon vous finirez tous les deux bannis.
    </li>
    <li>
      Les trolls, provocateurs gratuits, et emmerdeurs de service pourront être bannis sans sommation s'ils abusent trop.
    </li>
    <li>
      L'écriture SMS et la grammaire sans effort sont à éviter autant que possible. Prenez le temps de bien écrire, ça sera apprécié.
    </li>
  </ul>
  <br>
  On est avant tout sur NoBleme pour passer du bon temps. Si vos actions ou votre langage empêchent d'autres personnes de passer du bon temps, c'est un peu nul, non ? Essayez de rester tolérants, ce n'est pas un grand effort, et tout le monde en bénéficie.
</p>";
else
  $traduction['reg_coc']  = "
<p>
  NoBleme is a chill community where people are relaxed. There is no restriction on age or content. However, in order to all coexist peacefully, there is a minimalistic code of conduct that everyone should respect. In order to ensure that everyone reads it (it's short), you will have to answer a few questions about it when registering your account.
</p>
<p>
  <ul>
    <li>
      Since there is no age restriction <span class=\"gras\">pornography</span> or suggestive content <span class=\"gras\">is forbidden</span>.
    </li>
    <li>
      All <span class=\"gras\">gore images</span> and other disgusting things are <span class=\"gras\">also forbidden</span>. NoBleme is not the right place for it.
    </li>
    <li>
      Obviously, <span class=\"gras\">illegal content</span> will immediately be <span class=\"gras\">sent to the police</span>. Don't play with fire, there are other websites for that.
    </li>
    <li>
      If you have to argue with someone or solve a tense situation, do it privately. If done publicly, you will end up banned.
    </li>
    <li>
      Trolls and other kinds of purposeful agitators will be banned without a warning if they do it excessively.
    </li>
  </ul>
  <br>
  We are first and foremost on NoBleme to have a good time together. If your actions or your language prevent other people from having a good time, it's a bit silly, isn't it? Try to stay respectful of others and we'll all benefit from it.
</p>";




/*****************************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                         AFFICHAGE DES DONNÉES                                                         */
/*                                                                                                                                       */
/************************************************************************************************/ include './../../inc/header.inc.php'; ?>

      <div class="texte2">

        <h1><?=$traduction['titre']?></h1>

        <h5><?=$traduction['soustitre']?></h5>

        <?=$traduction['reg_coc']?>

        <br>

        <div class="minitexte2">

        <form method="POST" id="register_formulaire" action="register#register_formulaire">
          <fieldset>

            <label for="register_pseudo" id="label_register_pseudo"><?=$traduction['reg_pseudo']?></label>
            <input id="register_pseudo" name="register_pseudo" class="indiv" type="text" value="<?=$register_pseudo?>"><br>
            <br>

            <label for="register_pass_1" id="label_register_pass_1"><?=$traduction['reg_pass']?></label>
            <input id="register_pass_1" name="register_pass_1" class="indiv" type="password" value="<?=$register_pass_1?>"><br>
            <br>

            <label for="register_pass_2" id="label_register_pass_2"><?=$traduction['reg_pass2']?></label>
            <input id="register_pass_2" name="register_pass_2" class="indiv" type="password" value="<?=$register_pass_2?>"><br>
            <br>

            <label for="register_email" id="label_register_email"><?=$traduction['reg_email']?></label>
            <input id="register_email" name="register_email" class="indiv" type="text" value="<?=$register_email?>"><br>
            <br>

            <label for="register_question_1" id="label_register_question_1"><?=$traduction['reg_quest1']?></label>
            <div class="flexcontainer">
              <div style="flex:1">
            <input id="register_question_1" name="register_question_1" value="1" type="radio"<?=$register_check_q1[1]?>>
            <label class="label-inline" for="register_question_1"><?=$traduction['reg_repq11']?></label>
              </div>
              <div style="flex:1">
            <input id="register_question_1" name="register_question_1" value="2" type="radio"<?=$register_check_q1[2]?>>
            <label class="label-inline" for="register_question_1"><?=$traduction['reg_repq12']?></label>
              </div>
              <div style="flex:3">
            <input id="register_question_1" name="register_question_1" value="3" type="radio"<?=$register_check_q1[3]?>>
            <label class="label-inline" for="register_question_1"><?=$traduction['reg_repq13']?></label><br>
              </div>
            </div>
            <br>

            <label for="register_question_2" id="label_register_question_2"><?=$traduction['reg_quest2']?></label>
            <div class="flexcontainer">
              <div style="flex:1">
            <input id="register_question_2" name="register_question_2" value="1" type="radio"<?=$register_check_q2[1]?>>
            <label class="label-inline" for="register_question_2"><?=$traduction['reg_repq21']?></label>
              </div>
              <div style="flex:1">
            <input id="register_question_2" name="register_question_2" value="2" type="radio"<?=$register_check_q2[2]?>>
            <label class="label-inline" for="register_question_2"><?=$traduction['reg_repq22']?></label>
              </div>
              <div style="flex:3">
            <input id="register_question_2" name="register_question_2" value="3" type="radio"<?=$register_check_q2[3]?>>
            <label class="label-inline" for="register_question_2"><?=$traduction['reg_repq23']?></label><br>
              </div>
            </div>
            <br>

            <label for="register_question_3" id="label_register_question_3"><?=$traduction['reg_quest3']?></label>
            <div class="flexcontainer">
              <div style="flex:2">
            <input id="register_question_3" name="register_question_3" value="1" type="radio"<?=$register_check_q3[1]?>>
            <label class="label-inline" for="register_question_2"><?=$traduction['reg_repq31']?></label>
              </div>
              <div style="flex:3">
            <input id="register_question_3" name="register_question_3" value="2" type="radio"<?=$register_check_q3[2]?>>
            <label class="label-inline" for="register_question_3"><?=$traduction['reg_repq32']?></label>
              </div>
            </div>
            <br>

            <label for="register_question_4" id="label_register_question_4"><?=$traduction['reg_quest4']?></label>
            <div class="flexcontainer">
              <div style="flex:2">
            <input id="register_question_4" name="register_question_4" value="1" type="radio"<?=$register_check_q4[1]?>>
            <label class="label-inline" for="register_question_4"><?=$traduction['reg_repq41']?></label>
              </div>
              <div style="flex:3">
            <input id="register_question_4" name="register_question_4" value="2" type="radio"<?=$register_check_q4[2]?>>
            <label class="label-inline" for="register_question_4"><?=$traduction['reg_repq42']?></label>
              </div>
            </div>
            <br>

            <label for="register_captcha" id="label_register_captcha"><?=$traduction['reg_humain']?></label>
            <div class="flexcontainer">
              <div style="flex:1">
                <img src="<?=$chemin?>inc/captcha.inc.php" alt="<?=$traduction['reg_capalt']?>">
              </div>
              <div style="flex:4">
                <input id="register_captcha" name="register_captcha" class="indiv" type="text"><br>
              </div>
            </div>

          </fieldset>
        </form>

        <?php if($register_erreur != "") { ?>
        <p>
          <span class="texte_blanc negatif spaced moinsgros gras">
            <?=$register_erreur?>
          </span>
        </p>
        <?php } ?>

        <br>

        <button onclick="creer_compte('<?=$chemin?>');" id="register_formulaire"><?=$traduction['reg_creer']?></button>

      </div>

      </div>

<?php /***********************************************************************************************************************************/
/*                                                                                                                                       */
/*                                                              FIN DU HTML                                                              */
/*                                                                                                                                       */
/***************************************************************************************************/ include './../../inc/footer.inc.php';