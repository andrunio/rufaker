<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal\Generator;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\Calendar;
use RuFaker\Internal\Generator\OrganizationGenerator;
use RuFaker\Internal\Generator\PersonGenerator;
use RuFaker\Internal\TitleBook;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Organization;
use RuFaker\Result\Person;

#[CoversClass(OrganizationGenerator::class)]
#[CoversClass(Organization::class)]
final class OrganizationGeneratorTest extends TestCase
{
    /** How many sets each property is checked over. */
    private const int RUNS = 300;

    /** Years back the window of a registration closes, mirroring the constant of the generator. */
    private const int LAST_YEAR = 1;

    /** Age of a person old enough to register as a sole proprietor, article 21 of the Civil Code. */
    private const int ADULT_AGE = 18;

    /** Age of a person too young for it, drawn from the same article. */
    private const int CHILD_AGE = 10;

    /** Name handed to the generator; the title book holds no such word, so a draw cannot produce it. */
    private const string GIVEN_TITLE = 'Рассвет';

    /** Personal INN opened by region 66, twelve digits with a valid checksum. */
    private const string PROPRIETOR_INN = '663266525158';

    /** Personal INN whose two leading digits address no federal subject. */
    private const string HOMELESS_INN = '001234567887';

    /** Positions a head holds, in the order sort() puts them; the generator draws out of this list. */
    private const array POSITIONS = [
        'Генеральный директор',
        'Директор',
        'Президент',
    ];

    #[Test]
    public function it_generates_requisites_that_pass_their_own_validation(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate();

            $this->assertTrue(
                Inn::isValid($organization->inn),
            );

            $this->assertTrue(
                Ogrn::isValid($organization->ogrn),
            );

            if ($organization->kpp !== null) {
                $this->assertTrue(
                    Kpp::isValid($organization->kpp),
                );
            }
        }
    }

    #[Test]
    public function it_keeps_every_requisite_in_one_region(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate();
            $region = $organization->region;

            $this->assertSame(
                $region,
                $organization->inn()->region()?->value,
            );

            $this->assertSame(
                $region,
                $organization->ogrn()->region()?->value,
            );

            if ($organization->kpp !== null) {
                $this->assertSame(
                    $region,
                    $organization->kpp()?->region()?->value,
                );
            }
        }
    }

    #[Test]
    public function it_shares_one_tax_office_between_the_registry_number_and_the_kpp(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate(LegalForm::Ooo);

            $this->assertSame(
                substr($organization->ogrn, 5, 2),
                substr((string)$organization->kpp, 2, 2),
            );
        }
    }

    #[Test]
    #[DataProvider('forms')]
    public function it_follows_the_structure_of_the_legal_form(LegalForm $form): void
    {
        $organization = $this->generator()
            ->generate($form);

        $this->assertSame(
            $form->innDigits(),
            strlen($organization->inn),
        );

        $this->assertSame(
            $form->registryNumberDigits(),
            strlen($organization->ogrn),
        );

        $this->assertSame(
            $form->hasKpp(),
            $organization->kpp !== null,
        );
    }

    #[Test]
    #[DataProvider('registryOpenings')]
    public function it_dates_a_registry_number_within_the_life_of_its_registry(LegalForm $form, int $firstYear): void
    {
        $generator = $this->generator();
        $years = [];

        foreach (range(1, self::RUNS) as $ignored) {
            // The year sits right behind the leading digit that tells the two registries apart.
            $years[] = (int)substr($generator->generate($form)->ogrn, 1, 2);
        }

        $this->assertSame(
            $firstYear,
            min($years),
        );

        // The window closes with last year, so the newest number a generator writes carries it.
        $this->assertSame(
            (int)Calendar::yearCloses(self::LAST_YEAR)->format('y'),
            max($years),
        );
    }

    #[Test]
    #[DataProvider('forms')]
    public function it_dates_the_registration_within_the_life_of_the_registry(LegalForm $form): void
    {
        $generator = $this->generator();
        $opened = $form->registryOpenedOn();
        $last = Calendar::yearCloses(self::LAST_YEAR);

        foreach (range(1, self::RUNS) as $ignored) {
            $registered = $generator->generate($form)
                ->registrationDate();

            $this->assertGreaterThanOrEqual(
                $opened,
                $registered,
            );

            $this->assertLessThanOrEqual(
                $last,
                $registered,
            );
        }
    }

    #[Test]
    #[DataProvider('forms')]
    public function it_carries_the_year_of_the_registration_inside_the_number(LegalForm $form): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate($form);

            $this->assertSame(
                (int)$organization->registrationDate()->format('Y'),
                $organization->ogrn()->year(),
            );
        }
    }

    #[Test]
    public function it_registers_a_sole_proprietor_no_earlier_than_they_came_of_age(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate(LegalForm::Ip);

            $person = $organization->person();

            $this->assertInstanceOf(Person::class, $person);

            $this->assertGreaterThanOrEqual(
                $this->cameOfAge($person),
                $organization->registrationDate(),
            );
        }
    }

    #[Test]
    public function it_honours_a_requested_region(): void
    {
        $organization = $this->generator()
            ->generate(LegalForm::Ooo, Region::from('77'));

        $this->assertSame(
            '77',
            $organization->region,
        );

        $this->assertStringStartsWith(
            '77',
            $organization->inn,
        );
    }

    #[Test]
    public function it_registers_a_given_person_as_a_sole_proprietor(): void
    {
        $person = $this->person(
            region: Region::from('66'),
        );

        $organization = $this->generator()
            ->generate(person: $person);

        $this->assertSame(
            LegalForm::Ip,
            $organization->form(),
        );

        $this->assertSame(
            $person,
            $organization->person(),
        );

        $this->assertSame(
            $person->inn,
            $organization->inn,
        );

        $this->assertSame(
            '66',
            $organization->region,
        );

        $this->assertSame(
            '66',
            $organization->ogrn()->region()?->value,
        );

        $this->assertGreaterThanOrEqual(
            $this->cameOfAge($person),
            $organization->registrationDate(),
        );
    }

    #[Test]
    public function it_names_a_legal_entity_the_way_it_was_asked(): void
    {
        $generator = $this->generator();
        $drawn = [];

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate(title: self::GIVEN_TITLE);

            $this->assertSame(
                $organization->form . ' "' . self::GIVEN_TITLE . '"',
                $organization->shortName,
            );

            $drawn[$organization->form] = true;
        }

        $forms = array_keys($drawn);
        sort($forms);

        // A form drawn out of every case would hit a sole proprietor, and one carries no title.
        $this->assertSame(
            ['АО', 'ООО', 'ПАО'],
            $forms,
        );
    }

    #[Test]
    public function it_rejects_a_legal_form_a_person_cannot_take(): void
    {
        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(LegalForm::Ooo, person: $this->person());
    }

    #[Test]
    public function it_rejects_a_region_a_person_does_not_come_from(): void
    {
        $person = $this->person(region: Region::from('66'));

        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(region: Region::from('77'), person: $person);
    }

    #[Test]
    public function it_rejects_a_gender_a_person_does_not_have(): void
    {
        $person = $this->person(Gender::Female);

        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(gender: Gender::Male, person: $person);
    }

    #[Test]
    public function it_rejects_a_title_a_sole_proprietor_cannot_carry(): void
    {
        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(LegalForm::Ip, title: self::GIVEN_TITLE);
    }

    #[Test]
    public function it_rejects_a_person_carrying_no_number(): void
    {
        $person = $this->assembled(Calendar::yearOpens(self::ADULT_AGE), null);

        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(person: $person);
    }

    #[Test]
    public function it_rejects_a_number_addressing_no_region(): void
    {
        $person = $this->assembled(Calendar::yearOpens(self::ADULT_AGE), self::HOMELESS_INN);

        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(person: $person);
    }

    #[Test]
    public function it_rejects_a_person_who_has_not_come_of_age(): void
    {
        $person = $this->assembled(Calendar::yearOpens(self::CHILD_AGE), self::PROPRIETOR_INN);

        $this->expectException(InvalidRequisite::class);

        $this->generator()->generate(person: $person);
    }

    #[Test]
    public function it_names_a_legal_entity_out_of_the_title_book(): void
    {
        $generator = $this->generator();

        $expected = array_map(
            static fn(string $title): string => 'ООО "' . $title . '"',
            TitleBook::titles(),
        );

        foreach (range(1, self::RUNS) as $ignored) {
            $this->assertContains(
                $generator->generate(LegalForm::Ooo)->shortName,
                $expected,
            );
        }
    }

    #[Test]
    public function it_cuts_a_sole_proprietor_to_initials_only_when_asked(): void
    {
        $generator = $this->generator();

        $plain = $generator->generate(LegalForm::Ip);

        $this->assertSame(
            'ИП ' . $plain->person()?->fullName,
            $plain->shortName,
        );

        $short = $generator->generate(LegalForm::Ip, initials: true);

        $this->assertMatchesRegularExpression(
            '/^ИП [А-ЯЁ][а-яё]+ [А-ЯЁ]\.[А-ЯЁ]\.$/u',
            $short->shortName,
        );
    }

    #[Test]
    #[DataProvider('forms')]
    public function it_appoints_a_head_only_where_the_form_has_one(LegalForm $form): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate($form);

            $this->assertSame(
                $form->isCorporate(),
                $organization->head() instanceof Person,
            );

            $this->assertSame(
                $form->isCorporate(),
                $organization->headPosition !== null,
            );
        }
    }

    #[Test]
    public function it_draws_the_position_of_a_head_out_of_its_own_list(): void
    {
        $generator = $this->generator();
        $drawn = [];

        foreach (range(1, self::RUNS) as $ignored) {
            $drawn[] = $generator->generate(LegalForm::Ooo)->headPosition;
        }

        sort($drawn);

        $this->assertSame(
            self::POSITIONS,
            array_values(array_unique($drawn)),
        );
    }

    #[Test]
    public function it_gives_the_head_a_number_of_their_own(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $organization = $generator->generate(LegalForm::Ooo);
            $inn = $organization->head()?->inn();

            $this->assertInstanceOf(Inn::class, $inn);

            $this->assertTrue(
                $inn->isPersonal(),
            );

            $this->assertNotSame(
                $organization->inn,
                $inn->value,
            );
        }
    }

    #[Test]
    public function it_builds_standalone_requisites(): void
    {
        $generator = $this->generator();

        $this->assertTrue(
            Inn::isValid($generator->inn()->value),
        );

        $this->assertTrue(
            Ogrn::isValid($generator->registryNumber()->value),
        );

        $this->assertTrue(
            Kpp::isValid($generator->kpp()->value),
        );
    }

    /**
     * Every form against the year its registry opened, taken from the law rather than from the code.
     *
     * @return array<string, array{LegalForm, int}>
     */
    public static function registryOpenings(): array
    {
        return [
            'limited liability company' => [LegalForm::Ooo, 2],
            'joint-stock company' => [LegalForm::Ao, 2],
            'public joint-stock company' => [LegalForm::Pao, 2],
            'sole proprietor' => [LegalForm::Ip, 4],
        ];
    }

    /**
     * Returns the day the person turned eighteen, the earliest they may register as a proprietor.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param Person $person
     * @return DateTimeImmutable
     */
    private function cameOfAge(Person $person): DateTimeImmutable
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $person->birthDate()->modify('+18 years');
    }

    /**
     * Builds a person the way the package does, to hand them back as a sole proprietor.
     *
     * @param Gender|null $gender
     * @param Region|null $region
     * @return Person
     */
    private function person(?Gender $gender = null, ?Region $region = null): Person
    {
        $people = new PersonGenerator(
            new Randomizer(
                new Mt19937(4321),
            ),
        );

        return $people->generate($gender, $region);
    }

    /**
     * Assembles a person out of given parts, the way a user building a fixture by hand does.
     *
     * @param DateTimeImmutable $birthDate
     * @param string|null $inn
     * @return Person
     * @throws InvalidRequisite
     */
    private function assembled(DateTimeImmutable $birthDate, ?string $inn): Person
    {
        return new Person(
            Gender::Male,
            'Волков',
            'Пётр',
            'Ильич',
            $birthDate,
            $inn === null ? null : Inn::from($inn),
        );
    }

    /**
     * Every legal form, keyed by its own value so a failure names the form.
     *
     * @return array<string, array{LegalForm}>
     */
    public static function forms(): array
    {
        return array_combine(
            array_map(
                static fn(LegalForm $form): string => $form->value,
                LegalForm::cases(),
            ),
            array_map(
                static fn(LegalForm $form): array => [$form],
                LegalForm::cases(),
            ),
        );
    }

    /**
     * Builds a generator whose randomizer repeats itself for the same seed.
     *
     * @return OrganizationGenerator
     */
    private function generator(): OrganizationGenerator
    {
        return new OrganizationGenerator(
            new Randomizer(
                new Mt19937(1234),
            ),
        );
    }
}
