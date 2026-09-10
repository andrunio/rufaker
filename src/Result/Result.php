<?php

declare(strict_types=1);

namespace RuFaker\Result;

use JsonSerializable;

/**
 * Implemented by every set the package assembles out of requisites.
 */
interface Result extends JsonSerializable
{
    /**
     * Returns the parts as plain strings, ready for a fixture or a payload.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array;

    /**
     * Returns the value for json_encode().
     *
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array;
}
