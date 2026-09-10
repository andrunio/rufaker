<?php

declare(strict_types=1);

namespace RuFaker\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;

#[CoversClass(InvalidRequisite::class)]
final class InvalidRequisiteTest extends TestCase
{
    #[Test]
    public function it_names_the_requisite_and_the_value_it_rejected(): void
    {
        $this->assertSame(
            'Value [7707083894] is not a valid INN.',
            InvalidRequisite::for('INN', '7707083894')->getMessage(),
        );
    }

    #[Test]
    public function it_passes_a_contradiction_through_as_it_is(): void
    {
        $this->assertSame(
            'Presence of a KPP contradicts the legal form ip.',
            InvalidRequisite::because('Presence of a KPP contradicts the legal form ip.')->getMessage(),
        );
    }
}
