<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\Digits;
use RuFaker\Internal\StringValue;

/**
 * Taxpayer number: ten digits for an organization, twelve for a person or a sole proprietor.
 */
final readonly class Inn implements Requisite
{
    use StringValue;

    /** Weights of the single checksum digit of a 10-digit INN. */
    private const array WEIGHTS_10 = [2, 4, 10, 3, 5, 9, 4, 6, 8];

    /** Weights of the first checksum digit of a 12-digit INN. */
    private const array WEIGHTS_11 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];

    /** Weights of the second checksum digit of a 12-digit INN. */
    private const array WEIGHTS_12 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];

    /**
     * Wraps an INN, rejecting a broken format or checksum.
     *
     * @param string $value
     * @return self
     * @throws InvalidRequisite
     */
    public static function from(string $value): self
    {
        return self::tryFrom($value)
            ?? throw InvalidRequisite::for('INN', $value);
    }

    /**
     * Wraps an INN, returning null instead of throwing.
     *
     * @param string $value
     * @return self|null
     */
    public static function tryFrom(string $value): ?self
    {
        return self::isValid($value)
            ? new self($value)
            : null;
    }

    /**
     * Tells whether the value carries a correct INN checksum.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        if (Digits::areDigits($value, 10)) {
            return $value === self::complete(substr($value, 0, 9))->value;
        }

        if (Digits::areDigits($value, 12)) {
            return $value === self::complete(substr($value, 0, 10))->value;
        }

        return false;
    }

    /**
     * Appends the checksum to nine leading digits of an organization INN or ten of a personal one.
     *
     * @param string $body
     * @return self
     * @throws InvalidRequisite
     */
    public static function complete(string $body): self
    {
        if (Digits::areDigits($body, 9)) {
            return new self($body . self::checksum($body, self::WEIGHTS_10));
        }

        if (Digits::areDigits($body, 10)) {
            $eleventh = self::checksum($body, self::WEIGHTS_11);

            return new self($body . $eleventh . self::checksum($body . $eleventh, self::WEIGHTS_12));
        }

        throw InvalidRequisite::for('INN body', $body);
    }

    /**
     * Tells whether the INN belongs to a person or a sole proprietor.
     *
     * @return bool
     */
    public function isPersonal(): bool
    {
        return strlen($this->value) === 12;
    }

    /**
     * Reads the region the INN was issued in.
     *
     * @return Region|null
     */
    public function region(): ?Region
    {
        return Region::tryFrom(
            substr($this->value, 0, 2),
        );
    }

    /**
     * Computes one checksum digit of the body against the given weights.
     *
     * @param string $body
     * @param list<int> $weights
     * @return string
     */
    private static function checksum(string $body, array $weights): string
    {
        return (string)(Digits::weightedSum(Digits::toList($body), $weights) % 11 % 10);
    }
}
