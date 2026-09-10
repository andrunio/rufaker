<?php

declare(strict_types=1);

namespace RuFaker\Tests\Result;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\Gender;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Result\Person;

#[CoversClass(Person::class)]
final class PersonTest extends TestCase
{
    #[Test]
    public function it_puts_the_parts_in_the_official_order(): void
    {
        $this->assertSame(
            'Иванов Иван Иванович',
            $this->ivanov()->full(),
        );
    }

    #[Test]
    public function it_keeps_the_parts_apart(): void
    {
        $person = $this->ivanov();

        $this->assertSame(
            Gender::Male,
            $person->gender,
        );

        $this->assertSame(
            'Иванов',
            $person->lastName,
        );

        $this->assertSame(
            'Иван',
            $person->firstName,
        );

        $this->assertSame(
            'Иванович',
            $person->patronymic,
        );
    }

    #[Test]
    public function it_reads_as_a_string(): void
    {
        $this->assertSame(
            'Иванов Иван Иванович',
            (string)$this->ivanov(),
        );
    }

    #[Test]
    public function it_exports_the_parts_as_strings(): void
    {
        $this->assertSame(
            [
                'gender' => 'female',
                'last_name' => 'Иванова',
                'first_name' => 'Мария',
                'patronymic' => 'Ильинична',
            ],
            $this->ivanova()->toArray(),
        );
    }

    #[Test]
    public function it_encodes_itself_to_json(): void
    {
        $person = $this->ivanova();

        $this->assertSame(
            $person->toArray(),
            $person->jsonSerialize(),
        );

        $this->assertSame(
            '{"gender":"female","last_name":"Иванова","first_name":"Мария","patronymic":"Ильинична"}',
            json_encode($person, JSON_UNESCAPED_UNICODE),
        );
    }

    #[Test]
    #[DataProvider('emptyParts')]
    public function it_rejects_a_name_with_an_empty_part(
        string $lastName,
        string $firstName,
        string $patronymic,
        string $message,
    ): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage($message);

        new Person(Gender::Male, $lastName, $firstName, $patronymic);
    }

    /**
     * Names with one part missing, keyed by the part that is gone.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function emptyParts(): array
    {
        return [
            'last name' => [
                '',
                'Иван',
                'Иванович',
                'A person must have a last name.',
            ],
            'first name' => [
                'Иванов',
                '',
                'Иванович',
                'A person must have a first name.',
            ],
            'patronymic' => [
                'Иванов',
                'Иван',
                '   ',
                'A person must have a patronymic.',
            ],
        ];
    }

    /**
     * Builds a male full name whose parts agree with each other.
     *
     * @return Person
     * @throws InvalidRequisite
     */
    private function ivanov(): Person
    {
        return new Person(Gender::Male, 'Иванов', 'Иван', 'Иванович');
    }

    /**
     * Builds a female full name, the case where every part takes another form.
     *
     * @return Person
     * @throws InvalidRequisite
     */
    private function ivanova(): Person
    {
        return new Person(Gender::Female, 'Иванова', 'Мария', 'Ильинична');
    }
}
