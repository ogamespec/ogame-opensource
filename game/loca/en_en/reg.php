<?php

// Registration, login, forgot email send

$LOCA["en"]["REG_MAIL_TITLE"] = "Overview";
$LOCA["en"]["REG_MAIL_SEND"] = "Send Password";
$LOCA["en"]["REG_MAIL_NOTE"] = "Please enter your email-address";
$LOCA["en"]["REG_MAIL_EMAIL"] = "E-Mail:";
$LOCA["en"]["REG_MAIL_SUBMIT"] = "send login data";

$LOCA["en"]["REG_FORGOT_TITLE"] = "Send #1 Password";
$LOCA["en"]["REG_FORGOT_ERROR"] = "This email-address doesn't exist as a permanent or variable address";
$LOCA["en"]["REG_FORGOT_OK"] = "Your password has been sent to #1.";
$LOCA["en"]["REG_FORGOT_SUBJ"] = "#1 password";
$LOCA["en"]["REG_FORGOT_MAIL"] = "Hi #1,\n\n" .
"your password for #5 Universe #2 is:\n\n" .
"#3\n\n" .
"You may log in at #4 with this login data.\n\n" .
"We only send passwords to the E-Mail address entered in your account. Please ignore this email if you didn't request it.\n\n" .
"We wish you good success while playing #5!\n\n" .
"Your #5-Team";

// Session error on page load

$LOCA["en"]["REG_SESSION_INVALID"] = "The session is invalid.";
$LOCA["en"]["REG_SESSION_ERROR"] = "An error occurred";
$LOCA["en"]["REG_SESSION_ERROR_BODY"] = "    <br /><br />
    The session is invalid.<br/><br/>This can be due to several reasons: 
<br>- You logged into the same account several times; 
<br>- Your IP address has changed since the last time you logged in; 
<br>- You are accessing the Internet through AOL or a proxy. Turn off IP verification in the \"Settings\" menu of your account.    
    <br /><br />
";

$LOCA["en"]["REG_NOT_ACTIVATED"] = "Your game account has not been activated yet. Go to <a href=index.php?page=options&session=#1>Settings</a>, enter your e-mail address and receive an activation link to it";
$LOCA["en"]["REG_PENDING_DELETE"] = "Your account has been put up for deletion. Deletion date: #1";

// Player tries to write without account activation
$LOCA["en"]["REG_NOT_ACTIVATED_MESSAGE"] = "This feature is only available after account activation.";

// errorpage

$LOCA["en"]["REG_ERROR"] = "Error";
$LOCA["en"]["REG_ERROR_21"] = "You tried to enter universe #1 under nickname #2.";
$LOCA["en"]["REG_ERROR_22"] = "This account does not exist or you have entered your password incorrectly. ";
$LOCA["en"]["REG_ERROR_23"] = "Enter <a href='#1'>the correct password</a> or use <a href='mail.php'>password recovery</a>.";
$LOCA["en"]["REG_ERROR_24"] = "You can also create a <a href='new.php'>new account</a>.";
$LOCA["en"]["REG_ERROR_31"] = "This account has been locked to #1, see more details below <a href=../pranger.php>here</a>.<br> If you have any questions, please contact the person who blocked you <a href='#'>operator</a>.<br><br>WARNING: commander status is not terminated when blocked, termination is done separately!";

// new.php

$LOCA["en"]["REG_NEW_ERROR_AGB"] = "In order to start the game you must accept the Basic Policies!";
$LOCA["en"]["REG_NEW_ERROR_IP"] = "Registration from one IP not more than once per 10 minutes!";
$LOCA["en"]["REG_NEW_ERROR_CHARS"] = "Name #1 contains invalid characters or too few/many characters!";
$LOCA["en"]["REG_NEW_ERROR_EXISTS"] = "Name #1 already exists";
$LOCA["en"]["REG_NEW_ERROR_EMAIL"] = "Address #1 is invalid!";
$LOCA["en"]["REG_NEW_ERROR_EMAIL_EXISTS"] = "Address #1 already exists!";
$LOCA["en"]["REG_NEW_ERROR_MAX_PLAYERS"] = "The maximum number of players (#1) has been reached!";
$LOCA["en"]["REG_NEW_TITLE"] = "OGame Universe #1 Registration";
$LOCA["en"]["REG_NEW_SUCCESS"] = "Registration was a success!";
$LOCA["en"]["REG_NEW_TEXT"] = "Congratulations, <span class='fine'>#1</span>!<br /><br />You've successfully registered with OGame. (<span class='fine'>#2</span>). <br />\n".
            "You'll soon receive <span class='fine'>#3</span> an e-mail with a password and some important links.<br />\n".
            "In order to play, you must be logged in via <a href='#4'>home page</a>.<br />\n".
            "In the subsequent picture you will see how to do it correctly.<br /><br />\n" .
            "<center><a href='#5' style='text-decoration: underline;font-size: large;'>Let's go!</a></center><br /><br /> \n" .
            "Good luck<br /> \n" .
            "Your OGame team</th>";
$LOCA["en"]["REG_NEW_UNI"] = "Universe #1";
$LOCA["en"]["REG_NEW_CHOOSE_UNI"] = "Choose the universe";
$LOCA["en"]["REG_NEW_NAME"] = "Enter name";
$LOCA["en"]["REG_NEW_PASSWORD"] = "And the password sent!";
$LOCA["en"]["REG_NEW_ERROR"] = "Error";
$LOCA["en"]["REG_NEW_PLAYER_INFO"] = "Player information";
$LOCA["en"]["REG_NEW_PLAYER_NAME"] = "In-game name";
$LOCA["en"]["REG_NEW_PLAYER_EMAIL"] = "Email";
$LOCA["en"]["REG_NEW_ACCEPT"] = "I agree with";
$LOCA["en"]["REG_NEW_AGB"] = "Basic Regulations";
$LOCA["en"]["REG_NEW_SUBMIT"] = "Sign up";
$LOCA["en"]["REG_NEW_INFO"] = "Info";

$LOCA["en"]["REG_NEW_MESSAGE_0"] = "OK";
$LOCA["en"]["REG_NEW_MESSAGE_101"] = "Such a name already exists!";
$LOCA["en"]["REG_NEW_MESSAGE_102"] = "This address is already in use!";
$LOCA["en"]["REG_NEW_MESSAGE_103"] = "Name must be between 3 and 20 characters long!";
$LOCA["en"]["REG_NEW_MESSAGE_104"] = "The address is invalid!";
$LOCA["en"]["REG_NEW_MESSAGE_105"] = "Player's name is fine";
$LOCA["en"]["REG_NEW_MESSAGE_106"] = "The address is fine";
$LOCA["en"]["REG_NEW_MESSAGE_107"] = "The address is invalid!";
$LOCA["en"]["REG_NEW_MESSAGE_108"] = "Registration from one IP not more than once per 10 minutes!";
$LOCA["en"]["REG_NEW_MESSAGE_109"] = "The maximum number of players has been reached!";
$LOCA["en"]["REG_NEW_MESSAGE_201"] = "Name in Game: <br />This is the name of your character in the game. No two names can be the same in the same universe.";
$LOCA["en"]["REG_NEW_MESSAGE_202"] = "Email: <br />Your password will be sent to this address. If you enter a wrong or invalid address, you will not be able to play.";
$LOCA["en"]["REG_NEW_MESSAGE_203"] = "";
$LOCA["en"]["REG_NEW_MESSAGE_204"] = "In order to start the game you must agree to the Basic Regulations.";

// user.php

$LOCA["en"]["REG_GREET_MAIL_SUBJ"] = "Welcome to OGame ";
$LOCA["en"]["REG_GREET_MAIL_BODY"] = "Greetings #1,\n\n" .
            "You've decided to create your own empire in #2 of the OGame universe!\n\n" .
            "Click on this link to activate your account:\n" .
            "#3\n\n" .
            "Your gaming credentials:\n" .
            "Player name: #4\n" .
            "Password: #5\n" .
            "Universe: #6\n\n\n";
$LOCA["en"]["REG_GREET_MAIL_BOARD"] = "If you need help or advice from other emperors, you can find it all in our forum (#1).\n\n";
$LOCA["en"]["REG_GREET_MAIL_TUTORIAL"] = "Here (#1) is all the information gathered by players and team members to help newcomers understand the game as quickly as possible.\n\n";
$LOCA["en"]["REG_GREET_MAIL_FOOTER"] = "We wish you success in building your empire and good luck in the upcoming battles!\n\nYour OGame team";

$LOCA["en"]["REG_CHANGE_MAIL_SUBJ"] = "Your in-game e-mail address has been changed ";
$LOCA["en"]["REG_CHANGE_MAIL_BODY"] = "Greetings #1,\n\n" .
            "The temporary e-mail address of your account in the #2 universe has been changed in the settings to #3.\n" .
            "If you don't change it within a week, it will become permanent.\n\n" .
            "Confirm your new e-mail address using the following link to continue playing without any problems:\n\n" .
            "#4\n\n" .
            "Your OGame team";

$LOCA["en"]["REG_GREET_MSG_SUBJ"] = "Welcome to OGame!";
$LOCA["en"]["REG_GREET_MSG_TEXT"] = "Welcome to [b]OGame[/b] !\n" .
        "\n" .
        "First you need to develop the mines.\n" .
        "You can do this in the \"Buildings\" menu.\n" .
        "Select a metal mine and press \"build\".\n" .
        "Now you have some time to familiarize yourself with the game.\n" .
        "You can find help for the game at these links: \n" .
        "[url=#1/]Tutorial[/url]\n" .
        "[url=#2/]Forum[/url]\n" .
        "\n" .
        "In the meantime, your mine should be built by now.\n" .
        "The mines need energy to operate, so build a solar power plant to get it.\n" .
        "To do this, go back to the \"Buildings\" menu and click on the power plant.\n" .
        "To see how far you've come in your development, go to the \"Technology\" menu.\n" .
        "So, your victorious march through the universe has begun... Good luck!\n";

// logout

$LOCA["en"]["REG_LOGOUT"] = "See you soon!!";

?>