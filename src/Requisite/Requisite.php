<?php

declare(strict_types=1);

namespace RuFaker\Requisite;

use JsonSerializable;
use Stringable;

/**
 * Implemented by every requisite the package wraps.
 */
interface Requisite extends JsonSerializable, Stringable
{
    /**
     * Returns the value for json_encode().
     *
     * @return string
     */
    public function jsonSerialize(): string;
}
