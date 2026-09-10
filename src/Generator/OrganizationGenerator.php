<?php

declare(strict_types=1);

namespace RuFaker\Generator;

use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Organization;

/**
 * Builds business requisites that agree with each other.
 */
final readonly class OrganizationGenerator
{
    /** Alphabet a random digit sequence is drawn from. */
    private const string DIGITS = '0123456789';

    /** Earliest registration year encoded in a registry number, two digits. */
    private const int FIRST_YEAR = 2;

    /** Latest registration year encoded in a registry number, two digits. */
    private const int LAST_YEAR = 26;

    /** Reason a head office is put on record: the place of its own location. */
    private const string HEAD_OFFICE_REASON = '01';

    /** Sequence number of the first registration made on that reason. */
    private const string FIRST_SEQUENCE = '001';

    /** Names of sole proprietors, the one part of a business that is a person. */
    private PersonGenerator $people;

    /**
     * Builds a generator drawing from the given randomizer.
     *
     * @param Randomizer $randomizer
     */
    public function __construct(private Randomizer $randomizer)
    {
        $this->people = new PersonGenerator($randomizer);
    }

    /**
     * Builds one business whose INN, registry number and KPP share a region and a tax office.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @param Gender|null $gender
     * @return Organization
     */
    public function generate(?LegalForm $form = null, ?Region $region = null, ?Gender $gender = null): Organization
    {
        $form ??= $this->form();
        $region ??= Region::random($this->randomizer);
        $taxOffice = $this->taxOffice();

        return new Organization(
            $form,
            $region,
            $this->buildInn($form, $region),
            $this->buildRegistryNumber($form, $region, $taxOffice),
            $form->hasKpp() ? $this->buildKpp($region, $taxOffice) : null,
            $form->isIndividual() ? $this->people->generate($gender) : null,
        );
    }

    /**
     * Builds a standalone INN.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @return Inn
     */
    public function inn(?LegalForm $form = null, ?Region $region = null): Inn
    {
        return $this->buildInn(
            $form ?? $this->form(),
            $region ?? Region::random($this->randomizer),
        );
    }

    /**
     * Builds a standalone state registry number.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @return Ogrn
     */
    public function registryNumber(?LegalForm $form = null, ?Region $region = null): Ogrn
    {
        return $this->buildRegistryNumber(
            $form ?? $this->form(),
            $region ?? Region::random($this->randomizer),
            $this->taxOffice(),
        );
    }

    /**
     * Builds a standalone KPP of a head office.
     *
     * @param Region|null $region
     * @return Kpp
     */
    public function kpp(?Region $region = null): Kpp
    {
        return $this->buildKpp(
            $region ?? Region::random($this->randomizer),
            $this->taxOffice(),
        );
    }

    /**
     * Builds an INN of the given form, opened by the code of the given region.
     *
     * @param LegalForm $form
     * @param Region $region
     * @return Inn
     */
    private function buildInn(LegalForm $form, Region $region): Inn
    {
        // The region takes the first two digits, the checksum the last one or two.
        $body = $form->innDigits() - $form->innChecksumDigits();

        return Inn::complete($region->value . $this->digits($body - 2));
    }

    /**
     * Builds a registry number of the given form, region and tax office.
     *
     * @param LegalForm $form
     * @param Region $region
     * @param string $taxOffice
     * @return Ogrn
     */
    private function buildRegistryNumber(LegalForm $form, Region $region, string $taxOffice): Ogrn
    {
        // Prefix, year, region and tax office take seven digits, the checksum takes the eighth.
        $sequence = $form->registryNumberDigits() - 8;

        return Ogrn::complete(
            $form->registryNumberPrefix()
            . $this->year()
            . $region->value
            . $taxOffice
            . $this->digits($sequence),
        );
    }

    /**
     * Builds a KPP of a head office registered in the given region.
     *
     * @param Region $region
     * @param string $taxOffice
     * @return Kpp
     */
    private function buildKpp(Region $region, string $taxOffice): Kpp
    {
        return Kpp::from($region->value . $taxOffice . self::HEAD_OFFICE_REASON . self::FIRST_SEQUENCE);
    }

    /**
     * Picks a legal form at random.
     *
     * @return LegalForm
     */
    private function form(): LegalForm
    {
        $forms = LegalForm::cases();

        return $forms[$this->randomizer->getInt(0, count($forms) - 1)];
    }

    /**
     * Picks a two-digit tax office number at random.
     *
     * @return string
     */
    private function taxOffice(): string
    {
        return sprintf('%02d', $this->randomizer->getInt(1, 99));
    }

    /**
     * Picks a registration year out of the fixed range.
     *
     * @return string
     */
    private function year(): string
    {
        return sprintf('%02d', $this->randomizer->getInt(self::FIRST_YEAR, self::LAST_YEAR));
    }

    /**
     * Draws a random digit string of the given length.
     *
     * @param int $length
     * @return string
     */
    private function digits(int $length): string
    {
        return $this->randomizer->getBytesFromString(self::DIGITS, $length);
    }
}
