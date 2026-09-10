<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\Gender;
use RuFaker\Internal\NameBook;

#[CoversClass(NameBook::class)]
final class NameBookTest extends TestCase
{
    #[Test]
    #[DataProvider('genders')]
    public function it_holds_every_part_of_a_name_for_each_gender(Gender $gender): void
    {
        $this->assertNotEmpty(
            NameBook::firstNames($gender),
        );

        $this->assertNotEmpty(
            NameBook::patronymics($gender),
        );

        $this->assertNotEmpty(
            NameBook::lastNames($gender),
        );
    }

    #[Test]
    #[DataProvider('genders')]
    public function it_repeats_no_name_within_a_list(Gender $gender): void
    {
        $firstNames = NameBook::firstNames($gender);

        $this->assertSame(
            $firstNames,
            array_values(array_unique($firstNames)),
        );

        $lastNames = NameBook::lastNames($gender);

        $this->assertSame(
            $lastNames,
            array_values(array_unique($lastNames)),
        );
    }

    #[Test]
    public function it_gives_every_father_both_forms_of_his_patronymic(): void
    {
        $this->assertCount(
            count(NameBook::patronymics(Gender::Male)),
            NameBook::patronymics(Gender::Female),
        );

        $this->assertCount(
            count(NameBook::firstNames(Gender::Male)),
            NameBook::patronymics(Gender::Male),
        );
    }

    #[Test]
    public function it_gives_every_last_name_both_forms(): void
    {
        $this->assertCount(
            count(NameBook::lastNames(Gender::Male)),
            NameBook::lastNames(Gender::Female),
        );
    }

    #[Test]
    public function it_ends_a_patronymic_the_way_the_gender_requires(): void
    {
        foreach (NameBook::patronymics(Gender::Male) as $patronymic) {
            $this->assertMatchesRegularExpression(
                '/ич$/u',
                $patronymic,
            );
        }

        foreach (NameBook::patronymics(Gender::Female) as $patronymic) {
            $this->assertMatchesRegularExpression(
                '/на$/u',
                $patronymic,
            );
        }
    }

    #[Test]
    public function it_ends_a_last_name_the_way_the_gender_requires(): void
    {
        // A feminine last name either takes the -а/-ая ending or stays indeclinable, as Шевченко does.
        foreach (NameBook::lastNames(Gender::Female) as $lastName) {
            $this->assertMatchesRegularExpression(
                '/[аяо]$/u',
                $lastName,
            );
        }

        foreach (NameBook::lastNames(Gender::Male) as $lastName) {
            $this->assertDoesNotMatchRegularExpression(
                '/[ая]$/u',
                $lastName,
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
}
