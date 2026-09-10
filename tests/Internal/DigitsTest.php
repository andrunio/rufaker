<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Internal\Digits;

#[CoversClass(Digits::class)]
final class DigitsTest extends TestCase
{
    #[Test]
    public function it_accepts_a_string_of_the_given_length(): void
    {
        $this->assertTrue(
            Digits::areDigits('7707083893', 10),
        );
    }

    #[Test]
    #[DataProvider('rejectedValues')]
    public function it_rejects_anything_but_digits_of_that_length(string $value, int $length): void
    {
        $this->assertFalse(
            Digits::areDigits($value, $length),
        );
    }

    #[Test]
    public function it_splits_a_string_position_by_position(): void
    {
        $this->assertSame(
            [1, 0, 5],
            Digits::toList('105'),
        );
    }

    #[Test]
    public function it_sums_digits_against_the_weights_of_their_positions(): void
    {
        $this->assertSame(
            23,
            Digits::weightedSum([1, 2, 3], [3, 4, 4]),
        );
    }

    #[Test]
    public function it_stops_at_the_last_weight(): void
    {
        // The INN checksum runs over nine weights while the value carries ten digits.
        $this->assertSame(
            3,
            Digits::weightedSum([1, 2, 3], [3]),
        );
    }

    /**
     * Values that are not a digit string of the requested length.
     *
     * @return array<string, array{string, int}>
     */
    public static function rejectedValues(): array
    {
        return [
            'empty' => ['', 10],
            'too short' => ['770708389', 10],
            'too long' => ['77070838931', 10],
            'letters' => ['77070838ab', 10],
            'spaces' => ['7707 08389', 10],
            'signed' => ['-707083893', 10],
        ];
    }
}
