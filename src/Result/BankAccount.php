<?php

declare(strict_types=1);

namespace RuFaker\Result;

use RuFaker\Enum\LegalForm;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\ArrayValue;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;

/**
 * Payment details of one customer: a named bank and its two accounts, all keyed to the same BIK.
 */
final readonly class BankAccount implements Result
{
    use ArrayValue;

    /** Word every credit organization carries in its name, required by article 7 of law 395-1. */
    private const string KIND = 'Банк';

    /** Name of the bank the way it is written on a payment order: the short form. */
    public string $shortName;

    /** Name of the bank the way it is written in the registry: the full form. */
    public string $fullName;

    /** Identifier of the bank the accounts are opened in. */
    public string $bik;

    /** Account the bank itself holds with the Bank of Russia. */
    public string $correspondent;

    /** Account the customer holds with the bank. */
    public string $settlement;

    /** Bank identifier as the requisite it came from. */
    private Bik $bikType;

    /** Correspondent account as the requisite it came from. */
    private Account $correspondentType;

    /** Settlement account as the requisite it came from. */
    private Account $settlementType;

    /**
     * Assembles payment details, rejecting an account not keyed to the given BIK.
     *
     * @param Bik $bik
     * @param Account $correspondent
     * @param Account $settlement
     * @param LegalForm $form
     * @param string $title
     * @throws InvalidRequisite
     */
    public function __construct(
        Bik       $bik,
        Account   $correspondent,
        Account   $settlement,
        LegalForm $form,
        string    $title,
    )
    {
        if ($form->isIndividual()) {
            throw InvalidRequisite::because('A bank cannot be a sole proprietor.');
        }

        if (trim($title) === '') {
            throw InvalidRequisite::because('A bank must have a title.');
        }

        if (!Account::isValid($correspondent->value, $bik)) {
            throw InvalidRequisite::because("Correspondent account $correspondent->value is not keyed to BIK $bik->value.");
        }

        if (!Account::isValid($settlement->value, $bik)) {
            throw InvalidRequisite::because("Settlement account $settlement->value is not keyed to BIK $bik->value.");
        }

        if (!$correspondent->isCorrespondent()) {
            throw InvalidRequisite::because("Account $correspondent->value is not a correspondent one.");
        }

        if ($settlement->isCorrespondent()) {
            throw InvalidRequisite::because("Account $settlement->value is a correspondent one.");
        }

        $this->bikType = $bik;
        $this->correspondentType = $correspondent;
        $this->settlementType = $settlement;

        $this->shortName = $form->shortTitle() . ' ' . self::quoted($title);
        $this->fullName = $form->fullTitle() . ' ' . self::quoted($title);
        $this->bik = $bik->value;
        $this->correspondent = $correspondent->value;
        $this->settlement = $settlement->value;
    }

    /**
     * Returns the bank identifier as a requisite.
     *
     * @return Bik
     */
    public function bik(): Bik
    {
        return $this->bikType;
    }

    /**
     * Returns the correspondent account as a requisite.
     *
     * @return Account
     */
    public function correspondent(): Account
    {
        return $this->correspondentType;
    }

    /**
     * Returns the settlement account as a requisite.
     *
     * @return Account
     */
    public function settlement(): Account
    {
        return $this->settlementType;
    }

    /**
     * Builds what stands inside the quotes: the proper name and the word every bank carries.
     *
     * @param string $title
     * @return string
     */
    private static function quoted(string $title): string
    {
        return '"' . $title . ' ' . self::KIND . '"';
    }

    /**
     * Returns the details as plain strings, ready for a fixture or a payload.
     *
     * @return array{
     *     bank_short_name: string,
     *     bank_full_name: string,
     *     bik: string,
     *     correspondent_account: string,
     *     settlement_account: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'bank_short_name' => $this->shortName,
            'bank_full_name' => $this->fullName,
            'bik' => $this->bik,
            'correspondent_account' => $this->correspondent,
            'settlement_account' => $this->settlement,
        ];
    }
}
