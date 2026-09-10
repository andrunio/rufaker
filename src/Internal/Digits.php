<?php

declare(strict_types=1);

namespace RuFaker\Internal;

/**
 * Shared arithmetic over digit strings.
 *
 * @internal
 */
final class Digits
{
    /**
     * Tells whether the value is exactly the given number of digits.
     *
     * @param string $value
     * @param int $length
     * @return bool
     */
    public static function areDigits(string $value, int $length): bool
    {
        return strlen($value) === $length && ctype_digit($value);
    }

    /**
     * Splits a digit string into integers, position by position.
     *
     * @param string $value
     * @return list<int>
     */
    public static function toList(string $value): array
    {
        return array_map(
            intval(...),
            str_split($value),
        );
    }

    /**
     * Sums digits multiplied by the weight of their position.
     *
     * @param list<int> $digits
     * @param list<int> $weights
     * @return int
     */
    public static function weightedSum(array $digits, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $position => $weight) {
            $sum += $digits[$position] * $weight;
        }

        return $sum;
    }
}
