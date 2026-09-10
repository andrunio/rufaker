<?php

declare(strict_types=1);

namespace RuFaker\Tests\Requisite;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuFaker\Exception\InvalidRequisite;
use RuFaker\Requisite\Bik;

#[CoversClass(Bik::class)]
final class BikTest extends TestCase
{
    #[Test]
    #[DataProvider('realNumbers')]
    public function it_accepts_a_real_bik(string $value): void
    {
        $this->assertTrue(
            Bik::isValid($value),
        );
    }

    #[Test]
    #[DataProvider('malformedNumbers')]
    public function it_rejects_a_malformed_value(string $value): void
    {
        $this->assertFalse(
            Bik::isValid($value),
        );
    }

    #[Test]
    public function it_throws_on_an_invalid_value(): void
    {
        $this->expectException(InvalidRequisite::class);

        Bik::from('0445252251');
    }

    #[Test]
    public function it_splits_itself_into_parts(): void
    {
        $bik = Bik::from('044525225');

        $this->assertSame(
            '45',
            $bik->territory(),
        );

        $this->assertSame(
            '25',
            $bik->division(),
        );

        $this->assertSame(
            '225',
            $bik->participant(),
        );
    }

    /**
     * BIK of every bank the checks are built on.
     *
     * @return array<string, array{string}>
     */
    public static function realNumbers(): array
    {
        return [
            'Sberbank' => ['044525225'],
            'Alfa-Bank' => ['044525593'],
            'Bank of Russia, Moscow' => ['044525000'],
        ];
    }

    /**
     * Values that break the BIK format.
     *
     * @return array<string, array{string}>
     */
    public static function malformedNumbers(): array
    {
        return [
            'empty' => [''],
            'ten digits' => ['0445252251'],
            'eight digits' => ['04452522'],
            'foreign country prefix' => ['054525225'],
            'letters' => ['04452522a'],
        ];
    }
}
