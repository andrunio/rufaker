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
     * BIK of every participant the checks are built on, taken from the Bank of Russia directory.
     *
     * @return array<string, array{string}>
     */
    public static function realNumbers(): array
    {
        return [
            'Sberbank' => ['044525225'],
            'Alfa-Bank' => ['044525593'],
            'Bank of Russia, Moscow' => ['044525000'],
            'Federal Treasury of the Tula region' => ['017003983'],
            'Federal Treasury of the Rostov region' => ['016015102'],
            'a bank participating indirectly' => ['100070023'],
            'an election commission, a client of the Bank of Russia' => ['211000237'],
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
            'kind of participation out of range' => ['344525225'],
            'letters' => ['04452522a'],
        ];
    }
}
