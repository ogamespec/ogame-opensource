// ==UserScript==
// @name           DetailedStats
// @namespace      vulca
// @description    Old OGame Detailed Statistics
// @include        http://*.oldogame.*game/index.php?page=b_building*
// @include        http://*.oldogame.*game/index.php?page=buildings*
// @include        http://*.oldogame.*game/index.php?page=overview*
// @include        http://*.oldogame.*game/index.php?page=options*
// ==/UserScript==


/*===============================================================================================
 DERNIERE MISE A JOUR : 07/05/2009
 TOPIC DU FORUM OFFICIEL : http://board.ogame.fr/thread.php?postid=8634035#post8634035
 SCRIPT POUR OGAME.FR v 0.83

Адаптация для OldOgame - ogamespec@gmail.com, 2011.
 

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
var Langue='ru';
var Langue2='';

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
	MatchTab = url.match('uni([0-9]{1,2}).'+Langue2+'oldogame.'+Langue+'.game.index.php.page=([a-zA-Z0-9_]+)(?:&dsp=1)?(?:&no_header=1)?&session=([a-z0-9]{12})(?:&mode=([a-zA-Z0-9]+))?');
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
						BOUTTON[0].innerHTML = '<a href="http://uni'+uni+'.'+Langue2+'oldogame.'+Langue+'/game/index.php?page=options&session='+session+'&infocompte_plus=oui">'+done+'</a>';

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
					BOUTTON[1].innerHTML = '<a href="http://uni'+uni+'.'+Langue2+'oldogame.'+Langue+'/game/index.php?page=options&session='+session+'&infocompte_plus=oui">'+done+'</a>';
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
				New_Table.innerHTML = '<tr><td colspan="2" class="c"><a href="http://uni'+uni+'.'+Langue2+'oldogame.'+Langue+'/game/index.php?page=options&session='+session+'&infocompte_plus=oui">'+ICoption+'</a></td></tr>';
					
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
		
		for (var f=44; f<tdnode.length ; f++)
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
		
		for (var f=44; f<tdnode.length ; f++)
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
		
		for (var f=44; f<tdnode.length ; f++)
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
		
		var tr_evenements = document.getElementsByTagName('tbody')[4];
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
			tr_techno.innerHTML='<th width="60px" colspan="1"><a href="http://uni'+uni+'.'+Langue2+'oldogame.'+Langue+'/game/index.php?page=statistics&session='+session+'&who=player&type=research&start=-1&sort_per_member=0">'+Technology+'</a></th><th colspan=\"2\" ><a '+Color_techno+' TITLE="'+addPoints(Math.round(PointsTechno-parseInt(PointRef[4])))+' '+Points+' ('+pourcent2(PointsTechno-parseInt(PointRef[4]),PointsTechno)+' %)";>'+addPoints(PointsTechno)+' ( '+pourcent(PointsTechno)+' % ) </a></th>';
		}
		if(flottes)
		{
			var tr_flottes = tr_evenements.appendChild(document.createElement('tr'));
			tr_flottes.innerHTML='<th width="60px" colspan="1"><a href=http://uni'+uni+'.'+Langue2+'oldogame.'+Langue+'/game/index.php?page=statistics&session='+session+'&who=player&type=fleet&start=-1&sort_per_member=0">'+Fleet+'</a></th><th colspan=\"2\"><a '+Color_flotte+' TITLE="'+addPoints(Math.round(PointsFlotteTotal-parseInt(PointRef[5])))+' '+Points+' ('+pourcent2(PointsFlotteTotal-parseInt(PointRef[5]),PointsFlotteTotal)+' %)";>'+addPoints(PointsFlotteTotal) + ' ( '+pourcent(PointsFlotteTotal)+' % ) </a>'+flottesEnVol+'</th>';
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
