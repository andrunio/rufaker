<?php

declare(strict_types=1);

namespace RuFaker\Tests\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Generator\PersonGenerator;
use RuFaker\Internal\NameBook;
use RuFaker\Result\Person;

#[CoversClass(PersonGenerator::class)]
#[CoversClass(Person::class)]
final class PersonGeneratorTest extends TestCase
{
    /** How many names each property is checked over. */
    private const int RUNS = 300;

    #[Test]
    #[DataProvider('genders')]
    public function it_agrees_every_part_with_the_requested_gender(Gender $gender): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $person = $generator->generate($gender);

            $this->assertSame(
                $gender,
                $person->gender,
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
            $drawn[$generator->generate()->gender->value] = true;
        }

        $this->assertArrayHasKey(
            'male',
            $drawn,
        );

        $this->assertArrayHasKey(
            'female',
            $drawn,
        );
    }

    #[Test]
    public function it_never_mixes_forms_within_one_name(): void
    {
        $generator = $this->generator();

        foreach (range(1, self::RUNS) as $ignored) {
            $person = $generator->generate();
            $ending = $person->gender->isFemale() ? '/на$/u' : '/ич$/u';

            $this->assertMatchesRegularExpression(
                $ending,
                $person->patronymic,
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
