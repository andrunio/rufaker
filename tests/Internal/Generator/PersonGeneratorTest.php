<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Internal\Calendar;
use RuFaker\Internal\Generator\PersonGenerator;
use RuFaker\Internal\NameBook;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Person;

#[CoversClass(PersonGenerator::class)]
#[CoversClass(Person::class)]
final class PersonGeneratorTest extends TestCase
{
    /** How many names each property is checked over. */
    private const int RUNS = 300;

    /** Age of the oldest person the generator builds, mirroring its own constant. */
    private const int OLDEST_AGE = 80;

    /** Age of the youngest, mirroring the constant that keeps everyone past adulthood. */
    private const int YOUNGEST_AGE = 19;

    /** Age of majority under article 21 of the Civil Code. */
    private const int ADULT_AGE = 18;

    /** Personal INN handed to the generator, twelve digits with a valid checksum. */
    private const string PERSONAL_INN = '500100732259';

    #[Test]
    #[DataProvider('genders')]
    public function it_agrees_every_part_with_the_requested_gender(Gender $gender): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $person = $generator->generate($gender);

            $this->assertSame(
                $gender,
                $person->gender(),
            );

            $this->assertContains(
                $person->lastName,
                NameBook::lastNames($gender),
            );

            $this->assertContains(
                $person->firstName,
                NameBook::firstNames($gender),
            );

            $this->assertContains(
                $person->patronymic,
                NameBook::patronymics($gender),
            );
        }
    }

    #[Test]
    public function it_draws_both_genders_when_none_is_requested(): void
    {
        $generator = $this->generator();
        $drawn = [];

        foreach (range(1, self::RUNS) as $ignored) {
            $drawn[$generator->generate()->gender] = true;
        }

        $this->assertArrayHasKey(
            'мужской',
            $drawn,
        );

        $this->assertArrayHasKey(
            'женский',
            $drawn,
        );
    }

    #[Test]
    public function it_never_mixes_forms_within_one_name(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $person = $generator->generate();
            $ending = $person->gender()->isFemale() ? '/на$/u' : '/ич$/u';

            $this->assertMatchesRegularExpression(
                $ending,
                $person->patronymic,
            );
        }
    }

    #[Test]
    public function it_carries_the_number_it_was_handed(): void
    {
        $this->assertSame(
            self::PERSONAL_INN,
            $this->generator()->generate(inn: $this->inn())->inn,
        );
    }

    #[Test]
    public function it_carries_the_date_of_birth_it_was_handed(): void
    {
        $birthDate = Calendar::yearOpens(self::ADULT_AGE);

        $person = $this->generator()
            ->generate(birthDate: $birthDate);

        $this->assertSame(
            $birthDate->format('Y-m-d'),
            $person->birthDate,
        );

        $this->assertSame(
            $birthDate,
            $person->birthDate(),
        );
    }

    #[Test]
    public function it_issues_a_personal_number_of_the_requested_region(): void
    {
        $inn = $this->generator()
            ->generate(region: Region::from('66'))
            ->inn;

        $this->assertSame(
            '66',
            substr((string)$inn, 0, 2),
        );

        $this->assertSame(
            12,
            strlen((string)$inn),
        );
    }

    #[Test]
    public function it_draws_a_date_of_birth_of_a_grown_up_person(): void
    {
        $generator = $this->generator();
        $oldest = Calendar::yearOpens(self::OLDEST_AGE);
        $youngest = Calendar::yearCloses(self::YOUNGEST_AGE);

        foreach (range(1, self::RUNS) as $ignored) {
            $person = $generator->generate();
            $birth = $person->birthDate();

            $this->assertGreaterThanOrEqual(
                $oldest,
                $birth,
            );

            $this->assertLessThanOrEqual(
                $youngest,
                $birth,
            );

            $this->assertSame(
                $birth->format('Y-m-d'),
                $person->birthDate,
            );
        }
    }

    #[Test]
    public function it_draws_nobody_younger_than_the_age_of_majority(): void
    {
        $generator = $this->generator();

        $today = Calendar::yearCloses(0)->setDate(
            (int)Calendar::yearCloses(0)->format('Y'), 1, 1,
        );

        foreach (range(1, self::RUNS) as $ignored) {
            $age = $generator->generate()->birthDate()->diff($today)->y;

            $this->assertGreaterThanOrEqual(
                self::ADULT_AGE,
                $age,
            );
        }
    }

    /**
     * Both genders, keyed by value so a failure names the one that broke.
     *
     * @return array<string, array{Gender}>
     */
    public static function genders(): array
    {
        return [
            'male' => [Gender::Male],
            'female' => [Gender::Female],
        ];
    }

    /**
     * Builds the personal number every generated person carries here.
     *
     * @return Inn
     */
    private function inn(): Inn
    {
        return Inn::from(self::PERSONAL_INN);
    }

    /**
     * Builds a generator whose randomizer repeats itself for the same seed.
     *
     * @return PersonGenerator
     */
    private function generator(): PersonGenerator
    {
        return new PersonGenerator(
            new Randomizer(
                new Mt19937(1234),
            ),
        );
    }
}
