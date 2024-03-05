<?php

// Registrazione, login, invio dell'e-mail dimenticata

// The translation was adapted by Quaua, but SpaceWars replaced by OGame.

$LOCA["it"]["REG_MAIL_TITLE"] = "Riepilogo";
$LOCA["it"]["REG_MAIL_SEND"] = "Richiedi Password";
$LOCA["it"]["REG_MAIL_NOTE"] = "Inserisci il tuo indirizzo email";
$LOCA["it"]["REG_MAIL_EMAIL"] = "E-Mail:";
$LOCA["it"]["REG_MAIL_SUBMIT"] = "Richiedi Password";

$LOCA["it"]["REG_FORGOT_TITLE"] = "Invio Password";
$LOCA["it"]["REG_FORGOT_ERROR"] = "L'email inserita non esiste";
$LOCA["it"]["REG_FORGOT_OK"] = "La password &egrave; stata inviata per il giocatore #1.<br><br>Controlla anche la cartella di posta indesiderata se non ricevi l\'email contenente la tua password nella casella di posta principale.";
$LOCA["it"]["REG_FORGOT_SUBJ"] = "Password OGame";
$LOCA["it"]["REG_FORGOT_MAIL"] = "Ciao #1,\n\n" .
"La tua password per accedere a Universo #2 di OGame è:\n\n" .
"#3\n\n" .
"Da adesso puoi fare login all'indirizzo #4 con Username che hai scelto e la password che ti è stata appena inviata.\n\n" .
"Se non hai richiesto tu questa email ignora semplicemente il messaggio.\n\n" .
"\n\n" .
"Il team di OGame";

// Errore di sessione durante il caricamento della pagina

$LOCA["it"]["REG_SESSION_INVALID"] = "Sessione non valida.";
$LOCA["it"]["REG_SESSION_ERROR"] = "Errore";
$LOCA["it"]["REG_SESSION_ERROR_BODY"] = "    <br /><br />
    La tua sessione non &egrave; valida.<br/><br/>Pu&ograve; essere causato dai seguenti motivi: 
<br>- Hai effettuato pi&ugrave; volte l\'accesso allo stesso account 
<br>- Il tuo indirizzo IP &egrave; cambiato dal tuo ultimo accesso 
<br>- Utilizzi dei proxy. Esegui nuovamente l\'accesso, se il problema persiste contatta un operatore di gioco.    
    <br /><br />
";

$LOCA["it"]["REG_NOT_ACTIVATED"] = "Il tuo account non &egrave; stato ancora convalidato. Vai nelle <a href=index.php?page=options&session=#1>opzioni</a> per richiedere una nuova email di convalida.";
$LOCA["it"]["REG_PENDING_DELETE"] = "Il tuo account verr&agrave; cancellato il: #1";

// Il giocatore tenta di scrivere senza attivare l'account
$LOCA["it"]["REG_NOT_ACTIVATED_MESSAGE"] = "Funzione disponibile solo dopo aver attivato l\'account";

// errorpage

$LOCA["it"]["REG_ERROR"] = "Errore";
$LOCA["it"]["REG_ERROR_21"] = "Hai tentato di accedere nell'universo #1 con il nome utente #2.";
$LOCA["it"]["REG_ERROR_22"] = "Questo account non esiste oppure hai inserito la password in modo errato. ";
$LOCA["it"]["REG_ERROR_23"] = "Premi qui per ritornare alla homepage: <a href='#1'>Login</a> oppure premi qui per il recupero password: <a href='mail.php'>Recupera</a>.";
$LOCA["it"]["REG_ERROR_24"] = "In alternativa puoi creare un nuovo account: <a href='new.php'>Crea</a>.";
$LOCA["it"]["REG_ERROR_31"] = "Questo account &egrave; bannato fino al #1<br><br> Per ulteriori informazioni puoi visualizzare la lista bannati <a href=../pranger.php>QUI</a>.";

// user.php

$LOCA["it"]["REG_GREET_MAIL_SUBJ"] = "Registrazione a OGame ";
$LOCA["it"]["REG_GREET_MAIL_BODY"] = "Ciao #1,\n\n" .
                "Grazie per esserti iscritto a OGame  #2!\n\n" .
                "Per convalidare il tuo account clicca sul seguente link:\n" .
                "#3\n\n" .
                "Di seguito i tuoi dati di accesso:\n" .
                "Username: #4\n" .
                "Password: #5\n" .
                "Universo: #6\n\n\n";
$LOCA["it"]["REG_GREET_MAIL_BOARD"] = "Se hai bisogno di aiuto o di consigli da parte di altri imperatori, puoi trovare tutto nel nostro forum (#1).\n\n";
$LOCA["it"]["REG_GREET_MAIL_TUTORIAL"] = "Qui (#1) ci sono tutte le informazioni raccolte dai giocatori e dai membri della squadra per aiutare i nuovi arrivati a capire il gioco il più rapidamente possibile.\n\n";
$LOCA["it"]["REG_GREET_MAIL_FOOTER"] = "Vi auguriamo successo nella costruzione del vostro impero e buona fortuna nelle prossime battaglie! Lo staff di OGame.\n\n\n\n\n\n";

$LOCA["it"]["REG_CHANGE_MAIL_SUBJ"] = "Attivazione account OGame ";
$LOCA["it"]["REG_CHANGE_MAIL_BODY"] = "Ciao #1,\n\n" .
               "Hai richiesto la convalida dell'account di OGame Universo #2 inserendo l'indirizzo email #3.\n" .
               "\n\n" .
               "Per convalidare il tuo account clicca sul seguente link:\n\n" .
               "#4\n\n" .
               "Il team di OGame";

$LOCA["it"]["REG_GREET_MSG_SUBJ"] = "Benvenuto su OGame!";
$LOCA["it"]["REG_GREET_MSG_TEXT"] = "Ciao, benvenuto su [b]OGame[/b] !\n" .
        "\n" .
        "Per farlo clicca su \"Costruzioni\" nel menù a sinistra, seleziona la Miniera di metallo e clicca su Costruisci.\n" .
        "Ora hai un pò di tempo per conoscere il gioco.\n" .
        "\n" .
        "\n" .
        "Puoi trovare aiuto: Nel [url=#1/]Tutorial[/url] \n" .
        "Nel [url=#2/]Forum[/url]\n" .
        "\n" .
        "\n" .
        "Ormai la tua miniera dovrebbe essere terminata.\n" .
        "La miniera non produce niente se non ha energia, devi quindi costruire una Centrale Solare. Torna quindi su \"Costruzioni\", seleziona la Centrale Solare e costruiscila.\n" .
        "Per avere una panoramica delle navi, delle strutture di difesa, delle costruzioni e dei centri di ricerca che puoi costruire, clicca su Albero Tecnologico nel men&ugrave; a sinistra.\n" .
        "\n" .
        "Ora sei pronto per partire alla conquista della galassia...Buon inizio!\n";

// logout

$LOCA["it"]["REG_LOGOUT"] = "Alla prossima!!";

?>