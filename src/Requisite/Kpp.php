<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use RuFaker\Exception\InvalidRequisite;
use RuFaker\Internal\StringValue;

/**
 * Registration reason code: tax office, reason for the registration and its sequence number.
 */
final readonly class Kpp implements Requisite
{
    use StringValue;

    /** Four digits of a tax office, two of a reason, three of a sequence number. */
    private const string PATTERN = '/^\d{4}[\dA-Z]{2}\d{3}$/';

    /**
     * Wraps a KPP, rejecting a broken format.
     *
     * @param string $value
     * @return self
     * @throws InvalidRequisite
     */
    public static function from(string $value): self
    {
        return self::tryFrom($value)
            ?? throw InvalidRequisite::for('KPP', $value);
    }

    /**
     * Wraps a KPP, returning null instead of throwing.
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
     * Tells whether the value matches the KPP format; a KPP carries no checksum.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Reads the region of the tax office that registered the organization.
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
     * Reads the reason the organization was put on record.
     *
     * @return string
     */
    public function reason(): string
    {
        return substr($this->value, 4, 2);
    }
}
