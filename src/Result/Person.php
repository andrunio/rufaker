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
        public Gender $gender,
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
    }

    /**
     * Returns the name in the official order: last name, first name, patronymic.
     *
     * @return string
     */
    public function full(): string
    {
        return "$this->lastName $this->firstName $this->patronymic";
    }

    /**
     * Returns the name parts as plain strings, ready for a fixture or a payload.
     *
     * @return array{gender: string, last_name: string, first_name: string, patronymic: string}
     */
    public function toArray(): array
    {
        return [
            'gender' => $this->gender->value,
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
        return $this->full();
    }
}
