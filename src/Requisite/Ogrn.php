<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\Digits;
use RuFaker\Internal\StringValue;

/**
 * State registry number: thirteen digits for a legal entity, fifteen for a sole proprietor.
 */
final readonly class Ogrn implements Requisite
{
    use StringValue;

    /** Divisor of the checksum of a 13-digit OGRN. */
    private const int DIVISOR_13 = 11;

    /** Divisor of the checksum of a 15-digit OGRNIP. */
    private const int DIVISOR_15 = 13;

    /**
     * Wraps a registry number, rejecting a broken format or checksum.
     *
     * @param string $value
     * @return self
     * @throws InvalidRequisite
     */
    public static function from(string $value): self
    {
        return self::tryFrom($value)
            ?? throw InvalidRequisite::for('OGRN', $value);
    }

    /**
     * Wraps a registry number, returning null instead of throwing.
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
     * Tells whether the value carries a correct registry number checksum.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        if (Digits::areDigits($value, 13)) {
            return $value === self::complete(substr($value, 0, 12))->value;
        }

        if (Digits::areDigits($value, 15)) {
            return $value === self::complete(substr($value, 0, 14))->value;
        }

        return false;
    }

    /**
     * Appends the checksum to twelve leading digits of an OGRN or fourteen of an OGRNIP.
     *
     * @param string $body
     * @return self
     * @throws InvalidRequisite
     */
    public static function complete(string $body): self
    {
        if (Digits::areDigits($body, 12)) {
            return new self($body . self::checksum($body, self::DIVISOR_13));
        }

        if (Digits::areDigits($body, 14)) {
            return new self($body . self::checksum($body, self::DIVISOR_15));
        }

        throw InvalidRequisite::for('OGRN body', $body);
    }

    /**
     * Tells whether the number belongs to a sole proprietor.
     *
     * @return bool
     */
    public function isIndividual(): bool
    {
        return strlen($this->value) === 15;
    }

    /**
     * Reads the region the number was issued in.
     *
     * @return Region|null
     */
    public function region(): ?Region
    {
        return Region::tryFrom(
            substr($this->value, 3, 2),
        );
    }

    /**
     * Computes the checksum digit of the body against the given divisor.
     *
     * @param string $body
     * @param int $divisor
     * @return string
     */
    private static function checksum(string $body, int $divisor): string
    {
        // The checksum is the least significant digit of the remainder, so 10 and above wrap around.
        return substr((string)((int)$body % $divisor), -1);
    }
}
