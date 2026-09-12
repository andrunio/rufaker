<?php

declare(strict_types=1);

namespace RuFaker\Internal\Generator;

use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Internal\NameBook;
use RuFaker\Result\Person;

/**
 * Builds full names whose parts agree in gender.
 *
 * @internal
 */
final readonly class PersonGenerator
{
    /**
     * Builds a generator drawing from the given randomizer.
     *
     * @param Randomizer $randomizer
     */
    public function __construct(private Randomizer $randomizer)
    {
    }

    /**
     * Builds one person whose last name, first name and patronymic share a gender.
     *
     * @param Gender|null $gender
     * @return Person
     */
    public function generate(?Gender $gender = null): Person
    {
        $gender ??= $this->gender();

        return new Person(
            $gender,
            $this->pick(NameBook::lastNames($gender)),
            $this->pick(NameBook::firstNames($gender)),
            $this->pick(NameBook::patronymics($gender)),
        );
    }

    /**
     * Draws one name part out of the given list.
     *
     * @param list<string> $parts
     * @return string
     */
    private function pick(array $parts): string
    {
        return $parts[$this->randomizer->getInt(0, count($parts) - 1)];
    }

    /**
     * Picks a gender at random.
     *
     * @return Gender
     */
    private function gender(): Gender
    {
        $genders = Gender::cases();

        return $genders[$this->randomizer->getInt(0, count($genders) - 1)];
    }
}
