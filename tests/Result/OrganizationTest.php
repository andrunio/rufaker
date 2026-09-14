<?php

declare(strict_types=1);

namespace RuFaker\Tests\Result;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\LegalForm;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Enum\Gender;
use RuFaker\Result\Organization;
use RuFaker\Result\Person;

#[CoversClass(Organization::class)]
final class OrganizationTest extends TestCase
{
    /** Region code of Moscow, where every Sberbank requisite below was issued. */
    private const string MOSCOW = '77';

    /** Region code of the Moscow oblast, where the sole proprietor requisites below were issued. */
    private const string MOSCOW_OBLAST = '50';

    /** Region code of Saint Petersburg, used wherever a requisite must come from elsewhere. */
    private const string PETERSBURG = '78';

    /** Proper name of Sberbank, the one its full name carries. */
    private const string SBERBANK_TITLE = 'Сбербанк России';

    /** INN of Sberbank, a public joint-stock company. */
    private const string SBERBANK_INN = '7707083893';

    /** Registry number of Sberbank. */
    private const string SBERBANK_OGRN = '1027700132195';

    /** KPP of the Sberbank head office. */
    private const string SBERBANK_KPP = '773601001';

    /** INN of a person, twelve digits; the same value the INN test set is built on. */
    private const string PERSONAL_INN = '500100732259';

    /** Another personal INN of the same region, completed by the package: the fixture needs two. */
    private const string OTHER_PERSONAL_INN = '500100000199';

    /** Date of birth of the sole proprietor in the fixtures: any past date does. */
    private const string BIRTH_DATE = '1980-05-17';

    /** Registry number of a sole proprietor, fifteen digits, completed by the package itself. */
    private const string SOLE_PROPRIETOR_OGRN = '304500100000017';

    /** Day a legal entity in the fixtures was registered: any day of the year its number carries. */
    private const string ENTITY_REGISTERED = '2002-12-02';

    /** Day the sole proprietor in the fixtures was registered: the year again comes from the number. */
    private const string PROPRIETOR_REGISTERED = '2004-03-15';

    /** INN of a legal entity from another region, completed by the package itself. */
    private const string PETERSBURG_INN = '7800000010';

    /** Registry number of a legal entity from another region, completed by the package itself. */
    private const string PETERSBURG_OGRN = '1027801000017';

    #[Test]
    public function it_assembles_a_legal_entity(): void
    {
        $organization = $this->sberbank();

        $this->assertSame(
            LegalForm::Pao,
            $organization->form(),
        );

        $this->assertSame(
            self::SBERBANK_INN,
            $organization->inn,
        );

        $this->assertSame(
            self::SBERBANK_KPP,
            $organization->kpp,
        );
    }

    #[Test]
    public function it_assembles_a_sole_proprietor(): void
    {
        $organization = $this->soleProprietor();

        $this->assertSame(
            LegalForm::Ip,
            $organization->form(),
        );

        $this->assertSame(
            self::SOLE_PROPRIETOR_OGRN,
            $organization->ogrn,
        );

        $this->assertNull(
            $organization->kpp,
        );
    }

    #[Test]
    public function it_rejects_an_inn_of_the_wrong_length_for_the_form(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('INN of ip must be 12 digits long.');

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            null,
            $this->entrepreneur(),
        );
    }

    #[Test]
    public function it_rejects_a_registry_number_of_the_wrong_length_for_the_form(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Registry number of pao must be 13 digits long.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
        );
    }

    #[Test]
    public function it_rejects_a_legal_entity_without_a_kpp(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Presence of a KPP contradicts the legal form pao.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_with_a_kpp(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Presence of a KPP contradicts the legal form ip.');

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            $this->entrepreneur(),
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_without_a_name(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Presence of a full name contradicts the legal form ip.');

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
        );
    }

    #[Test]
    public function it_rejects_a_legal_entity_carrying_a_name(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Presence of a full name contradicts the legal form pao.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            $this->entrepreneur(),
        );
    }

    #[Test]
    public function it_names_a_legal_entity_in_both_forms(): void
    {
        $organization = $this->sberbank();

        $this->assertSame(
            'ПАО "Сбербанк России"',
            $organization->shortName,
        );

        $this->assertSame(
            'Публичное акционерное общество "Сбербанк России"',
            $organization->fullName,
        );
    }

    #[Test]
    public function it_names_a_sole_proprietor_by_the_full_name_of_the_person(): void
    {
        $organization = $this->soleProprietor();

        $this->assertSame(
            'ИП Иванов Иван Иванович',
            $organization->shortName,
        );

        $this->assertSame(
            'Индивидуальный предприниматель Иванов Иван Иванович',
            $organization->fullName,
        );
    }

    #[Test]
    public function it_cuts_the_name_of_a_sole_proprietor_to_initials_when_asked(): void
    {
        $organization = new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            null,
            $this->entrepreneur(),
            null,
            true,
        );

        $this->assertSame(
            'ИП Иванов И.И.',
            $organization->shortName,
        );

        $this->assertSame(
            'Индивидуальный предприниматель Иванов Иван Иванович',
            $organization->fullName,
        );
    }

    #[Test]
    public function it_rejects_a_legal_entity_without_a_title(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Presence of a title contradicts the legal form pao.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_carrying_a_title(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Presence of a title contradicts the legal form ip.');

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            null,
            $this->entrepreneur(),
            self::SBERBANK_TITLE,
        );
    }

    #[Test]
    public function it_rejects_an_empty_title(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('A business must have a title.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            null,
            '   ',
        );
    }

    #[Test]
    public function it_names_the_person_behind_a_sole_proprietor(): void
    {
        $this->assertSame(
            'Иванов Иван Иванович',
            $this->soleProprietor()->person()?->fullName,
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_without_a_number_of_their_own(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Иванов Иван Иванович carries no INN, and a sole proprietor must.');

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            null,
            new Person(
                Gender::Male,
                'Иванов',
                'Иван',
                'Иванович',
                $this->date(self::BIRTH_DATE),
            ),
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_whose_number_is_not_their_own(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage(
            'INN ' . self::OTHER_PERSONAL_INN . ' does not belong to Иванов Иван Иванович.',
        );

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::OTHER_PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            null,
            $this->entrepreneur(),
        );
    }

    #[Test]
    public function it_rejects_an_inn_from_another_region(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('INN ' . self::SBERBANK_INN . ' does not belong to region 78.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::PETERSBURG),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::PETERSBURG_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            null,
            self::SBERBANK_TITLE,
        );
    }

    #[Test]
    public function it_rejects_a_registry_number_from_another_region(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Registry number ' . self::SBERBANK_OGRN . ' does not belong to region 78.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::PETERSBURG),
            Inn::from(self::PETERSBURG_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            null,
            self::SBERBANK_TITLE,
        );
    }

    #[Test]
    public function it_rejects_a_kpp_from_another_region(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('KPP ' . self::SBERBANK_KPP . ' does not belong to region 78.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::PETERSBURG),
            Inn::from(self::PETERSBURG_INN),
            Ogrn::from(self::PETERSBURG_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            null,
            self::SBERBANK_TITLE,
        );
    }

    #[Test]
    public function it_rejects_a_date_earlier_than_the_registry_itself(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('The registry of pao opened on 2002-07-01, after 2002-01-15.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date('2002-01-15'),
            Kpp::from(self::SBERBANK_KPP),
            null,
            self::SBERBANK_TITLE,
        );
    }

    #[Test]
    public function it_rejects_a_registry_number_carrying_another_year(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Registry number 1027700132195 does not carry the year of 2019-04-08.');

        new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date('2019-04-08'),
            Kpp::from(self::SBERBANK_KPP),
            null,
            self::SBERBANK_TITLE,
        );
    }

    #[Test]
    public function it_rejects_a_sole_proprietor_registered_before_they_were_born(): void
    {
        $this->expectException(InvalidRequisite::class);
        $this->expectExceptionMessage('Иванов Иван Иванович was born after 2004-03-15.');

        new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            null,
            $this->newborn(),
        );
    }

    #[Test]
    public function it_exports_the_requisites_as_strings(): void
    {
        $this->assertSame(
            [
                'form' => 'ПАО',
                'short_name' => 'ПАО "Сбербанк России"',
                'full_name' => 'Публичное акционерное общество "Сбербанк России"',
                'region' => self::MOSCOW,
                'inn' => self::SBERBANK_INN,
                'ogrn' => self::SBERBANK_OGRN,
                'kpp' => self::SBERBANK_KPP,
                'registration_date' => self::ENTITY_REGISTERED,
                'person' => null,
            ],
            $this->sberbank()->toArray(),
        );
    }

    #[Test]
    public function it_leaves_the_kpp_of_a_sole_proprietor_empty(): void
    {
        $this->assertNull(
            $this->soleProprietor()->toArray()['kpp'],
        );
    }

    #[Test]
    public function it_encodes_itself_to_json(): void
    {
        $organization = $this->sberbank();

        $this->assertSame(
            $organization->toArray(),
            $organization->jsonSerialize(),
        );

        $this->assertSame(
            '{"form":"ПАО","short_name":"ПАО \"Сбербанк России\"",'
            . '"full_name":"Публичное акционерное общество \"Сбербанк России\"",'
            . '"region":"77","inn":"7707083893","ogrn":"1027700132195","kpp":"773601001",'
            . '"registration_date":"2002-12-02","person":null}',
            json_encode($organization, JSON_UNESCAPED_UNICODE),
        );
    }

    #[Test]
    public function it_gives_a_typed_form_of_every_field(): void
    {
        $organization = $this->sberbank();

        $this->assertSame(
            'ПАО',
            $organization->form,
        );

        $this->assertSame(
            'pao',
            $organization->form()->value,
        );

        $this->assertSame(
            $organization->region,
            $organization->region()->value,
        );

        $this->assertSame(
            $organization->inn,
            $organization->inn()->value,
        );

        $this->assertSame(
            $organization->ogrn,
            $organization->ogrn()->value,
        );

        $this->assertSame(
            $organization->kpp,
            $organization->kpp()?->value,
        );

        $this->assertNull(
            $organization->person(),
        );
    }

    #[Test]
    public function it_gives_the_name_of_a_sole_proprietor_part_by_part(): void
    {
        $person = $this->soleProprietor()->person();

        $this->assertInstanceOf(
            Person::class,
            $person,
        );

        $this->assertSame(
            'Иван',
            $person->firstName,
        );
    }

    /**
     * Builds the requisites of Sberbank, a legal entity whose parts agree with each other.
     *
     * @return Organization
     * @throws InvalidRequisite
     */
    private function sberbank(): Organization
    {
        return new Organization(
            LegalForm::Pao,
            Region::from(self::MOSCOW),
            Inn::from(self::SBERBANK_INN),
            Ogrn::from(self::SBERBANK_OGRN),
            $this->date(self::ENTITY_REGISTERED),
            Kpp::from(self::SBERBANK_KPP),
            null,
            self::SBERBANK_TITLE,
        );
    }

    /**
     * Builds the requisites of a sole proprietor, the form that carries no KPP.
     *
     * @return Organization
     * @throws InvalidRequisite
     */
    private function soleProprietor(): Organization
    {
        return new Organization(
            LegalForm::Ip,
            Region::from(self::MOSCOW_OBLAST),
            Inn::from(self::PERSONAL_INN),
            Ogrn::from(self::SOLE_PROPRIETOR_OGRN),
            $this->date(self::PROPRIETOR_REGISTERED),
            null,
            $this->entrepreneur(),
        );
    }

    /**
     * Wraps a calendar date written in the fixtures above.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param string $value
     * @return DateTimeImmutable
     */
    private function date(string $value): DateTimeImmutable
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new DateTimeImmutable($value);
    }

    /**
     * Builds a proprietor born after the registration date of the fixtures.
     *
     * @return Person
     * @throws InvalidRequisite
     */
    private function newborn(): Person
    {
        return new Person(
            Gender::Male,
            'Иванов',
            'Иван',
            'Иванович',
            $this->date('2005-06-01'),
            Inn::from(self::PERSONAL_INN),
        );
    }

    /**
     * Builds the full name a sole proprietor does business under.
     *
     * @return Person
     * @throws InvalidRequisite
     */
    private function entrepreneur(): Person
    {
        return new Person(
            Gender::Male,
            'Иванов',
            'Иван',
            'Иванович',
            $this->date(self::BIRTH_DATE),
            Inn::from(self::PERSONAL_INN),
        );
    }
}
