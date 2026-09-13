<?php

declare(strict_types=1);

namespace RuFaker\Enum;

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

    /** Year the register of legal entities opened under Federal Law 129-FZ, 1 July 2002. */
    private const int CompanyRegistryOpened = 2;

    /** Year the register of sole proprietors opened under Federal Law 76-FZ, 1 January 2004. */
    private const int ProprietorRegistryOpened = 4;

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
     * @return string
     */
    public function registryNumberPrefix(): string
    {
        return $this->isIndividual()
            ? '3'
            : '1';
    }

    /**
     * Earliest year a state registry number of this form can carry, two digits.
     *
     * @return int
     */
    public function registryNumberFirstYear(): int
    {
        return $this->isIndividual()
            ? self::ProprietorRegistryOpened
            : self::CompanyRegistryOpened;
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
