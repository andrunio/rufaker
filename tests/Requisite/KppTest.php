<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Kpp;

#[CoversClass(Kpp::class)]
final class KppTest extends TestCase
{
    #[Test]
    #[DataProvider('wellFormedNumbers')]
    public function it_accepts_a_well_formed_kpp(string $value): void
    {
        $this->assertTrue(
            Kpp::isValid($value),
        );
    }

    #[Test]
    #[DataProvider('malformedNumbers')]
    public function it_rejects_a_malformed_value(string $value): void
    {
        $this->assertFalse(
            Kpp::isValid($value),
        );
    }

    #[Test]
    public function it_throws_on_an_invalid_value(): void
    {
        $this->expectException(InvalidRequisite::class);

        Kpp::from('77360100');
    }

    #[Test]
    public function it_reads_the_region_and_the_reason(): void
    {
        $kpp = Kpp::from('773601001');

        $this->assertSame(
            '77',
            $kpp->region()?->value,
        );

        $this->assertSame(
            '01',
            $kpp->reason(),
        );
    }

    /**
     * KPP of every shape the format allows.
     *
     * @return array<string, array{string}>
     */
    public static function wellFormedNumbers(): array
    {
        return [
            'head office' => ['773601001'],
            'branch' => ['781043001'],
            'largest taxpayer' => ['997950001'],
            'foreign organization' => ['9909AB001'],
        ];
    }

    /**
     * Values that break the KPP format.
     *
     * @return array<string, array{string}>
     */
    public static function malformedNumbers(): array
    {
        return [
            'empty' => [''],
            'too short' => ['77360100'],
            'too long' => ['7736010011'],
            'lowercase reason' => ['9909ab001'],
            'letters in the tax office' => ['77AB01001'],
            'letters in the sequence' => ['773601A01'],
        ];
    }
}
