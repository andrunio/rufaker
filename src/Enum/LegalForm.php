<?php

declare(strict_types=1);

namespace RuFaker\Enum;

use DateTimeImmutable;

/**
 * Legal form of a business, the source of every structural rule about its requisites.
 */
enum LegalForm: string
{
    case Ooo = 'ooo';
    case Ao = 'ao';
    case Pao = 'pao';
    case Ip = 'ip';

    /** Commercial organization under first-order account 407 of Bank of Russia Regulation 809-P. */
    private const string CommercialOrganization = '40702';

    /** Sole proprietor under first-order account 408 of Bank of Russia Regulation 809-P. */
    private const string SoleProprietor = '40802';

    /** Day the register of legal entities opened under Federal Law 129-FZ. */
    private const string CompanyRegistryOpened = '2002-07-01';

    /** Day the register of sole proprietors opened under Federal Law 76-FZ. */
    private const string ProprietorRegistryOpened = '2004-01-01';

    /**
     * Returns the short label of this form, the one that opens an abbreviated name.
     *
     * @return string
     */
    public function shortTitle(): string
    {
        return match ($this) {
            self::Ooo => 'ООО',
            self::Ao => 'АО',
            self::Pao => 'ПАО',
            self::Ip => 'ИП',
        };
    }

    /**
     * Returns the full label of this form, spelled as in classifier OK 028-2012.
     *
     * @return string
     */
    public function fullTitle(): string
    {
        return match ($this) {
            self::Ooo => 'Общество с ограниченной ответственностью',
            self::Ao => 'Акционерное общество',
            self::Pao => 'Публичное акционерное общество',
            self::Ip => 'Индивидуальный предприниматель',
        };
    }

    /**
     * Number of digits in the INN of this form.
     *
     * @internal
     * @return int
     */
    public function innDigits(): int
    {
        return $this->isIndividual()
            ? 12
            : 10;
    }

    /**
     * Number of checksum digits at the end of the INN of this form.
     *
     * @internal
     * @return int
     */
    public function innChecksumDigits(): int
    {
        return $this->isIndividual()
            ? 2
            : 1;
    }

    /**
     * Number of digits in the state registry number of this form.
     *
     * @internal
     * @return int
     */
    public function registryNumberDigits(): int
    {
        return $this->isIndividual()
            ? 15
            : 13;
    }

    /**
     * Leading digit of the state registry number of this form.
     *
     * @internal
     * @return string
     */
    public function registryNumberPrefix(): string
    {
        return $this->isIndividual()
            ? '3'
            : '1';
    }

    /**
     * Day the registry of this form opened: no record in it can be dated earlier.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @internal
     * @return DateTimeImmutable
     */
    public function registryOpenedOn(): DateTimeImmutable
    {
        // Both constants are written here, so the date is valid by construction.
        /** @noinspection PhpUnhandledExceptionInspection */
        return new DateTimeImmutable($this->isIndividual()
            ? self::ProprietorRegistryOpened
            : self::CompanyRegistryOpened);
    }

    /**
     * Balance account a bank opens for this form.
     *
     * @return string
     */
    public function balanceAccount(): string
    {
        return $this->isIndividual()
            ? self::SoleProprietor
            : self::CommercialOrganization;
    }

    /**
     * Tells whether businesses of this form are put on record with a KPP.
     *
     * @return bool
     */
    public function hasKpp(): bool
    {
        return $this->isCorporate();
    }

    /**
     * Tells whether the form is a sole proprietor rather than a legal entity.
     *
     * @return bool
     */
    public function isIndividual(): bool
    {
        return $this === self::Ip;
    }

    /**
     * Tells whether the form is a company rather than a sole proprietor.
     *
     * @return bool
     */
    public function isCorporate(): bool
    {
        return !$this->isIndividual();
    }
}
