<?php

declare(strict_types=1);

namespace RuFaker\Exception;

use InvalidArgumentException;

/**
 * Thrown when a value breaks the format or the checksum of a requisite.
 */
final class InvalidRequisite extends InvalidArgumentException implements Exception
{
    /**
     * Reports a value rejected by the named requisite.
     *
     * @param string $requisite
     * @param string $value
     * @return self
     */
    public static function for(string $requisite, string $value): self
    {
        return new self("Value [$value] is not a valid $requisite.");
    }

    /**
     * Reports requisites that contradict each other.
     *
     * @param string $reason
     * @return self
     */
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
