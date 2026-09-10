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

    /**
     * Number of digits in the INN of this form.
     *
     * @return int
     */
    public function innDigits(): int
    {
        return $this->isIndividual() ? 12 : 10;
    }

    /**
     * Number of checksum digits at the end of the INN of this form.
     *
     * @return int
     */
    public function innChecksumDigits(): int
    {
        return $this->isIndividual() ? 2 : 1;
    }

    /**
     * Number of digits in the state registry number of this form.
     *
     * @return int
     */
    public function registryNumberDigits(): int
    {
        return $this->isIndividual() ? 15 : 13;
    }

    /**
     * Leading digit of the state registry number of this form.
     *
     * @return string
     */
    public function registryNumberPrefix(): string
    {
        return $this->isIndividual() ? '3' : '1';
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
        return !$this->isIndividual();
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
}
