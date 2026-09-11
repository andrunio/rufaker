<?php

declare(strict_types=1);

namespace RuFaker\Result;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\ArrayValue;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;

/**
 * Payment details of one customer: a bank and its two accounts, all keyed to the same BIK.
 */
final readonly class BankAccount implements Result
{
    use ArrayValue;

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
     * Assembles payment details, rejecting an account not keyed to the given bank.
     *
     * @param Bik $bik
     * @param Account $correspondent
     * @param Account $settlement
     * @throws InvalidRequisite
     */
    public function __construct(
        Bik     $bik,
        Account $correspondent,
        Account $settlement,
    )
    {
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
     * Returns the details as plain strings, ready for a fixture or a payload.
     *
     * @return array{bik: string, correspondent_account: string, settlement_account: string}
     */
    public function toArray(): array
    {
        return [
            'bik' => $this->bik,
            'correspondent_account' => $this->correspondent,
            'settlement_account' => $this->settlement,
        ];
    }
}
