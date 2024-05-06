# Игровые страницы

В данном разделе находится информация по игровым страницам Вселенной (`game`).

## Точки входа

Не очень удобно, но так вышло, что ванильная OGame содержит несколько точек входа, кроме собственно основной `index.php`.

|Точка входа|Описание|
|-----------|--------|
|ainfo.php|Внешняя страница для просмотра информации об альянсах (можно смотреть без входа в игру); Перенаправляется в `pages/ainfo/php` |
|index.php|Основная точка входа |
|maintenance.php|Это уже мы придумали, в оригинале не было. Показывается когда Вселенная поставлена на паузу (этой фичи тоже не было в оригинале) |
|pic.php|Все ссылки на внешние картинки проходят через этот скрипт, для возможной фильтрации формата и контента изображений |
|pranger.php|Внешняя страница для Столба Позора (можно смотреть без входа в игру); Перенаправляется в `pages/pranger.php` |
|redir.php|Для перенаправления на внешние ресурсы с возможной фильтрацией |
|validate.php|Используется для активации аккаунта. Ссылка активации отправляется на почту |

Система регистрации рассматривается отдельно.

## Pages

|Страница (`$_GET['page']`)|Параметры GET|Параметры POST|
|--------|-------------|--------------|
|ainfo|allyid | - |
|allianzdepot|cp, session |cXXX (stationed fleet index) |
|allianzen|cp, session, a, weiter=1, d(rank_id, settings mode), t=1,2,3(text type) |bcancel, tag, name, suchtext, r, text, newrang, newtag, newname, newrangname, uXrY=on (X: rank_id, Y:0...8), hp, logo, fname(Name of the founder's rank), bew, bewforce |
|b_building|cp, session, modus=[add, destroy, remove], planet, techid, listid | - |
|bericht|cp, session, bericht | - |
|bewerben|cp, session, allyid |weiter=loca(Template),loca(Submit), text |
|bewerbungen|cp, session, show, sort |aktion=loca(Accept),loca(Reject), text |
|buddy|cp, session, buddy_id, action=1,2,3,4,5,6,7,8 |text |
|buildings|cp, session, mode=[Forschung, Flotte, Verteidigung], bau, unbau |fmenge |
|changelog|cp, session | - |
|fleet_templates|cp, session, mode=["delete"], id | mode=["save"], template_id, template_name, ship[n] |
|flotten1|cp, session, galaxy, system, planet, planettype, target_mission |order_return, union_name, flotten, user_name, order_union |
|flotten2|cp, session |target_mission, target_galaxy, target_system, target_planet, target_planettype, shipXXX, consumptionXXX, speedXXX, capacityXXX |
|flotten3|cp, session |galaxy, system, planet, planettype, thisgalaxy, thissystem, thisplanet, thisplanettype, speedfactor, shipXXX, consumptionXXX, speedXXX, capacityXXX, speed, target_mission, union2 |
|flottenversand|session, ajax |speed, resource1, resource2, resource3, shipXXX, order, thisgalaxy, thissystem, thisplanet, thisplanettype, galaxy, system, planet, planettype, expeditiontime, holdingtime, union2 |
|flottenversand (AJAX)| - |order, galaxy, system, planet, planettype, shipcount |
|galaxy|cp, session, pdd, galaxy, p1, system, p2, position, p3, mode, zp |aktion, anz, pziel, galaxy, system, position, systemLeft, systemRight, galaxyLeft, galaxyRight |
|imperium|cp, session, modus=["add", "destroy", "remove"], planettype | - |
|infos|cp, session, gid |aktion, ab502, ab503 |
|logout|session | - |
|messages|pm, cp, session |deletemessages=["deleteall", "deletemarked", "deletenonmarked", "deleteallshown"], sneakXXX, delmesXXX, fullreports=on, espioopen=on, combatopen=on, expopen=on, allyopen=on, useropen=on, generalopen=on |
|micropayment|cp, session, buynow, type, days | - |
|notizen|a, n, session |betreff, text, s=1,2, u, n, delmes |
|options|cp intval, session |urlaub_aus=on, validate, db_email, db_password, db_character, newpass1, db_password, db_email, urlaubs_modus=on, db_deaktjava=on, dpath, design=on, lang, settings_sort, settings_order, noipcheck, spio_anz, settings_fleetactions, settings_esp=on, settings_wri=on, settings_bud=on, settings_mis=on, settings_rep=on, settings_folders=on, hide_go_email=on |
|overview|cp intval, session, lgn=1 | - |
|payment|cp intval, session |couponcode=/[\-0-9A-Z]{24}/, action=["check", "activate"] |
|phalanx|cp, session, spid | - |
|pranger|cp, session, from | - |
|renameplanet|cp, session |aktion=loca(Rename),loca(Abandon),loca(Delete), newname, pw, deleteid |
|resources|cp, session |last1, last2, last3, last4, last12, last212 |
|sprungtor|cp, session |qm, zm, cXXX (ships) |
|statistics|cp, session, start, type, who, sort_per_member | start, type, who, sort_per_member |
|suche|cp, session |type=[playername, planetname, allytag, allyname], searchtext |
|techtree|cp, session | - |
|techtreedetails|cp, session, tid | - |
|trader|cp, session |offer_id, call_trader, trade, 1_value, 2_value, 3_value |
|writemessages|cp, messageziel, betreff, gesendet=1, session |betreff, text |

## Admin Pages

Проект предполагает что админ не враг сам себе, поэтому особо фильтровать запросы для его страниц нет необходимости.

Единственная проверка, которая выполняется - это проверка свойства `admin` пользователя. Обычные пользователи (admin=0) просто не пропускаются в админку (`page=admin`).
То есть все потенциально вредные GET/POST запросы режутся ещё на этой ранней стадии.

## Reg

TBD.
