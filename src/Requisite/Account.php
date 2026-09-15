<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\Digits;
use RuFaker\Internal\StringValue;

/**
 * Twenty-digit account keyed to the BIK of the bank holding it; a treasury one carries no key.
 */
final readonly class Account implements Requisite
{
    use StringValue;

    /** Weights applied to the twenty-three digits the control key is computed over. */
    private const array KEY_WEIGHTS = [7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1];

    /** Balance accounts of this group are held at a Bank of Russia division. */
    private const string CENTRAL_BANK_PREFIX = '301';

    /** Balance accounts only a credit organization opens, under Bank of Russia Regulation 809-P. */
    private const array BANK_ONLY_BALANCES = ['40702', '40703', '40802', '40817', '40820'];

    /** Balance accounts only a Bank of Russia division opens: a bank correspondent and the treasury one. */
    private const array DIVISION_ONLY_BALANCES = ['30101', '40102'];

    /** A treasury account opens with a zero, where a balance account opens with its section. */
    private const string TREASURY_PREFIX = '0';

    /** Currency of a treasury account, written by the all-Russian classifier rather than as 810. */
    private const string TREASURY_CURRENCY = '643';

    /** Zero-based position of the currency code shared by every twenty-digit account. */
    private const int CURRENCY_POSITION = 5;

    /** Zero-based position of the control key inside the account number. */
    private const int KEY_POSITION = 8;

    /**
     * Wraps an account number, rejecting one whose control key does not match the bank.
     *
     * @param string $value
     * @param Bik $bik
     * @return self
     * @throws InvalidRequisite
     */
    public static function from(string $value, Bik $bik): self
    {
        return self::tryFrom($value, $bik)
            ?? throw InvalidRequisite::for('account number', $value);
    }

    /**
     * Wraps an account number, returning null instead of throwing.
     *
     * @param string $value
     * @param Bik $bik
     * @return self|null
     */
    public static function tryFrom(string $value, Bik $bik): ?self
    {
        return self::isValid($value, $bik)
            ? new self($value)
            : null;
    }

    /**
     * Tells whether the control key of the number matches the given BIK.
     *
     * The number alone rarely tells where the account is held, and the key is computed over the
     * division for an account held at the Bank of Russia and over the participant for one held at
     * a bank. Where the balance account leaves the choice open, either key is accepted.
     *
     * @param string $value
     * @param Bik $bik
     * @return bool
     */
    public static function isValid(string $value, Bik $bik): bool
    {
        if (!Digits::areDigits($value, 20)) {
            return false;
        }

        // A treasury account carries no key at all: its ninth digit belongs to the entity code.
        if (self::isTreasury($value)) {
            return true;
        }

        $balance = substr($value, 0, 5);
        $key = $value[self::KEY_POSITION];

        if (in_array($balance, self::DIVISION_ONLY_BALANCES, true)) {
            return self::key($value, self::divisionCode($bik)) === $key;
        }

        if (in_array($balance, self::BANK_ONLY_BALANCES, true)) {
            return self::key($value, $bik->participant()) === $key;
        }

        return self::key($value, self::divisionCode($bik)) === $key
            || self::key($value, $bik->participant()) === $key;
    }

    /**
     * Builds an account out of a twenty-digit draft, replacing its ninth digit with the correct key.
     *
     * @internal
     * @param string $draft
     * @param Bik $bik
     * @return self
     * @throws InvalidRequisite
     */
    public static function fromDraft(string $draft, Bik $bik): self
    {
        if (!Digits::areDigits($draft, 20)) {
            throw InvalidRequisite::for('account draft', $draft);
        }

        return new self(
            substr_replace($draft, self::key($draft, self::bankCode($draft, $bik)), self::KEY_POSITION, 1),
        );
    }

    /**
     * Tells whether the account is held at a Bank of Russia division rather than at a bank.
     *
     * @return bool
     */
    public function isCorrespondent(): bool
    {
        return str_starts_with($this->value, self::CENTRAL_BANK_PREFIX);
    }

    /**
     * Tells whether the account is a settlement one rather than a correspondent one.
     *
     * @return bool
     */
    public function isSettlement(): bool
    {
        return !$this->isCorrespondent();
    }

    /**
     * Computes the control key of the number against the code of the bank holding it.
     *
     * @param string $number
     * @param string $bankCode
     * @return string
     */
    private static function key(string $number, string $bankCode): string
    {
        $input = $bankCode . substr_replace($number, '0', self::KEY_POSITION, 1);
        $sum = Digits::weightedSum(Digits::toList($input), self::KEY_WEIGHTS);

        return (string)($sum % 10 * 3 % 10);
    }

    /**
     * Tells whether the number belongs to the treasury rather than to the banking system.
     *
     * @param string $value
     * @return bool
     */
    private static function isTreasury(string $value): bool
    {
        return str_starts_with($value, self::TREASURY_PREFIX)
            && substr($value, self::CURRENCY_POSITION, 3) === self::TREASURY_CURRENCY;
    }

    /**
     * Reads the code of the Bank of Russia division the account would be held at.
     *
     * @param Bik $bik
     * @return string
     */
    private static function divisionCode(Bik $bik): string
    {
        return '0' . $bik->division();
    }

    /**
     * Reads the bank code the control key is computed over.
     *
     * @param string $number
     * @param Bik $bik
     * @return string
     */
    private static function bankCode(string $number, Bik $bik): string
    {
        // Accounts held at the Bank of Russia are keyed by its division, the rest by the participant.
        return str_starts_with($number, self::CENTRAL_BANK_PREFIX)
            ? self::divisionCode($bik)
            : $bik->participant();
    }
}
