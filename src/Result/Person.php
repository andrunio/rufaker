<?php

declare(strict_types=1);

namespace RuFaker\Result;

use DateTimeImmutable;
use Override;
use RuFaker\Enum\Gender;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\ArrayValue;
use RuFaker\Requisite\Inn;
use Stringable;

/**
 * A person: a name agreeing in gender, a date of birth and a taxpayer number, if one was issued.
 */
final readonly class Person implements Result, Stringable
{
    use ArrayValue;

    /** Format the date of birth is handed out in: the ISO 8601 calendar date. */
    private const string DATE_FORMAT = 'Y-m-d';

    /** Label of the gender every part of the name agrees with. */
    public string $gender;

    /** Name in the official order: last name, first name, patronymic. */
    public string $fullName;

    /** Date of birth in ISO 8601. */
    public string $birthDate;

    /** Taxpayer number, the one a sole proprietor keeps after registration; empty if never issued. */
    public ?string $inn;

    /** Gender as the enum case it came from. */
    private Gender $genderType;

    /** Date of birth as the object it came from. */
    private DateTimeImmutable $birthDateType;

    /** Taxpayer number as the requisite it came from. */
    private ?Inn $innType;

    /**
     * Assembles a person, rejecting an empty part, a number of an organization or a future birth.
     *
     * @param Gender $gender
     * @param string $lastName
     * @param string $firstName
     * @param string $patronymic
     * @param DateTimeImmutable $birthDate
     * @param Inn|null $inn
     * @throws InvalidRequisite
     */
    public function __construct(
        Gender            $gender,
        public string     $lastName,
        public string     $firstName,
        public string     $patronymic,
        DateTimeImmutable $birthDate,
        ?Inn              $inn = null,
    )
    {
        $parts = [
            'last name' => $lastName,
            'first name' => $firstName,
            'patronymic' => $patronymic,
        ];

        foreach ($parts as $part => $value) {
            if (trim($value) === '') {
                throw InvalidRequisite::because("A person must have a $part.");
            }
        }

        if ($inn instanceof Inn && !$inn->isPersonal()) {
            throw InvalidRequisite::because("INN $inn->value is not a personal one.");
        }

        if ($birthDate > new DateTimeImmutable()) {
            throw InvalidRequisite::because('A person cannot be born in the future.');
        }

        $this->genderType = $gender;
        $this->birthDateType = $birthDate;
        $this->innType = $inn;

        $this->gender = $gender->title();
        $this->fullName = "$lastName $firstName $patronymic";
        $this->birthDate = $birthDate->format(self::DATE_FORMAT);
        $this->inn = $inn?->value;
    }

    /**
     * Returns the gender as an enum case.
     *
     * @return Gender
     */
    public function gender(): Gender
    {
        return $this->genderType;
    }

    /**
     * Returns the date of birth as the object it came from.
     *
     * @return DateTimeImmutable
     */
    public function birthDate(): DateTimeImmutable
    {
        return $this->birthDateType;
    }

    /**
     * Returns the taxpayer number as the requisite it came from.
     *
     * @return Inn|null
     */
    public function inn(): ?Inn
    {
        return $this->innType;
    }

    /**
     * Returns every part as a plain string, ready for a fixture or a payload.
     *
     * @return array{
     *     gender: string,
     *     last_name: string,
     *     first_name: string,
     *     patronymic: string,
     *     birth_date: string,
     *     inn: string|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'gender' => $this->gender,
            'last_name' => $this->lastName,
            'first_name' => $this->firstName,
            'patronymic' => $this->patronymic,
            'birth_date' => $this->birthDate,
            'inn' => $this->inn,
        ];
    }

    /**
     * Returns the value as a string.
     *
     * @return string
     */
    #[Override]
    public function __toString(): string
    {
        return $this->fullName;
    }
}
