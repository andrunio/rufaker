<?php

declare(strict_types=1);

namespace RuFaker\Internal;

use DateTimeImmutable;

/**
 * Renders a date the way the package hands it out.
 *
 * @internal
 */
final readonly class IsoDate
{
    /** Calendar date of ISO 8601: the form every date in the package takes. */
    private const string FORMAT = 'Y-m-d';

    /**
     * Renders the date as a calendar date, dropping the time of day.
     *
     * @param DateTimeImmutable $date
     * @return string
     */
    public static function format(DateTimeImmutable $date): string
    {
        return $date->format(self::FORMAT);
    }
}
