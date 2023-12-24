# Отладка боевого движка

Боевой движок (battle.exe) запускается с числовым параметром battle_id. Число указывает какой входной файл брать из папки `battledata`.

Затем боевой движок парсит входной файл и выдаёт результат в папке `battleresult` с тем же номером.

## battle_1.txt

Пример входных данных (текстовой файл):

```
Rapidfire = 1
FID = 30
DID = 0
Attackers = 1
Defenders = 1
Attacker0 = (<Attacker0> 5093 7 29 6 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 )
Defender0 = (<Defender0> 7183 9 89 8 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 )
```

## Процесс отладки

Нужно где-то отдельно разместить собранный battle.exe и создать для него нужные папки (в той же директории):

- Add `battledata` folder and put this .txt in there
- Add the `battleresult` folder
- Run `battle.exe -battle_id 1`

There should be something in the battleresult folder.
