<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuFaker\Internal\Calendar;

#[CoversClass(Calendar::class)]
final class CalendarTest extends TestCase
{
    /** How many draws the window is checked over. */
    private const int RUNS = 300;

    #[Test]
    public function it_opens_and_closes_the_year_standing_years_back(): void
    {
        $thisYear = (int)(new DateTimeImmutable())->format('Y');

        $this->assertSame(
            sprintf('%04d-01-01 00:00:00', $thisYear - 5),
            Calendar::yearOpens(5)->format('Y-m-d H:i:s'),
        );

        $this->assertSame(
            sprintf('%04d-12-31 00:00:00', $thisYear - 1),
            Calendar::yearCloses(1)->format('Y-m-d H:i:s'),
        );

        $this->assertSame(
            sprintf('%04d-12-31 00:00:00', $thisYear),
            Calendar::yearCloses(0)->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function it_draws_a_day_of_the_window_and_reaches_both_ends(): void
    {
        $randomizer = new Randomizer(new Mt19937(1234));
        $first = Calendar::yearOpens(3);
        $last = Calendar::yearCloses(3);
        $drawn = [];

        foreach (range(1, self::RUNS) as $ignored) {
            $day = Calendar::dayWithin($randomizer, $first, $last);

            $this->assertGreaterThanOrEqual(
                $first,
                $day,
            );

            $this->assertLessThanOrEqual(
                $last,
                $day,
            );

            $drawn[] = $day->format('Y-m-d');
        }

        $this->assertGreaterThan(
            1,
            count(array_unique($drawn)),
        );
    }

    #[Test]
    public function it_gives_the_start_of_a_window_turned_inside_out(): void
    {
        $randomizer = new Randomizer(new Mt19937(1234));
        $first = Calendar::yearCloses(1);

        $this->assertSame(
            $first->format('Y-m-d'),
            Calendar::dayWithin($randomizer, $first, Calendar::yearOpens(5))->format('Y-m-d'),
        );
    }
}
