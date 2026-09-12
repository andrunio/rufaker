<?php

declare(strict_types=1);

namespace RuFaker\Internal;

use Random\Engine;

/**
 * Draws randomness from the global Mersenne Twister, the one Faker seeds with mt_srand().
 *
 * @internal
 */
final class MtRandEngine implements Engine
{
    /** Width of one step of the engine, in bytes: a 32-bit word, as in Random\Engine\Mt19937. */
    private const int WORD = 4;

    /**
     * Produces the next word of randomness, a byte at a time.
     *
     * @return string
     */
    public function generate(): string
    {
        $word = '';

        // mt_rand() without a range yields 31 bits, so a word drawn at once would never set its top bit.
        for ($byte = 0; $byte < self::WORD; $byte++) {
            $word .= chr(mt_rand(0, 255));
        }

        return $word;
    }
}
