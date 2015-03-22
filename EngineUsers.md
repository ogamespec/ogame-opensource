# Introduction #

Add your content here.

# Сессии #

Сессия - это механизм распознания пользователей. В системе аутентификации пользователей Ogame используются два типа сессии - публичная и приватная.

Публичная сессия - это набор цифр и букв, которые вы можете видеть в каждой игровой ссылке, например `http://uni5.ogame.ru/game/index.php?page=overview&session=356dd05e994d`<br>
Приватная сессия хранится в кукисах вашего браузера. Кукис называется prsess_XXX, где XXX номер пользователя.<br>
В целях безопасности приватная сессия хранится всего-лишь 24 часа, кроме того все сессии удаляются каждую ночь, в 3:00 по серверу, во время так называемого "перелогина".<br>
<br>
После входа в игру каждому пользователю назначается уникальная приватная и публичная сессия. Приватная сессия записывается в кукисы, а публичная подставляется ко всем игровым ссылкам.<br>
<br>
Алгоритм проверки пользователя в игре осуществляется следующим образом:<br>
<ol><li>Из ссылки берется публичная сессия<br>
</li><li>В базе данных находится пользователь, которому назначена такая-же публичная сессия. Если публичная сессия не принадлежит никому, то открывается главная страница OGame.<br>
</li><li>Далее проверяется приватная сессия. Если приватная сессия из кукисов не совпадает с приватной сессией, записанной в базе данных пользователя, то выводится всем известная страница "Произошла ошбка. Сессия недействительна."<br>
</li><li>Затем, если в настройках включена проверка IP адреса, то сравнивается текущий IP-адрес пользователя, и IP-адрес, который у него был на момент входа в игру. Если IP-адреса не совпадают, то выводится та же самая страница с ошибкой.<br>
</li><li>Если все проверки прошли успешно, загружается игровая страница.</li></ol>

<h1>API</h1>

SendGreetingsMail<br>
SendChangeMail<br>
SendGreetingsMessage<br>
IsUserExist<br>
IsEmailExist<br>
CreateUser<br>
RemoveUser<br>
ValidateUser<br>
CheckPassword<br>
ChangeEmail<br>
ChangeActivationCode<br>
SelectPlanet<br>
LoadUser<br>
UpdateLastClick<br>
IsPlayerNewbie<br>
IsPlayerStrong<br>
<br>
<h1>Database</h1>

Структура таблицы <code>users</code>:<br>
<br>
<table><thead><th> <b>столбец</b> </th><th> <b>SQL тип</b> </th><th> <b>описание</b> </th></thead><tbody>
<tr><td>player_id</td><td>INT AUTO_INCREMENT PRIMARY KEY</td><td>Порядковый номер пользователя, начинается со 100000</td></tr>
<tr><td>regdate</td><td>INT UNSIGNED</td><td>Дата регистрации аккаунта time()</td></tr>
<tr><td>ally_id</td><td>INT</td><td>Номер альянса в котором состоит игрок (0 - без альянса)</td></tr>
<tr><td>joindate</td><td>INT UNSIGNED</td><td>Дата вступления в альянс time()</td></tr>
<tr><td>allyrank</td><td>INT</td><td>Ранг игрока в альянсе</td></tr>
<tr><td>session</td><td>CHAR (12)</td><td>Сессия для ссылок</td></tr>
<tr><td>private_session</td><td>CHAR(32)</td><td>Приватная сессия для кукисов</td></tr>
<tr><td>name</td><td>CHAR(20)</td><td>Имя пользователя lower-case для сравнения</td></tr>
<tr><td>oname</td><td>CHAR(20)</td><td>Имя пользователя оригинальное</td></tr>
<tr><td>name_changed</td><td>INT</td><td>Имя пользователя изменено? (1 или 0)</td></tr>
<tr><td> <b>Q</b> name_until</td><td>INT UNSIGNED</td><td>Когда можно поменять имя пользователя в следующий раз time()</td></tr>
<tr><td>password</td><td>CHAR(32)</td><td>MD5-хеш пароля и секретного слова</td></tr>
<tr><td>pemail</td><td>CHAR(50)</td><td>Постоянный почтовый адрес</td></tr>
<tr><td>email</td><td>CHAR(50)</td><td>Временный почтовый адрес</td></tr>
<tr><td>email_changed</td><td>INT</td><td>Временный почтовый адрес изменен</td></tr>
<tr><td> <b>Q</b> email_until</td><td>INT UNSIGNED</td><td>Когда заменить постоянный email на временный time()</td></tr>
<tr><td>disable</td><td>INT</td><td>Аккаунт поставлен на удаление</td></tr>
<tr><td> <b>Q</b> disable_until</td><td>INT UNSIGNED</td><td>Когда можно удалить аккаунт time()</td></tr>
<tr><td>vacation</td><td>INT</td><td>Аккаунт в режиме отпуска</td></tr>
<tr><td>vacation_until</td><td>INT UNSIGNED</td><td>Когда можно выключить режим отпуска time()</td></tr>
<tr><td>banned</td><td>INT</td><td>Аккаунт заблокирован</td></tr>
<tr><td> <b>Q</b> banned_until</td><td>INT UNSIGNED</td><td>Время окончания блокировки time()</td></tr>
<tr><td>noattack</td><td>INT</td><td>Запрет на атаки</td></tr>
<tr><td> <b>Q</b> noattack_until</td><td>INT UNSIGNED</td><td>Когда заканчивается запрет на атаки time()</td></tr>
<tr><td>lastlogin</td><td>INT UNSIGNED</td><td>Последняя дата входа в игру</td></tr>
<tr><td>lastclick</td><td>INT UNSIGNED</td><td>Последний щелчок мышкой, для определения активности игрока</td></tr>
<tr><td>ip_addr</td><td>CHAR(15)</td><td>IP адрес пользователя</td></tr>
<tr><td>validated</td><td>INT</td><td>Пользователь активирован. Если пользователь не активирован, то ему запрещено посылать игровые сообщения и заявки в альянсы.</td></tr>
<tr><td>validatemd</td><td>CHAR(32)</td><td>Код активации</td></tr>
<tr><td>hplanetid</td><td>INT</td><td>Порядковый номер Главной планеты</td></tr>
<tr><td>admin</td><td>INT</td><td>0 - обычный игрок, 1 - оператор, 2 - администратор</td></tr>
<tr><td>sortby</td><td>INT</td><td>Порядок сортировки планет: 0 - порядку колонизации, 1 - координатам, 2 - алфавиту</td></tr>
<tr><td>sortorder</td><td>INT</td><td>Порядок: 0 - по возрастанию, 1 - по убыванию</td></tr>
<tr><td>skin</td><td>CHAR(80)</td><td>Путь для скина. Получается путем слепления пути к хосту и названием скина, но длина строки не более 80 символов.</td></tr>
<tr><td>useskin</td><td>INT</td><td>Показывать скин, если 0 - то показывать скин по умолчанию</td></tr>
<tr><td>deact_ip</td><td>INT</td><td>Выключить проверку IP</td></tr>
<tr><td>maxspy</td><td>INT</td><td>Кол-во шпионских зондов (1 по умолчанию, 0...99)</td></tr>
<tr><td>maxfleetmsg</td><td>INT</td><td>Максимальные сообщения о флоте в Галактику (3 по умолчанию, 0...99, 0=1)</td></tr>
<tr><td>aktplanet</td><td>INT</td><td>Текущая выбранная планета.</td></tr>
<tr><td>dm</td><td>INT</td><td>Покупная ТМ</td></tr>
<tr><td>dmfree</td><td>INT</td><td>ТМ найденная в экспедиции</td></tr>
<tr><td>sniff</td><td>INT</td><td>Включить слежение за историей переходов (Админка)</td></tr>
<tr><td>score1,2,3</td><td>BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED</td><td>Очки за постройки, флот, исследования</td></tr>
<tr><td>place1,2,3</td><td>INT</td><td>Место за постройки, флот, исследования</td></tr>
<tr><td>oldscore1,2,3</td><td>BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED</td><td>Старые очки за постройки, флот, исследования</td></tr>
<tr><td>oldplace1,2,3</td><td>INT</td><td>старое место за постройки, флот, исследования</td></tr>
<tr><td>scoredate</td><td>INT UNSIGNED</td><td>Время сохранения старой статистики</td></tr>
<tr><td>rXXX</td><td>INT</td><td>Уровень исследования XXX</td></tr></tbody></table>

<b>Q</b> - для обработки этого события используется задание в очереди задач.