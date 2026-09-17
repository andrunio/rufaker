<?php

declare(strict_types=1);

namespace RuFaker\Internal\Generator;

use DateTimeImmutable;
use Random\Randomizer;
use RuFaker\Internal\Calendar;
use RuFaker\Internal\Digits;
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Internal\TitleBook;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Result\Organization;
use RuFaker\Result\Person;

/**
 * Builds business requisites that agree with each other.
 *
 * @internal
 */
final readonly class OrganizationGenerator
{
    /** Registration stops at the end of last year: a day of this one could still lie ahead. */
    private const int LAST_YEAR = 1;

    /** Age a person is drawn to reach before registering as a sole proprietor, article 21 of the Civil Code. */
    private const string ADULT_AGE = '+18 years';

    /** Reason a head office is put on record: the place of its own location. */
    private const string HEAD_OFFICE_REASON = '01';

    /** Sequence number of the first registration made on that reason. */
    private const string FIRST_SEQUENCE = '001';

    /** Names a sole executive body goes by, article 40 of Law 14-FZ and article 69 of Law 208-FZ. */
    private const array POSITIONS = [
        'Генеральный директор',
        'Директор',
        'Президент',
    ];

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
     * @param bool $initials
     * @return Organization
     */
    public function generate(
        ?LegalForm $form = null,
        ?Region    $region = null,
        ?Gender    $gender = null,
        bool       $initials = false,
    ): Organization
    {
        $form ??= $this->form();
        $region ??= Region::random($this->randomizer);
        $taxOffice = $this->taxOffice();
        $inn = $this->buildInn($form, $region);
        $person = $form->isIndividual() ? $this->people->generate($gender, $region, $inn) : null;
        $registrationDate = $this->registrationDate($form, $person);

        return new Organization(
            $form,
            $region,
            $inn,
            $this->buildRegistryNumber($form, $region, $taxOffice, $registrationDate),
            $registrationDate,
            $form->hasKpp() ? $this->buildKpp($region, $taxOffice) : null,
            $person,
            $form->isIndividual() ? null : TitleBook::random($this->randomizer),
            $initials,
            $form->isCorporate() ? $this->people->generate() : null,
            $form->isCorporate() ? $this->position() : null,
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
        $form ??= $this->form();

        return $this->buildRegistryNumber(
            $form,
            $region ?? Region::random($this->randomizer),
            $this->taxOffice(),
            $this->registrationDate($form, null),
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

        return Inn::fromBody($region->value . Digits::random($this->randomizer, $body - 2));
    }

    /**
     * Builds a registry number carrying the year of the given registration date.
     *
     * @param LegalForm $form
     * @param Region $region
     * @param string $taxOffice
     * @param DateTimeImmutable $registrationDate
     * @return Ogrn
     */
    private function buildRegistryNumber(
        LegalForm         $form,
        Region            $region,
        string            $taxOffice,
        DateTimeImmutable $registrationDate,
    ): Ogrn
    {
        // Prefix, year, region and tax office take seven digits, the checksum takes the eighth.
        $sequence = $form->registryNumberDigits() - 8;

        return Ogrn::fromBody(
            $form->registryNumberPrefix()
            . $registrationDate->format('y')
            . $region->value
            . $taxOffice
            . Digits::random($this->randomizer, $sequence),
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
     * Picks the position the head of a company holds.
     *
     * @return string
     */
    private function position(): string
    {
        $positions = self::POSITIONS;

        return $positions[$this->randomizer->getInt(0, count($positions) - 1)];
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
     * Draws a registration day no earlier than the registry opened and than the proprietor grew up.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param LegalForm $form
     * @param Person|null $person
     * @return DateTimeImmutable
     */
    private function registrationDate(LegalForm $form, ?Person $person): DateTimeImmutable
    {
        $first = $form->registryOpenedOn();

        if ($person instanceof Person) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $first = max($first, $person->birthDate()->modify(self::ADULT_AGE));
        }

        return Calendar::dayWithin($this->randomizer, $first, Calendar::yearCloses(self::LAST_YEAR));
    }
}
