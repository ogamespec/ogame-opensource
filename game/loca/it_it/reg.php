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

?>