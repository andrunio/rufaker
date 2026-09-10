<?php

declare(strict_types=1);

namespace RuFaker\Result;

use JsonSerializable;
use Override;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Account;
use RuFaker\Requisite\Bik;

/**
 * Payment details of one customer: a bank and its two accounts, all keyed to the same BIK.
 */
final readonly class BankAccount implements JsonSerializable
{
    /**
     * Assembles payment details, rejecting an account not keyed to the given bank.
     *
     * @param Bik $bik
     * @param Account $correspondent
     * @param Account $settlement
     * @throws InvalidRequisite
     */
    public function __construct(
        public Bik     $bik,
        public Account $correspondent,
        public Account $settlement,
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
    }

    /**
     * Returns the details as plain strings, ready for a fixture or a payload.
     *
     * @return array{bik: string, correspondent_account: string, settlement_account: string}
     */
    public function toArray(): array
    {
        return [
            'bik' => $this->bik->value,
            'correspondent_account' => $this->correspondent->value,
            'settlement_account' => $this->settlement->value,
        ];
    }

    /**
     * Returns the value for json_encode().
     *
     * @return array{bik: string, correspondent_account: string, settlement_account: string}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
