<?php

declare(strict_types=1);

namespace RuFaker\Faker;

use DateTimeImmutable;
use Random\Randomizer;
use RuFaker\Enum\Gender;
use RuFaker\Enum\LegalForm;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\MtRandEngine;
use RuFaker\Requisite\Bik;
use RuFaker\Requisite\Region;
use RuFaker\Result\Person;
use RuFaker\RuFaker;

/**
 * Everything the package generates, as plain strings and arrays: the value behind $faker->ruFaker().
 */
final readonly class Values
{
    /** The package itself: these methods add nothing but the conversion to strings. */
    private RuFaker $ru;

    /**
     * Builds values that follow the seed Faker sets, unless given a package of their own.
     *
     * @param RuFaker|null $ru
     */
    public function __construct(?RuFaker $ru = null)
    {
        $this->ru = $ru ?? new RuFaker(
            new Randomizer(
                new MtRandEngine(),
            ),
        );
    }

    /**
     * Builds requisites of one business that agree with each other.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @param Gender|null $gender
     * @param bool $initials
     * @param Person|null $person
     * @param string|null $title
     * @return array{
     *     form: string,
     *     short_name: string,
     *     full_name: string,
     *     region: string,
     *     inn: string,
     *     ogrn: string,
     *     kpp: string|null,
     *     registration_date: string,
     *     person: string|null,
     *     head: string|null,
     *     head_position: string|null,
     * }
     * @throws InvalidRequisite
     */
    public function organization(
        ?LegalForm $form = null,
        ?Region    $region = null,
        ?Gender    $gender = null,
        bool       $initials = false,
        ?Person    $person = null,
        ?string    $title = null,
    ): array
    {
        return $this->ru
            ->organization($form, $region, $gender, $initials, $person, $title)
            ->toArray();
    }

    /**
     * Builds a person: a full name agreeing in gender, a date of birth and a personal INN.
     *
     * @param Gender|null $gender
     * @param Region|null $region
     * @param DateTimeImmutable|null $birthDate
     * @return array{
     *     gender: string,
     *     last_name: string,
     *     first_name: string,
     *     patronymic: string,
     *     birth_date: string,
     *     inn: string|null,
     * }
     * @throws InvalidRequisite
     */
    public function person(
        ?Gender            $gender = null,
        ?Region            $region = null,
        ?DateTimeImmutable $birthDate = null,
    ): array
    {
        return $this->ru
            ->person($gender, $region, $birthDate)
            ->toArray();
    }

    /**
     * Builds payment details whose accounts are keyed to the BIK of their own bank.
     *
     * @param Bik|null $bik
     * @param LegalForm|null $form
     * @return array{
     *     bank_short_name: string,
     *     bank_full_name: string,
     *     bik: string,
     *     correspondent_account: string,
     *     settlement_account: string,
     * }
     */
    public function bankAccount(?Bik $bik = null, ?LegalForm $form = null): array
    {
        return $this->ru
            ->bankAccount($bik, $form)
            ->toArray();
    }

    /**
     * Builds a taxpayer number of the given legal form and region.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @return string
     */
    public function inn(?LegalForm $form = null, ?Region $region = null): string
    {
        return $this->ru
            ->inn($form, $region)
            ->value;
    }

    /**
     * Builds a registry number of the given legal form and region.
     *
     * @param LegalForm|null $form
     * @param Region|null $region
     * @return string
     */
    public function ogrn(?LegalForm $form = null, ?Region $region = null): string
    {
        return $this->ru
            ->ogrn($form, $region)
            ->value;
    }

    /**
     * Builds a tax registration code of the given region.
     *
     * @param Region|null $region
     * @return string
     */
    public function kpp(?Region $region = null): string
    {
        return $this->ru
            ->kpp($region)
            ->value;
    }

    /**
     * Builds an identifier of a bank in the Russian payment system.
     *
     * @return string
     */
    public function bik(): string
    {
        return $this->ru
            ->bik()
            ->value;
    }
}
