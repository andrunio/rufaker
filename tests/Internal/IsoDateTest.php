<?php

declare(strict_types=1);

namespace RuFaker\Tests\Internal;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Internal\IsoDate;

#[CoversClass(IsoDate::class)]
final class IsoDateTest extends TestCase
{
    #[Test]
    public function it_renders_a_date_as_a_calendar_date_of_iso_8601(): void
    {
        $this->assertSame('2002-12-02', IsoDate::format(new DateTimeImmutable('2002-12-02')));
    }

    #[Test]
    public function it_drops_the_time_of_day(): void
    {
        $date = new DateTimeImmutable('2002-12-02 23:59:59');

        $this->assertSame('2002-12-02', IsoDate::format($date));
    }
}
