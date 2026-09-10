<?php

declare(strict_types=1);

namespace RuFaker\Tests\Result;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\LegalForm;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Organization;

#[CoversClass(Organization::class)]
final class OrganizationTest extends TestCase
{
    /** Region code of Moscow, where every Sberbank requisite below was issued. */
    private const string MOSCOW = '77';

    /** Region code of the Moscow oblast, where the sole proprietor requisites below were issued. */
    private const string MOSCOW_OBLAST = '50';

    /** Region code of Saint Petersburg, used wherever a requisite must come from elsewhere. */
    private const string PETERSBURG = '78';

    /** INN of Sberbank, a public joint-stock company. */
    private const string SBERBANK_INN = '7707083893';

    /** Registry number of Sberbank. */
    private const string SBERBANK_OGRN = '1027700132195';

    /** KPP of the Sberbank head office. */
    private const string SBERBANK_KPP = '773601001';

    /** INN of a person, twelve digits; the same value the INN test set is built on. */
    private const string PERSONAL_INN = '500100732259';

    /** Registry number of a sole proprietor, fifteen digits, completed by the package itself. */
    private const string SOLE_PROPRIETOR_OGRN = '304500100000017';

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
            $organization->form,
        );

        $this->assertSame(
            self::SBERBANK_INN,
            $organization->inn->value,
        );

        $this->assertSame(
            self::SBERBANK_KPP,
            $organization->kpp?->value,
        );
    }

    #[Test]
    public function it_assembles_a_sole_proprietor(): void
    {
        $organization = $this->soleProprietor();

        $this->assertSame(
            LegalForm::Ip,
            $organization->form,
        );

        $this->assertSame(
            self::SOLE_PROPRIETOR_OGRN,
            $organization->ogrn->value,
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
            Kpp::from(self::SBERBANK_KPP),
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
            Kpp::from(self::SBERBANK_KPP),
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
            Kpp::from(self::SBERBANK_KPP),
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
            Kpp::from(self::SBERBANK_KPP),
        );
    }

    #[Test]
    public function it_exports_the_requisites_as_strings(): void
    {
        $this->assertSame(
            [
                'form' => 'pao',
                'region' => self::MOSCOW,
                'inn' => self::SBERBANK_INN,
                'ogrn' => self::SBERBANK_OGRN,
                'kpp' => self::SBERBANK_KPP,
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
            '{"form":"pao","region":"77","inn":"7707083893","ogrn":"1027700132195","kpp":"773601001"}',
            json_encode($organization),
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
            Kpp::from(self::SBERBANK_KPP),
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
        );
    }
}
