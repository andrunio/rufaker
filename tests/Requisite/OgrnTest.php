<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Ogrn;

#[CoversClass(Ogrn::class)]
final class OgrnTest extends TestCase
{
    #[Test]
    #[DataProvider('realNumbers')]
    public function it_accepts_a_real_registry_number(string $value): void
    {
        $this->assertTrue(
            Ogrn::isValid($value),
        );
    }

    #[Test]
    #[DataProvider('realNumbers')]
    public function it_rejects_every_other_checksum_digit(string $value): void
    {
        $position = strlen($value) - 1;

        foreach (str_split('0123456789') as $digit) {
            if ($digit === $value[$position]) {
                continue;
            }

            $this->assertFalse(
                Ogrn::isValid(
                    substr_replace($value, $digit, $position, 1),
                ),
            );
        }
    }

    #[Test]
    #[DataProvider('malformedNumbers')]
    public function it_rejects_a_malformed_value(string $value): void
    {
        $this->assertFalse(
            Ogrn::isValid($value),
        );
    }

    #[Test]
    public function it_completes_a_legal_entity_body(): void
    {
        $this->assertSame(
            '1027700132195',
            Ogrn::complete('102770013219')->value,
        );
    }

    #[Test]
    public function it_rejects_a_body_of_a_wrong_length(): void
    {
        $this->expectException(InvalidRequisite::class);

        Ogrn::complete('1234567890');
    }

    #[Test]
    public function it_reads_the_region_and_the_kind(): void
    {
        $entity = Ogrn::from('1027700132195');

        $this->assertSame(
            '77',
            $entity->region()?->value,
        );

        $this->assertFalse(
            $entity->isIndividual(),
        );
    }

    /**
     * Registry number of every real organization the checks are built on.
     *
     * @return array<string, array{string}>
     */
    public static function realNumbers(): array
    {
        return [
            'Sberbank' => ['1027700132195'],
            'Gazprom' => ['1027700070518'],
            'Yandex' => ['1027700229193'],
        ];
    }

    /**
     * Values that break the registry number format or checksum.
     *
     * @return array<string, array{string}>
     */
    public static function malformedNumbers(): array
    {
        return [
            'empty' => [''],
            'twelve digits' => ['102770013219'],
            'fourteen digits' => ['10277001321950'],
            'letters' => ['102770013219a'],
        ];
    }
}
