# rufaker

[![Tests](https://github.com/andrunio/rufaker/actions/workflows/ci.yml/badge.svg)](https://github.com/andrunio/rufaker/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/andrunio/rufaker/badges/coverage.json)](https://github.com/andrunio/rufaker/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/rufaker/rufaker)](https://packagist.org/packages/rufaker/rufaker)
[![PHP](https://img.shields.io/packagist/dependency-v/rufaker/rufaker/php)](https://packagist.org/packages/rufaker/rufaker)

Российские тестовые данные для PHP: ФИО, наименование и реквизиты организации, наименование банка
и его счета — ИНН, ОГРН и ОГРНИП, КПП, БИК, корреспондентский и расчётный счёт.

Отличие от генератора случайных цифр одно, и оно главное: **данные не противоречат друг другу.**

- ИНН, ОГРН и КПП одной организации принадлежат одному региону и одной налоговой инспекции;
- у ИП не бывает КПП, зато есть ФИО, а у юридического лица наоборот;
- контрольный ключ счёта вычисляется от БИК того банка, где счёт открыт;
- наименование банка выводится из БИК: два клиента одного банка видят один и тот же банк;
- фамилия, имя и отчество согласованы по роду.

Контрольные суммы считаются по-настоящему: сгенерированный ИНН, ОГРН или счёт проходит ту же
проверку, что и настоящий, — пакет умеет не только выдавать значения, но и разбирать чужие.

## Установка

```bash
composer require --dev rufaker/rufaker
```

Нужен PHP 8.3 или новее. Зависимостей нет — только сам PHP.

## Быстрый старт

```php
use RuFaker\RuFaker;

$ru = new RuFaker();

$company = $ru->organization();

$company->shortName;  // 'ООО "Ромашка"'
$company->fullName;   // 'Общество с ограниченной ответственностью "Ромашка"'
$company->form;       // 'ООО'
$company->inn;        // '1492876987'
$company->ogrn;       // '1101459307355'
$company->kpp;        // '145901001'
$company->region;     // '14'

$bank = $ru->bankAccount();

$bank->shortName;      // 'ПАО "Эталон Банк"'
$bank->bik;            // '040834121'
$bank->correspondent;  // '30101810700000000121'
$bank->settlement;     // '40702810500006981360'

$person = $ru->person();

$person->lastName;    // 'Поляков'
$person->firstName;   // 'Валентин'
$person->patronymic;  // 'Арсеньевич'
$person->gender;      // 'мужской'
$person->fullName;    // 'Поляков Валентин Арсеньевич'
```

Все поля — строки, готовые к подстановке в фикстуру, фабрику или запрос. В них стоит то, что
написано в документе: `ООО`, `мужской`. Машинное значение перечисления живёт за скобками:
`$company->form()->value` даёт `'ooo'`, `$person->gender()->value` — `'male'`.

## Как устроена выдача

**Поле без скобок — строка, то же имя со скобками — типизированное значение.** Это правило
работает во всём пакете:

```php
$company->inn;    // '1492876987' — string
$company->inn();  // RuFaker\Requisite\Inn — тип, знающий про себя всё
```

Строка нужна почти всегда, поэтому она и достаётся короче. Скобки — когда важен не текст,
а смысл значения:

```php
$company->inn()->region();               // Requisite\Region — регион, к которому приписан ИНН
$company->form()->hasKpp();              // true — бывает ли КПП у этой формы
$company->form()->balanceAccount();      // '40702' — какой счёт открывает банк
$bank->settlement()->isCorrespondent();  // false — расчётный счёт, не корреспондентский
$person->gender()->value;                // 'male'
```

Когда строки нужны сразу все, набор отдаёт их одним массивом — `toArray()`, а `json_encode()`
возвращает то же самое. Ключи в `snake_case`:

```php
$company->toArray();
// [
//     'form' => 'ООО',
//     'short_name' => 'ООО "Ромашка"',
//     'full_name' => 'Общество с ограниченной ответственностью "Ромашка"',
//     'region' => '14',
//     'inn' => '1492876987',
//     'ogrn' => '1101459307355',
//     'kpp' => '145901001',
//     'person' => null,
// ]

$bank->toArray();
// [
//     'bik' => '040834121',
//     'correspondent_account' => '30101810700000000121',
//     'settlement_account' => '40702810500006981360',
// ]
```

## Что генерирует пакет

### Организация

```php
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Requisite\Region;

$ru->organization();                                        // форма и регион случайные
$ru->organization(LegalForm::Ip);                           // форма задана
$ru->organization(LegalForm::Ooo, Region::from('77'));      // и регион
$ru->organization(LegalForm::Ip, null, Gender::Female);     // пол предпринимателя
$ru->organization(LegalForm::Ip, initials: true);           // ФИО в наименовании — инициалами
```

Формы — `Ooo`, `Ao`, `Pao`, `Ip`. Форма определяет всё остальное: длину ИНН и ОГРН, наличие КПП,
балансовый счёт. В поле `form` стоит краткий ярлык — `ООО`, `АО`, `ПАО`, `ИП`, — а полное название
даёт `form()->fullTitle()`: `Общество с ограниченной ответственностью`.

| Поле | Юридическое лицо | Индивидуальный предприниматель |
|---|---|---|
| `shortName` | ярлык формы и название: `ООО "Ромашка"` | `ИП` и ФИО целиком |
| `fullName` | форма целиком и название | `Индивидуальный предприниматель` и ФИО |
| `inn` | 10 знаков | 12 знаков |
| `ogrn` | 13 знаков, ОГРН | 15 знаков, ОГРНИП |
| `kpp` | 9 знаков | `null` |
| `person()` | `null` | `Result\Person` — ФИО предпринимателя |
| `region` | 2 знака, общий для всех реквизитов набора | то же |

```php
$company = $ru->organization(LegalForm::Ip, Region::from('66'));

$company->shortName;           // 'ИП Успенский Пётр Иванович'
$company->inn;                 // '664429287641'
$company->ogrn;                // '310663730735974'
$company->kpp;                 // null
$company->person()->fullName;  // 'Успенский Пётр Иванович'
$company->person()->lastName;  // 'Успенский'
```

Название юрлица берётся из словаря пакета — 127 слов, по одному слову в кавычках.

У предпринимателя наименование строится из ФИО, и у него есть третья, неофициальная форма —
с инициалами. Она включается флагом при генерации:

```php
$ru->organization(LegalForm::Ip, initials: true)->shortName;  // 'ИП Абрамова А.С.'
```

### ФИО

```php
$ru->person();                // пол случайный
$ru->person(Gender::Female);  // пол задан

$person->gender;              // 'женский'
$person->gender()->value;     // 'female'
```

Согласованы все три части сразу. Отчества, образованные не по общему правилу, и несклоняемые
фамилии в книге учтены:

```php
$ru->person(Gender::Female)->fullName;  // 'Шевченко Валерия Ильинична' — не 'Ильиновна'
```

Книга собственная: 159 имён, 168 отчеств и 134 фамилии — больше восьмисот тысяч сочетаний
на каждый пол. Сторонние справочники не подключаются.

### Банковские реквизиты

```php
$ru->bankAccount();                        // банк и счета случайные
$ru->bankAccount($bik);                    // банк задан
$ru->bankAccount(null, LegalForm::Ip);     // балансовый счёт под форму клиента
```

Счёт всегда приходит вместе со своим банком — отдельного способа получить номер счёта в API нет
намеренно: без БИК его контрольный ключ не посчитать.

| Поле | Что это |
|---|---|
| `shortName` | наименование банка: ярлык формы и название в кавычках |
| `fullName` | то же наименование с полным названием формы |
| `bik` | 9 знаков: территория, подразделение Банка России, номер участника |
| `correspondent` | счёт банка в Банке России, начинается на `301` |
| `settlement` | расчётный счёт клиента: `40702` для организации, `40802` для ИП |

Наименование банка устроено как у организации, с двумя отличиями. Слово «Банк» стоит внутри
кавычек: закон 395-1 требует указания на характер деятельности прямо в наименовании. Форма —
`ООО`, `АО` или `ПАО`: кредитная организация образуется как хозяйственное общество, и
предпринимателем банк не бывает.

```php
$bank->shortName;  // 'ПАО "Эталон Банк"'
$bank->fullName;   // 'Публичное акционерное общество "Эталон Банк"'
```

Наименование выводится из самого БИК, а не бросается заново: один и тот же БИК всегда даёт
один и тот же банк, сколько бы клиентов ему ни завели.

Балансовые счета взяты из Плана счетов Положения Банка России от 24.11.2022 № 809-П: `40702`
«Коммерческие организации», `40802` «Индивидуальные предприниматели».

## Проверка чужих значений

Каждый реквизит — самостоятельный тип с тремя способами разобрать чужую строку: `isValid()`
отвечает `bool`, `tryFrom()` возвращает `null`, `from()` бросает исключение. Поэтому пакет
работает и как валидатор, а не только как генератор.

```php
use RuFaker\Requisite\Inn;

Inn::isValid('7707083893');  // true
Inn::isValid('7707083894');  // false — контрольная сумма не сходится
Inn::tryFrom('7707083894');  // null
Inn::from('7707083894');     // выбросит RuFaker\Exception\InvalidRequisite
```

Разобранное значение умеет отвечать на вопросы о себе:

```php
$inn = Inn::from('7707083893');

$inn->value;         // '7707083893'
$inn->region();      // Requisite\Region — код '77'
$inn->isPersonal();  // false — это ИНН организации, а не физлица
```

Счёт проверяется только вместе с банком:

```php
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;

Account::isValid('30101810400000000225', Bik::from('044525225'));  // true
Account::isValid('30101810400000000225', Bik::from('044030653'));  // false — другой банк
```

## Рецепты

### Фабрика модели

Поля набора — обычные строки, поэтому подставляются в атрибуты напрямую:

```php
public function definition(): array
{
    $company = (new RuFaker())->organization(LegalForm::Ooo);

    return [
        'title' => $company->shortName,
        'inn' => $company->inn,
        'kpp' => $company->kpp,
        'ogrn' => $company->ogrn,
    ];
}
```

Если имена колонок совпадают с ключами набора, разворачивается целиком:

```php
return [...$company->toArray(), 'is_active' => true];
```

### Фикстура в JSON

```php
file_put_contents(
    'fixture.json',
    json_encode($ru->organization(LegalForm::Ip), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
);
// {
//     "form": "ИП",
//     "short_name": "ИП Куликова Лариса Григорьевна",
//     "full_name": "Индивидуальный предприниматель Куликова Лариса Григорьевна",
//     "region": "77",
//     "inn": "774726767382",
//     "ogrn": "321774281546078",
//     "kpp": null,
//     "person": "Куликова Лариса Григорьевна"
// }
```

### Один и тот же набор в каждом прогоне

Источник случайности — штатный `Random\Randomizer`. Одинаковый seed даёт одинаковый результат,
поэтому упавший тест воспроизводится:

```php
RuFaker::seeded(1234)->organization()->toArray();
// [
//     'form' => 'ИП',
//     'short_name' => 'ИП Абрамова Анна Саввична',
//     'full_name' => 'Индивидуальный предприниматель Абрамова Анна Саввична',
//     'region' => '55',
//     'inn' => '555104382976',
//     'ogrn' => '302556771471859',
//     'kpp' => null,
//     'person' => 'Абрамова Анна Саввична',
// ]
```

Сравнивать нужно массивы, а не наборы: два объекта с одинаковым содержимым совпадут по `==`,
но по `===` — никогда, это разные объекты.

Движок выбирается свой, если нужен другой алгоритм:

```php
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

$ru = new RuFaker(new Randomizer(new Xoshiro256StarStar(1234)));
```

### Организация и её расчётный счёт

Балансовый счёт зависит от организационно-правовой формы, поэтому форму передают дальше:

```php
$company = $ru->organization(LegalForm::Ip);
$bank = $ru->bankAccount(null, $company->form());

$company->inn;      // '193983068463'
$bank->bik;         // '040426598'
$bank->settlement;  // '40802810200009422091' — 40802, счёт предпринимателя
```

Для ООО тот же код даст счёт на `40702`.

### Несколько клиентов одного банка

Передавайте один `Bik` — корреспондентский счёт совпадёт, расчётные будут разными:

```php
$bik = $ru->bik();  // '047298058'

$first = $ru->bankAccount($bik);
$second = $ru->bankAccount($bik);

$first->shortName;      // 'ООО "Лотос Банк"'
$second->shortName;     // то же наименование: банк один

$first->correspondent;  // '30101810600000000058' — счёт самого банка
$second->correspondent; // тот же самый

$first->settlement;     // '40702810000004768305'
$second->settlement;    // '40702810800008330269'
```

Ключ у обоих расчётных сходится с этим БИК — проверка `Account::isValid()` их примет.

## Справочник

**Генерация.** Источник случайности задаётся конструктором: `new RuFaker()`,
`new RuFaker($randomizer)` или `RuFaker::seeded(1234)`.

| Вызов | Отдаёт |
|---|---|
| `organization(?LegalForm, ?Region, ?Gender, bool $initials = false)` | `Result\Organization` |
| `person(?Gender)` | `Result\Person` |
| `bankAccount(?Bik, ?LegalForm)` | `Result\BankAccount` |
| `inn(?LegalForm, ?Region)` | `Requisite\Inn` |
| `ogrn(?LegalForm, ?Region)` | `Requisite\Ogrn` |
| `kpp(?Region)` | `Requisite\Kpp` |
| `bik()` | `Requisite\Bik` |

**Наборы.** У каждого есть `toArray()` и `json_encode()`; `Person` дополнительно приводится
к строке — это его `fullName`.

| Поле | Без скобок | Со скобками |
|---|---|---|
| `$company->shortName`, `$company->fullName` | `string` | — |
| `$company->form` | `string` | `Enum\LegalForm` |
| `$company->region` | `string` | `Requisite\Region` |
| `$company->inn` | `string` | `Requisite\Inn` |
| `$company->ogrn` | `string` | `Requisite\Ogrn` |
| `$company->kpp` | `string\|null` | `Requisite\Kpp\|null` |
| `$company->person()` | — | `Result\Person\|null` |
| `$bank->shortName`, `$bank->fullName` | `string` | — |
| `$bank->bik` | `string` | `Requisite\Bik` |
| `$bank->correspondent` | `string` | `Requisite\Account` |
| `$bank->settlement` | `string` | `Requisite\Account` |
| `$person->gender` | `string` | `Enum\Gender` |
| `$person->fullName`, `lastName`, `firstName`, `patronymic` | `string` | — |

**Реквизиты.** У всех шести одинаково: `isValid()`, `tryFrom()`, `from()`, свойство `->value`,
приведение к строке и сериализация в JSON. У `Account` первые три принимают вторым аргументом
`Bik`.

| Тип | Что ещё умеет | Контрольная сумма |
|---|---|---|
| `Inn` | `region()`, `isPersonal()` | да |
| `Ogrn` | `region()`, `isIndividual()` | да |
| `Kpp` | `region()`, `reason()` | нет, только формат |
| `Bik` | `territory()`, `division()`, `participant()` | нет, только формат |
| `Account` | `isCorrespondent()`, `isSettlement()` | да, от БИК |
| `Region` | — | нет, только диапазон |

**Перечисления.** `Enum\LegalForm` — `Ooo`, `Ao`, `Pao`, `Ip`; умеет `innDigits()`,
`innChecksumDigits()`, `registryNumberDigits()`, `registryNumberPrefix()`, `balanceAccount()`,
`hasKpp()`, `isIndividual()`, `isCorporate()`, `shortTitle()` и `fullTitle()`.
`Enum\Gender` — `Male`, `Female`; умеет `title()`, `isMale()` и `isFemale()`.
У обоих штатные `cases()`, `from()`, `tryFrom()` и `->value`.

## Известные ограничения

- Название юрлица — одно слово из словаря в 127 слов; у банка — то же слово плюс «Банк».
- Наименование банка не связано с настоящим владельцем БИК: оно выводится из самого номера,
  а справочник действующих кредитных организаций в пакет не входит.
- Код территории внутри БИК не связан с регионом организации: соответствие кодов ОКАТО и кодов
  ФНС требует справочника, а справочники в пакет намеренно не включаются.
- Счёт считается открытым в подразделении Банка России, если его номер начинается на `301`.
  Счета бюджета (`40101`, `40102` и подобные) под это правило не попадают, и их контрольный ключ
  будет вычислен по правилу для счёта в кредитной организации.
- КПП выдаётся с причиной постановки на учёт `01` и порядковым номером `001`, то есть для
  головной организации. Обособленные подразделения не генерируются.
- Год регистрации внутри ОГРН выбирается из диапазона 2002–2026 и зафиксирован константой:
  привязка к текущей дате сломала бы воспроизводимость по seed.
- Код субъекта РФ генерация берёт из 83 значений, а проверка принимает любой код от `01` до `99`:
  перечень кодов — данные, и он правится patch-выпуском.
- ФИО выдаётся в именительном падеже: склонения по падежам в пакете нет.

## Совместимость

Публичный API — фасад `RuFaker` и типы из `Requisite\`, `Result\`, `Enum\` и `Exception\`.
Всё, что помечено `@internal` — пространство имён `RuFaker\Internal\` целиком и отдельные методы
реквизитов, нужные только генераторам, — меняется и исчезает без предупреждения: семантическое
версионирование на него не распространяется.

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
