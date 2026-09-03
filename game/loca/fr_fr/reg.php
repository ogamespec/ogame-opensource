<?php

// Inscription, connexion, envoi d'un courriel oublié

$LOCA["fr"]["REG_MAIL_TITLE"] = "Aperçu";
$LOCA["fr"]["REG_MAIL_SEND"] = "Envoyer mot de passe";
$LOCA["fr"]["REG_MAIL_NOTE"] = "Entrez votre adresse email";
$LOCA["fr"]["REG_MAIL_EMAIL"] = "Email:";
$LOCA["fr"]["REG_MAIL_SUBMIT"] = "Envoyer les données de connexion";

$LOCA["fr"]["REG_FORGOT_TITLE"] = "Envoyer le mot de passe #1";
$LOCA["fr"]["REG_FORGOT_ERROR"] = "Cette adresse e-mail n’existe pas en tant qu’adresse permanente ou variable";
$LOCA["fr"]["REG_FORGOT_OK"] = "Votre mot de passe a été envoyé à #1.";
$LOCA["fr"]["REG_FORGOT_SUBJ"] = "Mot de passe #1";
$LOCA["fr"]["REG_FORGOT_MAIL"] = "Bonjour #1,\n\n" .
"votre mot de passe pour l'Univers #2 de #5 est:\n\n" .
"#3\n\n" .
"Vous pouvez vous connecter à #4 avec ces données de connexion.\n\n" .
"Nous n’envoyons les mots de passe qu’à l’adresse e-mail indiquée dans votre compte. Veuillez ignorer cet e-mail si vous ne l’avez pas demandé.\n\n" .
"Nous vous souhaitons beaucoup de réussite en jouant à #5!\n\n" .
"Equipe #5";

// Erreur de session au chargement de la page

$LOCA["fr"]["REG_SESSION_INVALID"] = "La session est invalide.";
$LOCA["fr"]["REG_SESSION_ERROR"] = "Une erreur est survenue";
$LOCA["fr"]["REG_SESSION_ERROR_BODY"] = "    <br /><br />
    La session est invalide.<br/><br/>Cela peut être dû à plusieurs raisons :
<br>- Vous vous êtes connecté au même compte plusieurs fois ;
<br>- Votre adresse IP a changé depuis votre dernière connexion ;
<br>- Vous accédez à Internet via AOL ou un proxy. Désactivez la vérification IP dans le menu « Paramètres » de votre compte.
    <br /><br />
";

$LOCA["fr"]["REG_NOT_ACTIVATED"] = "Votre compte de jeu n'a pas encore été activé. Allez dans <a href=index.php?page=options&session=#1>Paramètres</a>, saisissez votre adresse e-mail et recevez un lien d'activation";
$LOCA["fr"]["REG_PENDING_DELETE"] = "Votre compte a été mis en attente de suppression. Date de suppression : #1";

// Le joueur essaie d'écrire sans activation du compte
$LOCA["fr"]["REG_NOT_ACTIVATED_MESSAGE"] = "Cette fonction n'est disponible qu'après l'activation du compte.";

// page d'erreur

$LOCA["fr"]["REG_ERROR"] = "Erreur";
$LOCA["fr"]["REG_ERROR_21"] = "Vous avez essayé de vous connecter à l'univers #1 sous le pseudonyme #2.";
$LOCA["fr"]["REG_ERROR_22"] = "Ce compte n'existe pas ou vous avez saisi un mot de passe incorrect. ";
$LOCA["fr"]["REG_ERROR_23"] = "Saisissez <a href='#1'>le mot de passe correct</a> ou utilisez <a href='mail.php'>la récupération du mot de passe</a>.";
$LOCA["fr"]["REG_ERROR_24"] = "Vous pouvez également créer un <a href='new.php'>nouveau compte</a>.";
$LOCA["fr"]["REG_ERROR_31"] = "Ce compte a été bloqué jusqu'au #1, voir plus de détails <a href=../pranger.php>ici</a>.<br> Si vous avez des questions, veuillez contacter la personne qui vous a bloqué, l'<a href='#'>opérateur</a>.<br><br>ATTENTION : le statut de commandant n'est pas résilié lors du blocage, la résiliation se fait séparément !";

// new.php

$LOCA["fr"]["REG_NEW_ERROR_AGB"] = "Pour commencer à jouer, vous devez accepter les règles de base !";
$LOCA["fr"]["REG_NEW_ERROR_IP"] = "Inscription depuis une seule IP pas plus d'une fois toutes les 10 minutes !";
$LOCA["fr"]["REG_NEW_ERROR_CHARS"] = "Le nom #1 contient des caractères invalides ou trop peu/trop de caractères !";
$LOCA["fr"]["REG_NEW_ERROR_EXISTS"] = "Le nom #1 existe déjà";
$LOCA["fr"]["REG_NEW_ERROR_EMAIL"] = "L'adresse #1 est invalide !";
$LOCA["fr"]["REG_NEW_ERROR_EMAIL_EXISTS"] = "L'adresse #1 existe déjà !";
$LOCA["fr"]["REG_NEW_ERROR_MAX_PLAYERS"] = "Le nombre maximum de joueurs (#1) a été atteint !";
$LOCA["fr"]["REG_NEW_TITLE"] = "Inscription à l'univers #1 #2";
$LOCA["fr"]["REG_NEW_SUCCESS"] = "L'inscription a réussi !";
$LOCA["fr"]["REG_NEW_TEXT"] = "Félicitations, <span class='fine'>Univers #1</span> !<br /><br />Vous vous êtes inscrit avec succès auprès de #6. (<span class='fine'>#2</span>). <br />\n".
            "Vous recevrez bientôt <span class='fine'>#3</span> un e-mail avec un mot de passe et quelques liens importants.<br />\n".
            "Pour jouer, vous devez vous connecter via la <a href='#4'>page d'accueil</a>.<br />\n".
            "Dans l'image suivante, vous verrez comment faire correctement.<br /><br />\n" .
            "<center><a href='#5' style='text-decoration: underline;font-size: large;'>C'est parti !</a></center><br /><br /> \n" .
            "Bonne chance<br /> \n" .
            "Votre équipe #6</th>";
$LOCA["fr"]["REG_NEW_UNI"] = "Univers #1";
$LOCA["fr"]["REG_NEW_CHOOSE_UNI"] = "Choisir l'univers";
$LOCA["fr"]["REG_NEW_NAME"] = "Entrer le nom";
$LOCA["fr"]["REG_NEW_PASSWORD"] = "Et le mot de passe envoyé !";
$LOCA["fr"]["REG_NEW_ERROR"] = "Erreur";
$LOCA["fr"]["REG_NEW_PLAYER_INFO"] = "Informations sur le joueur";
$LOCA["fr"]["REG_NEW_PLAYER_NAME"] = "Nom en jeu";
$LOCA["fr"]["REG_NEW_PLAYER_EMAIL"] = "Email";
$LOCA["fr"]["REG_NEW_ACCEPT"] = "Je suis d'accord avec";
$LOCA["fr"]["REG_NEW_AGB"] = "les règles de base";
$LOCA["fr"]["REG_NEW_SUBMIT"] = "S'inscrire";
$LOCA["fr"]["REG_NEW_INFO"] = "Info";

$LOCA["fr"]["REG_NEW_MESSAGE_0"] = "OK";
$LOCA["fr"]["REG_NEW_MESSAGE_101"] = "Un tel nom existe déjà !";
$LOCA["fr"]["REG_NEW_MESSAGE_102"] = "Cette adresse est déjà utilisée !";
$LOCA["fr"]["REG_NEW_MESSAGE_103"] = "Le nom doit contenir entre 3 et 20 caractères !";
$LOCA["fr"]["REG_NEW_MESSAGE_104"] = "L'adresse est invalide !";
$LOCA["fr"]["REG_NEW_MESSAGE_105"] = "Le nom du joueur est correct";
$LOCA["fr"]["REG_NEW_MESSAGE_106"] = "L'adresse est correcte";
$LOCA["fr"]["REG_NEW_MESSAGE_107"] = "L'adresse est invalide !";
$LOCA["fr"]["REG_NEW_MESSAGE_108"] = "Inscription depuis une seule IP pas plus d'une fois toutes les 10 minutes !";
$LOCA["fr"]["REG_NEW_MESSAGE_109"] = "Le nombre maximum de joueurs a été atteint !";
$LOCA["fr"]["REG_NEW_MESSAGE_201"] = "Nom en jeu : <br />C'est le nom de votre personnage dans le jeu. Deux noms ne peuvent pas être identiques dans le même univers.";
$LOCA["fr"]["REG_NEW_MESSAGE_202"] = "Email : <br />Votre mot de passe sera envoyé à cette adresse. Si vous saisissez une adresse erronée ou invalide, vous ne pourrez pas jouer.";
$LOCA["fr"]["REG_NEW_MESSAGE_203"] = "";
$LOCA["fr"]["REG_NEW_MESSAGE_204"] = "Pour commencer à jouer, vous devez accepter les règles de base.";

// user.php

$LOCA["fr"]["REG_GREET_MAIL_SUBJ"] = "Bienvenue dans #1 ";
$LOCA["fr"]["REG_GREET_MAIL_BODY"] = "Bonjour #1,\n\n" .
            "Vous avez décidé de créer votre propre empire dans #2 de l'univers #7 !\n\n" .
            "Cliquez sur ce lien pour activer votre compte :\n" .
            "#3\n\n" .
            "Vos identifiants de jeu :\n" .
            "Nom du joueur : #4\n" .
            "Mot de passe : #5\n" .
            "Univers : #6\n\n\n";
$LOCA["fr"]["REG_GREET_MAIL_BOARD"] = "Si vous avez besoin d'aide ou de conseils d'autres empereurs, vous trouverez tout cela sur notre forum (#1).\n\n";
$LOCA["fr"]["REG_GREET_MAIL_TUTORIAL"] = "Vous trouverez ici (#1) toutes les informations réunies par les joueurs et les membres de l'équipe pour aider les nouveaux venus à comprendre le jeu le plus rapidement possible.\n\n";
$LOCA["fr"]["REG_GREET_MAIL_FOOTER"] = "Nous vous souhaitons du succès dans la construction de votre empire et bonne chance dans les batailles à venir !\n\nVotre équipe #1";

$LOCA["fr"]["REG_CHANGE_MAIL_SUBJ"] = "Votre adresse e-mail en jeu a été modifiée ";
$LOCA["fr"]["REG_CHANGE_MAIL_BODY"] = "Bonjour #1,\n\n" .
            "L'adresse e-mail temporaire de votre compte dans l'univers #2 a été modifiée dans les paramètres en #3.\n" .
            "Si vous ne la modifiez pas dans un délai d'une semaine, elle deviendra permanente.\n\n" .
            "Confirmez votre nouvelle adresse e-mail via le lien suivant pour continuer à jouer sans problème :\n\n" .
            "#4\n\n" .
            "Votre équipe #5";

$LOCA["fr"]["REG_GREET_MSG_SUBJ"] = "Bienvenue dans #1 !";
$LOCA["fr"]["REG_GREET_MSG_TEXT"] = "Bienvenue dans [b]#3[/b] !\n" .
        "\n" .
        "Vous devez d'abord développer les mines.\n" .
        "Vous pouvez le faire dans le menu « Bâtiments ».\n" .
        "Sélectionnez une mine de métal et appuyez sur « construire ».\n" .
        "Vous avez maintenant du temps pour vous familiariser avec le jeu.\n" .
        "Vous pouvez trouver de l'aide pour le jeu à ces liens : \n" .
        "[url=#1/]Tutoriel[/url]\n" .
        "[url=#2/]Forum[/url]\n" .
        "\n" .
        "En attendant, votre mine devrait déjà être construite.\n" .
        "Les mines ont besoin d'énergie pour fonctionner, alors construisez une centrale solaire pour l'obtenir.\n" .
        "Pour cela, retournez dans le menu « Bâtiments » et cliquez sur la centrale électrique.\n" .
        "Pour voir où vous en êtes dans votre développement, allez dans le menu « Technologie ».\n" .
        "Ainsi, votre marche victorieuse à travers l'univers a commencé... Bonne chance !\n";

// déconnexion

$LOCA["fr"]["REG_LOGOUT"] = "À bientôt ! !";

?>
