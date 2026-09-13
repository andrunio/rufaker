<?php

declare(strict_types=1);

namespace RuFaker\Internal\Generator;

use DateTimeImmutable;
use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Internal\Digits;
use RuFaker\Internal\NameBook;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Person;

/**
 * Builds people: a name agreeing in gender, a date of birth and a personal INN.
 *
 * @internal
 */
final readonly class PersonGenerator
{
    /** Earliest year of birth a generator draws, the oldest person it builds. */
    private const int FIRST_YEAR = 1946;

    /** Latest year of birth: anyone born by then has been an adult since 2026. */
    private const int LAST_YEAR = 2008;

    /** Digits a personal INN carries beyond the region code and the two checksum ones. */
    private const int INN_BODY_DIGITS = 8;

    /**
     * Builds a generator drawing from the given randomizer.
     *
     * @param Randomizer $randomizer
     */
    public function __construct(private Randomizer $randomizer)
    {
    }

    /**
     * Builds one person whose name parts share a gender, carrying a personal INN of their own.
     *
     * @param Gender|null $gender
     * @param Region|null $region
     * @param Inn|null $inn
     * @return Person
     */
    public function generate(?Gender $gender = null, ?Region $region = null, ?Inn $inn = null): Person
    {
        $gender ??= $this->gender();
        $inn ??= $this->inn($region ?? Region::random($this->randomizer));

        return new Person(
            $gender,
            $this->pick(NameBook::lastNames($gender)),
            $this->pick(NameBook::firstNames($gender)),
            $this->pick(NameBook::patronymics($gender)),
            $this->birthDate(),
            $inn,
        );
    }

    /**
     * Builds a personal INN opened by the code of the given region.
     *
     * @param Region $region
     * @return Inn
     */
    private function inn(Region $region): Inn
    {
        return Inn::fromBody($region->value . Digits::random($this->randomizer, self::INN_BODY_DIGITS));
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
     * Draws a date of birth of a person old enough to sign for themselves.
     *
     * @return DateTimeImmutable
     */
    private function birthDate(): DateTimeImmutable
    {
        $year = $this->randomizer->getInt(self::FIRST_YEAR, self::LAST_YEAR);
        $month = $this->randomizer->getInt(1, 12);

        // The last day differs between months, and February differs between years.
        $days = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');

        // Every part comes from getInt() within known bounds, so the date is valid by construction.
        return new DateTimeImmutable(
            sprintf('%04d-%02d-%02d', $year, $month, $this->randomizer->getInt(1, $days)),
        );
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
