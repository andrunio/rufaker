<?php

declare(strict_types=1);

namespace RuFaker\Internal;

use Override;

/**
 * Shared body of a requisite that wraps one already validated string.
 *
 * @internal
 */
trait StringValue
{
    /**
     * Wraps a value already known to be valid.
     *
     * @param string $value
     */
    private function __construct(public readonly string $value)
    {
    }

    /**
     * Returns the value as a string.
     *
     * @return string
     */
    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Returns the value for json_encode().
     *
     * @return string
     */
    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
