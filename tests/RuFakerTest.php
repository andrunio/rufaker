<?php

declare(strict_types=1);

namespace RuFaker\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Requisite\Region;
use RuFaker\RuFaker;

#[CoversClass(RuFaker::class)]
final class RuFakerTest extends TestCase
{
    /** Date of birth handed to the generator: any past day does, this one is simply written down. */
    private const string BIRTH_DATE = '1990-05-17';

    /** Name handed to the generator, a word the title book does not hold. */
    private const string GIVEN_TITLE = 'Рассвет';

    #[Test]
    public function it_repeats_itself_for_one_seed(): void
    {
        $this->assertSame(
            RuFaker::seeded(1234)->organization()->toArray(),
            RuFaker::seeded(1234)->organization()->toArray(),
        );

        $this->assertSame(
            RuFaker::seeded(1234)->bankAccount()->toArray(),
            RuFaker::seeded(1234)->bankAccount()->toArray(),
        );

        $this->assertSame(
            RuFaker::seeded(1234)->person()->toArray(),
            RuFaker::seeded(1234)->person()->toArray(),
        );
    }

    #[Test]
    public function it_differs_between_seeds(): void
    {
        $this->assertNotSame(
            RuFaker::seeded(1234)->organization()->toArray(),
            RuFaker::seeded(4321)->organization()->toArray(),
        );
    }

    #[Test]
    public function it_works_without_a_seed(): void
    {
        $faker = new RuFaker();

        $this->assertNotSame(
            $faker->organization()->inn,
            $faker->organization()->inn,
        );
    }

    #[Test]
    public function it_passes_arguments_down_to_the_generators(): void
    {
        $organization = RuFaker::seeded(1234)
            ->organization(LegalForm::Ip, Region::from('66'));

        $this->assertSame(
            'ИП',
            $organization->form,
        );

        $this->assertSame(
            '66',
            $organization->region,
        );

        $this->assertNull(
            $organization->kpp,
        );
    }

    #[Test]
    public function it_builds_a_sole_proprietor_out_of_a_given_person(): void
    {
        $faker = RuFaker::seeded(1234);
        $person = $faker->person(region: Region::from('66'));

        $proprietor = $faker->organization(person: $person);

        $this->assertSame(
            'ИП',
            $proprietor->form,
        );

        $this->assertSame(
            $person->inn,
            $proprietor->inn,
        );

        $this->assertSame(
            'ИП ' . $person->fullName,
            $proprietor->shortName,
        );
    }

    #[Test]
    public function it_names_a_legal_entity_the_way_it_was_asked(): void
    {
        $organization = RuFaker::seeded(1234)
            ->organization(LegalForm::Ooo, title: self::GIVEN_TITLE);

        $this->assertSame(
            'ООО "' . self::GIVEN_TITLE . '"',
            $organization->shortName,
        );

        $this->assertSame(
            'Общество с ограниченной ответственностью "' . self::GIVEN_TITLE . '"',
            $organization->fullName,
        );
    }

    #[Test]
    public function it_takes_a_given_date_of_birth(): void
    {
        $person = RuFaker::seeded(1234)
            ->person(birthDate: $this->birthDate());

        $this->assertSame(
            self::BIRTH_DATE,
            $person->birthDate,
        );
    }

    #[Test]
    public function it_issues_a_personal_inn_in_the_requested_region(): void
    {
        $person = RuFaker::seeded(1234)
            ->person(Gender::Male, Region::from('66'));

        $this->assertSame(
            '66',
            $person->inn()?->region()?->value,
        );

        $this->assertTrue(
            $person->inn()->isPersonal(),
        );
    }

    #[Test]
    public function it_exposes_standalone_requisites(): void
    {
        $faker = RuFaker::seeded(1234);

        $this->assertSame(
            10,
            strlen($faker->inn(LegalForm::Ooo)->value),
        );

        $this->assertSame(
            15,
            strlen($faker->ogrn(LegalForm::Ip)->value),
        );

        $this->assertSame(
            9,
            strlen($faker->kpp()->value),
        );

        $this->assertSame(
            9,
            strlen($faker->bik()->value),
        );
    }

    #[Test]
    public function it_builds_a_full_name(): void
    {
        $person = RuFaker::seeded(1234)
            ->person(Gender::Female);

        $this->assertSame(
            Gender::Female,
            $person->gender(),
        );

        $this->assertSame(
            "$person->lastName $person->firstName $person->patronymic",
            $person->fullName,
        );
    }

    #[Test]
    public function it_passes_the_initials_flag_down_to_the_generator(): void
    {
        $organization = RuFaker::seeded(1234)
            ->organization(LegalForm::Ip, initials: true);

        $this->assertMatchesRegularExpression(
            '/^ИП [А-ЯЁ][а-яё]+ [А-ЯЁ]\.[А-ЯЁ]\.$/u',
            $organization->shortName,
        );
    }

    #[Test]
    public function it_gives_a_sole_proprietor_the_name_of_the_requested_gender(): void
    {
        $organization = RuFaker::seeded(1234)
            ->organization(LegalForm::Ip, null, Gender::Male);

        $this->assertSame(
            Gender::Male,
            $organization->person()?->gender(),
        );
    }

    #[Test]
    public function it_leaves_a_legal_entity_without_a_name(): void
    {
        $organization = RuFaker::seeded(1234)
            ->organization(LegalForm::Ooo);

        $this->assertNull(
            $organization->person(),
        );
    }

    #[Test]
    public function it_serialises_a_whole_set_to_json(): void
    {
        $organization = RuFaker::seeded(1234)
            ->organization(LegalForm::Ooo);

        $this->assertSame(
            [
                'form',
                'short_name',
                'full_name',
                'region',
                'inn',
                'ogrn',
                'kpp',
                'registration_date',
                'person',
                'head',
                'head_position',
            ],
            array_keys($organization->toArray()),
        );

        $this->assertJson(
            (string)json_encode($organization),
        );
    }

    /**
     * Builds the date of birth handed to the generator, written out rather than counted off today.
     *
     * @return DateTimeImmutable
     */
    private function birthDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::BIRTH_DATE);
    }
}
