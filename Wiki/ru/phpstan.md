# Статический анализ с PHPStan

Для запуска phpstan нужно выполнить следующие магические заклинания:

```
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyze
```

![phpstan](/imgstore/phpstan.png)

Последний отчёт находится в `phpstan_report_latest.txt`, когда скучно можно добавлять исправления.