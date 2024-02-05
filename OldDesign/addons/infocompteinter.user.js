// ==UserScript==
// @name           InfoCompteinter
// @namespace      vulca
// @description    InfoCompte Inter
// @include        http://*.ogame.*game/index.php?page=b_building*
// @include        http://*.ogame.*game/index.php?page=buildings*
// @include        http://*.ogame.*game/index.php?page=overview*
// @include        http://*.ogame.*game/index.php?page=options*
// @exclude		http://*.ogame.fr/game*
// ==/UserScript==




/*===============================================================================================
 DERNIERE MISE A JOUR : 07/05/2009
 TOPIC DU FORUM OFFICIEL : http://board.ogame.fr/thread.php?postid=8634035#post8634035
 SCRIPT POUR OGAME.FR v 0.83

Адаптация для русской Огамы Andorianin [СПЕЦНАЗ], 2009.
 

 ===============================================================================================*/



/* ******************************recuperation des caractere speciaux********************************/

var url=location.href;
var opt = String.fromCharCode(246);
var eaigu = String.fromCharCode(233);
var egrave = String.fromCharCode(232);
var agrave = String.fromCharCode(224);
var aaccent = String.fromCharCode(226);
var aaigu = String.fromCharCode(225);
var ptvirg = String.fromCharCode(59);
var apos = String.fromCharCode(39);
var emaj = String.fromCharCode(201);
var echap = String.fromCharCode(234);
var oaigu = String.fromCharCode(243);
var nacc = String.fromCharCode(241);
var Langue='';


/* ******************************Recherche la langue du serveur********************************/
const langue = new Array('fr','de','org','us','se','com.es','com.pt','nl','ba','bg','com.br','gr','onet.pl', 'no', 'fi', 'dk','ru');
var Langue2 ='';

for(var i=0 ; i<langue.length ; i++)
{
	if ((url.indexOf('ogame.'+langue[i],0))>=0)
	{
		Langue = langue[i];
		if (langue[i]=='org' && (url.indexOf('ba.ogame.',0))>=0) { Langue = 'ba'; }
		else if (langue[i]=='org' && (url.indexOf('bg.ogame.',0))>=0) { Langue = 'bg';  }
		else if (langue[i]=='org' && (url.indexOf('fi.ogame.',0))>=0) { Langue = 'fi'; Langue2 ='fi.';}
		else if (langue[i]=='org' && (url.indexOf('mx.ogame.',0))>=0) { Langue = 'mx';}
		break;
	}
}

/* ******************************Anglais ********************************/

var Pointdetails = 'Pointdetails:';

if (Langue=='org' || Langue=='us')
{
	var Mines='Mines';
	var Other_structure='Other structures';
	var Structure = 'Structure';
	var Technology='Technology';
	var Fleet ='Fleet';
	var Defense = 'Defense';
	var Progression = 'Progress' ;
	var Moyenne = 'Average';
	var Production = 'Production';
	var Indestructible = 'Indestructible';
	
	var Depuis = 'since';
	var Points = 'Points';
	
	var nom_def = new Array('Rocket Launcher</a>',"Light Laser</a>","Heavy Laser</a>","Gauss Cannon</a>","Ion Cannon</a>","Plasma Turret</a>","Anti-Ballistic Missiles</a>","Interplanetary Missiles</a>","Small Shield Dome</a>","Large Shield Dome</a>");
	var nom_tech = new Array('Espionage Technology',"Computer Technology","Weapons Technology","Shielding Technology","Armour Technology","Energy Technology","Hyperspace Technology","Combustion Drive","Impulse Drive","Hyperspace Drive","Laser Technology","Ion Technology","Plasma Technology","Intergalactic Research","Expedition Technology");
	var nom_bat = new Array('Metal Mine',"Crystal Mine","Deuterium Synthesizer","Solar Plant","Fusion Reactor","Robotics Factory","Nanite Factory","Shipyard","Metal Storage","Crystal Storage","Deuterium Tank","Research Lab","Terraformer","Missile Silo","Alliance Depot","Lunar Base","Sensor Phalanx","Jump Gate");
	var nom_flotte = new Array("Small Cargo","Large Cargo","Light Fighter","Heavy Fighter",'Cruiser',"Battleship","Colony Ship","Recycler","Espionage Probe","Bomber","Destroyer","Deathstar","Battlecruiser");
	var construction_time = 'Construction Time:';
	var level = 'level ';
	var dispo = 'available';
	var rank= 'Rank ';	
	var Planet= 'Planet';
	
	var soit = 'representing';
	
	var BBcode_debut="[quote][center][size=22][b]Pointdetails:[/b][/size]\n\n[size=16]Total points :";
	var BBcode_mine="Points in mines: ";
	var BBcode_bat="Points in other structures: ";
	var BBcode_batT="Point in total structure: ";
	var BBcode_fin1 = "Points in technology : ";
	var	Bcode_fin2 = "Points in fleet : ";
	var	BBcode_fin3 = "Points in defense : ";
	var	BBcode_fin4 = "Your account has ";
	var	BBcode_fin5 = "indestructible points\n";	
	var	BBcode_fin6 = "Average progress : ";
	var Point_day = "Points per day";
	
	var sur_lune='on moon';
	var en_vol='flying';
	var Avertissement ='Are you sure you want to restart your progresscount?';
	var restart = 'Click to restart your progresscount';
	var AffBBcode = 'click to have the BBcode';
	
	var done ='Done! Renew the page!';
	var ICoption = 'InfoCompte\'s options';
	
	var option1 ='<tr><th>Graphiccolours (fill in the number of cases you want)';
	var option2 ='<tr><th >Show total structure points';
	var option3 ='<tr><th>Show indestructible points';
	var option4 ='<tr><th>Show technology points';
	var option5 ='<tr><th>Show fleet points';
	var option6 ='<tr><th>Show defense points';
	var option7 ='<tr><th>Show percentage of fleet in movement';
	var option8 ='<tr><th>Show moonpoints';
	var option9 ='<tr><th>Show all in the same line (for fleet movement and moonpoints)';
	var option10 ='<tr><th>Show progress';
	var option11 ='<tr><th>Fill in if there is more than one player on the same computer and universe';
	var option12 ='<tr><th>Show in coulours in fonction of the progress';
	var option13 ='<tr><th>Show progress per day';
	var option14 ='<tr><th>Show points earned from mine';
	var option15 ='<tr><td class="c" colspan="2">Cancel / Save the made modifications  :';
	
	var Save='Save the made modifications';
	var valeurdefaut = 'defaultvalues';
	var speeduniX2 = new Array(40,30);
	var speeduniX5= new Array(35);
	
	var creeSign='create a signature with InfoCompte';
}


if (Langue=='fr' )
{
	var Mines='Mines';
	var Other_structure='Other structures';
	var Structure = 'Structure';
	var Technology='Technology';
	var Fleet ='Fleet';
	var Defense = 'Defense';
	var Progression = 'Progress' ;
	var Moyenne = 'Average';
	var Production = 'Production';
	var Indestructible = 'Indestructible';
	
	var Depuis = 'since';
	var Points = 'Points';
	
	var nom_def = new Array('Lanceur de missiles</a>',"Light Laser</a>","Heavy Laser</a>","Gauss Cannon</a>","Ion Cannon</a>","Plasma Turret</a>","Anti-Ballistic Missiles</a>","Interplanetary Missiles</a>","Small Shield Dome</a>","Large Shield Dome</a>");
	var nom_tech = new Array('Technologie Espionnage',"Computer Technology","Weapons Technology","Shielding Technology","Armour Technology","Energy Technology","Hyperspace Technology","Combustion Drive","Impulse Drive","Hyperspace Drive","Laser Technology","Ion Technology","Plasma Technology","Intergalactic Research","Expedition Technology");
	var nom_bat = new Array('Mine de métal',"Crystal Mine","Deuterium Synthesizer","Solar Plant","Fusion Reactor","Robotics Factory","Nanite Factory","Shipyard","Metal Storage","Crystal Storage","Deuterium Tank","Research Lab","Terraformer","Missile Silo","Alliance Depot","Lunar Base","Sensor Phalanx","Jump Gate");
	var nom_flotte = new Array("Small Cargo","Large Cargo","Light Fighter","Heavy Fighter",'Cruiser',"Battleship","Colony Ship","Recycler","Espionage Probe","Bomber","Destroyer","Deathstar","Battlecruiser");
	var construction_time = 'Durée de construction :';
	var level = 'Niveau';
	var dispo = 'disponible';
	var rank= 'Place ';	
	var Planet= 'Planète';
	
	
	var soit = 'representing';
	
	var BBcode_debut="[quote][center][size=22][b]Pointdetails:[/b][/size]\n\n[size=16]Total points :";
	var BBcode_mine="Points in mines: ";
	var BBcode_bat="Points in other structures: ";
	var BBcode_batT="Point in total structure: ";
	var BBcode_fin1 = "Points in technology : ";
	var	Bcode_fin2 = "Points in fleet : ";
	var	BBcode_fin3 = "Points in defense : ";
	var	BBcode_fin4 = "Your account has ";
	var	BBcode_fin5 = "indestructible points\n";	
	var	BBcode_fin6 = "Average progress : ";
	var Point_day = "Points per day";
	
	var sur_lune='on moon';
	var en_vol='flying';
	var Avertissement ='Are you sure you want to restart your progresscount?';
	var restart = 'Click to restart your progresscount';
	var AffBBcode = 'click to have the BBcode';
	
	var done ='Done! Renew the page!';
	var ICoption = 'InfoCompte\'s options';
	
	var option1 ='<tr><th>Graphiccolours (fill in the number of cases you want)';
	var option2 ='<tr><th >Show total structure points';
	var option3 ='<tr><th>Show indestructible points';
	var option4 ='<tr><th>Show technology points';
	var option5 ='<tr><th>Show fleet points';
	var option6 ='<tr><th>Show defense points';
	var option7 ='<tr><th>Show percentage of fleet in movement';
	var option8 ='<tr><th>Show moonpoints';
	var option9 ='<tr><th>Show all in the same line (for fleet movement and moonpoints)';
	var option10 ='<tr><th>Show progress';
	var option11 ='<tr><th>Fill in if there is more than one player on the same computer and universe';
	var option12 ='<tr><th>Show in coulours in fonction of the progress';
	var option13 ='<tr><th>Show progress per day';
	var option14 ='<tr><th>Show points earned from mine';
	var option15 ='<tr><td class="c" colspan="2">Cancel / Save the made modifications  :';
	
	var Save='Save the made modifications';
	var valeurdefaut = 'defaultvalues';
	var speeduniX2 = new Array(40,30);
	var speeduniX5= new Array(35);
	
	var creeSign='create a signature with InfoCompte';
}



if (Langue=='bg')
{
	var Mines='Мини';
	var Other_structure='Други структури';
	var Structure = 'Структури';
	var Technology='Технологии';
	var Fleet ='Флоти';
	var Defense = 'Защити';
	var Progression = 'Прогрес' ;
	var Moyenne = 'Средно';
	var Production = 'Продукция';
	var Indestructible = 'Неразрушими';
	
	var Depuis = 'от';
	var Points = 'Точки';
	
	var nom_def = new Array('Ракетна установка',"Лек лазер","Тежък лазер","Гаус оръдие","Йонно оръдие","Плазмено оръдие","Анти-балистични ракети","Междупланетарни ракети","Малък щит","Голям щит");
	var nom_tech = new Array('Технология за Шпионаж',"Компютърна технология","Оръжейна технология","Технология за щитовете","Технология за Броня","Енергийна технология","Хипер Технология","Реактивен Двигател","Импулсен Двигател","Хипер Двигател","Лазерни технологии","Йонова Технология","Плазмена Технология","Интергалактическа Проучвателна Мрежа","Технология за експедиции");
	var nom_bat = new Array('Мина за метал',"Мина за Кристал","Синтезатор за Деутериум","Соларен панел","Ядрен Реактор","Фабрика за роботи","Фабрика за наноботи","Докове","Склад за метал","Склад за кристали","Резервоар за деутериум","Изследователска лаборатория","Тераформер","Ракетен силоз","Склад на съюза","Лунна база","Фронтален сензор","Портал");
	var nom_flotte = new Array("Малък Транспортьор","Голям Транспортьор","Лек Изтребител","Тежък Изтребител","Кръстосвач","Боен Кораб","Колонизатор","Рециклатор","Шпионска сонда","Бомбардировач","Унищожител","Звезда на смъртта","Боен Кръстосвач");
	var construction_time = 'Време за изграждане:';
	var level = 'ниво ';
	var dispo = 'в наличност';
	var rank= 'Място ';
	var Planet= 'Планета';
	
	var soit = 'или';
	this_Planet='Тази планета';
	
	var BBcode_debut="[quote][center][size=22][b]Pазбивка:[/b][/size]\n\n[size=16]Общо точки:";
	var BBcode_mine="Точки от мини: ";
	var BBcode_bat="Точки от други структури: ";
	var BBcode_batT="Общо точки от структури: ";
	var BBcode_fin1 = "Теочки от технологии: ";
	var	Bcode_fin2 = "Точки от флот: ";
	var	BBcode_fin3 = "Точки от защити: ";
	var	BBcode_fin4 = "Акаунтът има ";
	var	BBcode_fin5 = "неунищожими точки\n";	
	var	BBcode_fin6 = "Средно развитие: ";
	var Point_day = "Точки на ден";
	
	var sur_lune='на луна';
	var en_vol='в полет';
	var Avertissement ='Сигурен ли сте, че искате да рестартирате брояча на развитието?';
	var restart = 'Рестартиране на брояча на развитието';
	var AffBBcode = 'BBcode';
	
	var done ='Готово! Обновете страницата!';
	var ICoption = 'Опции на InfoCompte';
	
	var option1 ='<tr><th>Цветове (Попълнете колко случая желаете)';
	var option2 ='<tr><th>Показване на точките от структури';
	var option3 ='<tr><th>Показване на неунищожимите точки';
	var option4 ='<tr><th>Показване на точките от технологии';
	var option5 ='<tr><th>Показване на точките от флот';
	var option6 ='<tr><th>Показване на точките от защита';
	var option7 ='<tr><th>Показване на процента флот в движение';
	var option8 ='<tr><th>Показване на точките от луната';
	var option9 ='<tr><th>Показване на всичко на един ред (за движението на флота и точките от луната)';
	var option10 ='<tr><th>Показване на развитието';
	var option11 ='<tr><th>Попълнете, ако има повече от един играч за един и същи компютър и вселена';
	var option12 ='<tr><th>Показване на цветове, в зависимост от развитието';
	var option13 ='<tr><th>Показване на дневното развитие';
	var option14 ='<tr><th>Показване на точките, печелени от мините';
	var option15 ='<tr><td class="c" colspan="2">Отказ / Запазване на промените:';
	
	var Save='Запазване на направените промени';
	var valeurdefaut = 'стойности по подразбиране';
	bunttext = 'цветно';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
	
	Langue2= Langue+'.';
	Langue = 'org';
}

if (Langue=='com.br')
{
	var Mines='Minas';
	var Other_structure='Outras estruturas';
	var Structure = 'Estruturas';
	var Technology='Tecnologia';
	var Fleet ='Frota';
	var Defense = 'Defesa';
	var Progression = 'Progresso' ;
	var Moyenne = 'Média';
	var Production = 'Produção';
	var Indestructible = 'Indestrutivel';

	var Depuis = 'Desde';
	var Points = 'Pontos';

	var nom_def = new Array('Lançador de Mísseis',"Laser Ligeiro","Laser Pesado","Canhão de Gauss ","Canhão de Íons","Canhão de Plasma","Mísseis de Intercepção","Mísseis Interplanetários","Pequeno Escudo Planetário","Grande Escudo Planetário");
	var nom_tech = new Array('Tecnologia de Espionagem',"Tecnologia de Computadores","Tecnologia de Armas","Tecnologia de Escudo","Tecnologia de Blindagem","Tecnologia de Energia","Tecnologia de hiperespaço","Motor de Combustão","Motor de Impulsão","Motor Propulsor de Hiperespaço","Tecnologia Laser","Tecnologia de Íons","Tecnologia de Plasma","Rede Intergaláctica de Pesquisas","Tecnologia de Expedição");
	var nom_bat = new Array('Mina de Metal',"Mina de Cristal","Sintetizador de Deutério","Planta de Energia Solar","Planta de Fusão","Fábrica de Robôs","Fábrica der Nanites","Hangar","Armazém de Metal","Armazém de Cristal","Armazém de Deutério","Laboratório de Pesquisas","Terra-formador","Silo de Mísseis","Depósito da Aliança","Base Lunar","Sensor Phalanx","Portal de Salto Quântico");
	var nom_flotte = new Array("Cargueiro Pequeno","Cargueiro Grande","Caça Ligeiro","Caça Pesado",'Cruzador',"Nave de Batalha","Nave de Colonizaçõa","Reciclador","Sonda de Espionagem","Bombardeiro","Destruidor","Estrela da Morte","Interceptador");
	var construction_time = 'Tempo de construção:';
	var level = 'Nível';
	var dispo = 'Disponivel';
	var rank= 'Classificação'; if (Langue=='com.br') { rank= 'classificação'; }
	var Planet= 'Planeta';

	var soit = 'representa';
	var Pointdetails="Detalhes de Pontuação";

	var BBcode_debut="[quote][center][size=22][b]"+Pointdetails+":[/b][/size]nn[size=16]Pontuação Total :";
	var BBcode_mine="Pontos em Minas: ";
	var BBcode_bat="Pontos em outras estruturas: ";
	var BBcode_batT="Pontuação Total em Estruturas: ";
	var BBcode_fin1 = "Pontuação em Tecnologia : ";
	var Bcode_fin2 = "Pontuaçao em Frota : ";
	var BBcode_fin3 = "Pontuação em Defesa : ";
	var BBcode_fin4 = "A tua conta tem";
	var BBcode_fin5 = "Pontuação Indestrutivel";
	var BBcode_fin6 = "Média de progresso : ";
	var Point_day = "Pontos por dia";

	var sur_lune='Na Lua';
	var en_vol='Em movimento';
	var Avertissement ='Tens a certeza que queres reiniciar a tua contagem de Progresso?';
	var restart = 'Carrega para reiniciar a contagem do teu progresso';
	var AffBBcode = 'Carrega para obteres os BBcode';

	var done ='Concluido! Actualiza a página!';
	var ICoption = "Opções do InfoComplete's";

	var option1 ='<tr><th>Graficos (Preenche o numero de casas que quiseres)';
	var option2 ='<tr><th >Mostrar pontuação total das estruturas';
	var option3 ='<tr><th>Mostrar pontuação indestrutivel';
	var option4 ='<tr><th>Mostrar pontuação em tecnologia';
	var option5 ='<tr><th>Mostrar pontuação em frota';
	var option6 ='<tr><th>Mostrar pontuação em defesa';
	var option7 ='<tr><th>Mostrar percentagem de frota em movimento';
	var option8 ='<tr><th>Mostrar pontuação da lua';
	var option9 ='<tr><th>Mostrar tudo na mesma linha (Para a frota em movimento e para a pontuação da lua)';
	var option10 ='<tr><th>Mostrar Progresso';
	var option11 ='<tr><th>Preenche aqui se existirem mais que 1 jogador no mesmo computador e universo';
	var option12 ='<tr><th>Mostrar em cores em função do teu progresso';
	var option13 ='<tr><th>Mostrar Progresso Diário';
	var option14 ='<tr><th>Mostrar Pontuação ganho em Minas';
	var option15 ='<tr><td class="c" colspan="2">Cancelar / Gravar configuração:';

	var Save='Gravar Configuração'; 
	var valeurdefaut = 'valores padrão';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
}





if (Langue=='de')
{
	var Mines='Minen';
	var Other_structure='Andere Gebäude';
	var Structure = 'Gebäude';
	var Technology='Forschung';
	var Fleet ='Flotte';
	var Defense = 'Verteidigung';
	var Progression = 'Steigerung' ;
	var Moyenne = 'Durchschnitt';
	var Production = 'Produktion';
	var Indestructible = 'unzerstörbar';
	
	var Depuis = 'seit';
	var Points = 'Punkte';
	
	var nom_def = new Array('Raketenwerfer',"Leichtes Lasergeschütz","Schweres Lasergeschütz","Gaußkanone","Ionengeschütz","Plasmawerfer","Abfangrakete","Interplanetarrakete","Kleine Schildkuppel","Große Schildkuppel");
	var nom_tech = new Array('Spionagetechnik',"Computertechnik","Waffentechnik","Schildtechnik","Raumschiffpanzerung","Energietechnik","Hyperraumtechnik","Verbrennungstriebwerk","Impulstriebwerk","Hyperraumantrieb","Lasertechnik","Ionentechnik","Plasmatechnik","Intergalaktisches Forschungsnetzwerk","Expeditionstechnik");
	var nom_bat = new Array('Metallmine',"Kristallmine","Deuteriumsynthetisierer","Solarkraftwerk","Fusionskraftwerk","Roboterfabrik","Nanitenfabrik","Raumschiffwerft","Metallspeicher","Kristallspeicher","Deuteriumtank","Forschungslabor","Terraformer","Raketensilo","Allianzdepot","Mondbasis","Sensorphalanx","Sprungtor");
	var nom_flotte = new Array("Kleiner Transporter","Großer Transporter","Leichter Jäger","Schwerer Jäger",'Kreuzer',"Schlachtschiff","Kolonieschiff","Recycler","Spionagesonde","Bomber","Zerstörer","Todesstern","Schlachtkreuzer");
	var construction_time = 'Produktionsdauer:';
	var level = 'Stufe ';
	var dispo = 'vorhanden';
	var rank= 'Platz ';	
	var Planet= 'Planet';
	
	var soit = 'das sind';
	
	var BBcode_debut="[quote][center][size=22][b]Punkteverteilung:[/b][/size]\n\n[size=16]Gesamtpunkte: ";
	var BBcode_mine="Punkte in Minen: ";
	var BBcode_bat="Punkte in anderen Gebäuden: ";
	var BBcode_batT="Punkte in allen Gebäuden: ";
	var BBcode_fin1 = "Punkte in Forschung: ";
	var	Bcode_fin2 = "Punkte in der Flotte: ";
	var	BBcode_fin3 = "Punkte in Verteidigung: ";
	var	BBcode_fin4 = "Dein Account hat ";
	var	BBcode_fin5 = "unzerstörbare Punkte\n";	
	var	BBcode_fin6 = "Durschnittliche Steigerung: ";
	var Point_day = "Punkte pro Tag";
	
	var sur_lune='auf Mond';
	var en_vol='unterwegs';
	var Avertissement ='Bist du sicher, dass du deinen Steigerungszähler zurücksetzen willst?';
	var restart = 'Alte Daten löschen';
	var AffBBcode = 'klick hier für BBcode';
	
	var done ='Fertig! Seite neu laden';
	var ICoption = 'InfoCompte Einstellungen';
	
	var option1 ='<tr><th>Farben (füll aus soviele du willst)';
	var option2 ='<tr><th>Zeige Gesamtgebäudepunkte';
	var option3 ='<tr><th>Zeige unzerstörbare Punkte';
	var option4 ='<tr><th>Zeige Forschungspunkte';
	var option5 ='<tr><th>Zeige Flottenpunkte';
	var option6 ='<tr><th>Zeige Verteidigungspunkte';
	var option7 ='<tr><th>Zeige Anteil an fliegenden Schiffen';
	var option8 ='<tr><th>Zeige Mondpunkte';
	var option9 ='<tr><th>Zeige alles in einer Zeile (für Flottenbewegung und Mondpunkte)';
	var option10 ='<tr><th>Zeige Steigerungung';
	var option11 ='<tr><th>Es gibt auf diesem Computer mehr als einen Account pro uni';
	var option12 ='<tr><th>Zeige Farben in der Steigerungsfunktion';
	var option13 ='<tr><th>Zeige Steigerung pro Tag';
	var option14 ='<tr><th>Zeige von Minen produzierte Punkte';
	var option15 ='<tr><td class="c" colspan="2">Änderungen speichern / verwerfen  :';
	option16='<tr><th>Zeige Detailinformationen auf jeder Seite';
	
	var Save='Änderungen speichern';
	var valeurdefaut = 'Standardwerte';
	var speeduniX2 = new Array(50,60,70);
	var speeduniX5= new Array();
	
	var creeSign = 'Erstellen einer Signatur mit InfoCompte';
}


/* ****************************** Portugais ********************************/
if (Langue=='com.pt')
{
	var Mines='Minas';
	var Other_structure='Outras estruturas';
	var Structure = 'Estruturas';
	var Technology='Tecnologia';
	var Fleet ='Frota';
	var Defense = 'Defesa';
	var Progression = 'Progresso' ;
	var Moyenne = 'Média';
	var Production = 'Produção';
	var Indestructible = 'Indestrutivel';

	var Depuis = 'Desde';
	var Points = 'Pontos';

	var nom_def = new Array('Lançador de Mísseis</a>',"Laser Ligeiro</a>","Laser Pesado</a>","Canhão de Gauss </a>","Canhão de Iões</a>","Canhão de Plasma</a>","Mísseis de Intercepção</a>","Mísseis Interplanetários</a>","Pequeno Escudo Planetário</a>","Grande Escudo Planetário</a>");
	var nom_tech = new Array('Tecnologia de Espionagem',"Tecnologia de Computadores","Tecnologia de Armas","Tecnologia de Escudo","Tecnologia de Blindagem","Tecnologia de Energia","Tecnologia de hiperespaço","Motor de Combustão","Motor de Impulsão","Motor Propulsor de Hiperespaço","Tecnologia Laser","Tecnologia de Iões","Tecnologia de Plasma","Rede Intergaláctica de Pesquisas","Tecnologia de Exploração Espacial");
	var nom_bat = new Array('Mina de Metal',"Mina de Cristal","Sintetizador de Deutério","Planta de Energia Solar","Planta de Fusão","Fábrica de Robots","Fábrica der Nanites","Hangar","Armazém de Metal","Armazém de Cristal","Armazém de Deutério","Laboratório de Pesquisas","Terra-formador","Silo de Mísseis","Depósito da Aliança","Base Lunar","Sensor Phalanx","Portal de Salto Quântico");
	var nom_flotte = new Array("Cargueiro Pequeno","Cargueiro Grande","Caça Ligeiro","Caça Pesado",'Cruzador',"Nave de Batalha","Nave de Colonizaçõa","Reciclador","Sonda de Espionagem","Bombardeiro","Destruidor","Estrela da Morte","Interceptor");
	var construction_time = 'Tempo de Construção:';
	var level = 'Nível';
	var dispo = 'Disponivel';
	var rank= 'Classifica';
	var Planet= 'Planeta';

	var soit = 'representa';

	var BBcode_debut="[quote][center][size=22][b]Detalhes de Pontuação:[/b][/size]nn[size=16]Pontuação Total :";
	var BBcode_mine="Pontos em Minas: ";
	var BBcode_bat="Pontos em outras estruturas: ";
	var BBcode_batT="Pontuação Total em Estruturas: ";
	var BBcode_fin1 = "Pontuação em Tecnologia : ";
	var Bcode_fin2 = "Pontuaçao em Frota : ";
	var BBcode_fin3 = "Pontuação em Defesa : ";
	var BBcode_fin4 = "A tua conta tem";
	var BBcode_fin5 = "Pontuação Indestrutivel";
	var BBcode_fin6 = "Média de progresso : ";
	var Point_day = "Pontos por dia";

	var sur_lune='Na Lua';
	var en_vol='Em movimento';
	var Avertissement ='Tens a certeza que queres reiniciar a tua contagem de Progresso?';
	var restart = 'Carrega para reiniciar a contagem do teu progresso';
	var AffBBcode = 'Carrega para obteres os BBcode';

	var done ='Concluido! Actualiza a página!';
	var ICoption = "Opções do InfoComplete's";

	var option1 ='<tr><th>Graficos (Preenche o numero de casas que quiseres)';
	var option2 ='<tr><th >Mostrar pontuação total das estruturas';
	var option3 ='<tr><th>Mostrar pontuação indestrutivel';
	var option4 ='<tr><th>Mostrar pontuação em tecnologia';
	var option5 ='<tr><th>Mostrar pontuação em frota';
	var option6 ='<tr><th>Mostrar pontuação em defesa';
	var option7 ='<tr><th>Mostrar percentagem de frota em movimento';
	var option8 ='<tr><th>Mostrar pontuação da lua';
	var option9 ='<tr><th>Mostrar tudo na mesma linha (Para a frota em movimento e para a pontuação da lua)';
	var option10 ='<tr><th>Mostrar Progresso';
	var option11 ='<tr><th>Preenche aqui se existirem mais que 1 jogador no mesmo computador e universo';
	var option12 ='<tr><th>Mostrar em cores em função do teu progresso';
	var option13 ='<tr><th>Mostrar Progresso Diário';
	var option14 ='<tr><th>Mostrar Pontuação ganho em Minas';
	var option15 ='<tr><td class="c" colspan="2">Cancelar / Gravar configuração:';

	var Save='Gravar Configuração'; 
	var valeurdefaut = 'valores padrão';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
}

/* ******************************Espagnol ********************************/
if (Langue=='com.es' || Langue=='mx' )
{
	var Mines='Minas';
	var Other_structure='Edificios';
	var Structure = 'Edificios';
	var Technology='tecnologias';
	var Fleet ='Flota';
	var Defense = 'Defensas';
	var Progression = 'Progresión' ;
	var Moyenne = 'Promedio';
	var Production = 'Production';
	var Indestructible = 'Indestructibles';
	
	var Depuis = 'desde el';
	var Points = 'Puntos';
	
	var nom_def = new Array("Lanzamisiles</a>","Láser pequeño</a>","Láser grande</a>","Cañón Gauss</a>","Cañón iónico</a>","Cañón de plasma</a>","Misil de intercepción</a>","Misil interplanetario</a>","Cúpula pequeña de protección</a>","Cúpula grande de protección</a>");
	var nom_tech = new Array("Tecnología de espionaje","Tecnología de computación","Tecnología militar","Tecnología de defensa","Tecnología de blindaje","Tecnología de energía","Tecnología de hiperespacio","Motor de combustión","Motor de impulso","Propulsor hiperespacial","Tecnología láser","Tecnología iónica","Tecnología de plasma","Red de investigación","Tecnología de expedición");
	var nom_bat = new Array("Mina de metal","Mina de cristal","Sintetizador de deuterio","Planta de energía solar","Planta de fusión","Fábrica de Robots","Fábrica de Nanobots","Hangar","Almacén de metal","Almacén de cristal","Contenedor de deuterio","Laboratorio de investigación","Terraformer","Silo","Depósito de la Alianza","Base lunar","Sensor Phalanx","Salto cuántico");
	var nom_flotte = new Array("Nave pequeña de carga","Nave grande de carga","Cazador ligero ","Cazador pesado","Crucero","Nave de batalla","Colonizador","Reciclador","Sonda de espionaje","Bombardero","Destructor","Estrella de la muerte","Acorazado");
	
	var construction_time = 'Tiempo de producci'+oaigu+'n:';
	var level = 'Nivel ';
	var dispo = 'dispo';
	var rank= 'Lugar';	
	var Planet= 'Planeta';
	
	var soit = 'son el';
	
	var BBcode_debut="[quote][center][size=22][b]Detalle de la inversión en puntos[/b][/size]\n\n[size=16]Puntos Totales : ";
	var BBcode_mine="Puntos Minas : ";
	var BBcode_bat="Puntos otros Edificios : ";
	var BBcode_batT="Puntos Edificios : ";
	var BBcode_fin1 = "Puntos en tecnologías  :";
	var	Bcode_fin2 = "PPuntos en Flota : ";
	var	BBcode_fin3 = "Puntos en Defensas : ";
	var	BBcode_fin4 = "su cuenta tiene  ";
	var	BBcode_fin5 = "de puntos indestructibles\n";	
	var	BBcode_fin6 = "promedio : ";
	var Point_day = "Puntos por día";
	
	var sur_lune='de lunas';
	var en_vol='en vuelo';
	var Avertissement ='Are you sure you want to restart your progresscount?';
	var restart = 'haga clic aquí para poner su progresión a 0';
	var AffBBcode = 'Haga clic aquí para ver BBcode (por el foro)';
	
	var done ='guardar cambios';
	var ICoption = 'Opciones InfoCompte:';
	
	var option1 ='<tr><th>Color de los graficos';
	var option2 ='<tr><th >Porcentaje de punctos en edificios';
	var option3 ='<tr><th>Porcentaje de punctos indestructibles';
	var option4 ='<tr><th>Porcentaje de punctos tecnologias';
	var option5 ='<tr><th>Porcentaje de punctos en flota';
	var option6 ='<tr><th>Porcentaje de punctos defensas';
	var option7 ='<tr><th>Porcentaje de puntos de flota en vuelo';
	var option8 ='<tr><th>Porcentaje de punctos en la luna';
	var option9 ='<tr><th>todos los porcentajes juntos (para la flota en vuelo y puntos de la luna)';
	var option10 ='<tr><th>Porcentaje de la progresion';
	var option11 ='<tr><th>compruebe si son varios en el mismo equipo e incluso universo';
	var option12 ='<tr><th>Ver en color en funcion de los progresos';
	var option13 ='<tr><th>Mostrar los avances de días';
	var option14 ='<tr><th>Mostrar los puntos obtenidos de las minas';
	var option15 ='<tr><td class="c" colspan="2">Anular / Guardar las modificaciones :';
	
	var valeurdefaut = 'valores por defecto';
	var Save='guardar cambios';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
	
	if ( Langue=='mx' )
	{
		Langue2= Langue+'.';
		Langue = 'org';
	}
}


if (Langue=='nl')
{
	var Mines='Mijnen';
	var Other_structure='Andere Gebouwen';
	var Structure = 'Gebouwen';
	var Technology='Technologie';
	var Fleet ='Vloot';
	var Defense = 'Verdediging';
	var Progression = 'Voortgang' ;
	var Moyenne = 'Gemiddelde';
	var Production = 'Productie';
	var Indestructible = 'Onverwoestbaar';
	
	var Depuis = 'sinds';
	var Points = 'Punten';
	
	var nom_def = new Array('Raketlanceerder',"Kleine laser","Grote laser","Gausskanon","Ionkanon","Plasmakanon","Anti-ballistische raket","Interplanetaire raket","Kleine planetaire schildkoepel","Grote planetaire schildkoepel");
	var nom_tech = new Array('Spionagetechniek',"Computertechniek","Wapentechniek","Schildtechniek","Pantsertechniek","Energietechniek","Hyperruimtetechniek","Verbrandingsmotor","Impulsmotor","Hyperruimtemotor","Lasertechniek","Iontechniek","Plasmatechniek","Intergalactisch Onderzoeksnetwerk","Expeditietechniek");
	var nom_bat = new Array('Metaalmijn',"Kristalmijn","Deuteriumfabriek","Zonne-energiecentrale","Fusiecentrale","Robotfabriek","Nanorobotfabriek","Werf","Metaalopslag","Kristalopslag","Deuteriumtank","Onderzoekslab","Terravormer","Raketsilo","Alliantie hangar","Maanbasis","Sensor phalanx","Sprongpoort");
	var nom_flotte = new Array("Klein vrachtschip","Groot vrachtschip","Licht gevechtsschip","Zwaar gevechtsschip",'Kruiser',"Slagschip","Kolonisatieschip","Recycler","Spionagesonde","Bommenwerper","Interceptor","Ster des Doods","Vernietiger");
	var construction_time = 'Productie tijd:';
	var level = 'Niveau ';
	var dispo = 'beschikbaar ';
	var rank= 'rang ';	
	var Planet= 'Planeet';
	
	var soit = 'dat zijn';
	this_Planet='Deze Planeet';
	var Pointdetails='Puntenverdeling';
	
	var BBcode_debut="[quote][center][size=22][b]"+Pointdetails+":[/b][/size]\n\n[size=16]Totaalpunten: ";
	var BBcode_mine="Punten in Mijnen: ";
	var BBcode_bat="Punten in andere Gebouwen: ";
	var BBcode_batT="Punten in alle Gebouwen: ";
	var BBcode_fin1 = "Punten in Ontwikkeling: ";
	var	Bcode_fin2 = "Punten in de Vloot: ";
	var	BBcode_fin3 = "Punten in Verdediging: ";
	var	BBcode_fin4 = "Uw account heeft ";
	var	BBcode_fin5 = "onverwoestbare punten\n";	
	var	BBcode_fin6 = "Gemiddelde stijging: ";
	var Point_day = "Punten per dag";
	
	var sur_lune='op Maan';
	var en_vol='onderweg';
	var Avertissement ='Bent u zeker dat u uw voortgangswaarde wilt resetten?';
	var restart = 'Oude punten resetten';
	var AffBBcode = 'klik hier voor BBcode';
	
	var done ='Klaar! Pagina opnieuw laden';
	var ICoption = 'InfoCompte Instellingen';
	
	var option1 ='<tr><th>Kleuren';
	var option2 ='<tr><th>Toon totaal aantal punten van gebouwen';
	var option3 ='<tr><th>Toon onverwoestbare punten';
	var option4 ='<tr><th>Toon onderzoekspunten';
	var option5 ='<tr><th>Toon vlootpunten';
	var option6 ='<tr><th>Toon verdedgingspunten';
	var option7 ='<tr><th>Toon procent vloot in vlucht';
	var option8 ='<tr><th>Toon maanpunten';
	var option9 ='<tr><th>Toon alles op een lijn(voor vlootbeweging en maanpunten)';
	var option10 ='<tr><th>Toon voortgang';
	var option11 ='<tr><th>Invullen als er op deze computer meer dan een account per universum is';
	var option12 ='<tr><th>Toon kleuren naargelang stijging';
	var option13 ='<tr><th>Toon voortgang per dag';
	var option14 ='<tr><th>Toon door mijnen geproduceerde punten';
	var option15 ='<tr><td class="c" colspan="2">Veranderingen opslaan / Annuleren  :';
	option16='<tr><th>Toon details op elke pagina';
	
	var Save='Veranderingen opslaan';
	var valeurdefaut = 'Standaardwaarden';
	var speeduniX2 = new Array(10);
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
}

/* ******************************Bosnian ********************************/
if (Langue=='ba') 
{
  var Mines='Rudnici'; 
	var Other_structure='Ostale Zgrade';
	var Structure = 'Zgrade';
	var Technology='Tehnologija';
	var Fleet ='Flota';
	var Defense = 'Obrana';
	var Progression = 'Napredak' ;
	var Moyenne = 'Prosjecno';
	var Production = 'Produkcija';
	var Indestructible = 'Neunistivo';
	
	var Depuis = 'od';
	var Points = 'Bodovi';
	
	var nom_def = new Array('Raketobacaci',"Mali laser","Veliki laser","Gausov top","Ionski top","Plazma top","Anti-balisticke rakete","Interplanetarne rakete","Mala stitna kupola","Velika stitna kupola");
	var nom_tech = new Array('Tehnologija za spijunazu',"Tehnologija za kompjutere","Tehnologija za oruzje","Tehnologija za stitove","Tehnologija za oklop","Tehnologija za energiju","Tehnologija za hiperzonu","Mehanizam sagorjevanja","Impulsni pogon","Hyperspace pogon","Tehnologija za lasere","Tehnologija za ione","Tehnologija za plazmu","Intergalakticna znanstvena mreza","Tehnologija za Ekspedicije");
	var nom_bat = new Array('Rudnik metala',"Rudnik kristala","Sintizer deuterija","Solarna elektrana","Fuzijska elektrana","Tvornica robota","Tvornica nanita","Tvornica brodova","Spremnik metala","Spremnik kristala","Spremnik deuterija","Centar za istrazivanje","Terraformer","Silos za rakete","Depo saveza","Svemirska baza na mjesecu","Senzorfalanga","Odskocna vrata");
	var nom_flotte = new Array("Mali transporter","Veliki transporter","Mali lovac","Veliki lovac",'Krstarice',"Borbeni brodovi","Kolonijalni brodovi","Recikler","Sonde za spijunazu","Bombarder","Razaraci","Zvijezda smrti","Oklopna krstarica");
	var construction_time = 'Trajanje izgradnje:';
	var level = 'Level ';
	var dispo = 'postoji';
	var rank= 'Mjesto ';	
	var Planet= 'Planeta';
	
	var soit = 'predstavljanje';
	this_Planet='Ova Planeta';
	var Pointdetails='Detalji bodova';
	
	var BBcode_debut="[quote][center][size=22][b]"+Pointdetails+":[/b][/size]\n\n[size=16]Ukupno bodova :";
	var BBcode_mine="Bodovi u Rudnicima: ";
	var BBcode_bat="Bodovi u Ostalim Zgradama: ";
	var BBcode_batT="Bodovi u ukupnoj izgradnji: ";
	var BBcode_fin1 = "Bodovi u Tehnologiji : ";
	var	Bcode_fin2 = "Bodovi u Floti : ";
	var	BBcode_fin3 = "Bodovi u Obrani : ";
	var	BBcode_fin4 = "Vas racun ima ";
	var	BBcode_fin5 = "neunistivih bodova";	
	var	BBcode_fin6 = "Prosjecni napredak : ";
	var Point_day = "Bodovi po danu";
	
	var sur_lune='na Mjesecu';
	var en_vol='u letu';
	var Avertissement ='Jeste li sigurni da zelite resetirati vas brojac napretka?';
	var restart = 'Kliknite za resetiranje brojaca napretka';
	var AffBBcode = 'Kliknite za BBcode';
	
	var done ='Gotovo! Obnovite stranicu!';
	var ICoption = 'InfoStat opcije';
	
	var option1 ='<tr><th>Boje grafikona (popunite brojem ukoliko zelite)';
	var option2 ='<tr><th >Prikazi Ukupno Bodovi u Ukupnoj Izgradnji';
	var option3 ='<tr><th>Prikazi neunistive bodove';
	var option4 ='<tr><th>Prikazi Bodove u Tehnologiji';
	var option5 ='<tr><th>Prikazi Bodove u Floti';
	var option6 ='<tr><th>Prikazi Bodove u Obrani';
	var option7 ='<tr><th>Prikazi postotak flote u pokretu';
	var option8 ='<tr><th>Prikazi bodove na Mjesecu';
	var option9 ='<tr><th>Prikazi sve u istom redu (za flotu u pokretu i bodove na Mjesecu)';
	var option10 ='<tr><th>Prikazi napredak';
	var option11 ='<tr><th>Popunite ako je vise od jednog igraca na istom PC-u ili univerzumu';
	var option12 ='<tr><th>Prikazi u boji u fonction napretka';
	var option13 ='<tr><th>Prikazi napredak po danu';
	var option14 ='<tr><th>Prikazi bodove dobivene od rudnika';
	var option15 ='<tr><td class="c" colspan="2">Odustani / Spremi napravljene preinake  :';
	option16='<tr><th>Prikazi Debug Info na svakoj stranici';
	
	var Save='Spremi napravljene preinake';
	var valeurdefaut = 'defaultvalues';
	var speeduniX2 = new Array(); 
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
	
	Langue2= Langue+'.';
	Langue = 'org';
}


/* ******************************Greek ********************************/
if (Langue=='gr')
{
	var Mines='Ορυχεία';
	var Other_structure='Άλλα κτίρια';
	var Structure = 'Κτίρια';
	var Technology='Έρευνα';
	var Fleet ='Στόλος';
	var Defense = 'Άμυνα';
	var Progression = 'Πρόοδος' ;
	var Moyenne = 'Μέσος Όρος';
	var Production = 'Παραγωγή';
	var Indestructible = 'Μη καταστρέψιμα';
	
	var Depuis = 'από την';
	var Points = 'Βαθμοί';
	
	var nom_def = new Array('Εκτοξευτής Πυραύλων',"Ελαφρύ Λέιζερ","Βαρύ Λέιζερ","Κανόνι Gauss","Κανόνι Ιόντων","Πυργίσκοι Πλάσματος","Αντι-Βαλλιστικοί Πύραυλοι","Διαπλανητικοί Πύραυλοι","Μικρός Αμυντικός Θόλος","Μεγάλος Αμυντικός Θόλος");
	var nom_tech = new Array('Τεχνολογία Κατασκοπείας',"Τεχνολογία Υπολογιστών","Τεχνολογία Όπλων","Τεχνολογία Ασπίδων","Τεχνολογία Θωράκισης","Τεχνολογία ενέργειας","Υπερδιαστημική Τεχνολογία","Προώθηση Καύσεως","Ωστική Προώθηση","Υπερδιαστημική Προώθηση","Τεχνολογία Λέιζερ","Τεχνολογία Ιόντων","Τεχνολογία Πλάσματος","Διαγαλαξιακό Δίκτυο Έρευνας","Τεχνολογία Αποστολών");
	var nom_bat = new Array('Ορυχείο Μετάλλου',"Ορυχείο Κρυστάλλου","Συνθέτης Δευτέριου","Εργοστάσιο Ηλιακής Ενέργειας","Αντιδραστήρας Σύντηξης","Εργοστάσιο Ρομποτικής","Εργοστάσιο Νανιτών","Ναυπηγείο","Αποθήκη Μετάλλου","Αποθήκη Κρυστάλλου","Δεξαμενή Δευτέριου","Εργαστήριο Ερευνών","Terraformer","Σιλό Πυραύλων","Σταθμός Συμμαχίας","Σεληνιακή Βάση","Αισθητήρας Φάλαγγας","Διαγαλαξιακή Πύλη");
	var nom_flotte = new Array("Μικρό Μεταγωγικό","Μεγάλο Μεταγωγικό","Ελαφρύ Μαχητικό","Βαρύ Μαχητικό",'Καταδιωκτικό',"Καταδρομικό","Σκάφος Αποικιοποίησης","Ανακυκλωτής","Κατασκοπευτικό Στέλεχος","Βομβαρδιστικό","Destroyer","Deathstar","Θωρηκτό Αναχαίτισης");
	var construction_time = 'Χρόνος Κατασκευής:';
	var level = 'επίπεδο ';
	var dispo = 'available';
	var rank= 'Κατάταξη ';	
	var Planet= 'Πλανήτης';
	
	var soit = 'δηλαδή';
	this_Planet='Σε αυτόν τον Πλανήτη';
	var Pointdetails="Πληροφορίες βαθμών";
	
	var BBcode_debut="[quote][center][size=22][b]"+Pointdetails+":[/b][/size]\n\n[size=16]Συνολικοί βαθμοί: ";
	var BBcode_mine="Βαθμοί από ορυχεία: ";
	var BBcode_bat="Βαθμοί από άλλα κτίρια: ";
	var BBcode_batT="Συνολικοί βαθμοί από κτίρια: ";
	var BBcode_fin1 = "Βαθμοί από έρευνα: ";
	var	Bcode_fin2 = "Βαθμοί από στόλο: ";
	var	BBcode_fin3 = "Βαθμοί από άμυνα: ";
	var	BBcode_fin4 = "Ο λογαριασμός σας έχει ";
	var	BBcode_fin5 = "μη καταστρέψιμους βαθμούς\n";	
	var	BBcode_fin6 = "Μέσος όρος προόδου: ";
	var Point_day = "Βαθμοί τη μέρα";
	
	var sur_lune='σε φεγγάρι';
	var en_vol='σε πτήση';
	var Avertissement ='Είστε σίγουροι ότι θέλετε να μηδενίσετε το δείκτη προόδου;';
	var restart = 'Κάντε κλικ για να μηδενίσετε το δείκτη προόδου';
	var AffBBcode = 'Κάντε κλικ για να πάρετε το BBcode';
	
	var done ='Έγινε! Ανανεώστε τη σελίδα!';
	var ICoption = 'Επιλογές του InfoCompte';
	
	var option1 ='<tr><th>Χρώματα γραφήματος (συμπληρώστε όσες περιπτώσεις χρειάζεστε)';
	var option2 ='<tr><th>Εμφάνιση συνολικών βαθμών από κτίρια';
	var option3 ='<tr><th>Εμφάνιση μη καταστρέψιμων βαθμών';
	var option4 ='<tr><th>Εμφάνιση βαθμών από έρευνα';
	var option5 ='<tr><th>Εμφάνιση βαθμών από στόλο';
	var option6 ='<tr><th>Εμφάνιση βαθμών από άμυνα';
	var option7 ='<tr><th>Εμφάνιση ποσοστού του στόλου σε κίνηση';
	var option8 ='<tr><th>Εμφάνιση βαθμών σε φεγγάρια';
	var option9 ='<tr><th>Εμφάνιση όλων στην ίδια σειρά (για κίνηση στόλου και βαθμούς σε φεγγάρια)';
	var option10 ='<tr><th>Εμφάνιση προόδου';
	var option11 ='<tr><th>Τσεκάρετε αν υπάρχουν περισσότεροι από ένας παίκτες στον ίδιο υπολογιστή και το ίδιο σύμπαν';
	var option12 ='<tr><th>Εμφάνιση λειτουργίας προόδου με χρώματα';
	var option13 ='<tr><th>Εμφάνιση προόδου ανά μέρα';
	var option14 ='<tr><th>Εμφάνιση βαθμών που κερδίζονται από τα ορυχεία';
	var option15 ='<tr><td class="c" colspan="2">Ακύρωση / Αποθήκευση των αλλαγών:';
	option16='<tr><th>Εμφάνιση Πληροφοριών Αποσφαλμάτωσης σε κάθε σελίδα';
	
	var Save='Αποθήκευση των αλλαγών';
	var valeurdefaut = 'Προεπιλεγμένες ρυθμίσεις';
	bunttext = 'με χρώματα';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
}


/* ******************************Polish ********************************/
if (Langue=='onet.pl')
{
var Mines='Kopalnie';
	var Other_structure='Inne budynki';
	var Structure = 'Budynki';
	var Technology='Technologia';
	var Fleet ='Flota';
	var Defense = 'Obrona';
	var Progression = 'Postęp' ;
	var Moyenne = 'Średnica';
	var Production = 'Produkcja';
	var Indestructible = 'Niezniszczalne';
	
	var Depuis = 'od';
	var Points = 'Punkty';
	
	var nom_def = new Array('Wyrzutnia rakiet',"Lekkie działo laserowe","Ciężkie działo laserowe","Działo Gaussa","Działo jonowe","Wyrzutnia plazmy","Przeciwrakieta","Rakieta międzyplanetarna","Mała powłoka ochronna","Duża powłoka ochronna");
	var nom_tech = new Array('Technologia szpiegowska',"Technologia komputerowa","Technologia bojowa","Technologia ochronna","Opancerzenie","Technologia energetyczna","Technologia nadprzestrzenna","Napęd spalinowy","Napęd impulsowy","Napęd nadprzestrzenny","Technologia laserowa","Technologia jonowa","Technologia plazmowa","Międzygalaktyczna Sieć Badań Naukowych","Technologia Ekspedycji");
	var nom_bat = new Array('Kopalnia metalu',"Kopalnia kryształu","Ekstraktor deuteru","Elektrownia słoneczna","Elektrownia fuzyjna","Fabryka robotów","Fabryka nanitów","Stocznia","Magazyn metalu","Magazyn kryształu","Zbiornik deuteru","Laboratorium badawcze","Terraformer","Silos rakietowy","Depozyt sojuszniczy","Stacja księżycowa","Falanga czujników","Teleporter");
	var nom_flotte = new Array("Mały transporter","Duży transporter","Lekki myśliwiec","Ciężki myśliwiec",'Krążownik',"Okręt wojenny","Statek kolonizacyjny","Recykler","Sonda szpiegowska","Bombowiec","Niszczyciel","Gwiazda Śmierci","Pancernik");
	var construction_time = 'Czas ukończenia:';
	var level = 'Poziom';
	var dispo = "wybudowano";
	var rank= 'Miejsce';	
	var Planet= 'Planeta';
	
	var soit = 'to są';
	this_Planet='ta planeta';
	var Pointdetails="Szczegóły punktów";
	
	var BBcode_debut="[quote][center][size=22][b]"+Pointdetails+":[/b][/size]\n\n[size=16]Wszystkie punkty :";
	var BBcode_mine="Punkty w kopalniach: ";
	var BBcode_bat="Punkty w innych budynkach: ";
	var BBcode_batT="Punkty we wszystkich budynkach: ";
	var BBcode_fin1 = "Punkty w technologii : ";
	var	Bcode_fin2 = "Punkty we flocie : ";
	var	BBcode_fin3 = "Punkty w obronie : ";
	var	BBcode_fin4 = "Twoje konto posiada ";
	var	BBcode_fin5 = "Niezniszczalne punkty";	
	var	BBcode_fin6 = "Średni postęp : ";
	var Point_day = "Punkty na dzień";
	
	var sur_lune='na księżycu';
	var en_vol='w locie';
	var Avertissement ='Czy napewno chcesz zresetować postęp';
	var restart = 'Kliknij aby zresetować postęp';
	var AffBBcode = 'Kliknij tutaj po BBcode';
	
	var done ='Gotowe! Odswież stronę!';
	var ICoption = 'opcje InfoCompte';
	
	var option1 ='<tr><th>Kolory (wypełnij jak chcesz)';
	var option2 ='<tr><th >Pokoaż punkty wszystkich budynków';
	var option3 ='<tr><th>Pokoaż punkty niezniszczalne';
	var option4 ='<tr><th>Pokoaż punkty technologii';
	var option5 ='<tr><th>Pokoaż punkty floty';
	var option6 ='<tr><th>Pokoaż punkty obrony';
	var option7 ='<tr><th>Pokoaż procent floty w locie';
	var option8 ='<tr><th>Pokoaż punkty księżyca';
	var option9 ='<tr><th>Pokoaż w jednej lini(dla ruchu flot i  punkty księżyca)';
	var option10 ='<tr><th>Pokoaż postęp';
	var option11 ='<tr><th>Zaznacz jeżeli jest wiecej niż jedna osoba na tym komputerze i universum';
	var option12 ='<tr><th>Pokoaż postęp w kolorze';
	var option13 ='<tr><th>Pokoaż postęp na dzień';
	var option14 ='<tr><th>Pokoaż punkty zyskane z kopalnii';
	var option15 ='<tr><td class="c" colspan="2">Anuluj / Zapisz dokonane modyfikacje  :';
	option16='<tr><th>Pokoaż szczegóły na każdej stronie';
	
	var Save='Zapisz zmiany';
	var valeurdefaut = 'ustawienia fabryczne';
	bunttext = 'w kolorach';
	var speeduniX2 = new Array(50,60);
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
}




/* ******************************Suèdois ********************************/
if (Langue=='se')
{	
	var Mines='Gruvor';
	var Other_structure='Andra byggnader';
	var Structure = 'Byggnader';
	var Technology='Teknologi';
	var Fleet ='Flotta';
	var Defense = 'F'+opt+'rsvar';
	var Progression = 'Genomsnitt' ;
	var Moyenne = 'Tillv&auml;xt';
	var Production = 'Resursproduktion';
	var Indestructible = 'Indestructible';
	
	var Depuis = 'sen';
	var Points = 'Po&auml;ng';
	
	var nom_def = new Array('Raketramp</a>',"Litet lasertorn</a>","Stort lasertorn</a>","Gausskanon</a>","Jonkanon</a>","Plasmakanon</a>","Antiballistiska missiler</a>","Interplanet</a>","Liten sk</a>","Stor sk</a>");
	var nom_tech = new Array('Spionageteknologi',"Datorteknologi","Vapenteknologi","ldteknologi","ldteknologi","Energiteknologi","Hyperrymdteknologi","Raketmotor","Impulsmotor","Hyperrymdmotor","Laserteknologi","Jonteknologi","Plasmateknologi","Intergalaktiskt forskningsn?tverk ","Expeditionsteknologi");
	var nom_bat = new Array('Metallgruva',"Kristallgruva","Deuteriumplattform","Solkraftverk","Fusionskraftverk","Robotfabrik","Nanofabrik","Skeppsvarv","Metallager","Kristallager","Deuteriumtank","Forskningslabb","Terraformare","Missilsilo","Alliansdep","nbas","Radarstation","nportal");
	var nom_flotte = new Array("Litet transportskepp","Stort transportskepp","Litet jaktskepp","Stort jaktskepp",'Kryssare',"Slagskepp","Koloniskepp","&Aring;tervinnare","Spionsond","Bombare","Flaggskepp","D&ouml;dsstj&aouml;rna","Jagare");
	var construction_time = 'Konstruktionstid:';
	var level = 'level ';
	var dispo = 'tillg';
	var rank= 'rank ';	
	var Planet= 'Planet';
	
	var soit = 'representerar';
	
	var BBcode_debut="[quote][center][size=22][b]Po&auml;ngdetaljer:[/b][/size]\n\n[size=16]Totala po&auml;ng :";
	var BBcode_mine="Po&auml;ng i gruvor: ";
	var BBcode_bat="Po&auml;ng i &ouml;vriga byggnader : ";
	var BBcode_batT="Totala po&auml;ng i byggnader : ";
	var BBcode_fin1 = "Po&auml;ng i teknologi : ";
	var	Bcode_fin2 = "Po&auml;ng i flotta : ";
	var	BBcode_fin3 = "Po&auml;ng i f&ouml;rsvar :";
	var	BBcode_fin4 = "Ditt konto har ";
	var	BBcode_fin5 = "of&ouml;rst&ouml;rbara po&auml;ng\n";	
	var	BBcode_fin6 = "Genomsnittlig tillv&auml;xt : ";
	var Point_day = "Po&auml;ng per dag";
	
	var sur_lune='p&aring; m&aring;ne';
	var en_vol='flygande';
	var Avertissement ='OBS! Detta kommer reseta allt!';
	var restart = 'Click to restart your progresscount';
	var AffBBcode = 'klicka f&ouml;r att f&aring; BBcode';
	
	var done ='Klart! Uppdatera sidan!';
	var ICoption = 'InfoCompte\'s inst&auml;llningar';
	
	var option1 ='<tr><th>Graf f&auml;rger (fyll i f&ouml;r olika f&auml;rger i f&auml;lten)';
	var option2 ='<tr><th >Visa totala po&auml;ngen f&ouml;r byggnader';
	var option3 ='<tr><th>Visa of&ouml;rst&ouml;rbara po&auml;ng';
	var option4 ='<tr><th>Visa po&auml;ngen f&ouml;r teknologi';
	var option5 ='<tr><th>Visa po&auml;ngen f&ouml;r flottan';
	var option6 ='<tr><th>Visa po&auml;ngen f&ouml;r f&ouml;rsvaret';
	var option7 ='<tr><th>Visa procent av flottan i r&ouml;relse';
	var option8 ='<tr><th>Visa po&auml;ngen f&ouml;r m&aring;nar';
	var option9 ='<tr><th>Visa allt i samma f&auml;lt (f&ouml;r flott r&ouml;relser och m&aring;npo&auml;ng)';
	var option10 ='<tr><th>Visa tillv&auml;xt';
	var option11 ='<tr><th>Tryck i om det &auml;r fler &auml;n en spelare p&aring; samma dator som spelar i samma universum';
	var option12 ='<tr><th>Visa f&auml;rger i po&auml;ngen f&ouml;r tillv&auml;xt';
	var option13 ='<tr><th>Visa tillv&auml;xt per dag';
	var option14 ='<tr><th>Visa po&auml;ng intj&auml;nat fr&aring;n gruvor';
	var option15 ='<tr><td class="c" colspan="2">Avbryt / Spara &Auml;ndringarna  :';
	
	var Save='Spara &Auml;ndringar';
	var valeurdefaut = 'Standard';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	
	var creeSign='create a signature with InfoCompte';
}

if (Langue=='fi')
{
	var Mines='Kaivokset';
	var Other_structure='Muut rakennukset';
	var Structure = 'Rakennukset';
	var Technology='Teknologia';
	var Fleet ='Laivasto';
	var Defense = 'Puolustus';
	var Progression = 'Edistyminen' ;
	var Moyenne = 'Keskimääräinen';
	var Production = 'Tuotanto';
	var Indestructible = 'Tuhoutumattomia';
	
	var Depuis = 'lähtien';
	var Points = 'pistettä';
	
	var nom_def = new Array('Raketinheitin</a>',"Kevyt laser</a>","Raskas laser</a>","Gaussin tykki</a>","Ionitykki</a>","Plasmatorni</a>","Torjuntaohjukset</a>","Planeettainväliset ohjukset</a>","Pieni suojakupu</a>","Suuri suojakupu</a>");
	var nom_tech = new Array('Vakoiluteknologia',"Tietokoneteknologia","Aseteknologia","Suojausteknologia","Panssariteknologia","Energiateknologia","Hyperavaruusteknologia","Polttoajo","Impulssiajo","Hyperavaruusajo","Laserteknologia","Ioniteknologia","Plasmateknologia","Galaksienvälinen tutkimusverkko","Retkikuntateknologia");
	var nom_bat = new Array('Metallikaivos',"Kristallikaivos","Deuteriumin syntetisoija","Aurinkovoimala","Fuusioreaktori","Robottitehdas","Nanokonetehdas","Telakka","Metallivarasto","Kristallivarasto","Deuterium-säiliö","Tutkimuslaboratorio","Terraformer","Ohjussiilo","Liittoutuman varasto","Kuutukikohta","Sensoriryhmittymä","Tähtiportti");
	var nom_flotte = new Array("Pieni rahtialus","Suuri rahtialus"," Kevyt Hävittäjä","Raskas Hävittäjä",'Risteilijä',"Taistelualus","Siirtokunta-alus","Kierrättäjä","Vakoiluluotain","Pommittaja","Tuhoaja","Kuolemantähti","Taisteluristeilijä");
	var construction_time = 'Rakennusaika:';
	var level = 'taso ';
	var dispo = 'saatavilla';
	var rank= 'Rank ';	
	var Planet= 'Planeetta';
	
	var soit = 'kaikista pisteistä';
	
	var BBcode_debut="[quote][center][size=22][b]Pisteet:[/b][/size]\n\n[size=16]Pisteitä yhteensä: ";
	var BBcode_mine="Pisteitä kaivoksissa: ";
	var BBcode_bat="Pisteitä muissa rakennuksissa: ";
	var BBcode_batT="Pisteitä kaikissa rakennuksissa: ";
	var BBcode_fin1 = "Pisteitä tutkimuksissa: ";
	var	Bcode_fin2 = "Pisteitä laivoissa: ";
	var	BBcode_fin3 = "Pisteitä puolustuksissa: ";
	var	BBcode_fin4 = "Tililläsi on ";
	var	BBcode_fin5 = " tuhoutumattomia pisteitä\n";
	var	BBcode_fin6 = "Kehityksen keskiarvo: ";
	var Point_day = "pistettä päivässä";
	
	var sur_lune='kuussa';
	var en_vol='lennossa';
	var Avertissement ='Oletko varma että haluat resetoida pistelaskurin?';
	var restart = 'Paina resetoidaksesi pistelaskurin';
	var AffBBcode = 'Paina saadaksesi BBcodena';
	
	var done ='Valmis! Päivitä sivu!';
	var ICoption = 'InfoCompten asetukset';
	
	var option1 ='<tr><th>Ympyrädiagrammin värit (täytä värikoodit sektoreiden mukaan)';
	var option2 ='<tr><th >Näytä kaikkien rakennuksien pisteet';
	var option3 ='<tr><th>Näytä tuhoutumattomat pisteet';
	var option4 ='<tr><th>Näytä teknologiapisteet';
	var option5 ='<tr><th>Näytä laivastopisteet';
	var option6 ='<tr><th>Näytä puolustuspisteet';
	var option7 ='<tr><th>Näytä lennossa olevan laivaston prosenttiosuus';
	var option8 ='<tr><th>Näytä kuiden rakennusten pisteet';
	var option9 ='<tr><th>Näytä kaikki samalla rivillä (lennossa oleva laivasto ja kuun pisteet)';
	var option10 ='<tr><th>Näytä kehitys';
	var option11 ='<tr><th>Laita täppä jos tällä tietokoneella pelaa useampi henkilö samassa universumissa';
	var option12 ='<tr><th>Näytä edistyminen värillä';
	var option13 ='<tr><th>Näytä pisteitä päivässä';
	var option14 ='<tr><th>Näytä kaivoksilla saadut pisteet';
	var option15 ='<tr><td class="c" colspan="2">Peruuta / Tallenna tehdyt muutokset:';
	
	var Save='Tallenna tehdyt muutokset';
	var valeurdefaut = 'Oletusarvot';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();
	var creeSign='Luo allekirjoitus InfoComptella';
	
	if (Langue2=='fi.')
	{
		Langue = 'org';
	}
	Pointdetails = 'Pisteiden jakautuminen';
}

if (Langue=='no')
{
	var Mines='Miner';
	var Other_structure='andre byggninger';
	var Structure = 'Structure';
	var Technology='Teknologi';
	var Fleet ='Flåte';
	var Defense = 'forsvar';
	var Progression = 'framskritt' ;
	var Moyenne = 'gjennomsnittlig';
	var Production = 'Produksjon';
	var Indestructible = 'Indestructible';

	var Depuis = 'siden';
	var Points = 'Poeng';

	var nom_def = new Array('Rakettkaster</a>',"Lett laser</a>","Tung Laser</a>","Gauss Kannon</a>","Ion Kannon</a>","Plasma Tårn</a>","Anti-Ballistiske Missiler</a>","Interplanetariske Missiler</a>","Liten Skjold-Kuppel</a>","Stor Skjold-Kuppel</a>");
	var nom_tech = new Array('Spionasjeteknologi',"Datateknologi","Våpenteknologi","Skjoldteknologi","Panserteknologi","Energiteknologi","Hyperromfartsteknologi","Forbrennings Driv","Impuls Driv","Hyperromfartsmotor","Laser Teknologi","Ione Teknologi","Plasma Teknologi","Intergalaktisk Forsknings Nettverk","Ekspedisjonsteknologi");
	var nom_bat = new Array('Metallgruve',"Krystallgruve","Deuteriumsfremstiller","Solpanel","Fusjons Reaktor","Robot Fabrikk","Nanitt Fabrikk","Skipsverft","Metall Lagring","Krystall Lagring","Deuterium Tank","Forsknings Lab","Terraformer","Missil Silo","Allianse havn","Månebase","Phalanx Sensor","Sprangportal");
	var nom_flotte = new Array("Lite Lasteskip","Stort Lasteskip","Lett Jeger","Tung Jeger",'Krysser',"Slagskip","Koloni Skip","Resirkulerer","Spionasjesonde","Bomber","Destroyer","Døds stjerne","Slagkrysser");
	var construction_time = 'Byggings-tid:';
	var level = 'level';
	var dispo = 'tilgjengelig';
	var rank= 'Rang ';
	var Planet= 'Planet';

	var soit = 'representerer';

	var BBcode_debut="[quote][center]poengdetaljer:\n\n[size=16]Total poengsum :";
	var BBcode_mine="Poeng i Miner: ";
	var BBcode_bat="Poeng i byggninger: ";
	var BBcode_batT="Total poengsum byggninger: ";
	var BBcode_fin1 = "Poeng teknologi : ";
	var Bcode_fin2 = "Poeng i flåte : ";
	var BBcode_fin3 = "Poeng i forsvar : ";
	var BBcode_fin4 = "Din konto har ";
	var BBcode_fin5 = "indestructible points\n";
	var BBcode_fin6 = "Gjennomsnittlig fremgang : ";
	var Point_day = "Poeng per dag";

	var sur_lune='på måne';
	var en_vol='fly';
	var Avertissement ='Are you sure you want to restart your progresscount?';
	var restart = 'Click to restart your progresscount';
	var AffBBcode = 'click to have the BBcode';

	var done ='Ferdig! Refresh siden!';
	var ICoption = 'InfoCompte\'s options';

	var option1 ='<tr><th>Graphiccolours (fill in the number of cases you want)';
	var option2 ='<tr><th >Vis total poengsum byggninger';
	var option3 ='<tr><th>Show indestructible points';
	var option4 ='<tr><th>Vis total poengsum Teknologi';
	var option5 ='<tr><th>Vis poengsum Flåte';
	var option6 ='<tr><th>Vis poengsum forsvar';
	var option7 ='<tr><th>Vis prosenter av flåte i bevegelse';
	var option8 ='<tr><th>Vis poengsum måne';
	var option9 ='<tr><th>Vist alt i samme linje (får flåte bevegelse og poeng måne )';
	var option10 ='<tr><th>Vis fremskritt';
	var option11 ='<tr><th>Fyll inn vist det er mer enn en spiller på samme pc eller univers';
	var option12 ='<tr><th>Show in coulours in fonction of the progress';
	var option13 ='<tr><th>Vis fremskritt per dag';
	var option14 ='<tr><th>Vis poeng tjent på miner';
	var option15 ='<tr><td class="c" colspan="2">Kanseler / Lagre modifikasjoner :';

	var Save='Lagre modifikasjoner';
	var valeurdefaut = 'defaultvalues';
	var speeduniX2 = new Array();
	var speeduniX5= new Array();

	var creeSign='create a signature with InfoCompte'; 
	Pointdetails = 'poengdetaljer';
}


if (Langue=='dk')
{
	var Mines='Miner';
	var Other_structure='Andre bygninger';
	var Structure = 'Bygninger';
	var Technology='Forskning';
	var Fleet ='Flåde';
	var Defense = 'Forsvar';
	var Progression = 'Fremskridt' ;
	var Moyenne = 'Gennemsnit';
	var Production = 'Produktion';
	var Indestructible = 'Sikre';
	
	var Depuis = 'siden';
	var Points = 'Points';
	
	var nom_def = new Array('Raketkanon</a>',"Lille Laserkanon</a>","Stor Laserkanon</a>","Gausskanon</a>","Ionknon</a>","Plasmakanon</a>","Forsvarsraket</a>","Interplanetarraket</a>","Lille Planetskjold</a>","Stort Planetskjold</a>");
	var nom_tech = new Array('Spionageteknologi',"Computerteknologi","Våbenteknologi","Skjoldteknologi","Rumskibspansring","Energiteknologi","Hyperrumteknologi","Forbrændingssystem","Impulssystem","Hyperrumsystem","Laserteknologi","Ionteknologi","Plasmateknologi","Intergalaktisk Forskningsnetværk","Ekspedition Teknologi");
	var nom_bat = new Array('Metalmine',"Krystalmine","Deuteriumsyntetiserer","Solkraftværk","Fusionskraftværk","Robotfabrik","Nanitfabrik","Rumskibsværft","Metallager","Krystallager","Deuteriumlager","Forskningslaboratorium","Terraformer","Raketsilo","Alliancedepot","Månebase","Phalanxbygning","Springportal");
	var nom_flotte = new Array("Lille Transporter","Stor Transporter","Lille Jæger","Stor Jæger",'Krydser',"Slagskib","Koloniskib","Recycler","Spionagesonde","Bomber","Destroyer","Dødsstjerne","Interceptor");
	var construction_time = 'Produktionstid:';
	var level = 'Level ';
	var dispo = 'til stede';
	var rank= 'Plads ';	
	var Planet= 'Planet';
	
	var soit = 'tilsvarende';
	
	var BBcode_debut="[quote][center][size=22][b]Pointdetaljer:[/b][/size]\n\n[size=16]Totale points :";
	var BBcode_mine="Points i miner : ";
	var BBcode_bat="Points i andre bygninger : ";
	var BBcode_batT="Point i alle bygninger : ";
	var BBcode_fin1 = "Points i forskning : ";
	var	Bcode_fin2 = "Points i flåde : ";
	var	BBcode_fin3 = "Points i forsvar : ";
	var	BBcode_fin4 = "Din konto har ";
	var	BBcode_fin5 = "sikre points\n";	
	var	BBcode_fin6 = "Gennemsnitlig fremskridt : ";
	var Point_day = "Points om dagen";
	
	var sur_lune='på måne';
	var en_vol='flyvende';
	var Avertissement ='Er du sikker på at du vil nulstille din fremskridtstæller?';
	var restart = 'Klik for at nulstille din fremskridtstæller.';
	var AffBBcode = 'Klik for at få BB kode';
	
	var done ='Færdig! Opdater siden!';
	var ICoption = 'InfoCompte\'s indstillinger';
	
	var option1 ='<tr><th>Farver på graf (hex koder)';
	var option2 ='<tr><th >Vis total bygningspoints (lægger miner og andre byg. sammen)';
	var option3 ='<tr><th>Vis sikre points';
	var option4 ='<tr><th>Vis forskningspoints';
	var option5 ='<tr><th>Vis flådepoints';
	var option6 ='<tr><th>Vis forsvarspoints';
	var option7 ='<tr><th>Vis procent af flåde der er flyvende';
	var option8 ='<tr><th>Vis månepoints';
	var option9 ='<tr><th>Vis alle på samme linje (for flyvende flåde og månepoints)';
	var option10 ='<tr><th>Vis fremskridt';
	var option11 ='<tr><th>Udfyld hvis der er mere end en spiller på samme computer og i samme univers';
	var option12 ='<tr><th>Vis farver som funktion af fremskridt/tilbagegang';
	var option13 ='<tr><th>Vis fremskridt hver dag';
	var option14 ='<tr><th>Vis points tjent på miner';
	var option15 ='<tr><td class="c" colspan="2">Annuller / Gem ændringer  :';
	
	var Save='Gem ændringerne';
	var valeurdefaut = 'Nulstil gemte indstillinger';
	var speeduniX2 = new Array(10);
	var speeduniX5= new Array();
	
	Pointdetails = 'Pointdetaljer:';
	var creeSign='create a signature with InfoCompte';
}


/* ****************************** Русский ********************************/
if (Langue=='ru')
{
	var Mines='Шахты';
	var Other_structure='Постройки';
	var Structure = 'Все Постройки';
	var Technology='Исследования';
	var Fleet ='Флот';
	var Defense = 'Оборона';
	var Progression = 'Рост' ;
	var Moyenne = 'В среднем';
	var Production = 'Прирост';
	var Indestructible = 'Неснулямое';
	
	var Depuis = 'с';
	var Points = 'Очки';
	
	var nom_def = new Array('Ракетная установка',"Лёгкий лазер","Тяжёлый лазер","Пушка Гаусса","Ионное орудие","Плазменное орудие","Ракета-перехватчик","Межпланетная ракета","Малый щитовой купол","Большой щитовой купол");
	var nom_tech = new Array('Шпионаж',"Компьютерная технология","Оружейная технология","Щитовая технология","Броня космических кораблей","Энергетическая технология","Гиперпространственная технология","Реактивный двигатель","Импульсный двигатель","Гиперпространственный двигатель","Лазерная технология","Ионная технология","Плазменная технология","Межгалактическая исследовательская сеть","Экспедиционная технология");
	var nom_bat = new Array('Рудник по добыче металла',"Рудник по добыче кристалла","Синтезатор дейтерия","Солнечная электростанция","Термоядерная электростанция","Фабрика роботов","Фабрика нанитов","Верфь","Хранилище металла","Хранилище кристалла","Ёмкость для дейтерия","Исследовательская лаборатория","Терраформер","Ракетная шахта","Склад альянса","Лунная база","Сенсорная фаланга","Ворота");
	var nom_flotte = new Array("Малый транспорт","Большой транспорт","Лёгкий истребитель","Тяжёлый истребитель",'Крейсер',"Линкор","Колонизатор","Переработчик","Шпионский зонд","Бомбардировщик","Уничтожитель","Звезда смерти","Линейный крейсер");
	var construction_time = 'Длительность:';
	var level = 'уровень ';
	var dispo = 'в наличии';
	var rank= 'место ';	
	var Planet= 'Планета';
	
	var soit = 'в среднем';
	
	var BBcode_debut="[quote][center][size=22][b]Распределение очков:[/b][/size]\n\n[size=16]Всего очков :";
	var BBcode_mine="Очков в шахтах: ";
	var BBcode_bat="Очков в др. постройках: ";
	var BBcode_batT="Очков во всех постройках: ";
	var BBcode_fin1 = "Очков в исследованиях : ";
	var	Bcode_fin2 = "Очков во флоте : ";
	var	BBcode_fin3 = "Очков в обороне : ";
	var	BBcode_fin4 = "На вашем аккаунте ";
	var	BBcode_fin5 = " неснуляемых очков\n";	
	var	BBcode_fin6 = "Средний рост : ";
	var Point_day = "очков в день";
	
	var sur_lune='на луне';
	var en_vol='в полете';
	var Avertissement ='Вы уверены что хотите сброить свою статистику?';
	var restart = 'Нажмите чтобы сбросить статистику';
	var AffBBcode = 'нажмите для получения BBcode';
	
	var done ='Готово! Обновите страницу!';
	var ICoption = 'Настройки InfoCompte';
	
	var option1 ='<tr><th>Цвета графиков (можно раскрасить самому)';
	var option2 ='<tr><th >Показывать очки в постройках';
	var option3 ='<tr><th>Показывать неснуляемые очки';
	var option4 ='<tr><th>Показывать очки в исследованиях';
	var option5 ='<tr><th>Показывать очки во флоте';
	var option6 ='<tr><th>Показывать очки в обороне';
	var option7 ='<tr><th>Показывать процент летающего флота';
	var option8 ='<tr><th>Показывать очки за луны';
	var option9 ='<tr><th>Показывать в одну строку (перемещения флота и очки за луны)';
	var option10 ='<tr><th>Показывать рост';
	var option11 ='<tr><th>Укажите, если за этим компьютером в этой вселенной играет больше одного человека';
	var option12 ='<tr><th>Раскрашивать текст';
	var option13 ='<tr><th>Показывать рост за день';
	var option14 ='<tr><th>Показывать очки за шахты';
	var option15 ='<tr><td class="c" colspan="2">Отменить / Сохранить сделанные изменения  :';
	
	var Save='Сохранить изменения';
	var valeurdefaut = 'По умолчанию';
	var speeduniX2 = new Array(10);
	var speeduniX5= new Array(35);
	
	var creeSign='Сделать подпись на форум';
	Pointdetails = 'Распределение очков:';
}


if (Langue != '')	
{

	var selectnode = document.getElementsByTagName('select');
	var numeroplanete = selectnode[0].selectedIndex+1;
	var nbplanetesetlunes = selectnode[0].length;

	if (selectnode.length >0) 
	{
	    var numeroplanete = selectnode[0].selectedIndex;
		var nombreplanetesetlunes = selectnode[0].options.length;
	}


	var pseudo='';
		
	// Recuperation de session/uni
	MatchTab = url.match('uni([0-9]{1,2}).'+Langue2+'ogame.'+Langue+'.game.index.php.page=([a-zA-Z0-9_]+)(?:&dsp=1)?(?:&no_header=1)?&session=([a-z0-9]{12})(?:&mode=([a-zA-Z0-9]+))?');
	var uni = MatchTab[1];
	var session = MatchTab[3];


	/* *****************************************************   OPTION    ******************************************************** */	
	function oui_non_en_checked(oui_non) 
	{
		if (oui_non == "true") {return "checked";} else {return "unchecked";} 
	}

	var OptionSauvegarde = GM_getValue("options"+uni+pseudo,"3333ff;false;false;true;true;true;true;false;false;true;false;true;true;")+'false';
	var option = new Array();
	option = OptionSauvegarde.split(/;/);

	var CouleurGraph = option[0];

	// Page Options
	if ((url.indexOf('option',0))>=0)
	{
		if ((url.indexOf('&infocompte_plus=oui',0))>=0) 
		{
			var couleur = new Array('','','','','');
			var listeCouleur = new Array();
			listeCouleur = option[0].split(/,/);
			
			for (var i=0 ; i< listeCouleur.length ; i++)
				{couleur[i] = listeCouleur[i];}

			for(var i=0 ; i< option.length ; i++)
				{option[i] = oui_non_en_checked(option[i]);}

			var tdnode = document.getElementById('content').getElementsByTagName('table');
			
			// Ajout du tableau :
				tdnode[0].innerHTML = '<tr><td class="c" colspan="2">'+ICoption+' :</td></tr>';
				tdnode[0].innerHTML += option1 +'</th> 		<th><input class="couleur" name="couleur" maxlength="6" value="'+couleur[0]+'" type="text" size="8" style="text-align:center;">  <input class="couleur" name="couleur" maxlength="6" value="'+couleur[1]+'" type="text" size="8" style="text-align:center;">  <input class="couleur" name="couleur" maxlength="6" value="'+couleur[2]+'" type="text" size="8" style="text-align:center;">  <input class="couleur" name="couleur" maxlength="6" value="'+couleur[3]+'" type="text" size="8" style="text-align:center;"> <input class="couleur" name="couleur" maxlength="6" value="'+couleur[4]+'" type="text" size="8" style="text-align:center;"></th></tr>';			
				tdnode[0].innerHTML += option2 +'</th> 		<th><input class="InfoOptions" '+option[1]+' name="batTotal" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option3 +'</th> 		<th><input class="InfoOptions" '+option[2]+' name="indestructible" type="checkbox"></th></tr>';
				tdnode[0].innerHTML+= option4 +'</th> 		<th><input class="InfoOptions" '+option[3]+' name="Techno" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option5 +'</th> 		<th><input class="InfoOptions" '+option[4]+' name="Flotte" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option6 +'</th> 		<th><input class="InfoOptions" '+option[5]+' name="Defense" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option7 +'</th> 		<th><input class="InfoOptions" '+option[6]+' name="vol" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option8 +'</th> 		<th><input class="InfoOptions" '+option[7]+' name="lune" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option9 +'</th> 		<th><input class="InfoOptions" '+option[8]+' name="br" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option10 +'</th> 	<th><input class="InfoOptions" '+option[9]+' name="prog" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option11 +'</th>		<th><input class="InfoOptions" '+option[10]+' name="plein" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option12 +'</th>		<th><input class="InfoOptions" '+option[11]+' name="couleurProg" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option13 +'</th>		<th><input class="InfoOptions" '+option[12]+' name="progjours" type="checkbox"></th></tr>';
				tdnode[0].innerHTML += option14 +'</th>		<th><input class="InfoOptions" '+option[13]+' name="prodjours" type="checkbox"></th></tr>';
				
				tdnode[0].innerHTML += option15 +'</td></tr>';
				tdnode[0].innerHTML += '<tr><th class="boutton_VG"><input title="'+valeurdefaut+'" value="'+valeurdefaut+'" type="submit" class="Reset_VG"></th><th class="boutton_VG"><input title="'+Save+' " value="'+Save+' " type="submit" class="Sauver_VG"></th></tr>';

		
				// Definition du code du bouton de reset :
			var Boutton = document.getElementsByClassName("Reset_VG");
					
			if (Boutton[0]) 
			{
				Boutton[0].addEventListener("click", function() 
				{

						GM_setValue("options"+uni+pseudo, "3333ff;false;false;true;true;true;true;false;false;true;false;true;true;true;");

						BOUTTON = document.getElementsByClassName("boutton_VG");
						BOUTTON[0].innerHTML = '<a href="http://uni'+uni+'.'+Langue2+'ogame.'+Langue+'/game/index.php?page=options&session='+session+'&infocompte_plus=oui">'+done+'</a>';

				}, true);
			}
			// Definition du code du bouton de sauvegarde :
			var Boutton = document.getElementsByClassName("Sauver_VG");
					
			if (Boutton[0]) 
			{
				Boutton[0].addEventListener("click", function() 
				{
					var Block1 = document.getElementsByClassName('couleur');
					if (Block1[0].value) 
					{
						CouleurGraph='';
						for (var i =0 ; i< Block1.length; i++)
						{
							if (Block1[i].value.length == 6)
							{
								CouleurGraph += Block1[i].value + ',';
							}
						}
					}
					CouleurGraph = CouleurGraph.substring(0, CouleurGraph.length-1)
					
					var SOptions = CouleurGraph+';';
					var Block = document.getElementsByClassName('InfoOptions');
					for (var f=0 ; f < Block.length ; f++ )
					{
						if (Block[f].checked) 
							{SOptions += "true;";} 
						else 
							{SOptions += "false;";}
					}
					GM_setValue("options"+uni+pseudo, SOptions);
					
					BOUTTON = document.getElementsByClassName("boutton_VG");
					BOUTTON[1].innerHTML = '<a href="http://uni'+uni+'.'+Langue2+'ogame.'+Langue+'/game/index.php?page=options&session='+session+'&infocompte_plus=oui">'+done+'</a>';
				}, true);
			}
		 }
		
		else 
		{
			if (url.indexOf('=oui',0) == -1) 
			{
				var tdnode = document.getElementsByTagName('table')[0];
				var New_Table = document.createElement('table');
				New_Table.style.width = "519";
				New_Table.innerHTML = '<tr><td colspan="2" class="c"><a href="http://uni'+uni+'.'+Langue2+'ogame.'+Langue+'/game/index.php?page=options&session='+session+'&infocompte_plus=oui">'+ICoption+'</a></td></tr>';
					
				var tdnode = document.getElementById('content').getElementsByTagName('table')[0];
				tdnode.parentNode.insertBefore(New_Table, tdnode.nextSibling);
			}
		}
	}// fin page option 
	
	var BatTotal = false; 
	var indestructible = false; 
	var techno = false; 
	var flottes = false; 
	var Def = false; 
	var VaisseauxVol  = false; 
	var pointLune = false; 
	var sauterLignePourPourcentageFlotteVol = false; 
	var progression = false; 
	var PlusieurSurMemeUni  = false;
	var debugGraphique = false;
	var couleurPoint = false ;
	var ProgJours = true;
	var ProdJours = true;

	if (option[1] == 'true')
		{BatTotal = true;} 
	if (option[2] == 'true')
		{indestructible = true;}
	if (option[3] == 'true')
		{techno = true; }
	if (option[4] == 'true')
		{flottes = true; }
	if (option[5] == 'true')
		{Def = true; }
	if (option[6] == 'true')
		{VaisseauxVol  = true;}
	if (option[7] == 'true')
		{pointLune = true;}
	if (option[8] == 'true')
		{sauterLignePourPourcentageFlotteVol = true;}
	if (option[9] == 'true')
		{progression = true;}
	if (option[10] == 'true')
		{PlusieurSurMemeUni  = true; }
	if (option[11] == 'true')
		{couleurPoint = true; }
	if (option[12] == 'false')
		{ProgJours = false; }
	if (option[13] == 'false')
		{ProdJours = false; }

		if (PlusieurSurMemeUni)
		{pseudo = GM_getValue("pseudo"+uni+Langue+Langue2,'');}


	if(BatTotal)
	{
		var AutreBat = false;
		var mine = false; 
	}
	else
	{
		var AutreBat = true;
		var mine = true; 
	}
	/* ******************************************************* fin OPTION************************************************************************************/

	/* ******************************Fonctions********************************/
	
	
	//For FireFox2: 
	if (!document.getElementsByClassName) 
	{
		 document.getElementsByClassName = function(clsName) 
		 {
		 	 var retVal = new Array();
		 	 var elements = document.getElementsByTagName("*");
		 	 for(var i = 0;i < elements.length;i++)
			 {
			 	  if(elements[i].className.indexOf(" ") >= 0)
				  {
			 	  	var classes = elements[i].className.split(" ");
			 	  	for(var j = 0;j < classes.length;j++) 
					{
			 	  		if(classes[j] == clsName) retVal.push(elements[i]);
			 	  	}
			 	 } 
				 else if(elements[i].className == clsName) retVal.push(elements[i]);
		 } return retVal; 
	  }
	}
	
	
	
	var draw_pie = function(data)
	  {
			var data_url = data.join(","); 
			
			if( Langue=='se')
			{
				if(mine)
					{var labels_url = "Gruvor|Andra byggnader|Teknologi|Flotta|F'o'rsvar";}
				if(BatTotal)
					{var labels_url = "Byggnader|Teknologi|Flotta|F'o'rsvar";}
			}
			else
			{
				if(mine)
					{var labels_url = Mines+"|"+Other_structure+"|"+Technology+"|"+Fleet+"|"+Defense;}
				if(BatTotal)
					{var labels_url = Structure+"|"+Technology+"|"+Fleet+"|"+Defense;}
			}	
			var google_url = "http://chart.apis.google.com/chart?cht=p3&chf=bg,s,efefef00&chs=270x130&chld=M&&chtt=&chl=" + labels_url + "&chco="+CouleurGraph+"&chd=t:" + data_url;
			var img = document.createElement("img");
			img.setAttribute("src",google_url);
			img.setAttribute("align","top");
			if (!debugGraphique) {img.setAttribute("style", "margin-top:-30px");} 
			return img;
	  }
	  
	function addPoints(nombre)
	{
		if (nombre==0) {return nombre;} 
		else 
		{
			var signe = '';
			if (nombre<0)
			{
				nombre = Math.abs(nombre);
				signe = '-';
			}
			var str = nombre.toString(), n = str.length;
			if (n <4) {return signe + nombre;} 
			else 
			{
				return  signe + (((n % 3) ? str.substr(0, n % 3) + '.' : '') + str.substr(n % 3).match(new RegExp('[0-9]{3}', 'g')).join('.'));
			}
		}
	}

	function pourcent (nombre)
	{	
		if (PointsTotal == 0) 
			{return 0;}
		else
		{	
			var pourcent = parseInt(nombre/PointsTotal*1000)/10;
			return pourcent;
		}	
	}

	function pourcent2(nombre,ref)
	{
		if (ref == 0) 
			{return 0;}
		else
		{
			var pourcent = parseInt(nombre/ref*1000)/10;
			return pourcent;
		}
	}
		
	function trouverInfo(def, init, R, sentence1 ,sentence2)
	{
		var pos1 = (tdnode[f].innerHTML).indexOf(sentence1,10);
		if (pos1>=0) 
		{ 
			var pos2 = (tdnode[f].innerHTML).indexOf(sentence2,pos1+sentence1.length);
			var nombre = (tdnode[f].innerHTML).substring(pos1+sentence1.length,pos2);
			
			nombre = parseInt(nombre.replace(/\./g,'')); 
			if(isNaN(nombre)) nombre = 0;

			var cout= init *(Math.pow(R,nombre)-1)/(R-1) + def *nombre;
			
			return cout;
		}
		else return 0;
	}
		
	/* ******************************************************PAGE DEFENSE*****************************************************************************/ 
	if ((url.indexOf('mode=Verteidigung',0))>=0)
	{	
		var tdnode = document.getElementsByTagName('td');
		var valeur = new Array(2,2,8,37,8,130,10,25,20,100);
		var prix = new Array(0,0,0,0,0,0,0,0,0,0);
		
		for (var f=54; f<tdnode.length ; f++)
		{
			if(tdnode[f].innerHTML.length<1000)
			{
				for(var i =0 ; i< nom_def.length ; i++)
				{
					if ((tdnode[f].innerHTML).indexOf(nom_def[i],0)>=0)
					{	
						prix[i]+=parseInt(trouverInfo(valeur[i],0,0,"("+dispo,")"));
						prix[i]+=parseInt(trouverInfo(valeur[i],0,0,"("," "+dispo));
						//alert (nom_def[i] + ":" + prix[i]);
					}				
				}		
			}	
		}
		
		var PointsDef = 0;
		for(var i =0 ; i< nom_def.length ; i++)
			{PointsDef += prix[i];}

		var DefPlanete = new Array();
		DefPlanete = GM_getValue("DefPlanete"+uni+pseudo+Langue+Langue2,'0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0').split(/;/);
		DefPlanete[numeroplanete] = parseInt(PointsDef);
		GM_setValue("DefPlanete"+uni+pseudo+Langue+Langue2,DefPlanete.join(";"));
	}

	
	/* ****************************************************************** PAGE RECHERCHES*****************************************************************************/ 
	if ((url.indexOf('mode=Forschung',0))>=0) 
	{ 
		var tdnode = document.getElementsByTagName('td');
		var prixInitial = new Array(1.4,1,1,0.8,1,1.2,6,1,6.6,36,0.3,1.4,7,800,16);
		var prix = new Array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
		
		for (var f=54; f<tdnode.length ; f++)
		{
			if(tdnode[f].innerHTML.length<1000)
			{
				for(var i =0 ; i< nom_tech.length ; i++)
				{
					if ((tdnode[f].innerHTML).indexOf(nom_tech[i],0)>=0)
					{	
						prix[i]=trouverInfo(0,prixInitial[i],2,level ,")");
					}
				}
			}
		}
		var pointRecherche=0;
		for(var i =0 ; i< prix.length ; i++)
			{pointRecherche += Math.floor(prix[i]*1000)/1000;}
		if(pointRecherche != 0 )
			{GM_setValue("pointTechnoUni"+uni+pseudo+Langue+Langue2,parseInt(pointRecherche));} // enregistrement si  y'as des chose a enregistrer
	}



	/* ************************************************************PAGE BATIMENTS************************************************************************/
	if ((url.indexOf('/game/index.php?page=b_building',0))>=0) 
	{ 
		var tdnode = document.getElementsByTagName('td');
		var temps=0; 
		
		var prixInitial = new Array(0.075,0.072,0.3,0.105,1.44,0.720,1600,0.7,2,3,4,0.8,150,41,60,80,80,8000);
		var exposant = new Array(1.5,1.6,1.5,1.5,1.8,2,2,2,2,2,2,2,2,2,2,2,2,2)
		var prix = new Array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
		var prod=new Array(0,0,0);
		
		for (var f=54; f<tdnode.length ; f++)
		{
			if(tdnode[f].innerHTML.length<1000)
			{
				for(var i =0 ; i< nom_bat.length ; i++)
				{
				
					if ((tdnode[f].innerHTML).indexOf(nom_bat[i],0)>=0)
					{	
						
						prix[i]=trouverInfo(0,prixInitial[i],exposant[i],level,")");
					
						
						if (i<3) 
						{ // Calcul de la production 
							var sentence1 = level;
							var sentence2 = ")";
							
							var pos1 = (tdnode[f].innerHTML).indexOf(sentence1,10);
							if (pos1>=0) 
							{ 
								var pos2 = (tdnode[f].innerHTML).indexOf(sentence2,pos1+sentence1.length);
								var nombre = (tdnode[f].innerHTML).substring(pos1+sentence1.length,pos2);
								nombre = parseInt(nombre.replace(/\./g,'')); 
								if(isNaN(nombre)) nombre = 0;
								
								prod[i] += (3-i)*10*nombre*Math.pow(1.1,nombre);	
								
							}			
						}
						
					}		
				}
			}
		}
		

		
				for(var i=0 ; i<speeduniX2.length ; i++)
				{
					if (uni == speeduniX2[i]) // si speeduni on baisse la nanite
					{
						prod[0]=prod[0]*2;
						prod[1]=prod[1]*2;
						prod[2]=prod[2]*2;
					}
				}
				for(var i=0 ; i<speeduniX5.length ; i++)
				{
					if (uni == speeduniX5[i])
					{
						prod[0]=prod[0]*5;
						prod[1]=prod[1]*5;
						prod[2]=prod[2]*5;
					}
				}	
				
			
		
		var prixMines=Math.round(prix[0]+prix[1]+prix[2]);
		
		var prixBatiment=0;
		for(var i =3 ; i< nom_bat.length ; i++)
			{prixBatiment+=Math.floor(prix[i]*1000)/1000;}
		prixBatiment=Math.round(prixBatiment);

		ProdPlanete = GM_getValue("ProdPlanete"+uni+pseudo+Langue+Langue2,'0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0').split(/;/);
		ProdPlanete[numeroplanete] = prod[0]+'/'+prod[1]+'/'+prod[2];
		GM_setValue("ProdPlanete"+uni+pseudo+Langue+Langue2,ProdPlanete.join(";"));
		
		var BatPlanete = new Array(); // recuperation des données
		BatPlanete = GM_getValue("BatPlanete"+uni+pseudo+Langue+Langue2,'0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0').split(/;/);
		BatPlanete[2*(numeroplanete+1)-1] = prixBatiment;
		BatPlanete[2*(numeroplanete+1)-2] = prixMines;
		GM_setValue("BatPlanete"+uni+pseudo+Langue+Langue2,BatPlanete.join(";"));
	}

	
	/* ***********************************************************************VUE GENERALE************************************************************************/
	if ((url.indexOf('/game/index.php?page=overview',0))>=0) 
	{ 

		var thnode = document.getElementsByTagName('th');
		var tdnode = document.getElementsByTagName('td');
		var f=0; 
		/* ******************************Récuperation pseudo********************************/
		
			for (f=0 ; f<tdnode.length ; f++)
			{
				if ((tdnode[f].innerHTML).indexOf(">"+Planet,0)>=0)
				{
					var sentence1 = '(';
					var sentence2 = ")";
					var pos1 = (tdnode[f].innerHTML).indexOf(sentence1,0);
					if (pos1>=0)
					{
						var pos2 = (tdnode[f].innerHTML).indexOf(sentence2,pos1+sentence1.length);
						var pseudosign = (tdnode[f].innerHTML).substring(pos1+1,pos2);
						GM_setValue("pseudo"+uni+Langue+Langue2,pseudosign);
					}
				}
			}
		
		pseudosign = GM_getValue("pseudo"+uni+Langue+Langue2,'');

		if (PlusieurSurMemeUni)
		{
			pseudo=pseudosign;
		}
		
		
		
		/* ******************************Calculs des points********************************/
		var PointsMinesTotal=0;
		var PointsBatimentsTotal=0;
		var PointsDefTotal=0;
		
		var PointsMines= new Array();
		var PointsBat= new Array();
		
		var BatPlanete = new Array();
		BatPlanete = GM_getValue("BatPlanete"+uni+pseudo+Langue+Langue2,'0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0').split(/;/);
		
		var DefPlanete = new Array();
		DefPlanete = GM_getValue("DefPlanete"+uni+pseudo+Langue+Langue2,'0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0').split(/;/);
		var pointLuneTotal = 0;

		for (var i = 0; i < 2*nombreplanetesetlunes; i++) 
		{
			if (i%2 == 0) // Si i est pair c'est une mine
			{
				PointsMinesTotal += parseInt(BatPlanete[i]);
				if (parseInt(BatPlanete[i]) == 0 && pointLune)
				{
					pointLuneTotal += parseInt(BatPlanete[i+1]);
				}
			} 
				
			if(i%2 == 1) // si i est impair
				{PointsBatimentsTotal += parseInt(BatPlanete[i]);} // c'est un bat
			
			if (i < nombreplanetesetlunes)
				{PointsDefTotal += parseInt(DefPlanete[i]);}
		}
		
		var PointsTechno=GM_getValue("pointTechnoUni"+uni+pseudo+Langue+Langue2,0);
		var temps=0; 

		var prixVaisseauxVol =0;
		var vaisseaux = new Array();
		
		/* ******************************Récuperation point total********************************/
		for (f=0 ; f<thnode.length ; f++)
		{
			if ((thnode[f].innerHTML).indexOf(rank,0)>=0)
			{
				var sentence1 = "";
				var sentence2 = " ("+rank;
				var pos1 = (thnode[f].innerHTML).indexOf(sentence1,0);
				if (pos1>=0)
				{
					var pos2 = (thnode[f].innerHTML).indexOf(sentence2,pos1+sentence1.length);
					var PointsTotal = (thnode[f].innerHTML).substring(pos1+0,pos2);
					PointsTotal=PointsTotal.replace(/\./g,'');
					
					var PointsFlotteTotal = PointsTotal-PointsTechno-PointsMinesTotal-PointsBatimentsTotal-PointsDefTotal;
				}
			}
			/* ******************************Calculs Point flotte en vol ********************************/
			if (((thnode[f].innerHTML).indexOf("return own",0)>=0 || (thnode[f].innerHTML).indexOf("flight owndeploy",0)>=0) && VaisseauxVol)
			{ // Comptage des points vaisseau en vol, en comptant les flottes sur le retour + les stationnés
				var sentence1 = "<b>";
				var sentence2 = "</b>";
				var pos1 = (thnode[f].innerHTML).indexOf(sentence1,0);
				if (pos1>=0)
				{
					var pos2 = (thnode[f].innerHTML).indexOf(sentence2,pos1+sentence1.length);
					var flottes = (thnode[f].innerHTML).substring(pos1+3,pos2);
					var vaisseaux = flottes.split(/<br>/);
					
					var prix_vaisseau = new Array(4,12,4,10,29,60,40,18,1,90,125,10000,85);
				
					for (var i=0 ; i<vaisseaux.length ; i++)
					{
						for(var j = 0 ; j<nom_flotte.length ; j++)
						{
							if (vaisseaux[i].indexOf(nom_flotte[j],0)>=0)
								{prixVaisseauxVol += prix_vaisseau[j] * vaisseaux[i].replace(/\./g,'').replace(nom_flotte[j]+' ','');}
						}
					}
				}			
			}
		}
		
		
		/* ******************************Calcul Prod ********************************/
		ProdPlanete = GM_getValue("ProdPlanete"+uni+pseudo+Langue+Langue2,'0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0').split(/;/);
		var prod = new Array(0,0,0);
		for (var i = 0; i < nombreplanetesetlunes; i++) 
		{
			prod[0]+=parseInt(ProdPlanete[i].split('/')[0]);
			prod[1]+=parseInt(ProdPlanete[i].split('/')[1]);
			prod[2]+=parseInt(ProdPlanete[i].split('/')[2]);
		}
		prod[0]= Math.round(prod[0]*24/1000);
		prod[1]= Math.round(prod[1]*24/1000);
		prod[2]= Math.round(prod[2]*24/1000);
		
		/* ******************************Récuperation des données de reférence********************************/
		date = new Date()+ '';
		
		var dates = new Array();
		dates = date.split(/ /);
		date = dates[1] +' '+dates[2];
		
		var PointRef = GM_getValue("PointRef"+uni+pseudo+Langue+Langue2,PointsTotal+';'+date+';'+PointsMinesTotal+';'+PointsBatimentsTotal+';'+PointsTechno+';'+PointsFlotteTotal+';'+PointsDefTotal+';true').split(/;/);
			
		if(PointRef[7]== 'true') // Si y'avais rien d'enregistré on enregistre
		{
			GM_setValue("PointRef"+uni+pseudo+Langue+Langue2,PointsTotal+';'+date+';'+PointsMinesTotal+';'+PointsBatimentsTotal+';'+PointsTechno+';'+PointsFlotteTotal+';'+PointsDefTotal+';false;'+PointsTotal+';'+Date.parse(new Date()) / 1000);
		}

		if (PointRef[9]==undefined)
		{
			PointRef[8] = PointsTotal;
			PointRef[9] = Date.parse(new Date()) / 1000;
			GM_setValue("PointRef"+uni+pseudo+Langue+Langue2,PointRef.join(";"));
		}
		
		/* ****************************** BBCode + nb colone pour graphique ********************************/
		var BBcode=BBcode_debut+"[b][color=#ff0000]"+addPoints(PointsTotal)+"[/color][/b][/size]\n";
		var nbAfficher=0;
		if(mine) 
		{
			nbAfficher++;
			BBcode+=BBcode_mine+"[b][color=#ff0000]"+addPoints(PointsMinesTotal)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsMinesTotal)+"[/color][/b] %)\n";
		}
		if(AutreBat) 
		{
			nbAfficher++;
			BBcode+=BBcode_bat+"[b][color=#ff0000]"+addPoints(PointsBatimentsTotal)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsBatimentsTotal)+"[/color][/b] %)\n";
		}
		if(BatTotal) 
		{
			nbAfficher++;
			BBcode+=BBcode_batT+"[b][color=#ff0000]"+addPoints(PointsMinesTotal+PointsBatimentsTotal)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsMinesTotal+PointsBatimentsTotal)+"[/color][/b] %) \n";
		}
		
		BBcode+=BBcode_fin1+"[b][color=#ff0000]"+addPoints(PointsTechno)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsTechno)+"[/color][/b] %)\n";;
		BBcode+=Bcode_fin2+"[b][color=#ff0000]"+addPoints(PointsFlotteTotal)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsFlotteTotal)+"[/color][/b] %) \n";
		BBcode+=BBcode_fin3+="[b][color=#fF0000]"+addPoints(PointsDefTotal)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsDefTotal)+"[/color][/b] %) \n\n";
		BBcode+=BBcode_fin4+="[b][color=#ff0000]"+addPoints(PointsMinesTotal+PointsBatimentsTotal+PointsTechno)+"[/color][/b] ("+soit+" [b][color=#ff0000]"+pourcent(PointsMinesTotal+PointsBatimentsTotal+PointsTechno)+"[/color][/b] %)";
		BBcode+=BBcode_fin5;	
		BBcode+=BBcode_fin6+addPoints(Math.round((PointsTotal- PointRef[8])/((Date.parse(new Date())/1000-PointRef[9])/(3600*24))))+ ' '+Point_day+' \n';
		BBcode+="Uni : [b][color=#ff0000]"+ uni+"[/color][/b][/center][/quote]";
		
		if(techno) nbAfficher++;
		if(flottes) nbAfficher++;
		if(Def) nbAfficher++;
		if(indestructible) nbAfficher++;
		
		/* ****************************** options ********************************/
		var br = '';
		if (!sauterLignePourPourcentageFlotteVol)
			{br ='<br/>';}
		
		var flottesEnVol = '';
		if(VaisseauxVol && PointsFlotteTotal>0) 
			{flottesEnVol = ' '+br+parseInt(prixVaisseauxVol/PointsFlotteTotal*1000)/10+' % '+en_vol;}
			
		var affichePointLune ='';
		if (pointLune && AutreBat)
			{affichePointLune = ' '+br+parseInt(pointLuneTotal/PointsBatimentsTotal*1000)/10+' % '+sur_lune;}
		else if (pointLune && BatTotal)
			{affichePointLune = ' '+br+parseInt(pointLuneTotal/(PointsMinesTotal+PointsBatimentsTotal)*1000)/10+' % '+sur_lune;}
		
		/* ****************************** Etablissement des couleurs ********************************/
		
		var Color_mine= '';
		var Color_autreBat= '';
		var Color_batTotal= '';
		var Color_techno= '';
		var Color_flotte= '';
		var Color_def= '';
		var Color_indestr= '';
		var Color_prog= '';
		
		if	(couleurPoint)
		{
			if(PointsMinesTotal>parseInt(PointRef[2])+1) 			{Color_mine= 'style="color: #00FF00;"';}
			else if (PointsMinesTotal<parseInt(PointRef[2]) -1) 	{Color_mine= 'style="color: #FF0000;"';}
			
			if( PointsBatimentsTotal>parseInt(PointRef[3])+1) 		{Color_autreBat= 'style="color: #00FF00;"';}
			else if (PointsBatimentsTotal<parseInt(PointRef[3])-1) 	{Color_autreBat= 'style="color: #FF0000;"';}
			
			if((PointsMinesTotal+PointsBatimentsTotal)>(parseInt(PointRef[2])+parseInt(PointRef[3])+1)) 			{Color_batTotal= 'style="color: #00FF00;"';}
			else if ((PointsMinesTotal+PointsBatimentsTotal)<(parseInt(PointRef[2])+parseInt(PointRef[3])) -1)  	{Color_batTotal= 'style="color: #FF0000;"';}
			
			if( PointsTechno>parseInt(PointRef[4])+1) 			{Color_techno= 'style="color: #00FF00;"';}
			else if (PointsTechno<parseInt(PointRef[4]) -1) 		{Color_techno= 'style="color: #FF0000;"';}
			
			if( PointsFlotteTotal>parseInt(PointRef[5])+1) 		{Color_flotte= 'style="color: #00FF00;"';}
			else if (PointsFlotteTotal<parseInt(PointRef[5]) -1) 	{Color_flotte= 'style="color: #FF0000;"';}
			
			if( PointsDefTotal>parseInt(PointRef[6])+1)			{Color_def= 'style="color: #00FF00;"';}
			else if (PointsDefTotal<parseInt(PointRef[6]) -1) 		{Color_def= 'style="color: #FF0000;"';}
			
			if((PointsMinesTotal+PointsBatimentsTotal+PointsTechno)>(parseInt(PointRef[2])+parseInt(PointRef[3])+parseInt(PointRef[4])+1)) 			{Color_indestr= 'style="color: #00FF00;"';}
			else if((PointsMinesTotal+PointsBatimentsTotal+PointsTechno)<(parseInt(PointRef[2])+parseInt(PointRef[3])+parseInt(PointRef[4]) -1)) 	{Color_indestr= 'style="color: #FF0000;"';}

			if( PointsTotal>parseInt(PointRef[0])+1) 				{Color_prog= 'style="color: #00FF00;"';}
			else if (PointsTotal<parseInt(PointRef[0]) -1) 		{Color_prog= 'style="color: #FF0000;"';}
		}	
		
		
		/* ****************************** Affichage ********************************/
		
		var tr_evenements = document.getElementsByTagName('tbody')[5];
		var tr1 = tr_evenements.appendChild(document.createElement('tr'));
		tr1.innerHTML = '<td class="c" colspan="4" width="96%">'+Pointdetails+'</td><td style="background-color:transparent;"><a TITLE="'+AffBBcode+'";><img id="copybbcode" style="margin-left:-20px; position:relative;" src="data:image/gif;base64,R0lGODlhEAAQAPUAAChsKDA8EdrtwXvEApjWAYnNAur13EZRKoPJAidsJ8PjmJPTAcTxAIzDSJ3ZAbjJmqPdAZPKTJrVGozMHKfgAbvsALXoAHWRCXTAAqviAa/YepnMRFxlQ73hipSahLrgfJTQJ6ncN63If7PbfKPYOMHhl7HmALbch5+lkXS2BIekB4mtBni3BJTLRGu6AnmTCYzHPpS2Sc7t3AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAADIALAAAAAAQABAAAAaOQJlwSCwaE4Bk0igERAzQaARQBDQE2Cy2kSA2FJ3OY1xSmGFDp2b0EXk8qI/m1KLKAK4BiBQKxTgcIAMYdgAYKQEBB4sHiQgDhQMsiZSUBQiRBQsEGSYqiQQFkE0IBQQQK5QUDguYQxOmEBcXLwyrBRNEABsLDhUMwBALG3ZpEpwWFRYEEsVFSEpdTNNFQQA7"/></a></td>';
		// On rajoute la zone de texte, mais invisible :
		var tr_zone_txt_invis = tr_evenements.appendChild(document.createElement('tr'));
		tr_zone_txt_invis.setAttribute('id', 'zonecode');
		tr_zone_txt_invis.setAttribute('style', 'display:none;');
		tr_zone_txt_invis.innerHTML='<td colspan="4" width="96%"><textarea cols="20" onClick="javascript:this.select();">'+BBcode+'</textarea></td>';

		if(mine)
		{
			var tr_mine = tr_evenements.appendChild(document.createElement('tr'));
			tr_mine.innerHTML='<th width="60px" colspan="1">'+Mines+'</th><th colspan=\"2\" ><a '+Color_mine+' TITLE="'+addPoints(Math.round(PointsMinesTotal-parseInt(PointRef[2])))+' '+Points+' ('+pourcent2(PointsMinesTotal-parseInt(PointRef[2]),PointsMinesTotal)+' %)";>'+addPoints(PointsMinesTotal)+' ( '+pourcent(PointsMinesTotal)+' % ) </a></th><th id="piebox" rowspan="'+nbAfficher+'"></th>';
		}
		if(AutreBat)
		{
			var tr_AutreBat = tr_evenements.appendChild(document.createElement('tr'));
			tr_AutreBat.innerHTML='<th width="60px" colspan="1">'+Other_structure+'</th><th colspan=\"2\" ><a '+Color_autreBat+' TITLE="'+addPoints(Math.round(PointsBatimentsTotal-parseInt(PointRef[3])))+' '+Points+'  ('+pourcent2(PointsBatimentsTotal-parseInt(PointRef[3]),PointsBatimentsTotal)+' %)";>'+addPoints(PointsBatimentsTotal)+' ( '+pourcent(PointsBatimentsTotal)+' % )  </a>'+affichePointLune+'</th>';
		}
		if(BatTotal)
		{
			var tr_BatTotal = tr_evenements.appendChild(document.createElement('tr'));
			tr_BatTotal.innerHTML='<th width="60px" colspan="1">'+Structure+'</th><th colspan=\"2\" ><a '+Color_batTotal+' TITLE="'+addPoints(Math.round((PointsMinesTotal+PointsBatimentsTotal)-(parseInt(PointRef[2])+parseInt(PointRef[3]))))+' '+Points+' ('+pourcent2((PointsMinesTotal+PointsBatimentsTotal)-(parseInt(PointRef[2])+parseInt(PointRef[3])),PointsMinesTotal+PointsBatimentsTotal)+' %)";>'+addPoints(PointsMinesTotal+PointsBatimentsTotal)+' ( '+pourcent(PointsMinesTotal+PointsBatimentsTotal)+' % )  </a>'+affichePointLune+' </th><th id="piebox" rowspan="'+nbAfficher+'"></th>';
		}
		if(techno)
		{
			var tr_techno = tr_evenements.appendChild(document.createElement('tr'));
			tr_techno.innerHTML='<th width="60px" colspan="1"><a href="http://uni'+uni+'.'+Langue2+'ogame.'+Langue+'/game/index.php?page=statistics&session='+session+'&who=player&type=research&start=-1&sort_per_member=0">'+Technology+'</a></th><th colspan=\"2\" ><a '+Color_techno+' TITLE="'+addPoints(Math.round(PointsTechno-parseInt(PointRef[4])))+' '+Points+' ('+pourcent2(PointsTechno-parseInt(PointRef[4]),PointsTechno)+' %)";>'+addPoints(PointsTechno)+' ( '+pourcent(PointsTechno)+' % ) </a></th>';
		}
		if(flottes)
		{
			var tr_flottes = tr_evenements.appendChild(document.createElement('tr'));
			tr_flottes.innerHTML='<th width="60px" colspan="1"><a href=http://uni'+uni+'.'+Langue2+'ogame.'+Langue+'/game/index.php?page=statistics&session='+session+'&who=player&type=fleet&start=-1&sort_per_member=0">'+Fleet+'</a></th><th colspan=\"2\"><a '+Color_flotte+' TITLE="'+addPoints(Math.round(PointsFlotteTotal-parseInt(PointRef[5])))+' '+Points+' ('+pourcent2(PointsFlotteTotal-parseInt(PointRef[5]),PointsFlotteTotal)+' %)";>'+addPoints(PointsFlotteTotal) + ' ( '+pourcent(PointsFlotteTotal)+' % ) </a>'+flottesEnVol+'</th>';
		}
		if(Def)
		{
			var tr_Def = tr_evenements.appendChild(document.createElement('tr'));
			tr_Def.innerHTML='<th width="60px" colspan="1">'+Defense+'</th><th colspan=\"2\" ><a '+Color_def+' TITLE="'+addPoints(Math.round(PointsDefTotal-parseInt(PointRef[6])))+' '+Points+' ('+pourcent2(PointsDefTotal-parseInt(PointRef[6]),PointsDefTotal)+' %)";>'+addPoints(PointsDefTotal)+' ( '+pourcent(PointsDefTotal)+' % ) </a></th>';
		}
		if(indestructible)
		{
			var tr_indestructible = tr_evenements.appendChild(document.createElement('tr'));
			tr_indestructible.innerHTML='<th width="60px" colspan="1">'+Indestructible+'</th><th colspan=\"2\" ><a '+Color_indestr+' TITLE="'+addPoints(Math.round((PointsMinesTotal+PointsBatimentsTotal+PointsTechno)-(parseInt(PointRef[2])+parseInt(PointRef[3])+parseInt(PointRef[4]))))+' '+Points+' ('+pourcent2((PointsMinesTotal+PointsBatimentsTotal+PointsTechno)-(parseInt(PointRef[2])+parseInt(PointRef[3])+parseInt(PointRef[4])),PointsMinesTotal+PointsBatimentsTotal+PointsTechno)+' %)";>'+addPoints(PointsMinesTotal+PointsBatimentsTotal+PointsTechno)+' ( '+pourcent(PointsMinesTotal+PointsBatimentsTotal+PointsTechno)+' % ) </a></th>';
		}

		if (progression)
		{		
			var tr_progression = tr_evenements.appendChild(document.createElement('tr'));
			tr_progression.innerHTML = '<th width="60px" colspan="1" >'+Progression+'</th><th colspan=\"3\" ><a '+Color_prog+' TITLE="";>'+addPoints(Math.round(PointsTotal-parseInt(PointRef[0])))+' '+Points+' ('+soit+' ' +Math.round((PointsTotal-PointRef[0])/PointRef[0]*1000)/10 +' % ) '+Depuis+' '+PointRef[1]+'</th><td style="background-color:transparent;"><a TITLE="'+restart+'";><img id="pointRef" style="margin-left:-20px; position:relative;" src="data:image/gif;base64,R0lGODlhEAAQAPUAAChsKDA8EdrtwXvEApjWAYnNAur13EZRKoPJAidsJ8PjmJPTAcTxAIzDSJ3ZAbjJmqPdAZPKTJrVGozMHKfgAbvsALXoAHWRCXTAAqviAa/YepnMRFxlQ73hipSahLrgfJTQJ6ncN63If7PbfKPYOMHhl7HmALbch5+lkXS2BIekB4mtBni3BJTLRGu6AnmTCYzHPpS2Sc7t3AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAADIALAAAAAAQABAAAAaOQJlwSCwaE4Bk0igERAzQaARQBDQE2Cy2kSA2FJ3OY1xSmGFDp2b0EXk8qI/m1KLKAK4BiBQKxTgcIAMYdgAYKQEBB4sHiQgDhQMsiZSUBQiRBQsEGSYqiQQFkE0IBQQQK5QUDguYQxOmEBcXLwyrBRNEABsLDhUMwBALG3ZpEpwWFRYEEsVFSEpdTNNFQQA7"/></a></td>';	
		}
		
		if (ProgJours)
		{		
			var tr_ProgJours = tr_evenements.appendChild(document.createElement('tr'));
			tr_ProgJours.innerHTML = '<th width="60px" colspan="1" >'+Moyenne+'</th><th colspan=\"3\" >'+addPoints(Math.round((PointsTotal- PointRef[8])/((Date.parse(new Date())/1000-PointRef[9])/(3600*24))))+ ' '+Point_day+'</td>';	
		}
		if (ProdJours)
		{		
			var tr_ProdJours = tr_evenements.appendChild(document.createElement('tr'));
			tr_ProdJours.innerHTML = '<th width="60px" colspan="1" >'+Production+'</th><th colspan=\"3\" >'+addPoints(Math.round(prod[0]+prod[1]+prod[2]))+ ' ('+addPoints(Math.round(prod[0]))+' / '+addPoints(Math.round(prod[1]))+' / '+addPoints(Math.round(prod[2]))+' ) '+Point_day+'</td>';	
		}

		
		var totalCompte= BBcode_debut.substring(BBcode_debut.indexOf("size=16]",0)+8,BBcode_debut.length);

		
/*		
		var signature ='<form action="http://vulca.olympe-network.com/signature.php?langue=en"  target="_blank" method="post">';	
	
		signature+=	'<textarea name="pseudo" style="display:none;">'+pseudosign+'</textarea>';
		signature+=	'<textarea name="uni" style="display:none;">'+uni+'</textarea>';
		signature+=	'<textarea name="langue" style="display:none;">'+Langue2+Langue+'</textarea>';

		signature+=	'<textarea name="total" style="display:none;">'+addPoints(PointsTotal)+'</textarea>';
		signature+=	'<textarea name="mine" style="display:none;">'+addPoints(PointsMinesTotal)+' '+Points+' ( '+pourcent(PointsMinesTotal,PointsTotal)+' % )</textarea>';
		signature+=	'<textarea name="bat" style="display:none;">'+addPoints(PointsBatimentsTotal)+' '+Points+' ( '+pourcent(PointsBatimentsTotal,PointsTotal)+' % )</textarea>';
		signature+=	'<textarea name="techno" style="display:none;">'+addPoints(PointsTechno)+' '+Points+' ( '+pourcent(PointsTechno,PointsTotal)+' % )</textarea>';
		signature+=	'<textarea name="flotte" style="display:none;">'+addPoints(PointsFlotteTotal) + ' '+Points+' ( '+pourcent(PointsFlotteTotal,PointsTotal)+' % )</textarea>';
		signature+=	'<textarea name="def" style="display:none;">'+addPoints(PointsDefTotal)+' '+Points+' ( '+pourcent(PointsDefTotal,PointsTotal)+' % ) </textarea>';

		signature+=	'<textarea name="totalt" style="display:none;">'+totalCompte+'</textarea>';
		signature+=	'<textarea name="minet" style="display:none;">'+Mines+'</textarea>';
		signature+=	'<textarea name="batt" style="display:none;">'+Other_structure+'</textarea>';
		signature+=	'<textarea name="technot" style="display:none;">'+Technology+'</textarea>';
		signature+=	'<textarea name="flottet" style="display:none;">'+Fleet+ '</textarea>';
		signature+=	'<textarea name="deft" style="display:none;">'+Defense+'</textarea>';
	

	signature+='<input type="submit" value="'+creeSign+'" /></form>';
	
	var tr_sign = tr_evenements.appendChild(document.createElement('tr'));
	tr_sign.innerHTML = '<td width="60px" colspan="4" >'+signature+'</th>';
*/

		
		
		/* ****************************** Affichage du graphique ********************************/
		if (mine)
			{var pie = draw_pie([pourcent(PointsMinesTotal),pourcent(PointsBatimentsTotal),pourcent(PointsTechno),pourcent(PointsFlotteTotal),pourcent(PointsDefTotal)]);}
		else if(BatTotal)
			{var pie = draw_pie([pourcent(PointsMinesTotal+PointsBatimentsTotal),pourcent(PointsTechno),pourcent(PointsFlotteTotal),pourcent(PointsDefTotal)]);}
		var piebox = document.getElementById('piebox');		
		piebox.appendChild(pie);
		
		/* ****************************** BBcode ouvrant/fermant ********************************/
		var imgbbcode=document.getElementById("copybbcode");
		imgbbcode.addEventListener("click", function(event) 
		{
			var cellule = document.getElementById('zonecode');
			if (cellule.style.display == 'none') 
				{cellule.style.display = '';}
			else 
				{cellule.style.display = 'none';}
		}, true);
		
		/* ****************************** RaZ progression ********************************/
		document.getElementById("pointRef").addEventListener("click", function(event) 
		{
			if(confirm(Avertissement)) 
			{	
				GM_setValue("PointRef"+uni+pseudo+Langue+Langue2,PointsTotal+';'+date+';'+PointsMinesTotal+';'+PointsBatimentsTotal+';'+PointsTechno+';'+PointsFlotteTotal+';'+PointsDefTotal+';false;'+PointRef[8]+';'+PointRef[9]);
			}	
		}, true);
	}
}


	