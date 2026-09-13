<?php

declare(strict_types=1);

namespace RuFaker\Tests\Result;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\Gender;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Inn;
use RuFaker\Result\Person;

#[CoversClass(Person::class)]
final class PersonTest extends TestCase
{
    /** Personal INN of the fixture person, twelve digits with a valid checksum. */
    private const string PERSONAL_INN = '500100732259';

    /** Date of birth of the fixture person: any past date does. */
    private const string BIRTH_DATE = '1980-05-17';

    #[Test]
    public function it_puts_the_parts_in_the_official_order(): void
    {
        $this->assertSame(
            'Иванов Иван Иванович',
            $this->ivanov()->fullName,
        );
    }

    #[Test]
    public function it_keeps_the_parts_apart(): void
    {
        $person = $this->ivanov();

        $this->assertSame(
            Gender::Male,
            $person->gender(),
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
                'gender' => 'женский',
                'last_name' => 'Иванова',
                'first_name' => 'Мария',
                'patronymic' => 'Ильинична',
                'birth_date' => self::BIRTH_DATE,
                'inn' => self::PERSONAL_INN,
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
            '{"gender":"женский","last_name":"Иванова","first_name":"Мария","patronymic":"Ильинична",'
            . '"birth_date":"1980-05-17","inn":"500100732259"}',
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

        new Person(
            Gender::Male,
            $lastName,
            $firstName,
            $patronymic,
            new DateTimeImmutable(self::BIRTH_DATE),
            Inn::from(self::PERSONAL_INN),
        );
    }

    #[Test]
    public function it_gives_a_typed_form_of_the_gender(): void
    {
        $person = $this->ivanov();

        $this->assertSame(
            'мужской',
            $person->gender,
        );

        $this->assertSame(
            'male',
            $person->gender()->value,
        );
    }

    #[Test]
    public function it_hands_out_the_date_of_birth_in_both_forms(): void
    {
        $person = $this->ivanov();

        $this->assertSame(
            '1980-05-17',
            $person->birthDate,
        );

        $this->assertSame(
            '1980-05-17',
            $person->birthDate()->format('Y-m-d'),
        );
    }

    #[Test]
    public function it_hands_out_the_taxpayer_number_in_both_forms(): void
    {
        $person = $this->ivanov();

        $this->assertSame(
            self::PERSONAL_INN,
            $person->inn,
        );

        $this->assertSame(
            self::PERSONAL_INN,
            $person->inn()?->value,
        );
    }

    #[Test]
    public function it_accepts_a_person_who_never_got_a_number(): void
    {
        $person = new Person(
            Gender::Male,
            'Иванов',
            'Иван',
            'Иванович',
            new DateTimeImmutable(self::BIRTH_DATE),
        );

        $this->assertNull(
            $person->inn,
        );

        $this->assertNull(
            $person->inn(),
        );

        $this->assertNull(
            $person->toArray()['inn'],
        );
    }

    #[Test]
    public function it_rejects_a_number_issued_to_an_organization(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('INN 7707083893 is not a personal one.');

        new Person(
            Gender::Male,
            'Иванов',
            'Иван',
            'Иванович',
            new DateTimeImmutable(self::BIRTH_DATE),
            Inn::from('7707083893'),
        );
    }

    #[Test]
    public function it_rejects_a_person_born_tomorrow(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('A person cannot be born in the future.');

        new Person(
            Gender::Male,
            'Иванов',
            'Иван',
            'Иванович',
            new DateTimeImmutable('+1 day'),
            Inn::from(self::PERSONAL_INN),
        );
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
        return new Person(
            Gender::Male,
            'Иванов',
            'Иван',
            'Иванович',
            new DateTimeImmutable(self::BIRTH_DATE),
            Inn::from(self::PERSONAL_INN),
        );
    }

    /**
     * Builds a female full name, the case where every part takes another form.
     *
     * @return Person
     * @throws InvalidRequisite
     */
    private function ivanova(): Person
    {
        return new Person(
            Gender::Female,
            'Иванова',
            'Мария',
            'Ильинична',
            new DateTimeImmutable(self::BIRTH_DATE),
            Inn::from(self::PERSONAL_INN),
        );
    }
}
