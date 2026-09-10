<?php

declare(strict_types=1);

namespace RuFaker;

use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Enum\LegalForm;
use RuFaker\Generator\BankAccountGenerator;
use RuFaker\Generator\OrganizationGenerator;
use RuFaker\Requisite\Bik;
use RuFaker\Requisite\Inn;
use RuFaker\Requisite\Kpp;
use RuFaker\Requisite\Ogrn;
use RuFaker\Requisite\Region;
use RuFaker\Result\BankAccount;
use RuFaker\Result\Organization;

/**
 * Entry point of the package.
 */
final readonly class RuFaker
{
    private OrganizationGenerator $organizations;

    private BankAccountGenerator $bankAccounts;

    /**
     * Builds a faker drawing from the given randomizer.
     *
     * @param Randomizer $randomizer
     */
    public function __construct(Randomizer $randomizer = new Randomizer())
    {
        $this->organizations = new OrganizationGenerator($randomizer);
        $this->bankAccounts = new BankAccountGenerator($randomizer);
    }

    /**
     * Builds a generator that repeats its output for the same seed.
     *
     * @param int $seed
     * @return self
     */
    public static function seeded(int $seed): self
    {
        return new self(
            new Randomizer(
                new Mt19937($seed),
            ),
        );
    }

    /**
     * Builds requisites of one business that agree with each other.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @return Organization
     */
    public function organization(?LegalForm $form = null, ?Region $region = null): Organization
    {
        return $this->organizations->generate($form, $region);
    }

    /**
     * Builds a bank together with its correspondent account and one customer account.
     *
     * @param Bik|null $bik
     * @param LegalForm|null $form
     * @return BankAccount
     */
    public function bankAccount(?Bik $bik = null, ?LegalForm $form = null): BankAccount
    {
        return $this->bankAccounts->generate($bik, $form);
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
        return $this->organizations->inn($form, $region);
    }

    /**
     * Builds a standalone state registry number.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @return Ogrn
     */
    public function ogrn(?LegalForm $form = null, ?Region $region = null): Ogrn
    {
        return $this->organizations->registryNumber($form, $region);
    }

    /**
     * Builds a standalone KPP of a head office.
     *
     * @param Region|null $region
     * @return Kpp
     */
    public function kpp(?Region $region = null): Kpp
    {
        return $this->organizations->kpp($region);
    }

    /**
     * Builds a standalone BIK.
     *
     * @return Bik
     */
    public function bik(): Bik
    {
        return $this->bankAccounts->bik();
    }
}
