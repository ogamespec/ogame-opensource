# Install

Requirements:
- A decent web server
- PHP 7.x (8.x is also suitable in principle)
- MySQL

## PHP Setup

An important part is to properly configure PHP.INI. The following are important parameters and values that are recommended.

|PHP.INI variable|Description|
|---|---|
|short_open_tag = On|Allows PHP short PHP inclusions of the form `<?= .... ?>`. This option must be enabled because short inclusions are used everywhere in the game engine.|
|max_execution_time = 300|Script execution time. 300 seconds is enough for most tasks. 20kkk loss level battles are processed in about 10-15 seconds, depending on the server.|
|memory_limit = 1024M|Here you need to choose experimentally, depending on the battle engine being used (the PHP engine can consume more memory than the C engine). A large battle can eat up 2GB of memory (#140)|
|display_errors = On|Show script execution errors, if desired.|
|variables_order = "EGPCS"|The order of processing global variables.|

Required extensions:
- extension=php_gd2.dll
- extension=php_mbstring.dll
- extension=php_mysql.dll

## Preparing Files

In the root of your server you need to copy all the contents from the `wwwroot` folder.

The `game` and `feed` folder should be copied to the Universe instance.

## Creating a Universe subdomain

If you are deploying to the Web and don't want to have one universe at the root with the game, you can create a subdomain like `uni1.mygame.com` and put the `game` and `feed` folder there.

:warning: The `game` and `feed` folder in the root of the universe instance should be put in its entirety, i.e. just as a folder, not its contents. Game scripts rely on this.

If you just want to have one universe where the main page is, just put the `game` and `feed` folder in the root of your web server.

## Battle Engine

You need to build the battle engine executable as written in the `BattleEngine` folder and put the executable wherever you need it.

As of recently, the game also has a backup PHP battle engine. If you don't have the opportunity to use your CGI-hosted applications, you can check the "Use PHP battle engine" box during installation.

## Installing the Master Base

After opening an uninstalled game for the first time, you will be asked to initialize the master database:

![install1.png](/wiki/imgstore/install1.png)

Specify all data (MySQL database should be created separately by some other means you like, for example with Navicat MySQL).

![install2.png](/wiki/imgstore/install2.png)

## Customizing the Universe

To customize the universe, you need to follow the direct link to the setup page, for example: http://localhost/game/install.php

![install3.png](/wiki/imgstore/install3.png)

Be sure to check the box and specify the master base settings to make the universe available from the home page.

## Checking for correct installation

To check, just log into the universe under the nickname `legor` (admin). 

In the admin you can check the combat engine in the Simulator section. If everything works well, there should be an adequate combat report.

Poke different buttons in the admin; Walk around the pages; Try to build something at Legor and check the command queue.

## External links

The original game contains a number of links from the side menu to external resources. You can change them at your preference in install script:
- Forum
- Tutorial
- Rules
- About us

## Uninstall

- Delete config.php from the server root and config.php from the game folder
- Clear all databases (master database and universe database)