# rufaker

[![Tests](https://github.com/andrunio/rufaker/actions/workflows/ci.yml/badge.svg)](https://github.com/andrunio/rufaker/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/rufaker/rufaker)](https://packagist.org/packages/rufaker/rufaker)
[![PHP](https://img.shields.io/packagist/dependency-v/rufaker/rufaker/php)](https://packagist.org/packages/rufaker/rufaker)
[![License](https://img.shields.io/packagist/l/rufaker/rufaker)](LICENSE)

Российские тестовые данные для PHP: реквизиты организаций и банков — ИНН, ОГРН и ОГРНИП, КПП,
БИК, корреспондентский и расчётный счёт.

Данные выдаются **согласованными между собой**, и в этом вся разница с генератором строк нужной
длины: ИНН, ОГРН и КПП одной организации принадлежат одному региону и одной налоговой инспекции,
у ИП не бывает КПП, а контрольный ключ счёта вычисляется от БИК того банка, где счёт открыт.

## Установка

```bash
composer require --dev rufaker/rufaker
```

Нужен PHP 8.3 или новее. Других зависимостей нет.

## Быстрый старт

```php
use RuFaker\RuFaker;

$ru = new RuFaker();

$company = $ru->organization();

$company->form->value;   // 'ooo'
$company->inn->value;    // '1492876987'
$company->ogrn->value;   // '1101459307355'
$company->kpp->value;    // '145901001'
$company->region->value; // '14'

$bank = $ru->bankAccount();

$bank->bik->value;           // '040834121'
$bank->correspondent->value; // '30101810700000000121'
$bank->settlement->value;    // '40702810500006981360'
```

Форму и регион можно задать явно:

```php
use RuFaker\Enum\LegalForm;
use RuFaker\Requisite\Region;

$entrepreneur = $ru->organization(LegalForm::Ip, Region::from('66'));

$entrepreneur->inn->value;   // '664429287641' — 12 знаков
$entrepreneur->ogrn->value;  // '310663730735974' — 15 знаков, ОГРНИП
$entrepreneur->kpp;          // null — у ИП КПП не бывает
```

## Воспроизводимость

Источник случайности — штатный `Random\Randomizer`. Один и тот же seed даёт один и тот же
результат:

```php
RuFaker::seeded(1234)->organization()->toArray()
    === RuFaker::seeded(1234)->organization()->toArray();  // true
```

Можно передать собственный `Randomizer` с любым движком:

```php
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

$ru = new RuFaker(new Randomizer(new Xoshiro256StarStar(1234)));
```

## Проверка чужих значений

Каждый реквизит — самостоятельный тип с тремя методами: `from()` бросает исключение,
`tryFrom()` возвращает `null`, `isValid()` отвечает `bool`. Поэтому пакет работает и как
валидатор, а не только как генератор.

```php
use RuFaker\Requisite\Inn;

Inn::isValid('7707083893');   // true
Inn::isValid('7707083894');   // false — контрольная сумма не сходится
Inn::tryFrom('7707083894');   // null
Inn::from('7707083894');      // RuFaker\Exception\InvalidRequisite
```

Счёт всегда проверяется вместе с банком, потому что его контрольный ключ вычисляется от БИК:

```php
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;

Account::isValid('30101810400000000225', Bik::from('044525225'));  // true
Account::isValid('30101810400000000225', Bik::from('044030653'));  // false — другой банк
```

Получить номер счёта в отрыве от БИК нельзя: такого метода в API нет намеренно.

## Что входит

| Реквизит | Тип | Контрольная сумма |
|---|---|---|
| ИНН, 10 и 12 знаков | `Requisite\Inn` | да |
| ОГРН 13 знаков, ОГРНИП 15 | `Requisite\Ogrn` | да |
| КПП | `Requisite\Kpp` | нет, только формат |
| БИК | `Requisite\Bik` | нет, только формат |
| Корреспондентский и расчётный счёт | `Requisite\Account` | да, от БИК |
| Код субъекта РФ | `Requisite\Region` | нет, только диапазон |

Организационно-правовые формы — ООО, АО, ПАО и ИП, перечисление `Enum\LegalForm`. Форма решает
всё остальное: длину ИНН и ОГРН, наличие КПП, балансовый счёт.

Готовые наборы — `Result\Organization` и `Result\BankAccount`. Их конструкторы отвергают
противоречивые данные, так что собрать вручную ИП с КПП или счёт, не соответствующий своему
банку, не получится.

Любой реквизит приводится к строке и сериализуется в JSON, а у набора есть `toArray()`.

## Известные ограничения

- Код территории внутри БИК не связан с регионом организации: соответствие кодов ОКАТО и кодов
  ФНС требует справочника, а справочники в пакет намеренно не включаются.
- Счёт считается открытым в подразделении Банка России, если его номер начинается на `301`.
  Счета бюджета (`40101`, `40102` и подобные) под это правило не попадают, и их контрольный ключ
  будет вычислен по правилу для счёта в кредитной организации.
- КПП выдаётся с причиной постановки на учёт `01` и порядковым номером `001`, то есть для
  головной организации. Обособленные подразделения не генерируются.
- Год регистрации внутри ОГРН выбирается из диапазона 2002–2026 и зафиксирован константой:
  привязка к текущей дате сломала бы воспроизводимость по seed.
- Наименований, ФИО, адресов и банковских названий пакет не генерирует.

## Совместимость

Публичный API — фасад `RuFaker` и типы из `Requisite\`, `Result\`, `Enum\` и `Exception\`.
Всё в `RuFaker\Internal\` помечено `@internal`: оно меняется и исчезает без предупреждения, и
семантическое версионирование на него не распространяется.

Пока версия остаётся в ветке `0.x`, публичный API тоже может измениться в минорном выпуске —
об этом говорит `CHANGELOG.md`.

## Разработка

```bash
composer install
composer test     # тесты на PHP хоста
composer stan     # статический анализ, level 10
composer check    # то и другое
```

Поддерживаются PHP 8.3, 8.4 и 8.5 — все три проверяются в CI. Зависимости разрешаются под
минимальную версию: `composer.json` объявляет `config.platform.php`.

Для каждой версии есть контейнер — можно и прогнать тесты, и работать внутри:

```bash
docker compose run --rm php83 composer install

composer test:83   # то же самое для test:84 и test:85
```

```bash
docker compose run --rm php85 sh   # оболочка внутри контейнера
```
