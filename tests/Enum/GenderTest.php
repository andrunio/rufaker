<?php

declare(strict_types=1);

namespace RuFaker\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Enum\Gender;

#[CoversClass(Gender::class)]
final class GenderTest extends TestCase
{
    #[Test]
    public function it_treats_only_the_female_case_as_female(): void
    {
        $this->assertTrue(
            Gender::Female->isFemale(),
        );

        $this->assertFalse(
            Gender::Male->isFemale(),
        );
    }

    #[Test]
    public function it_names_itself_in_russian(): void
    {
        $this->assertSame(
            'мужской',
            Gender::Male->title(),
        );

        $this->assertSame(
            'женский',
            Gender::Female->title(),
        );
    }

    #[Test]
    public function it_carries_values_a_payload_can_hold(): void
    {
        $this->assertSame(
            [
                'male',
                'female',
            ],
            array_map(
                static fn(Gender $gender): string => $gender->value,
                Gender::cases(),
            ),
        );
    }
}
