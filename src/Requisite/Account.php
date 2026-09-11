<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\Digits;
use RuFaker\Internal\StringValue;

/**
 * Twenty-digit bank account whose control key is derived from the BIK of its bank.
 */
final readonly class Account implements Requisite
{
    use StringValue;

    /** Weights applied to the twenty-three digits the control key is computed over. */
    private const array KEY_WEIGHTS = [7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1];

    /** Balance accounts of this group are held at a Bank of Russia division. */
    private const string CENTRAL_BANK_PREFIX = '301';

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
     * Tells whether the control key of the number matches the given bank.
     *
     * @param string $value
     * @param Bik $bik
     * @return bool
     */
    public static function isValid(string $value, Bik $bik): bool
    {
        return Digits::areDigits($value, 20)
            && $value[self::KEY_POSITION] === self::key($value, $bik);
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
            substr_replace($draft, self::key($draft, $bik), self::KEY_POSITION, 1),
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
     * Computes the control key of the number against the given bank.
     *
     * @param string $number
     * @param Bik $bik
     * @return string
     */
    private static function key(string $number, Bik $bik): string
    {
        $input = self::bankCode($number, $bik) . substr_replace($number, '0', self::KEY_POSITION, 1);
        $sum = Digits::weightedSum(Digits::toList($input), self::KEY_WEIGHTS);

        return (string)($sum % 10 * 3 % 10);
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
            ? '0' . $bik->division()
            : $bik->participant();
    }
}
