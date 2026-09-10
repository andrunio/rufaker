<?php

declare(strict_types=1);

namespace RuFaker\Internal;

use Override;

/**
 * Shared body of a result set that serialises through its own toArray().
 *
 * @internal
 */
trait ArrayValue
{
    /**
     * Returns the parts as plain strings, ready for a fixture or a payload.
     *
     * @return array<string, string|null>
     */
    abstract public function toArray(): array;

    /**
     * Returns the value for json_encode().
     *
     * @return array<string, string|null>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
