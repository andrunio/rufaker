<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Inn;

#[CoversClass(Inn::class)]
final class InnTest extends TestCase
{
    #[Test]
    #[DataProvider('realNumbers')]
    public function it_accepts_a_real_inn(string $value): void
    {
        $this->assertTrue(
            Inn::isValid($value),
        );
    }

    #[Test]
    #[DataProvider('realNumbers')]
    public function it_rejects_every_other_checksum_digit(string $value): void
    {
        $position = strlen($value) - 1;

        foreach (self::otherDigits($value[$position]) as $digit) {
            $this->assertFalse(
                Inn::isValid(substr_replace($value, $digit, $position, 1)),
            );
        }
    }

    #[Test]
    #[DataProvider('malformedNumbers')]
    public function it_rejects_a_malformed_value(string $value): void
    {
        $this->assertFalse(
            Inn::isValid($value),
        );
    }

    #[Test]
    public function it_completes_an_organization_body(): void
    {
        $this->assertSame(
            '7707083893',
            Inn::complete('770708389')->value,
        );
    }

    #[Test]
    public function it_completes_a_personal_body(): void
    {
        $this->assertSame(
            '500100732259',
            Inn::complete('5001007322')->value,
        );
    }

    #[Test]
    public function it_rejects_a_body_of_a_wrong_length(): void
    {
        $this->expectException(InvalidRequisite::class);

        Inn::complete('12345678');
    }

    #[Test]
    public function it_throws_on_an_invalid_value(): void
    {
        $this->expectException(InvalidRequisite::class);

        Inn::from('7707083894');
    }

    #[Test]
    public function it_returns_null_on_an_invalid_value(): void
    {
        $this->assertNull(
            Inn::tryFrom('7707083894'),
        );
    }

    #[Test]
    public function it_reads_the_region_and_the_kind(): void
    {
        $organization = Inn::from('7707083893');

        $this->assertSame(
            '77',
            $organization->region()?->value,
        );

        $this->assertFalse(
            $organization->isPersonal(),
        );

        $this->assertTrue(
            Inn::from('500100732259')->isPersonal(),
        );
    }

    #[Test]
    public function it_is_usable_as_a_string(): void
    {
        $inn = Inn::from('7707083893');

        $this->assertSame(
            '7707083893',
            (string)$inn,
        );

        $this->assertSame(
            '"7707083893"',
            json_encode($inn),
        );
    }

    /**
     * INN of every real organization the checks are built on.
     *
     * @return array<string, array{string}>
     */
    public static function realNumbers(): array
    {
        return [
            'Sberbank' => ['7707083893'],
            'Gazprom' => ['7736050003'],
            'Yandex' => ['7736207543'],
            'person' => ['500100732259'],
        ];
    }

    /**
     * Values that break the INN format or checksum.
     *
     * @return array<string, array{string}>
     */
    public static function malformedNumbers(): array
    {
        return [
            'empty' => [''],
            'too short' => ['770708389'],
            'eleven digits' => ['77070838931'],
            'too long' => ['7707083893123'],
            'letters' => ['77070838ab'],
            'spaces' => ['7707 08389'],
        ];
    }

    /**
     * Lists every digit but the given one.
     *
     * @param string $digit
     * @return list<string>
     */
    private static function otherDigits(string $digit): array
    {
        return array_values(
            array_diff(
                str_split('0123456789'),
                [$digit],
            ),
        );
    }
}
