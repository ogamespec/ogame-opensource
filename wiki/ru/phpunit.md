## Юнит-тестирование с PHPUnit

В целом обычные юнит-тесты. Находятся в папке `testing`.

```
sudo apt install php8.1-xml php8.1-dom php8.1-xmlwriter php8.1-mbstring
composer require --dev phpunit/phpunit "^10.0"
./vendor/bin/phpunit --testdox
```

### База данных в памяти

Тесты не требуют MySQL-сервера: в `phpunit.xml` задано
`DB_CONNECTION=sqlite` и `DB_DATABASE=:memory:`, поэтому игровой слой
работы с БД (`game/core/db.php`) подключает альтернативный бэкенд
`game/core/db_sqlite.php` — SQLite через PDO, полностью в памяти.
MySQL-специфичные запросы, которые встречаются в игровом коде
(`LOCK TABLES`, `SHOW COLUMNS`, `ALTER TABLE ... AUTO_INCREMENT`,
`SET @var := ...`, `= ANY (...)`, ...), автоматически транслируются
в SQLite-эквиваленты.

Тесты бэкенда — `testing/DbSqliteTest.php` (создание схемы из
`install_tabs.php`, CRUD, сериализация/восстановление БД и т.д.).

В обычной (боевой) среде переменная `DB_CONNECTION` не задана, поэтому
используется прежний MySQL-бэкенд `game/core/db_mysql.php`.