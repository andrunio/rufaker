<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\StringValue;

/**
 * Bank identification code: country, territory, Bank of Russia division and participant number.
 */
final readonly class Bik implements Requisite
{
    use StringValue;

    /** Every participant of the Russian payment system carries the 04 country prefix. */
    private const string PATTERN = '/^04\d{7}$/';

    /**
     * Wraps a BIK, rejecting a broken format.
     *
     * @param string $value
     * @return self
     * @throws InvalidRequisite
     */
    public static function from(string $value): self
    {
        return self::tryFrom($value)
            ?? throw InvalidRequisite::for('BIK', $value);
    }

    /**
     * Wraps a BIK, returning null instead of throwing.
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
     * Tells whether the value matches the BIK format; a BIK carries no checksum.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Reads the territory code, digits three and four.
     *
     * @return string
     */
    public function territory(): string
    {
        return substr($this->value, 2, 2);
    }

    /**
     * Reads the Bank of Russia division, digits five and six.
     *
     * @return string
     */
    public function division(): string
    {
        return substr($this->value, 4, 2);
    }

    /**
     * Reads the participant number, digits seven to nine.
     *
     * @return string
     */
    public function participant(): string
    {
        return substr($this->value, 6, 3);
    }
}
