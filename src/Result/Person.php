<?php

declare(strict_types=1);

namespace RuFaker\Result;

use Override;
use RuFaker\Enum\Gender;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\ArrayValue;
use Stringable;

/**
 * Full name of a person, every part agreeing with one gender.
 */
final readonly class Person implements Result, Stringable
{
    use ArrayValue;

    /** Label of the gender every part of the name agrees with. */
    public string $gender;

    /** Name in the official order: last name, first name, patronymic. */
    public string $fullName;

    /** Gender as the enum case it came from. */
    private Gender $genderType;

    /**
     * Assembles a full name, rejecting a part left empty.
     *
     * @param Gender $gender
     * @param string $lastName
     * @param string $firstName
     * @param string $patronymic
     * @throws InvalidRequisite
     */
    public function __construct(
        Gender        $gender,
        public string $lastName,
        public string $firstName,
        public string $patronymic,
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

        $this->genderType = $gender;
        $this->gender = $gender->title();
        $this->fullName = "$lastName $firstName $patronymic";
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
     * Returns the name parts as plain strings, ready for a fixture or a payload.
     *
     * @return array{gender: string, last_name: string, first_name: string, patronymic: string}
     */
    public function toArray(): array
    {
        return [
            'gender' => $this->gender,
            'last_name' => $this->lastName,
            'first_name' => $this->firstName,
            'patronymic' => $this->patronymic,
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
