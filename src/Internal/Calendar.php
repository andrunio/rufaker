<?php

declare(strict_types=1);

namespace RuFaker\Internal;

use DateInterval;
use DateTimeImmutable;
use Random\Randomizer;

/**
 * Draws the windows a generator picks its dates from, counting them off the current year.
 *
 * @internal
 */
final readonly class Calendar
{
    /**
     * Returns the first day of the year standing the given number of years back.
     *
     * @param int $years
     * @return DateTimeImmutable
     */
    public static function yearOpens(int $years): DateTimeImmutable
    {
        return self::day($years, 1, 1);
    }

    /**
     * Returns the last day of the year standing the given number of years back.
     *
     * @param int $years
     * @return DateTimeImmutable
     */
    public static function yearCloses(int $years): DateTimeImmutable
    {
        return self::day($years, 12, 31);
    }

    /**
     * Draws one day of the window, both ends included; a window turned inside out gives its start.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param Randomizer $randomizer
     * @param DateTimeImmutable $first
     * @param DateTimeImmutable $last
     * @return DateTimeImmutable
     */
    public static function dayWithin(
        Randomizer        $randomizer,
        DateTimeImmutable $first,
        DateTimeImmutable $last,
    ): DateTimeImmutable
    {
        if ($first >= $last) {
            return $first;
        }

        $days = (int)$first->diff($last)->days;

        // The interval is built out of one non-negative number, so its string is valid by construction.
        /** @noinspection PhpUnhandledExceptionInspection */
        return $first->add(new DateInterval('P' . $randomizer->getInt(0, $days) . 'D'));
    }

    /**
     * Builds a day of the year standing the given number of years back, with the time cut off.
     *
     * @param int $years
     * @param int $month
     * @param int $day
     * @return DateTimeImmutable
     */
    private static function day(int $years, int $month, int $day): DateTimeImmutable
    {
        $today = new DateTimeImmutable();

        return $today
            ->setDate((int)$today->format('Y') - $years, $month, $day)
            ->setTime(0, 0);
    }
}
