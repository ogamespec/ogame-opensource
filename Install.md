# Installation

Requirements:
- A decent web server
- PHP 7
- MySQL

## Preparing Files

In the root of your server you need to copy all the contents from the `wwwroot` folder.

The `game` folder should be copied to the Universe instance.

## Creating a Universe subdomain

If you are deploying to the Web and don't want to have one universe at the root with the game, you can create a subdomain like `uni1.mygame.com` and put the `game` folder there.

If you just want to have one universe where the main page is, just put the `game` folder in the root of your web server.

## Battle Engine

You need to build the battle engine executable as written in the `BattleEngine` folder and put the executable wherever you need it.

## Installing the Master Base

After opening an uninstalled game for the first time, you will be asked to initialize the master database:

![install1.png](/imgstore/install1.png)

Specify all data (MySQL database should be created separately by some other means you like, for example with Navicat MySQL).

![install2.png](/imgstore/install2.png)

## Customizing the Universe

To customize the universe, you need to follow the direct link to the setup page, for example: http://localhost/game/install.php

![install3.png](/imgstore/install3.png)

Be sure to check the box and specify the master base settings to make the universe available from the home page.

## Checking for correct installation

To check, just log into the universe under the nickname `legor` (admin). 

In the admin you can check the combat engine in the Simulator section. If everything works well, there should be an adequate combat report.

Poke different buttons in the admin; Walk around the pages; Try to build something at Legor and check the command queue.

## External links

The original game contains a number of links from the side menu to external resources. You can change them at your preference in the game/page.php file:
- Forum
- Tutorial
- Rules
- About us

## Uninstallation

- Delete config.php from the server root and config.php from the game folder
- Clear all databases (master database and universe database)
